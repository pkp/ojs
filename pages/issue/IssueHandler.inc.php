<?php

/**
 * @file pages/issue/IssueHandler.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
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
	function __construct() {
		parent::__construct();
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request));

		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));

		import('classes.security.authorization.OjsJournalMustPublishPolicy');
		$this->addPolicy(new OjsJournalMustPublishPolicy($request));

		import('classes.security.authorization.OjsIssueRequiredPolicy');
		// the 'archives' op does not need this policy so it is left out of the operations array.
		$this->addPolicy(new OjsIssueRequiredPolicy($request, $args, array('view', 'download')));

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize($request, $args) {
		// Get the issue galley
		$galleyId = isset($args[1]) ? $args[1] : 0;
		if ($galleyId) {
			$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
			$galleyDao = DAORegistry::getDAO('IssueGalleyDAO');
			$journal = $request->getJournal();
			$galley = $galleyDao->getByBestId($galleyId, $issue->getId());

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

		$journal = $request->getJournal();

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getCurrent($journal->getId(), true);

		$templateMgr = TemplateManager::getManager($request);

		if ($issue != null) {
			$request->redirect(null, 'issue', 'view', $issue->getId(), $request->getQueryArray());
		}

		// consider public identifiers
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
		$templateMgr->assign('pubIdPlugins', $pubIdPlugins);
		$templateMgr->display('frontend/pages/issue.tpl');
	}

	/**
	 * View an issue.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function view($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);
		$journal = $request->getJournal();

		if ($galley = $this->getGalley()) {
			if (!HookRegistry::call('IssueHandler::view::galley', array(&$request, &$issue, &$galley))) {
				$request->redirect(null, null, 'download', array($issue->getBestIssueId($journal), $galley->getBestGalleyId($journal)));
			}
		} else {
			$this->_setupIssueTemplate($request, $issue, $request->getUserVar('showToc') ? true : false);
			$templateMgr->assign('issueId', $issue->getBestIssueId());

			// consider public identifiers
			$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
			$templateMgr->assign('pubIdPlugins', $pubIdPlugins);
			$templateMgr->display('frontend/pages/issue.tpl');
		}
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
		$coverImagePath = $request->getBaseUrl() . '/';
		$coverImagePath .= $publicFileManager->getJournalFilesPath($journal->getId()) . '/';
		$templateMgr->assign('coverImagePath', $coverImagePath);

		$rangeInfo = $this->getRangeInfo($request, 'issues');
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$publishedIssuesIterator = $issueDao->getPublishedIssues($journal->getId(), $rangeInfo);
		$templateMgr->assign('issues', $publishedIssuesIterator);
		$templateMgr->display('frontend/pages/issueArchive.tpl');
	}

	/**
	 * Downloads an issue galley file
	 * @param $args array ($issueId, $galleyId)
	 * @param $request Request
	 */
	function download($args, $request) {
		if ($this->userCanViewGalley($request)) {
			$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
			$galley = $this->getGalley();

			if (!HookRegistry::call('IssueHandler::download', array(&$issue, &$galley))) {
				import('classes.file.IssueFileManager');
				$issueFileManager = new IssueFileManager($issue->getId());
				return $issueFileManager->downloadFile($galley->getFileId(), $request->getUserVar('inline')?true:false);
			}
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
	 * Given an issue, set up the template with all the required variables for
	 * frontend/objects/issue_toc.tpl to function properly (i.e. current issue
	 * and view issue).
	 * @param $issue object The issue to display
	 * @param $showToc boolean iff false and a custom cover page exists,
	 * 	the cover page will be displayed. Otherwise table of contents
	 * 	will be displayed.
	 */
	function _setupIssueTemplate($request, $issue, $showToc = false) {
		$journal = $request->getJournal();
		$templateMgr = TemplateManager::getManager($request);

		// Determine pre-publication access
		// FIXME: Do that. (Bug #8278)

		if (!$issue) {
			$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		}

		$templateMgr->assign(array(
			'issueIdentification' => $issue->getIssueIdentification(),
			'issueTitle' => $issue->getLocalizedTitle(),
			'issueSeries' => $issue->getIssueIdentification(array('showTitle' => false)),
		));

		$locale = AppLocale::getLocale();

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		$templateMgr->assign(array(
			'coverImagePath' => $request->getBaseUrl() . '/' . $publicFileManager->getJournalFilesPath($journal->getId()) . '/',
			'locale' => $locale,
		));

		$issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');

		$templateMgr->assign(array(
			'issue' => $issue,
			'issueGalleys' => $issueGalleyDao->getByIssueId($issue->getId()),
			'publishedArticles' => $publishedArticleDao->getPublishedArticlesInSections($issue->getId(), true),
		));

		// Subscription Access
		import('classes.issue.IssueAction');
		$issueAction = new IssueAction();
		$subscriptionRequired = $issueAction->subscriptionRequired($issue);
		$subscribedUser = $issueAction->subscribedUser($journal);
		$subscribedDomain = $issueAction->subscribedDomain($journal);

		if ($subscriptionRequired && !$subscribedUser && !$subscribedDomain) {
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

		$templateMgr->assign(array(
			'hasAccess' => !$subscriptionRequired || $issue->getAccessStatus() == ISSUE_ACCESS_OPEN || $subscribedUser || $subscribedDomain
		));

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		if ( $paymentManager->onlyPdfEnabled() ) {
			$templateMgr->assign('restrictOnlyPdf', true);
		}
		if ( $paymentManager->purchaseArticleEnabled() ) {
			$templateMgr->assign('purchaseArticleEnabled', true);
		}
	}
}

?>
