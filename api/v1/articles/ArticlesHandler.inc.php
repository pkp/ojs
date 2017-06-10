<?php 

/**
 * @file api/v1/articles/ArticlesHandler.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticlesHandler
 * @ingroup api_v1_articles
 *
 * @brief Handle API requests for articles operations.
 *
 */

import('lib.pkp.classes.handler.APIHandler');
import('classes.core.ServicesContainer');

class ArticlesHandler extends APIHandler {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->_handlerPath = 'articles';
		$roles = array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_REVIEWER, ROLE_ID_AUTHOR);
		$this->_endpoints = array(
			'GET' => array (
					array(
						'pattern' => $this->getEndpointPattern() . '/{submissionId}',
						'handler' => array($this,'getArticle'),
						'roles' => $roles
					),
				)
		);
		parent::__construct();
	}
	
	//
	// Implement methods from PKPHandler
	//
	function authorize($request, &$args, $roleAssignments) {
		$routeName = null;
		$slimRequest = $this->getSlimRequest();
	
		if (!is_null($slimRequest) && ($route = $slimRequest->getAttribute('route'))) {
			$routeName = $route->getName();
		}
		
		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request));
		
		import('classes.security.authorization.OjsJournalMustPublishPolicy');
		$this->addPolicy(new OjsJournalMustPublishPolicy($request));
		
		import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
		$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));

		return parent::authorize($request, $args, $roleAssignments);
	}
	
	//
	// Public handler methods
	//
	/**
	 * Handle article view
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 * @return Response
	 */
	public function getArticle($slimRequest, $response, $args) {
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_READER, LOCALE_COMPONENT_PKP_SUBMISSION);
		
		$request = $this->getRequest();
		$dispatcher = $request->getDispatcher();
		$context = $request->getContext();
		$journal = $request->getJournal();

		$article = $this->getAuthorizedContextObject(ASSOC_TYPE_ARTICLE);
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = $publishedArticleDao->getPublishedArticleByBestArticleId((int) $journal->getId(), $article->getId(), true);
		
		$issue = null;
		$issueDao = DAORegistry::getDAO('IssueDAO');
		if (isset($publishedArticle)) {
			$issue = $issueDao->getById($publishedArticle->getIssueId(), $publishedArticle->getJournalId(), true);
			$article = $publishedArticle;
		}
		
		// 404 if article or issue not found
		if (!$article || !$issue) {
			return $response->withStatus(404)->withJsonError('api.submissions.404.resourceNotFound');
		}
		
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$section = $sectionDao->getById($article->getSectionId(), $journal->getId(), true);
		
		// public identifiers
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
		$pubIds = array_map(function($pubIdPlugin) use($issue,$article) {
			if ($pubIdPlugin->getPubIdType() != 'doi')
				continue;
			$doiUrl = null;
			$pubId = $issue->getPublished() ? 
						$article->getStoredPubId($pubIdPlugin->getPubIdType()) :
						$pubIdPlugin->getPubId($article);
			if($pubId) {
				$doiUrl = $pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId);
			}
			
			return array(
				'pubId'             => $$pubId,
				'doiUrl'            => $doiUrl,
			);
		}, $pubIdPlugins);
		
		// Citation formats
		$citationPlugins = PluginRegistry::loadCategory('citationFormats');
		uasort($citationPlugins, create_function('$a, $b', 'return strcmp($a->getDisplayName(), $b->getDisplayName());'));
		$citations = array_map(function($citationPlugin) use($article, $issue, $context) {
			return $citationPlugin->fetchCitation($article, $issue, $context);
		}, $citationPlugins);
		
		$authors = array_map(function($author) {
			return array(
				'name'              => $author->getFullName(),
				'affiliation'       => $author->getLocalizedAffiliation(),
				'orcid'             => $author->getOrcid(),
			);
		}, $article->getAuthors());
		
		$coverImage = $article->getLocalizedCoverImage() ?
							$article->getLocalizedCoverImageUrl() :
							$issue->getLocalizedCoverImageUrl();
		
		$galleys = array_map(function($galley) use ($context, $request, $dispatcher, $articleId) {
			$url = null;
			if ($galley->getRemoteURL()) {
				$url = $galley->getRemoteURL();
			}
			else {
				$url = $dispatcher->url($request, ROUTE_PAGE, $context, 'article', 'download', 
						array($articleId, $galley->getBestGalleyId()));
			}
			return array(
				'id'                => $galley->getBestGalleyId(),
				'label'             => $galley->getGalleyLabel(),
				'filetype'          => $galley->getFileType(),
				'url'               => $url,
			);
		}, $article->getGalleys());

		$data = array(
			'issueId'                    => $issue->getId(),
			'issue'                      => $issue->getIssueIdentification(),
			'section'                    => $section->getLocalizedTitle(),
			'title'                      => $article->getLocalizedTitle(),
			'subtitle'                   => $article->getLocalizedSubtitle(),
			'authors'                    => $authors,
			'pubIds'                     => $pubIds,
			'abstract'                   => $article->getLocalizedAbstract(),
			'citations'                  => $article->getCitations(),
			'cover_image'                => $coverImage,
			'galleys'                    => $galleys,
			'datePublished'              => $article->getDatePublished(),
			'citations'                  => $citations,
		);
		
		return $response->withJson($data, 200);
	}
}