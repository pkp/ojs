<?php

/**
 * @file pages/article/ArticleHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
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
	/** @var $journal Journal: journal associated with the request **/
	var $journal;

	/** @var $issue Issue: issue associated with the request **/
	var $issue;

	/** @var $article Article: article associated with the request **/
	var $article;

	/** @var $galley Galley: galley associated with the request **/
	var $galley;

	/** @var $submissionRevision int: submission(article) revision ID associated with the request **/
	var $submissionRevision;

	/**
	 * Constructor
	 * @param $request Request
	 */
	function __construct() {
		parent::__construct();
	}

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

		$this->journal = $request->getContext();

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$articleDao = DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');

		// get published article object (handle pub ids)
		$publishedArticle = $publishedArticleDao->getPublishedArticleByBestArticleId((int) $this->journal->getId(), $articleId, false, null);

		// get data of publishedArticle
		if (isset($publishedArticle)) {
			$issue = $issueDao->getById($publishedArticle->getIssueId(), $publishedArticle->getJournalId(), true);
			$this->issue = $issue;
			$this->article = $publishedArticle;
		} else {
			$articleDao = DAORegistry::getDAO('ArticleDAO');
			$article = $articleDao->getById((int) $articleId, $this->journal->getId(), true);
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
	 * Handle article versions. Calls view().
	 * @param $args array
	 * @param $request Request
	 */
	function version($args, $request){
		$articleId = $args[0];
		$this->submissionRevision = isset($args[1]) ? $args[1] : null;
		$galleyId = isset($args[2]) ? $args[2] : 0;
		array_splice($args, 1, 1);

		// get this published article version
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$this->article = $publishedArticleDao->getPublishedArticleByBestArticleId((int) $this->journal->getId(), $articleId, false, $this->submissionRevision);

		$this->view($args, $request);
	}

	/**
	 * View Article. (Either article landing page or galley view.)
	 * @param $args array
	 * @param $request Request
	 */
	function view($args, $request) {
		$articleId = $args[0];
		$galleyId = $args[1];
		$fileId = isset($args[2]) ? $args[2] : 0;
		$fileRevision = isset($args[3]) ? $args[3] : 0;

		$journal = $request->getJournal();
		$issue = $this->issue;
		$article = $this->article;
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'issue' => $issue,
			'article' => $article,
			'fileId' => $fileId,
			'fileRevision' => $fileRevision,
		));
		$this->setupTemplate($request);

		if (!$this->userCanViewGalley($request, $articleId, $galleyId)) fatalError('Cannot view galley.');

		// Fetch and assign the section to the template
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$section = $sectionDao->getById($article->getSectionId(), $journal->getId(), true);
		$templateMgr->assign('section', $section);

		// Fetch and assign the galley to the template
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$this->galley = $galleyDao->getByBestGalleyId($galleyId, $this->article->getId(), $this->submissionRevision);
		if ($this->galley && $this->galley->getRemoteURL()) $request->redirectUrl($this->galley->getRemoteURL());

		// Copyright and license info
		$templateMgr->assign(array(
			'copyright' => $journal->getLocalizedSetting('copyrightNotice'),
			'copyrightHolder' => $journal->getLocalizedSetting('copyrightHolder'),
			'copyrightYear' => $journal->getSetting('copyrightYear')
		));
		if ($article->getLicenseURL()) $templateMgr->assign(array(
			'licenseUrl' => $article->getLicenseURL(),
			'ccLicenseBadge' => Application::getCCLicenseBadge($article->getLicenseURL()),
		));

		// Keywords
		$submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO');
		$templateMgr->assign('keywords', $submissionKeywordDao->getKeywords($article->getId(), array(AppLocale::getLocale())));

		// Consider public identifiers
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
		$templateMgr->assign('pubIdPlugins', $pubIdPlugins);

		// Citation formats
		$citationPlugins = PluginRegistry::loadCategory('citationFormats');
		uasort($citationPlugins, create_function('$a, $b', 'return strcmp($a->getDisplayName(), $b->getDisplayName());'));
		$templateMgr->assign('citationPlugins', $citationPlugins);

		// Versioning
		$templateMgr->assign('versioningEnabled', $journal->getSetting('versioningEnabled'));

		if (!$this->galley) {
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

			$subscribedUser = $issueAction->subscribedUser($journal, isset($issue) ? $issue->getId() : null, isset($article) ? $article->getId() : null);
			$subscribedDomain = $issueAction->subscribedDomain($journal, isset($issue) ? $issue->getId() : null, isset($article) ? $article->getId() : null);

			$templateMgr->assign('hasAccess', !$subscriptionRequired || (isset($article) && $article->getAccessStatus() == ARTICLE_ACCESS_OPEN) || $subscribedUser || $subscribedDomain);

			import('classes.payment.ojs.OJSPaymentManager');
			$paymentManager = new OJSPaymentManager($request);
			if ( $paymentManager->onlyPdfEnabled() ) {
				$templateMgr->assign('restrictOnlyPdf', true);
			}
			if ( $paymentManager->purchaseArticleEnabled() ) {
				$templateMgr->assign('purchaseArticleEnabled', true);
			}

			// article versioning
			$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');

			// check if this is an old version
			if ($this->submissionRevision && ($this->submissionRevision < $article->getCurrentVersionId())) {
				$templateMgr->assign('isPreviousRevision', true);
			}

			// get all published previous article versions
			$previousRevisions = $publishedArticleDao->getPublishedSubmissionRevisions($this->article->getId(), $this->journal->getId(), SORT_DIRECTION_DESC);

			$templateMgr->assign('submissionRevision', $this->submissionRevision);
			$templateMgr->assign('previousRevisions', $previousRevisions);

			if (!HookRegistry::call('ArticleHandler::view', array(&$request, &$issue, &$article))) {
				return $templateMgr->display('frontend/pages/article.tpl');
			}
		} else {
			// Galley: Prepare the galley file download.
			if (!HookRegistry::call('ArticleHandler::view::galley', array(&$request, &$issue, &$galley, &$article))) {
				$this->download($args, $request);
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

		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$this->galley = $galleyDao->getByBestGalleyId($galleyId, $this->article->getId(), $this->submissionRevision);

		if ($this->galley->getRemoteURL()) $request->redirectUrl($this->galley->getRemoteURL());
		if ($this->userCanViewGalley($request, $articleId, $galleyId)) {
			if (!$fileId) {
				$submissionFile = $this->galley->getFile();
				if ($submissionFile) {
					$fileId = $submissionFile->getFileId();
					// The file manager expects the real article id.  Extract it from the submission file.
					$articleId = $submissionFile->getSubmissionId();
				} else { // no proof files assigned to this galley!
					return null;
				}
			}

			if (!HookRegistry::call('ArticleHandler::download', array($this->article, &$this->galley, &$fileId))) {
				import('lib.pkp.classes.file.SubmissionFileManager');
				$submissionFileManager = new SubmissionFileManager($this->article->getContextId(), $this->article->getId());
				$submissionFileManager->downloadFile($fileId, null, $request->getUserVar('inline')?true:false);
			}
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
			$isSubscribedDomain = $issueAction->subscribedDomain($journal, $issue->getId(), $publishedArticle->getId());

			// Check if login is required for viewing.
			if (!$isSubscribedDomain && !Validation::isLoggedIn() && $journal->getSetting('restrictArticleAccess') && isset($galleyId) && $galleyId) {
				Validation::redirectLogin();
			}

			// bypass all validation if subscription based on domain or ip is valid
			// or if the user is just requesting the abstract
			if ( (!$isSubscribedDomain && $subscriptionRequired) && (isset($galleyId) && $galleyId) ) {

				// Subscription Access
				$subscribedUser = $issueAction->subscribedUser($journal, $issue->getId(), $publishedArticle->getId());

				import('classes.payment.ojs.OJSPaymentManager');
				$paymentManager = new OJSPaymentManager($request);

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
							Validation::redirectLogin("payment.loginRequired.forArticle");
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
						} else {
							$queuedPayment = $paymentManager->createQueuedPayment($journalId, PAYMENT_TYPE_PURCHASE_ARTICLE, $user->getId(), $publishedArticle->getId(), $journal->getSetting('purchaseArticleFee'));
							$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

							$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
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
	 * Fetch an item citation
	 * @param $args
	 * @param $request
	 */
	function cite($args, $request) {
		$router = $request->getRouter();
		$this->setupTemplate($request);
		$articleId = isset($args[0]) ? $args[0] : 0;
		$version = isset($args[1]) ? $args[1] : null;
		$citeType = isset($args[2]) ? $args[2] : null;
		$returnFormat = isset($args[3]) ? $args[3] : null;

		$citationPlugins = PluginRegistry::loadCategory('citationFormats');

		import('lib.pkp.classes.core.JSONMessage');

		if (empty($citeType) || !isset($citationPlugins[$citeType])) {
			AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION);
			$errorMessage = __('submission.citationFormat.notFound');
			if ($returnFormat == 'json') {
				return new JSONMessage(false, $errorMessage);
			} else {
				echo $errorMessage;
			}
			return;
		}

		$article = $this->article;
		$issue = $this->issue;
		$journal = $request->getContext();

		// Initiate a file download and exit
		if ($citationPlugins[$citeType]->isDownloadable()) {
			$citationPlugins[$citeType]->downloadCitation($article, $issue, $journal, $version);
			return;
		}

		$citation = $citationPlugins[$citeType]->fetchCitation($article, $issue, $journal, $version);

		// Return a JSON formatted string
		if ($returnFormat == 'json') {
			return new JSONMessage(true, $citation);

		// Display it straight to the browser
		} else {
			echo $citation;
			return;
		}
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
