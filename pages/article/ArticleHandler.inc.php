<?php

/**
 * @file pages/article/ArticleHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleHandler
 * @ingroup pages_article
 *
 * @brief Handle requests for article functions.
 *
 */

import('classes.handler.Handler');

use \Firebase\JWT\JWT;

class ArticleHandler extends Handler {
	/** context associated with the request **/
	var $context;

	/** issue associated with the request **/
	var $issue;

	/** submission associated with the request **/
	var $article;

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

		import('classes.security.authorization.OjsJournalMustPublishPolicy');
		$this->addPolicy(new OjsJournalMustPublishPolicy($request));

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @see PKPHandler::initialize()
	 * @param $args array Arguments list
	 */
	function initialize($request, $args = array()) {
		$urlPath = isset($args[0]) ? $args[0] : 0;

		// Look for a publication with a publisher-id that matches the url path
		$publications = Services::get('publication')->getMany([
			'contextIds' => $request->getContext()->getId(),
			'publisherIds' => $urlPath,
		]);
		if (!empty($publications)) {
			$submissionId = $publications[0]->getData('submissionId');
		} elseif (ctype_digit($urlPath)) {
			$submissionId = $urlPath;
		}

		if (!$submissionId) {
			$request->getDispatcher()->handle404();
		}

		$submission = Services::get('submission')->get($submissionId);

		if (!$submission) {
			$request->getDispatcher()->handle404();
		}

		// If we retrieved the submission from the publisher-id and it no longer
		// matches the publisher-id of the current publication, redirect to the
		// URL for the current publication
		if ($urlPath && !empty($publications) &&
				$submission->getCurrentPublication()->getData('pub-id::publisher-id') !== $publications[0]->getData('pub-id::publisher-id')) {
			$newUrlPath = $submission->getCurrentPublication()->getData('pub-id::publisher-id');
			if (!$newUrlPath) {
				$newUrlPath = $submission->getId();
			}
			$newArgs = $args;
			$newArgs[0] = $newUrlPath;
			$request->redirect(null, $request->getRequestedPage(), $request->getRequestedOp(), $newArgs);
		}

		$this->article = $submission;

		if (in_array($request->getRequestedOp(), ['view', 'download'])) {
			$galleyId = isset($args[1]) ? $args[1] : 0;
			if ($galleyId) {
				$this->galley = DAORegistry::getDAO('ArticleGalleyDAO')->getByBestGalleyId($galleyId, $submission->getCurrentPublication()->getId());
				if (!$this->galley) {
					$request->getDispatcher()->handle404();
				}
			}
		}

		if ($submission->getCurrentPublication()->getData('issueId')) {
			$this->issue = DAORegistry::getDAO('IssueDAO')->getById($submission->getCurrentPublication()->getData('issueId'), $submission->getData('contextId'), true);
		}
	}

