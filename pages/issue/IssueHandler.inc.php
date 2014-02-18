<?php

/**
 * @file pages/issue/IssueHandler.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueHandler
 * @ingroup pages_issue
 *
 * @brief Handle requests for issue functions.
 */

import ('classes.issue.IssueAction');
import('classes.handler.Handler');

class IssueHandler extends Handler {
	/** @var IssueGalley retrieved issue galley */
	var $_galley = null;

	/**
	 * Constructor
	 **/
	function IssueHandler() {
		parent::Handler();
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request));

		import('classes.security.authorization.OjsJournalMustPublishPolicy');
		$this->addPolicy(new OjsJournalMustPublishPolicy($request));

		import('classes.security.authorization.OjsIssueRequiredPolicy');
		// the 'archives' op does not need this policy so it is left out of the operations array.
		$this->addPolicy(new OjsIssueRequiredPolicy($request, $args, array('view', 'viewIssue', 'viewFile', 'viewDownloadIterstitial', 'download')));

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize($request, $args) {
		// Get the issue galley
		$galleyId = isset($args[1]) && is_numeric($args[1]) ? $args[1] : 0;
		if ($galleyId) {
			$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
			$galleyDao = DAORegistry::getDAO('IssueGalleyDAO');
			$journal = $request->getJournal();
			if ($journal->getSetting('enablePublicGalleyId')) {
				$galley = $galleyDao->getByBestId($galleyId, $issue->getId());
			} else {
				$galley = $galleyDao->getById($galleyId, $issue->getId());
			}

			// Invalid galley id, redirect to issue page
			if (!$galley) $request->redirect(null, null, 'view', $issue->getId());

			$this->setGalley($galley);
		}
	}

	/**
	 * Display about index page.
	 */
	function index($args, $request) {
		$this->current($args, $request);
	}

	/**
	 * Display current issue page.
	 */
	function current($args, $request) {
		$this->setupTemplate($request);

		$showToc = isset($args[0]) ? $args[0] : '';

		$journal = $request->getJournal();

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getCurrent($journal->getId(), true);

		$templateMgr = TemplateManager::getManager($request);

		if ($issue != null) {
			$request->redirect(null, 'issue', 'view', $issue->getId(), $request->getQueryArray());
		} else {
			$issueCrumbTitle = __('current.noCurrentIssue');
			$issueHeadingTitle = __('current.noCurrentIssue');
		}

		// consider public identifiers
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
		$templateMgr->assign('pubIdPlugins', $pubIdPlugins);
		$templateMgr->display('issue/viewPage.tpl');
	}

	/**
	 * Display issue view page.
	 */
	function view($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$showToc = isset($args[1]) ? $args[1] : '';

		$this->setupTemplate($request);

		$journal = $request->getJournal();

		$templateMgr = TemplateManager::getManager($request);
		$this->_setupIssueTemplate($request, $issue, ($showToc == 'showToc') ? true : false);
		$templateMgr->assign('issueId', $issue->getBestIssueId());

		// consider public identifiers
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
		$templateMgr->assign('pubIdPlugins', $pubIdPlugins);
		$templateMgr->display('issue/viewPage.tpl');

	}

	/**
	 * Display the issue archive listings
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function archive($args, $request) {
		$this->setupTemplate($request);
		$journal = $request->getJournal();

		$templateMgr = TemplateManager::getManager($request);
		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		$coverPagePath = $request->getBaseUrl() . '/';
		$coverPagePath .= $publicFileManager->getJournalFilesPath($journal->getId()) . '/';
		$templateMgr->assign('coverPagePath', $coverPagePath);

		$rangeInfo = $this->getRangeInfo($request, 'issues');
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$publishedIssuesIterator = $issueDao->getPublishedIssues($journal->getId(), $rangeInfo);
		$templateMgr->assign('issues', $publishedIssuesIterator);
		$templateMgr->display('issue/archive.tpl');
	}

	/**
	 * View a PDF issue galley inline
	 * @param $args array ($issueId, $galleyId)
	 * @param $request Request
	 */
	function viewIssue($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$galleyId = isset($args[1]) ? $args[1] : 0;

		if ($galleyId && $this->userCanViewGalley($request)) {
			$this->setupTemplate($request);

			// Ensure we have PDF galley for inline viewing
			// Otherwise redirect to download issue galley page
			$galley = $this->getGalley();
			if (!$galley->isPdfGalley()) {
				$request->redirect(null, null, 'viewDownloadInterstitial', array($issue->getId(), $galleyId));
			}

			// Display PDF galley inline
			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->addJavaScript('js/inlinePdf.js');
			$templateMgr->addJavaScript('lib/pkp/lib/pdfobject/js/pdfobject.js');
			$templateMgr->addStyleSheet($request->getBaseUrl().'/styles/pdfView.css');

			$templateMgr->assign('issue', $issue);
			$templateMgr->assign('galley', $galley);
			$templateMgr->assign('issueId', $issue->getId());
			$templateMgr->assign('galleyId', $galleyId);

			$templateMgr->assign('issueHeadingTitle', __('issue.viewIssue'));
			$templateMgr->display('issue/issueGalley.tpl');
		}
	}

	/**
	 * Issue galley interstitial page for non-PDF files
	 * @param $args array ($issueId, $galleyId)
	 * @param $request Request
	 */
	function viewDownloadInterstitial($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$galleyId = isset($args[1]) ? $args[1] : 0;
		if ($galleyId && $this->userCanViewGalley($request)) {
			$this->setupTemplate($request);
			$galley = $this->getGalley();

			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->assign('issueId', $issue->getId());
			$templateMgr->assign('galleyId', $galleyId);
			$templateMgr->assign('galley', $galley);
			$templateMgr->assign('issue', $issue);
			$templateMgr->display('issue/interstitial.tpl');
		}
	}

	/**
	 * View an issue galley file (inline file).
	 * @param $args array ($issueId, $galleyId)
	 * @param $request Request
	 */
	function viewFile($args, $request) {
		$galleyId = isset($args[1]) ? $args[1] : 0;
		if ($galleyId && $this->userCanViewGalley($request)) {
			$this->_showIssueGalley($request, true);
		}
	}

	/**
	 * Downloads an issue galley file
	 * @param $args array ($issueId, $galleyId)
	 * @param $request Request
	 */
	function download($args, $request) {
		$galleyId = isset($args[1]) ? $args[1] : 0;
		if ($galleyId && $this->userCanViewGalley($request)) {
			$this->_showIssueGalley($request, false);
		}
	}

	/**
	 * Get the retrieved issue galley
	 * @return IssueGalley
	 */
	function getGalley() {
		return $this->_galley;
	}

	/**
	 * Set a retrieved issue galley
	 * @param $galley IssueGalley
	 */
	function setGalley($galley) {
		$this->_galley = $galley;
	}

	/**
	 * Determines whether or not a user can view an issue galley.
	 * @param $request Request
	 */
	function userCanViewGalley($request) {

		import('classes.issue.IssueAction');
		$issueAction = new IssueAction();

		$journal = $request->getJournal();
		$user = $request->getUser();
		$userId = $user ? $user->getId() : 0;
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$galley = $this->getGalley();

		// If this is an editorial user who can view unpublished issue galleys,
		// bypass further validation
		if ($issueAction->allowedIssuePrePublicationAccess($journal)) return true;

		// Ensure reader has rights to view the issue galley
		if ($issue->getPublished()) {
			$subscriptionRequired = $issueAction->subscriptionRequired($issue);
			$isSubscribedDomain = $issueAction->subscribedDomain($journal, $issue->getId());

			// Check if login is required for viewing.
			if (!$isSubscribedDomain && !Validation::isLoggedIn() && $journal->getSetting('restrictArticleAccess')) {
				Validation::redirectLogin();
			}

			// If no domain/ip subscription, check if user has a valid subscription
			// or if the user has previously purchased the issue
			if (!$isSubscribedDomain && $subscriptionRequired) {

				// Check if user has a valid subscription
				$subscribedUser = $issueAction->subscribedUser($journal, $issue->getId());

				if (!$subscribedUser) {
					// Check if payments are enabled,
					import('classes.payment.ojs.OJSPaymentManager');
					$paymentManager = new OJSPaymentManager($request);

					if ($paymentManager->purchaseIssueEnabled() || $paymentManager->membershipEnabled() ) {
						// If only pdf files are being restricted, then approve all non-pdf galleys
						// and continue checking if it is a pdf galley
						if ($paymentManager->onlyPdfEnabled() && !$galley->isPdfGalley()) return true;

						if (!Validation::isLoggedIn()) {
							Validation::redirectLogin("payment.loginRequired.forIssue");
						}

						// If the issue galley has been purchased, then allow reader access
						$completedPaymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO');
						$dateEndMembership = $user->getSetting('dateEndMembership', 0);
						if ($completedPaymentDao->hasPaidPurchaseIssue($userId, $issue->getId()) || (!is_null($dateEndMembership) && $dateEndMembership > time())) {
							return true;
						} else {
							// Otherwise queue an issue purchase payment and display payment form
							$queuedPayment =& $paymentManager->createQueuedPayment($journal->getId(), PAYMENT_TYPE_PURCHASE_ISSUE, $userId, $issue->getId(), $journal->getSetting('purchaseIssueFee'));
							$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

							$templateMgr = TemplateManager::getManager($request);
							$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
							exit;
						}
					}

					if (!Validation::isLoggedIn()) {
						Validation::redirectLogin("reader.subscriptionRequiredLoginText");
					}
					$request->redirect(null, 'about', 'subscriptions');
				}
			}
		} else {
			$request->redirect(null, 'index');
		}
		return true;
	}

	function setupTemplate($request) {
		parent::setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_READER, LOCALE_COMPONENT_APP_EDITOR);
	}

	/**
	 * Show an issue galley file (either inline or download)
	 * @param $issueId int
	 * @param $galleyId int
	 * @param $request Request
	 * @param $inline boolean
	 */
	function _showIssueGalley($request, $inline = false) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$galley = $this->getGalley();

		$galleyDao = DAORegistry::getDAO('IssueGalleyDAO');

		if (!HookRegistry::call('IssueHandler::viewFile', array(&$issue, &$galley))) {
			import('classes.file.IssueFileManager');
			$issueFileManager = new IssueFileManager($issue->getId());
			return $issueFileManager->downloadFile($galley->getFileId(), $inline);
		}
	}

	/**
	 * Given an issue, set up the template with all the required variables for
	 * issues/view.tpl to function properly (i.e. current issue and view issue).
	 * @param $issue object The issue to display
	 * @param $showToc boolean iff false and a custom cover page exists,
	 * 	the cover page will be displayed. Otherwise table of contents
	 * 	will be displayed.
	 */
	function _setupIssueTemplate($request, $issue, $showToc = false) {
		$journal = $request->getJournal();
		$journalId = $journal->getId();
		$templateMgr = TemplateManager::getManager($request);

		// Determine pre-publication access
		// FIXME: Do that. (Bug #8278)
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');

		$prePublicationAccess = false;

		if (!$issue) {
			$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		}
		$issueHeadingTitle = $issue->getIssueIdentification(false, true);
		$locale = AppLocale::getLocale();

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		$coverPagePath = $request->getBaseUrl() . '/';
		$coverPagePath .= $publicFileManager->getJournalFilesPath($journalId) . '/';
		$templateMgr->assign('coverPagePath', $coverPagePath);
		$templateMgr->assign('locale', $locale);


		if (!$showToc && $issue->getFileName($locale) && $issue->getShowCoverPage($locale) && !$issue->getHideCoverPageCover($locale)) {
			$templateMgr->assign('fileName', $issue->getFileName($locale));
			$templateMgr->assign('width', $issue->getWidth($locale));
			$templateMgr->assign('height', $issue->getHeight($locale));
			$templateMgr->assign('coverPageAltText', $issue->getCoverPageAltText($locale));
			$templateMgr->assign('originalFileName', $issue->getOriginalFileName($locale));

			$showToc = false;
		} else {
			// Issue galleys
			$issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO');
			$issueGalleys = $issueGalleyDao->getByIssueId($issue->getId());
			$templateMgr->assign('issueGalleys', $issueGalleys);

			// Published articles
			$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
			$publishedArticles =& $publishedArticleDao->getPublishedArticlesInSections($issue->getId(), true);

			$publicFileManager = new PublicFileManager();
			$templateMgr->assign('publishedArticles', $publishedArticles);
			$showToc = true;
		}
		$templateMgr->assign('showToc', $showToc);
		$templateMgr->assign('issue', $issue);

		// Subscription Access
		import('classes.issue.IssueAction');
		$issueAction = new IssueAction();
		$subscriptionRequired = $issueAction->subscriptionRequired($issue);
		$subscribedUser = $issueAction->subscribedUser($journal);
		$subscribedDomain = $issueAction->subscribedDomain($journal);
		$subscriptionExpiryPartial = $journal->getSetting('subscriptionExpiryPartial');

		if ($showToc && $subscriptionRequired && !$subscribedUser && !$subscribedDomain && $subscriptionExpiryPartial) {
			$templateMgr->assign('subscriptionExpiryPartial', true);

			// Partial subscription expiry for issue
			$partial = $issueAction->subscribedUser($journal, $issue->getId());
			if (!$partial) $issueAction->subscribedDomain($journal, $issue->getId());
			$templateMgr->assign('issueExpiryPartial', $partial);

			// Partial subscription expiry for articles
			$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
			$publishedArticlesTemp =& $publishedArticleDao->getPublishedArticles($issue->getId());

			$articleExpiryPartial = array();
			foreach ($publishedArticlesTemp as $publishedArticle) {
				$partial = $issueAction->subscribedUser($journal, $issue->getId(), $publishedArticle->getId());
				if (!$partial) $issueAction->subscribedDomain($journal, $issue->getId(), $publishedArticle->getId());
				$articleExpiryPartial[$publishedArticle->getId()] = $partial;
			}
			$templateMgr->assign('articleExpiryPartial', $articleExpiryPartial);
		}

		$templateMgr->assign('subscriptionRequired', $subscriptionRequired);
		$templateMgr->assign('subscribedUser', $subscribedUser);
		$templateMgr->assign('subscribedDomain', $subscribedDomain);
		$templateMgr->assign('showGalleyLinks', $journal->getSetting('showGalleyLinks'));

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		if ( $paymentManager->onlyPdfEnabled() ) {
			$templateMgr->assign('restrictOnlyPdf', true);
		}
		if ( $paymentManager->purchaseArticleEnabled() ) {
			$templateMgr->assign('purchaseArticleEnabled', true);
		}

		if ($styleFileName = $issue->getStyleFileName()) {
			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$templateMgr->addStyleSheet(
				$request->getBaseUrl() . '/' . $publicFileManager->getJournalFilesPath($journalId) . '/' . $styleFileName
			);
		}

		$templateMgr->assign('issueHeadingTitle', $issueHeadingTitle);
	}
}

?>
