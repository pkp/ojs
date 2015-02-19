<?php

/**
 * @file pages/issue/IssueHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
	/** @var Issue retrieved issue */
	var $_issue = null;

	/** @var IssueGalley retrieved issue galley */
	var $_galley = null;

	/**
	 * Constructor
	 **/
	function IssueHandler() {
		parent::Handler();

		$this->addCheck(new HandlerValidatorJournal($this));
		$this->addCheck(new HandlerValidatorCustom($this, false, null, null, create_function('$journal', 'return $journal->getSetting(\'publishingMode\') != PUBLISHING_MODE_NONE;'), array(Request::getJournal())));
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
		$this->validate($request);
		$this->setupTemplate();

		$showToc = isset($args[0]) ? $args[0] : '';

		$journal =& $request->getJournal();

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issue =& $issueDao->getCurrentIssue($journal->getId(), true);

		$templateMgr =& TemplateManager::getManager();

		if ($issue != null) {
			if ($showToc == 'showToc') {
				$request->redirect(null, 'issue', 'view', array($issue->getId(), "showToc"), $request->getQueryArray());
			} else {
				$request->redirect(null, 'issue', 'view', $issue->getId(), $request->getQueryArray());
			}
		} else {
			$issueCrumbTitle = __('current.noCurrentIssue');
			$issueHeadingTitle = __('current.noCurrentIssue');
		}

		$templateMgr->assign('pageHierarchy', array(array($request->url(null, 'issue', 'current'), 'current.current')));
		$templateMgr->assign('helpTopicId', 'user.currentAndArchives');
		// consider public identifiers
		$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true);
		$templateMgr->assign('pubIdPlugins', $pubIdPlugins);
		$templateMgr->display('issue/viewPage.tpl');
	}

	/**
	 * Display issue view page.
	 */
	function view($args, $request) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$showToc = isset($args[1]) ? $args[1] : '';

		$this->validate($request, $issueId);
		$this->setupTemplate();

		$journal =& $request->getJournal();
		$issue =& $this->getIssue();

		$templateMgr =& TemplateManager::getManager();
		$this->_setupIssueTemplate($request, $issue, ($showToc == 'showToc') ? true : false);
		if ($issue) $templateMgr->assign('issueId', $issue->getBestIssueId());

		$templateMgr->assign('pageHierarchy', array(array($request->url(null, 'issue', 'archive'), 'archive.archives')));
		$templateMgr->assign('helpTopicId', 'user.currentAndArchives');
		// consider public identifiers
		$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true);
		$templateMgr->assign('pubIdPlugins', $pubIdPlugins);
		$templateMgr->display('issue/viewPage.tpl');

	}

	/**
	 * Display the issue archive listings
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function archive($args, $request) {
		$this->validate($request);
		$this->setupTemplate();

		$journal =& $request->getJournal();
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$rangeInfo = $this->getRangeInfo('issues');

		$publishedIssuesIterator = $issueDao->getPublishedIssues($journal->getId(), $rangeInfo);

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		$coverPagePath = $request->getBaseUrl() . '/';
		$coverPagePath .= $publicFileManager->getJournalFilesPath($journal->getId()) . '/';

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('coverPagePath', $coverPagePath);
		$templateMgr->assign('locale', AppLocale::getLocale());
		$templateMgr->assign_by_ref('issues', $publishedIssuesIterator);
		$templateMgr->assign('helpTopicId', 'user.currentAndArchives');
		$templateMgr->display('issue/archive.tpl');
	}

	/**
	 * View a PDF issue galley inline
	 * @param $args array ($issueId, $galleyId)
	 * @param $request Request
	 */
	function viewIssue($args, $request) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;

		$this->validate($request, $issueId, $galleyId);
		$this->setupTemplate();

		$journal =& $request->getJournal();
		$issue =& $this->getIssue();
		$galley =& $this->getGalley();

		// Ensure we have PDF galley for inline viewing
		// Otherwise redirect to download issue galley page
		if (!$galley->isPdfGalley()) {
			$request->redirect(null, null, 'viewDownloadInterstitial', array($issueId, $galleyId));
		}

		// Display PDF galley inline
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->addJavaScript('js/inlinePdf.js');
		$templateMgr->addJavaScript('js/pdfobject.js');
		$templateMgr->addStyleSheet($request->getBaseUrl().'/styles/pdfView.css');

		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign_by_ref('galley', $galley);
		$templateMgr->assign_by_ref('journal', $journal);
		$templateMgr->assign('issueId', $issueId);
		$templateMgr->assign('galleyId', $galleyId);

		$templateMgr->assign('pageHierarchy', array(array($request->url(null, 'issue', 'view', $issueId), $issue->getIssueIdentification(false, true), true)));
		$templateMgr->assign('issueHeadingTitle', __('issue.viewIssue'));
		$templateMgr->assign('locale', AppLocale::getLocale());

		$templateMgr->display('issue/issueGalley.tpl');
	}

	/**
	 * Issue galley interstitial page for non-PDF files
	 * @param $args array ($issueId, $galleyId)
	 * @param $request Request
	 */
	function viewDownloadInterstitial($args, $request) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;

		$this->validate($request, $issueId, $galleyId);
		$this->setupTemplate();

		$journal =& $request->getJournal();
		$issue =& $this->getIssue();
		$galley =& $this->getGalley();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('issueId', $issueId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('galley', $galley);
		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->display('issue/interstitial.tpl');
	}

	/**
	 * View an issue galley file (inline file).
	 * @param $args array ($issueId, $galleyId)
	 * @param $request Request
	 */
	function viewFile($args, $request) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;

		$this->validate($request, $issueId, $galleyId);

		$this->_showIssueGalley($request, true);
	}

	/**
	 * Downloads an issue galley file
	 * @param $args array ($issueId, $galleyId)
	 * @param $request Request
	 */
	function download($args, $request) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;

		$this->validate($request, $issueId, $galleyId);

		$this->_showIssueGalley($request, false);
	}

	/**
	 * Get the retrieved issue
	 * @return Issue
	 */
	function &getIssue() {
		return $this->_issue;
	}

	/**
	 * Set a retrieved issue
	 * @param $issue Issue
	 */
	function setIssue($issue) {
		$this->_issue =& $issue;
	}

	/**
	 * Get the retrieved issue galley
	 * @return IssueGalley
	 */
	function &getGalley() {
		return $this->_galley;
	}

	/**
	 * Set a retrieved issue galley
	 * @param $galley IssueGalley
	 */
	function setGalley($galley) {
		$this->_galley =& $galley;
	}

	/**
	 * Validation
	 * @see lib/pkp/classes/handler/PKPHandler#validate()
	 * @param $request Request
	 * @param $issueId int
	 * @param $galleyId int
	 */
	function validate($request, $issueId = null, $galleyId = null) {
		$returner = parent::validate(null, $request);

		// Validate requests that don't specify an issue or galley
		if (!$issueId && !$galleyId) {
			return $returner;
		}

		// Require an issue id to continue
		if (!$issueId) $request->redirect(null, 'index');

		import('classes.issue.IssueAction');

		$journal =& $request->getJournal();
		$journalId = $journal->getId();
		$user =& $request->getUser();
		$userId = $user ? $user->getId() : 0;
		$issue = null;
		$galley = null;

		// Get the issue
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		if ($journal->getSetting('enablePublicIssueId')) {
			$issue =& $issueDao->getIssueByBestIssueId($issueId, $journalId);
		} else {
			$issue =& $issueDao->getIssueById((int) $issueId, null, true);
		}

		// Invalid issue id, redirect to current issue
		if (!$issue || !$this->_isVisibleIssue($issue, $journalId)) $request->redirect(null, null, 'current');

		$this->setIssue($issue);

		// If no issue galley id provided, then we're done
		if (!$galleyId) return true;

		// Get the issue galley
		$galleyDao =& DAORegistry::getDAO('IssueGalleyDAO');
		if ($journal->getSetting('enablePublicGalleyId')) {
			$galley =& $galleyDao->getGalleyByBestGalleyId($galleyId, $issue->getId());
		} else {
			$galley =& $galleyDao->getGalley($galleyId, $issue->getId());
		}

		// Invalid galley id, redirect to issue page
		if (!$galley) $request->redirect(null, null, 'view', $issueId);

		$this->setGalley($galley);

		// If this is an editorial user who can view unpublished issue galleys,
		// bypass further validation
		if (IssueAction::allowedIssuePrePublicationAccess($journal)) return true;

		// Ensure reader has rights to view the issue galley
		if ($issue->getPublished()) {
			$subscriptionRequired = IssueAction::subscriptionRequired($issue);
			$isSubscribedDomain = IssueAction::subscribedDomain($journal, $issueId);

			// Check if login is required for viewing.
			if (!$isSubscribedDomain && !Validation::isLoggedIn() && $journal->getSetting('restrictArticleAccess')) {
				Validation::redirectLogin();
			}

			// If no domain/ip subscription, check if user has a valid subscription
			// or if the user has previously purchased the issue
			if (!$isSubscribedDomain && $subscriptionRequired) {

				// Check if user has a valid subscription
				$subscribedUser = IssueAction::subscribedUser($journal, $issueId);

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
						$completedPaymentDao =& DAORegistry::getDAO('OJSCompletedPaymentDAO');
						$dateEndMembership = $user->getSetting('dateEndMembership', 0);
						if ($completedPaymentDao->hasPaidPurchaseIssue($userId, $issueId) || (!is_null($dateEndMembership) && $dateEndMembership > time())) {
							return true;
						} else {
							// Otherwise queue an issue purchase payment and display payment form
							$queuedPayment =& $paymentManager->createQueuedPayment($journalId, PAYMENT_TYPE_PURCHASE_ISSUE, $userId, $issueId, $journal->getSetting('purchaseIssueFee'));
							$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

							$templateMgr =& TemplateManager::getManager();
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

	function setupTemplate() {
		parent::setupTemplate();
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_READER, LOCALE_COMPONENT_OJS_EDITOR);
	}

	/**
	 * Show an issue galley file (either inline or download)
	 * @param $issueId int
	 * @param $galleyId int
	 * @param $request Request
	 * @param $inline boolean
	 */
	function _showIssueGalley($request, $inline = false) {
		$journal =& $request->getJournal();
		$issue =& $this->getIssue();
		$galley =& $this->getGalley();

		$galleyDao =& DAORegistry::getDAO('IssueGalleyDAO');

		if (!HookRegistry::call('IssueHandler::viewFile', array(&$issue, &$galley))) {
			import('classes.file.IssueFileManager');
			$issueFileManager = new IssueFileManager($issue->getId());
			return $issueFileManager->downloadFile($galley->getFileId(), $inline);
		}
	}

	/**
	 * Given an issue and journal id, return whether the current user can view the issue in the journal
	 * @param $issue object The issue to display
	 * @param $journalId int The id of the journal
	 */
	function _isVisibleIssue($issue, $journalId) {
		if (isset($issue) && ($issue->getPublished() || Validation::isEditor($journalId) || Validation::isLayoutEditor($journalId) || Validation::isProofreader($journalId)) && $issue->getJournalId() == $journalId) {
			return true;
		} else {
			return false;
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
		$journal =& $request->getJournal();
		$journalId = $journal->getId();
		$templateMgr =& TemplateManager::getManager();
		if (IssueHandler::_isVisibleIssue($issue, $journalId)) {

			$issueHeadingTitle = $issue->getIssueIdentification(false, true);
			$issueCrumbTitle = $issue->getIssueIdentification(false, true);

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
				$issueGalleyDao =& DAORegistry::getDAO('IssueGalleyDAO');
				$issueGalleys =& $issueGalleyDao->getGalleysByIssue($issue->getId());
				$templateMgr->assign_by_ref('issueGalleys', $issueGalleys);

				// Published articles
				$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticles =& $publishedArticleDao->getPublishedArticlesInSections($issue->getId(), true);

				$publicFileManager = new PublicFileManager();
				$templateMgr->assign_by_ref('publishedArticles', $publishedArticles);
				$showToc = true;
			}
			$templateMgr->assign('showToc', $showToc);
			$templateMgr->assign_by_ref('issue', $issue);

			// Subscription Access
			import('classes.issue.IssueAction');
			$subscriptionRequired = IssueAction::subscriptionRequired($issue);
			$subscribedUser = IssueAction::subscribedUser($journal);
			$subscribedDomain = IssueAction::subscribedDomain($journal);
			$subscriptionExpiryPartial = $journal->getSetting('subscriptionExpiryPartial');

			if ($showToc && $subscriptionRequired && !$subscribedUser && !$subscribedDomain && $subscriptionExpiryPartial) {
				$templateMgr->assign('subscriptionExpiryPartial', true);

				// Partial subscription expiry for issue
				$partial = IssueAction::subscribedUser($journal, $issue->getId());
				if (!$partial) IssueAction::subscribedDomain($journal, $issue->getId());
				$templateMgr->assign('issueExpiryPartial', $partial);

				// Partial subscription expiry for articles
				$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticlesTemp =& $publishedArticleDao->getPublishedArticles($issue->getId());

				$articleExpiryPartial = array();
				foreach ($publishedArticlesTemp as $publishedArticle) {
					$partial = IssueAction::subscribedUser($journal, $issue->getId(), $publishedArticle->getId());
					if (!$partial) IssueAction::subscribedDomain($journal, $issue->getId(), $publishedArticle->getId());
					$articleExpiryPartial[$publishedArticle->getId()] = $partial;
				}
				$templateMgr->assign_by_ref('articleExpiryPartial', $articleExpiryPartial);
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

		} else {
			$issueCrumbTitle = __('archive.issueUnavailable');
			$issueHeadingTitle = __('archive.issueUnavailable');
		}

		if ($issue && $styleFileName = $issue->getStyleFileName()) {
			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$templateMgr->addStyleSheet(
				$request->getBaseUrl() . '/' . $publicFileManager->getJournalFilesPath($journalId) . '/' . $styleFileName
			);
		}

		$templateMgr->assign('pageCrumbTitleTranslated', $issueCrumbTitle);
		$templateMgr->assign('issueHeadingTitle', $issueHeadingTitle);
	}
}

?>