	/**
	 * View Article. (Either article landing page or galley view.)
	 * @param $args array
	 * @param $request Request
	 */
	function view($args, $request) {
		$articleId = array_shift($args);
		$galleyId = array_shift($args);
		$fileId = array_shift($args);

		$context = $request->getContext();
		$user = $request->getUser();
		$issue = $this->issue;
		$article = $this->article;
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'issue' => $issue,
			'article' => $article,
			'fileId' => $fileId,
		));
		$this->setupTemplate($request);

		// TODO: this defaults to the current publication but should
		// retrieve the publication requested from the URL if it is
		// passed as an arg
		$requestedPublication = $this->article->getCurrentPublication();

		if (!$this->userCanViewGalley($request, $article->getId(), $galleyId)) fatalError('Cannot view galley.');

		// Get galleys sorted into primary and supplementary groups
		$galleys = $article->getGalleys();
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
		if ($requestedPublication->getData('citationsRaw')) {
			$parsedCitations = DAORegistry::getDAO('CitationDAO')->getByPublicationId($requestedPublication->getId());
			$templateMgr->assign([
				'citations' => $parsedCitations->toArray(),
			]);
		}

		// Assign deprecated values to the template manager for
		// compatibility with older themes
		$templateMgr->assign([
			'section' => DAORegistry::getDAO('SectionDAO')->getById($requestedPublication->getData('sectionId')),
			'licenseTerms' => $context->getLocalizedData('licenseTerms'),
			'licenseUrl' => $requestedPublication->getData('licenseUrl'),
			'ccLicenseBadge' => Application::get()->getCCLicenseBadge($requestedPublication->getData('licenseUrl')),
			'copyrightHolder' => $requestedPublication->getData('copyrightHolder'),
			'copyrightYear' => $requestedPublication->getData('copyrightYear'),
			'pubIdPlugins' => PluginRegistry::loadCategory('pubIds', true),
			'parsedCitations' => $parsedCitations ?? [],
			// @TODO
			// 'keywords' => ...,
		]);

		// Fetch and assign the galley to the template
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = $galleyDao->getByBestGalleyId($galleyId, $article->getCurrentPublication()->getId());
		if ($galley && $galley->getRemoteURL()) $request->redirectUrl($galley->getRemoteURL());

		if (!$galley) {
			// No galley: Prepare the article landing page.

			// Get the subscription status if displaying the abstract;
			// if access is open, we can display links to the full text.
			import('classes.issue.IssueAction');

			// The issue may not exist, if this is an editorial user
			// and scheduling hasn't been completed yet for the article.
			$issueAction = new IssueAction();
			$subscriptionRequired = false;
			if ($issue) {
				$subscriptionRequired = $issueAction->subscriptionRequired($issue, $context);
			}

			$subscribedUser = $issueAction->subscribedUser($user, $context, isset($issue) ? $issue->getId() : null, isset($article) ? $article->getId() : null);
			$subscribedDomain = $issueAction->subscribedDomain($request, $context, isset($issue) ? $issue->getId() : null, isset($article) ? $article->getId() : null);

			$templateMgr->assign('hasAccess', !$subscriptionRequired || (isset($article) && $article->getAccessStatus() == ARTICLE_ACCESS_OPEN) || $subscribedUser || $subscribedDomain);

			$paymentManager = Application::get()->getPaymentManager($context);
			if ( $paymentManager->onlyPdfEnabled() ) {
				$templateMgr->assign('restrictOnlyPdf', true);
			}
			if ( $paymentManager->purchaseArticleEnabled() ) {
				$templateMgr->assign('purchaseArticleEnabled', true);
			}

			if (!HookRegistry::call('ArticleHandler::view', array(&$request, &$issue, &$article))) {
				return $templateMgr->display('frontend/pages/article.tpl');
			}
		} else {
			// Galley: Prepare the galley file download.
			if (!HookRegistry::call('ArticleHandler::view::galley', array(&$request, &$issue, &$galley, &$article))) {
				$request->redirect(null, null, 'download', array($article->getId(), $galleyId));
			}

		}
	}

	/**
	 * Download an article file
	 * For deprecated OJS 2.x URLs; see https://github.com/pkp/pkp-lib/issues/1541
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewFile($args, $request) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;
		$fileId = isset($args[2]) ? (int) $args[2] : 0;
		header('HTTP/1.1 301 Moved Permanently');
		$request->redirect(null, null, 'download', array($articleId, $galleyId, $fileId));
	}

	/**
	 * Download a supplementary file.
	 * For deprecated OJS 2.x URLs; see https://github.com/pkp/pkp-lib/issues/1541
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function downloadSuppFile($args, $request) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$article = Services::get('submission')->get($articleId);
		if (!$article) {
			$dispatcher = $request->getDispatcher();
			$dispatcher->handle404();
		}
		$suppId = isset($args[1]) ? $args[1] : 0;
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$submissionFiles = $submissionFileDao->getBySubmissionId($articleId);
		foreach ($submissionFiles as $submissionFile) {
			if ($submissionFile->getData('old-supp-id') == $suppId) {
				$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
				$articleGalleys = $articleGalleyDao->getByPublicationId($article->getCurrentPublication()->getId());
				while ($articleGalley = $articleGalleys->next()) {
					$galleyFile = $articleGalley->getFile();
					if ($galleyFile && $galleyFile->getFileId() == $submissionFile->getFileId()) {
						header('HTTP/1.1 301 Moved Permanently');
						$request->redirect(null, null, 'download', array($articleId, $articleGalley->getId(), $submissionFile->getFileId()));
					}
				}
			}
		}
		$dispatcher = $request->getDispatcher();
		$dispatcher->handle404();
	}

	/**
	 * Download an article file
	 * @param array $args
	 * @param PKPRequest $request
	 */
	function download($args, $request) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;
		$fileId = isset($args[2]) ? (int) $args[2] : 0;

		if (!isset($this->galley)) $request->getDispatcher()->handle404();
		if ($this->galley->getRemoteURL()) $request->redirectUrl($this->galley->getRemoteURL());
		else if ($this->userCanViewGalley($request, $articleId, $galleyId)) {
			if (!$fileId) {
				$submissionFile = $this->galley->getFile();
				if ($submissionFile) {
					$fileId = $submissionFile->getFileId();
					// The file manager expects the real article id.  Extract it from the submission file.
					$articleId = $submissionFile->getSubmissionId();
				} else { // no proof files assigned to this galley!
					header('HTTP/1.0 403 Forbidden');
					echo '403 Forbidden<br>';
					return;
				}
			}

			if (!HookRegistry::call('ArticleHandler::download', array($this->article, &$this->galley, &$fileId))) {
				import('lib.pkp.classes.file.SubmissionFileManager');
				$submissionFileManager = new SubmissionFileManager($this->article->getContextId(), $this->article->getId());
				$submissionFileManager->downloadById($fileId, null, $request->getUserVar('inline')?true:false);
			}
		} else {
			header('HTTP/1.0 403 Forbidden');
			echo '403 Forbidden<br>';
		}
	}

	/**
	 * Determines whether a user can view this article galley or not.
	 * @param $request Request
	 * @param $articleId string
	 * @param $galleyId int or string
	 */
	function userCanViewGalley($request, $articleId, $galleyId = null) {

		import('classes.issue.IssueAction');
		$issueAction = new IssueAction();

		$context = $request->getContext();
		$submission = $this->article;
		$issue = $this->issue;
		$contextId = $context->getId();
		$user = $request->getUser();
		$userId = $user?$user->getId():0;

		// If this is an editorial user who can view unpublished/unscheduled
		// articles, bypass further validation. Likewise for its author.
		if ($submission && $issueAction->allowedPrePublicationAccess($context, $submission, $user)) {
			return true;
		}

		// Make sure the reader has rights to view the article/issue.
		if ($issue && $issue->getPublished() && $submission->getStatus() == STATUS_PUBLISHED) {
			$subscriptionRequired = $issueAction->subscriptionRequired($issue, $context);
			$isSubscribedDomain = $issueAction->subscribedDomain($request, $context, $issue->getId(), $submission->getId());

			// Check if login is required for viewing.
			if (!$isSubscribedDomain && !Validation::isLoggedIn() && $context->getData('restrictArticleAccess') && isset($galleyId) && $galleyId) {
				Validation::redirectLogin();
			}

			// bypass all validation if subscription based on domain or ip is valid
			// or if the user is just requesting the abstract
			if ( (!$isSubscribedDomain && $subscriptionRequired) && (isset($galleyId) && $galleyId) ) {

				// Subscription Access
				$subscribedUser = $issueAction->subscribedUser($user, $context, $issue->getId(), $submission->getId());

				import('classes.payment.ojs.OJSPaymentManager');
				$paymentManager = Application::get()->getPaymentManager($context);

				$purchasedIssue = false;
				if (!$subscribedUser && $paymentManager->purchaseIssueEnabled()) {
					$completedPaymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO');
					$purchasedIssue = $completedPaymentDao->hasPaidPurchaseIssue($userId, $issue->getId());
				}

				if (!(!$subscriptionRequired || $submission->getAccessStatus() == ARTICLE_ACCESS_OPEN || $subscribedUser || $purchasedIssue)) {

					if ( $paymentManager->purchaseArticleEnabled() || $paymentManager->membershipEnabled() ) {
						/* if only pdf files are being restricted, then approve all non-pdf galleys
						 * and continue checking if it is a pdf galley */
						if ( $paymentManager->onlyPdfEnabled() ) {

							if ($this->galley && !$this->galley->isPdfGalley() ) {
								$this->issue = $issue;
								$this->article = $submission;
								return true;
							}
						}

						if (!Validation::isLoggedIn()) {
							Validation::redirectLogin('payment.loginRequired.forArticle');
						}

						/* if the article has been paid for then forget about everything else
						 * and just let them access the article */
						$completedPaymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO');
						$dateEndMembership = $user->getSetting('dateEndMembership', 0);
						if ($completedPaymentDao->hasPaidPurchaseArticle($userId, $submission->getId())
							|| (!is_null($dateEndMembership) && $dateEndMembership > time())) {
							$this->issue = $issue;
							$this->article = $submission;
							return true;
						} elseif ($paymentManager->purchaseArticleEnabled()) {
							$queuedPayment = $paymentManager->createQueuedPayment($request, PAYMENT_TYPE_PURCHASE_ARTICLE, $user->getId(), $submission->getId(), $context->getData('purchaseArticleFee'));
							$paymentManager->queuePayment($queuedPayment);

							$paymentForm = $paymentManager->getPaymentForm($queuedPayment);
							$paymentForm->display($request);
							exit;
						}
					}

					if (!isset($galleyId) || $galleyId) {
						if (!Validation::isLoggedIn()) {
							Validation::redirectLogin('reader.subscriptionRequiredLoginText');
						}
						$request->redirect(null, 'about', 'subscriptions');
					}
				}
			}
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
