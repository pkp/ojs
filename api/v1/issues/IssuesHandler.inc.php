<?php 

/**
 * @file api/v1/issues/IssuesHandler.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssuesHandler
 * @ingroup api_v1_issues
 *
 * @brief Handle API requests for issues operations.
 *
 */

import('lib.pkp.classes.handler.APIHandler');
import('classes.core.ServicesContainer');

class IssuesHandler extends APIHandler {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->_handlerPath = 'issues';
		$roles = array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_REVIEWER, ROLE_ID_AUTHOR);
		$this->_endpoints = array(
			'GET' => array (
				array(
					'pattern' => $this->getEndpointPattern(),
					'handler' => array($this,'getIssueList'),
					'roles' => $roles
				),
				array(
					'pattern' => $this->getEndpointPattern().  '/{issueId}',
					'handler' => array($this,'getIssue'),
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
		
		if ($routeName === 'getIssue') {
			import('classes.security.authorization.OjsIssueRequiredPolicy');
			$this->addPolicy(new OjsIssueRequiredPolicy($request, $args));
		}
		
		return parent::authorize($request, $args, $roleAssignments);
	}
	
	//
	// Public handler methods
	//
	/**
	 * Handle file download
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 * @return Response
	 */
	public function getIssueList($slimRequest, $response, $args) {
		$request = $this->getRequest();
		$context = $request->getContext();
		$journal = $request->getJournal();
		$data = array();
		
		$volume = $this->getParameter('volume', null);
		$number = $this->getParameter('number', null);
		$year = $this->getParameter('year', null);
		
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issues = $issueDao->getPublishedIssuesByNumber($journal->getId(), $volume, $number, $year);
		
		while ($issue = $issues->next()) {
			$data[] = array(
				'id'				=> $issue->getBestIssueId(),
				'title'				=> $issue->getLocalizedTitle(),
				'series'			=> $issue->getIssueSeries(),
				'datePublished'		=> $issue->getDatePublished(),
				'lastModified'		=> $issue->getLastModified(),
				'current'			=> (bool) ($issue->getCurrent() == $issue->getBestIssueId()),
			);
		}
		
		return $response->withJson($data, 200);
	}
	
	/**
	 * Get issue metadata
	 * 
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 * 
	 * @return Response
	 */
	public function getIssue($slimRequest, $response, $args) {
		$request = $this->getRequest();
		$context = $request->getContext();
		$journal = $request->getJournal();
		
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		
		$publishedArticlesData = array();
		$issueService = ServicesContainer::instance()->get('issue');
		$publishedArticles = $publishedArticleDao->getPublishedArticlesInSections($issue->getId(), true);
		foreach ($publishedArticles as $pArticles) {
			$publishedArticlesData[$pArticles['title']] = array_map(function($article) use($journal, $issue, $issueService)
			{
				$item = array(
					'id'				=> $article->getId(),
					'title'				=> $article->getTitle(null),
					'author'			=> $article->getAuthorString(),
					'datePublished'		=> $article->getDatePublished(),
				);
				
				$hasAccess = $issueService->userHasAccessToGalleys($journal, $issue);
				if ($hasAccess) {
					$galleys = array();
					foreach ($article->getGalleys() as $galley) {
						$galleys[] = array(
							'id'			=> $galley->getId(),
							'label'			=> $galley->getGalleyLabel(),
							'submissionId'	=> $galley->getSubmissionId(),
						);
					}
					$item['galleys'] = $galleys;
				}
				
				return $item;
			}, $pArticles['articles']);
		}
		
		$data = array(
			'id'				=> $issue->getBestIssueId(),
			'volume'			=> $issue->getVolume(),
			'number'			=> $issue->getNumber(),
			'year'				=> $issue->getYear(),
			'current'			=> (bool) ($issue->getCurrent() == $issue->getBestIssueId()),
			'title'				=> $issue->getLocalizedTitle(),
			'series'			=> $issue->getIssueSeries(),
			'issueCover'		=> $issue->getLocalizedCoverImageUrl(),
			'overImageAltText'	=> $issue->getLocalizedCoverImageAltText(),
			'description'		=> $issue->getLocalizedDescription(),
			'datePublished'		=> $issue->getDatePublished(),
			'lastModified'		=> $issue->getLastModified(),
			'pubId'				=> $issue->getStoredPubId(),
			'issueGalleys' 		=> $issueGalleyDao->getByIssueId($issue->getId()),
			'articles'			=> $publishedArticlesData,
		);
		
		return $response->withJson($data, 200);
	}
}