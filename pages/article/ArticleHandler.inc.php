<?php

/**
 * @file pages/preprint/PreprintHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PreprintHandler
 * @ingroup pages_preprint
 *
 * @brief Handle requests for preprint functions.
 *
 */

import('classes.handler.Handler');

use \Firebase\JWT\JWT;

class PreprintHandler extends Handler {
	/** context associated with the request **/
	var $context;

	/** submission associated with the request **/
	var $preprint;

	/** publication associated with the request **/
	var $publication;

	/** galley associated with the request **/
	var $galley;


	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		// Permit the use of the Authorization header and an API key for access to unpublished/subscription content
		if ($header = array_search('Authorization', array_flip(getallheaders()))) {
			list($bearer, $jwt) = explode(' ', $header);
			if (strcasecmp($bearer, 'Bearer') == 0) {
				$apiToken = json_decode(JWT::decode($jwt, Config::getVar('security', 'api_key_secret', ''), array('HS256')));
				$this->setApiToken($apiToken);
			}
		}

		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request));

		import('classes.security.authorization.PpsServerMustPublishPolicy');
		$this->addPolicy(new PpsServerMustPublishPolicy($request));

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @see PKPHandler::initialize()
	 * @param $args array Arguments list
	 */
	function initialize($request, $args = array()) {
		$urlPath = empty($args) ? 0 : array_shift($args);

		// Look for a publication with a publisher-id that matches the url path
		$publicationsIterator = Services::get('publication')->getMany([
			'contextIds' => $request->getContext()->getId(),
			'publisherIds' => $urlPath,
		]);
		$publicationWithMatchingUrl = null;
		if (count($publicationsIterator)) {
			$publicationWithMatchingUrl = $publicationsIterator->current();
			$submissionId = $publicationWithMatchingUrl->getData('submissionId');
		} elseif (ctype_digit($urlPath)) {
			$submissionId = $urlPath;
		}

		if (!isset($submissionId)) {
			$request->getDispatcher()->handle404();
		}

		$submission = Services::get('submission')->get($submissionId);

		if (!$submission) {
			$request->getDispatcher()->handle404();
		}

		// If we retrieved the submission from the publisher-id and it no longer
		// matches the publisher-id of the current publication, redirect to the
		// URL for the current publication
		if ($urlPath && $publicationWithMatchingUrl &&
				$submission->getCurrentPublication()->getData('pub-id::publisher-id') !== $publicationWithMatchingUrl->getData('pub-id::publisher-id')) {
			$newUrlPath = $submission->getCurrentPublication()->getData('pub-id::publisher-id');
			if (!$newUrlPath) {
				$newUrlPath = $submission->getId();
			}
			$newArgs = $args;
			$newArgs[0] = $newUrlPath;
			$request->redirect(null, $request->getRequestedPage(), $request->getRequestedOp(), $newArgs);
		}

		$this->preprint = $submission;

		// Get the requested publication or if none requested get the current publication
		$subPath = empty($args) ? 0 : array_shift($args);
		if ($subPath === 'version') {
			$publicationId = (int) array_shift($args);
			$galleyId = empty($args) ? 0 : array_shift($args);
			foreach ((array) $this->article->getData('publications') as $publication) {
				if ($publication->getId() === $publicationId) {
					$this->publication = $publication;
				}
			}
			if (!$this->publication) {
				$request->getDispatcher()->handle404();
			}
		} else {
			$this->publication = $this->article->getCurrentPublication();
			$galleyId = $subPath;
		}

		if ($galleyId && in_array($request->getRequestedOp(), ['view', 'download'])) {
			$this->galley = DAORegistry::getDAO('ArticleGalleyDAO')->getByBestGalleyId($galleyId, $this->publication->getId());
			if (!$this->galley) {
				$request->getDispatcher()->handle404();
			}
		}

		if ($this->publication->getData('issueId')) {
			$this->issue = DAORegistry::getDAO('IssueDAO')->getById($this->publication->getData('issueId'), $submission->getData('contextId'), true);
		}
	}

	/**
	 * View Preprint. (Either preprint landing page or galley view.)
	 * @param $args array
	 * @param $request Request
	 */
	function view($args, $request) {
		$preprintId = array_shift($args);
		$subPath = array_shift($args);

		if ($subPath === 'version') {
			$publicationId = array_shift($args);
			$galleyId = array_shift($args);
			$fileId = array_shift($args);
		} else {
			$galleyId = $subPath;
			$fileId = array_shift($args);
		}

		$context = $request->getContext();
		$user = $request->getUser();
		$preprint = $this->preprint;
		$publication = $this->publication;
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'preprint' => $preprint,
			'publication' => $publication,
			'firstPublication' => reset($preprint->getData('publications')),
			'currentPublication' => $preprint->getCurrentPublication(),
			'galley' => $this->galley,
			'fileId' => $fileId,
		));
		$this->setupTemplate($request);

		// TODO: this defaults to the current publication but should
		// retrieve the publication requested from the URL if it is
		// passed as an arg
		$templateMgr->assign([
			'ccLicenseBadge' => Application::get()->getCCLicenseBadge($publication->getData('licenseUrl')),
			'publication' => $publication,
			'section' => DAORegistry::getDAO('SectionDAO')->getById($publication->getData('sectionId')),
		]);

		if ($this->galley && !$this->userCanViewGalley($request, $preprint->getId(), $this->galley->getId())) {
			fatalError('Cannot view galley.');
		}

		// Get galleys sorted into primary and supplementary groups
		$galleys = $publication->getData('galleys');
		$primaryGalleys = array();
		$supplementaryGalleys = array();
		if ($galleys) {
			$genreDao = DAORegistry::getDAO('GenreDAO');
			$primaryGenres = $genreDao->getPrimaryByContextId($context->getId())->toArray();
			$primaryGenreIds = array_map(function($genre) {
				return $genre->getId();
			}, $primaryGenres);
			$supplementaryGenres = $genreDao->getBySupplementaryAndContextId(true, $context->getId())->toArray();
			$supplementaryGenreIds = array_map(function($genre) {
				return $genre->getId();
			}, $supplementaryGenres);

			foreach ($galleys as $galley) {
				$remoteUrl = $galley->getRemoteURL();
				$file = $galley->getFile();
				if (!$remoteUrl && !$file) {
					continue;
				}
				if ($remoteUrl || in_array($file->getGenreId(), $primaryGenreIds)) {
					$primaryGalleys[] = $galley;
				} elseif (in_array($file->getGenreId(), $supplementaryGenreIds)) {
					$supplementaryGalleys[] = $galley;
				}
			}
		}
		$templateMgr->assign(array(
			'primaryGalleys' => $primaryGalleys,
			'supplementaryGalleys' => $supplementaryGalleys,
		));

		// Citations
		if ($publication->getData('citationsRaw')) {
			$parsedCitations = DAORegistry::getDAO('CitationDAO')->getByPublicationId($publication->getId());
			$templateMgr->assign([
				'parsedCitations' => $parsedCitations->toArray(),
			]);
		}

		// Assign deprecated values to the template manager for
		// compatibility with older themes
		$templateMgr->assign([
			'licenseTerms' => $context->getLocalizedData('licenseTerms'),
			'licenseUrl' => $publication->getData('licenseUrl'),
			'copyrightHolder' => $publication->getData('copyrightHolder'),
			'copyrightYear' => $publication->getData('copyrightYear'),
			'pubIdPlugins' => PluginRegistry::loadCategory('pubIds', true),
			'keywords' => $publication->getData('keywords'),
		]);

		// Fetch and assign the galley to the template
		if ($this->galley && $this->galley->getRemoteURL()) $request->redirectUrl($this->galley->getRemoteURL());

		if (empty($this->galley)) {
			// No galley: Prepare the preprint landing page.

			// Ask robots not to index outdated versions and point to the canonical url for the latest version
			if ($publication->getId() !== $article->getCurrentPublication()->getId()) {
				$templateMgr->addHeader('noindex', '<meta name="robots" content="noindex">');
				$url = $request->getDispatcher()->url($request, ROUTE_PAGE, null, 'article', 'view', $article->getBestId());
				$templateMgr->addHeader('canonical', '<link rel="canonical" href="' . $url . '">');
			}

			if (!HookRegistry::call('PreprintHandler::view', array(&$request, &$issue, &$preprint, $publication))) {
				return $templateMgr->display('frontend/pages/preprint.tpl');
			}
		} else {

			// Ask robots not to index outdated versions
			if ($publication->getId() !== $article->getCurrentPublication()->getId()) {
				$templateMgr->addHeader('noindex', '<meta name="robots" content="noindex">');
			}

			// Galley: Prepare the galley file download.
			if (!HookRegistry::call('PreprintHandler::view::galley', array(&$request, &$issue, &$this->galley, &$preprint, $publication))) {
				$request->redirect(null, null, 'download', array($preprint->getId(), $this->galley->getId()));
			}
		}
	}

	/**
	 * Download an preprint file
	 * For deprecated OJS 2.x URLs; see https://github.com/pkp/pkp-lib/issues/1541
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewFile($args, $request) {
		$preprintId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;
		$fileId = isset($args[2]) ? (int) $args[2] : 0;
		header('HTTP/1.1 301 Moved Permanently');
		$request->redirect(null, null, 'download', array($preprintId, $galleyId, $fileId));
	}

	/**
	 * Download a supplementary file.
	 * For deprecated OJS 2.x URLs; see https://github.com/pkp/pkp-lib/issues/1541
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function downloadSuppFile($args, $request) {
		$preprintId = isset($args[0]) ? $args[0] : 0;
		$preprint = Services::get('submission')->get($preprintId);
		if (!$preprint) {
			$dispatcher = $request->getDispatcher();
			$dispatcher->handle404();
		}
		$suppId = isset($args[1]) ? $args[1] : 0;
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$submissionFiles = $submissionFileDao->getBySubmissionId($preprintId);
		foreach ($submissionFiles as $submissionFile) {
			if ($submissionFile->getData('old-supp-id') == $suppId) {
				$preprintGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
				$preprintGalleys = $preprintGalleyDao->getByPublicationId($preprint->getCurrentPublication()->getId());
				while ($preprintGalley = $preprintGalleys->next()) {
					$galleyFile = $preprintGalley->getFile();
					if ($galleyFile && $galleyFile->getFileId() == $submissionFile->getFileId()) {
						header('HTTP/1.1 301 Moved Permanently');
						$request->redirect(null, null, 'download', array($preprintId, $preprintGalley->getId(), $submissionFile->getFileId()));
					}
				}
			}
		}
		$dispatcher = $request->getDispatcher();
		$dispatcher->handle404();
	}

	/**
	 * Download an preprint file
	 * @param array $args
	 * @param PKPRequest $request
	 */
	function download($args, $request) {
		$preprintId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;
		$fileId = isset($args[2]) ? (int) $args[2] : 0;

		if (!isset($this->galley)) $request->getDispatcher()->handle404();
		if ($this->galley->getRemoteURL()) $request->redirectUrl($this->galley->getRemoteURL());
		else if ($this->userCanViewGalley($request, $preprintId, $galleyId)) {
			if (!$fileId) {
				$submissionFile = $this->galley->getFile();
				if ($submissionFile) {
					$fileId = $submissionFile->getFileId();
					// The file manager expects the real preprint id.  Extract it from the submission file.
					$preprintId = $submissionFile->getSubmissionId();
				} else { // no proof files assigned to this galley!
					header('HTTP/1.0 403 Forbidden');
					echo '403 Forbidden<br>';
					return;
				}
			}

			if (!HookRegistry::call('PreprintHandler::download', array($this->preprint, &$this->galley, &$fileId))) {
				import('lib.pkp.classes.file.SubmissionFileManager');
				$submissionFileManager = new SubmissionFileManager($this->preprint->getContextId(), $this->preprint->getId());
				$submissionFileManager->downloadById($fileId, null, $request->getUserVar('inline')?true:false);
			}
		} else {
			header('HTTP/1.0 403 Forbidden');
			echo '403 Forbidden<br>';
		}
	}

	/**
	 * Determines whether a user can view this preprint galley or not.
	 * @param $request Request
	 * @param $preprintId string
	 * @param $galleyId int or string
	 */
	function userCanViewGalley($request, $preprintId, $galleyId = null) {
		$submission = $this->preprint;
		if ($submission->getStatus() == STATUS_PUBLISHED) {
			return true;
		} else {
			$request->redirect(null, 'search');
		}
		return true;
	}

	/**
	 * Set up the template. (Load required locale components.)
	 * @param $request PKPRequest
	 */
	function setupTemplate($request) {
		parent::setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_READER, LOCALE_COMPONENT_PKP_SUBMISSION);
	}
}
