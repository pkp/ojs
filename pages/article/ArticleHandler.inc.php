<?php

/**
 * @file pages/article/ArticleHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleHandler
 * @ingroup pages_article
 *
 * @brief Handle requests for article functions.
 *
 */

import('classes.handler.Handler');

class ArticleHandler extends Handler {
	/** journal associated with the request **/
	var $journal;

	/** issue associated with the request **/
	var $issue;

	/** article associated with the request **/
	var $article;

	/** galley associated with the request **/
	var $galley;


	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request));

		import('classes.security.authorization.OjsJournalMustPublishPolicy');
		$this->addPolicy(new OjsJournalMustPublishPolicy($request));

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize($request, $args) {
		$articleId = isset($args[0]) ? $args[0] : 0;

		$journal = $request->getContext();
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = $publishedArticleDao->getPublishedArticleByBestArticleId((int) $journal->getId(), $articleId, true);

		$issueDao = DAORegistry::getDAO('IssueDAO');
		if (isset($publishedArticle)) {
			$issue = $issueDao->getById($publishedArticle->getIssueId(), $publishedArticle->getJournalId(), true);
			$this->issue = $issue;
			$this->article = $publishedArticle;
		} else {
			$articleDao = DAORegistry::getDAO('ArticleDAO');
			$article = $articleDao->getById((int) $articleId, $journal->getId(), true);
			$this->article = $article;
		}

		if (!isset($this->article)) $request->getDispatcher()->handle404();

		if (in_array($request->getRequestedOp(), array('view', 'download'))) {
			$galleyId = isset($args[1]) ? $args[1] : 0;
			$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
			$this->galley = $galleyDao->getByBestGalleyId($galleyId, $this->article->getId());
			if ($galleyId && !$this->galley) $request->getDispatcher()->handle404();
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

		$journal = $request->getJournal();
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

		if (!$this->userCanViewGalley($request, $articleId, $galleyId)) fatalError('Cannot view galley.');

		// Get galleys sorted into primary and supplementary groups
		$galleys = $article->getGalleys();
		$primaryGalleys = array();
		$supplementaryGalleys = array();
		if ($galleys) {
			$genreDao = DAORegistry::getDAO('GenreDAO');
			$primaryGenres = $genreDao->getPrimaryByContextId($journal->getId())->toArray();
			$primaryGenreIds = array_map(function($genre) {
				return $genre->getId();
			}, $primaryGenres);
			$supplementaryGenres = $genreDao->getBySupplementaryAndContextId(true, $journal->getId())->toArray();
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

		// Fetch and assign the section to the template
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$section = $sectionDao->getById($article->getSectionId(), $journal->getId(), true);
		$templateMgr->assign('section', $section);

		// Fetch and assign the galley to the template
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = $galleyDao->getByBestGalleyId($galleyId, $article->getId());
		if ($galley && $galley->getRemoteURL()) $request->redirectUrl($galley->getRemoteURL());

		// Copyright and license info
		$templateMgr->assign(array(
			'copyright' => $journal->getLocalizedSetting('copyrightNotice'),
		));
		if ($article->getLicenseURL()) $templateMgr->assign(array(
			'licenseUrl' => $article->getLicenseURL(),
			'ccLicenseBadge' => Application::getCCLicenseBadge($article->getLicenseURL()),
			'copyrightHolder' => $article->getLocalizedCopyrightHolder(),
			'copyrightYear' => $article->getCopyrightYear(),
		));

		// Citations
		$citationDao = DAORegistry::getDAO('CitationDAO');
		$parsedCitations = $citationDao->getBySubmissionId($article->getId());
		$templateMgr->assign('parsedCitations', $parsedCitations);

		// Keywords
		$submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO');
		$templateMgr->assign('keywords', $submissionKeywordDao->getKeywords($article->getId(), array(AppLocale::getLocale())));

		// Consider public identifiers
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
		$templateMgr->assign('pubIdPlugins', $pubIdPlugins);

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
				$subscriptionRequired = $issueAction->subscriptionRequired($issue, $journal);
			}

			$subscribedUser = $issueAction->subscribedUser($user, $journal, isset($issue) ? $issue->getId() : null, isset($article) ? $article->getId() : null);
			$subscribedDomain = $issueAction->subscribedDomain($request, $journal, isset($issue) ? $issue->getId() : null, isset($article) ? $article->getId() : null);

			$templateMgr->assign('hasAccess', !$subscriptionRequired || (isset($article) && $article->getAccessStatus() == ARTICLE_ACCESS_OPEN) || $subscribedUser || $subscribedDomain);

			$paymentManager = Application::getPaymentManager($journal);
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
				$request->redirect(null, null, 'download', array($articleId, $galleyId));
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
		$suppId = isset($args[1]) ? $args[1] : 0;
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$submissionFiles = $submissionFileDao->getBySubmissionId($articleId);
		foreach ($submissionFiles as $submissionFile) {
			if ($submissionFile->getData('old-supp-id') == $suppId) {
				$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
				$articleGalleys = $articleGalleyDao->getBySubmissionId($articleId);
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
				$submissionFileManager->downloadFile($fileId, null, $request->getUserVar('inline')?true:false);
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

		$journal = $request->getJournal();
		$publishedArticle = $this->article;
		$issue = $this->issue;
		$journalId = $journal->getId();
		$user = $request->getUser();
		$userId = $user?$user->getId():0;

		// If this is an editorial user who can view unpublished/unscheduled
		// articles, bypass further validation. Likewise for its author.
		if ($publishedArticle && $issueAction->allowedPrePublicationAccess($journal, $publishedArticle, $user)) {
			return true;
		}

		// Make sure the reader has rights to view the article/issue.
		if ($issue && $issue->getPublished() && $publishedArticle->getStatus() == STATUS_PUBLISHED) {
			$subscriptionRequired = $issueAction->subscriptionRequired($issue, $journal);
			$isSubscribedDomain = $issueAction->subscribedDomain($request, $journal, $issue->getId(), $publishedArticle->getId());

			// Check if login is required for viewing.
			if (!$isSubscribedDomain && !Validation::isLoggedIn() && $journal->getSetting('restrictArticleAccess') && isset($galleyId) && $galleyId) {
				Validation::redirectLogin();
			}

			// bypass all validation if subscription based on domain or ip is valid
			// or if the user is just requesting the abstract
			if ( (!$isSubscribedDomain && $subscriptionRequired) && (isset($galleyId) && $galleyId) ) {

				// Subscription Access
				$subscribedUser = $issueAction->subscribedUser($user, $journal, $issue->getId(), $publishedArticle->getId());

				import('classes.payment.ojs.OJSPaymentManager');
				$paymentManager = Application::getPaymentManager($journal);

				$purchasedIssue = false;
				if (!$subscribedUser && $paymentManager->purchaseIssueEnabled()) {
					$completedPaymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO');
					$purchasedIssue = $completedPaymentDao->hasPaidPurchaseIssue($userId, $issue->getId());
				}

				if (!(!$subscriptionRequired || $publishedArticle->getAccessStatus() == ARTICLE_ACCESS_OPEN || $subscribedUser || $purchasedIssue)) {

					if ( $paymentManager->purchaseArticleEnabled() || $paymentManager->membershipEnabled() ) {
						/* if only pdf files are being restricted, then approve all non-pdf galleys
						 * and continue checking if it is a pdf galley */
						if ( $paymentManager->onlyPdfEnabled() ) {

							if ($this->galley && !$this->galley->isPdfGalley() ) {
								$this->issue = $issue;
								$this->article = $publishedArticle;
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
						if ($completedPaymentDao->hasPaidPurchaseArticle($userId, $publishedArticle->getId())
							|| (!is_null($dateEndMembership) && $dateEndMembership > time())) {
							$this->issue = $issue;
							$this->article = $publishedArticle;
							return true;
						} elseif ($paymentManager->purchaseArticleEnabled()) {
							$queuedPayment = $paymentManager->createQueuedPayment($request, PAYMENT_TYPE_PURCHASE_ARTICLE, $user->getId(), $publishedArticle->getId(), $journal->getSetting('purchaseArticleFee'));
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

?>
