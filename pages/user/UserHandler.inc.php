<?php

/**
 * @file pages/user/UserHandler.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserHandler
 * @ingroup pages_user
 *
 * @brief Handle requests for user functions.
 */

// $Id$


import('handler.Handler');

class UserHandler extends Handler{

	/**
	 * Display user index page.
	 */
	function index() {
		$this->validate();

		$sessionManager = &SessionManager::getManager();
		$session = &$sessionManager->getUserSession();

		$roleDao = &DAORegistry::getDAO('RoleDAO');

		$this->setupTemplate();
		$templateMgr = &TemplateManager::getManager();

		$journal = &Request::getJournal();
		$templateMgr->assign('helpTopicId', 'user.userHome');
		
		$user =& Request::getUser();
		$userId = $user->getUserId();
		
		$setupIncomplete = array();
		$submissionsCount = array();
		$isValid = array();

		if ($journal == null) { // Curently at site level
			unset($journal);

			// Show roles for all journals
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$journals = &$journalDao->getJournals();

			// Fetch the user's roles for each journal
			while ($journal =& $journals->next()) {
				$journalId = $journal->getJournalId();
				
				// Determine if journal setup is incomplete, to provide a message for JM
				$setupIncomplete[$journalId] = $this->checkCompleteSetup($journal);
							
				if ($journal->getEnabled()) {
					$roles =& $roleDao->getRolesByUserId($userId, $journalId);
					if (!empty($roles)) {
						$userJournals[] =& $journal;
						$this->getRoleDataForJournal($userId, $journalId, $submissionsCount, $isValid);
					}
				}

				unset($journal);
			}

			$templateMgr->assign_by_ref('userJournals', $userJournals);
			$templateMgr->assign('showAllJournals', 1);

		} else { // Currently within a journal's context.
			$journalId = $journal->getJournalId();
			
			// Determine if journal setup is incomplete, to provide a message for JM
			$setupIncomplete[$journalId] = $this->checkCompleteSetup($journal);
			
			$userJournals = array($journal);
			
			$this->getRoleDataForJournal($userId, $journalId, $submissionsCount, $isValid);
			
			import('payment.ojs.OJSPaymentManager');
			$paymentManager =& OJSPaymentManager::getManager();
			$membershipEnabled = $paymentManager->membershipEnabled();
			$templateMgr->assign('membershipEnabled', $membershipEnabled);
			$subscriptionEnabled = $paymentManager->acceptSubscriptionPayments();
			$templateMgr->assign('subscriptionEnabled', $subscriptionEnabled);

			if ( $subscriptionEnabled ) {
				import('subscription.SubscriptionDAO');
				$subscriptionDAO =& DAORegistry::getDAO('SubscriptionDAO');
				$subscriptionId = $subscriptionDAO->getSubscriptionIdByUser($user->getUserId(), $journal->getJournalId());
				$templateMgr->assign('userHasSubscription', $subscriptionId);
				if ( $subscriptionId !== false ) {
					$subscription =& $subscriptionDAO->getSubscription($subscriptionId);
					$templateMgr->assign('subscriptionEndDate', $subscription->getDateEnd());
				}
			}

			if ( $membershipEnabled ) {
				$templateMgr->assign('dateEndMembership', $user->getSetting('dateEndMembership', 0));
			}

			$templateMgr->assign('allowRegAuthor', $journal->getSetting('allowRegAuthor'));
			$templateMgr->assign('allowRegReviewer', $journal->getSetting('allowRegReviewer'));

			$templateMgr->assign_by_ref('userJournals', $userJournals);
		}

		$templateMgr->assign('isValid', $isValid);
		$templateMgr->assign('submissionsCount', $submissionsCount);
		$templateMgr->assign('setupIncomplete', $setupIncomplete); 
		$templateMgr->assign('isSiteAdmin', $roleDao->getRole(0, $userId, ROLE_ID_SITE_ADMIN));
		$templateMgr->display('user/index.tpl');
	}
	
	/**
	 * Gather information about a user's role within a journal.
	 * @param $userId int
	 * @param $journalId int 
	 * @param $submissionsCount array reference
	 * @param $isValid array reference
	
	 */
	function getRoleDataForJournal($userId, $journalId, &$submissionsCount, &$isValid) {
		if (Validation::isJournalManager($journalId)) {
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$isValid["JournalManager"][$journalId] = true;
		}
		if (Validation::isSubscriptionManager($journalId)) {
			$isValid["SubscriptionManager"][$journalId] = true;
		}
		if (Validation::isAuthor($journalId)) {
			$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
			$submissionsCount["Author"][$journalId] = $authorSubmissionDao->getSubmissionsCount($userId, $journalId);
			$isValid["Author"][$journalId] = true;
		}
		if (Validation::isCopyeditor($journalId)) {
			$copyeditorSubmissionDao =& DAORegistry::getDAO('CopyeditorSubmissionDAO');
			$submissionsCount["Copyeditor"][$journalId] = $copyeditorSubmissionDao->getSubmissionsCount($userId, $journalId);
			$isValid["Copyeditor"][$journalId] = true;
		}
		if (Validation::isLayoutEditor($journalId)) {
			$layoutEditorSubmissionDao =& DAORegistry::getDAO('LayoutEditorSubmissionDAO');
			$submissionsCount["LayoutEditor"][$journalId] = $layoutEditorSubmissionDao->getSubmissionsCount($userId, $journalId);
			$isValid["LayoutEditor"][$journalId] = true;
		}
		if (Validation::isEditor($journalId)) {
			$editorSubmissionDao =& DAORegistry::getDAO('EditorSubmissionDAO');
			$submissionsCount["Editor"][$journalId] = $editorSubmissionDao->getEditorSubmissionsCount($journalId);
			$isValid["Editor"][$journalId] = true;
		}
		if (Validation::isSectionEditor($journalId)) {
			$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
			$submissionsCount["SectionEditor"][$journalId] = $sectionEditorSubmissionDao->getSectionEditorSubmissionsCount($userId, $journalId);
			$isValid["SectionEditor"][$journalId] = true;
		}
		if (Validation::isProofreader($journalId)) {
			$proofreaderSubmissionDao =& DAORegistry::getDAO('ProofreaderSubmissionDAO');
			$submissionsCount["Proofreader"][$journalId] = $proofreaderSubmissionDao->getSubmissionsCount($userId, $journalId);
			$isValid["Proofreader"][$journalId] = true;
		}
		if (Validation::isReviewer($journalId)) {
			$reviewerSubmissionDao =& DAORegistry::getDAO('ReviewerSubmissionDAO');
			$submissionsCount["Reviewer"][$journalId] = $reviewerSubmissionDao->getSubmissionsCount($userId, $journalId);
			$isValid["Reviewer"][$journalId] = true;
		}
	}
	
	/**
	 * Determine if the journal's setup has been sufficiently completed.
	 * @param $journal Object 
	 * @return boolean True iff setup is incomplete
	 */
	function checkCompleteSetup($journal) {
		if($journal->getJournalInitials() == "" || $journal->getSetting('contactEmail') == "" || 
		   $journal->getSetting('contactName') == "" || $journal->getLocalizedSetting('abbreviation') == "") {
			return true;
		} else return false;
	}

	/**
	 * Change the locale for the current user.
	 * @param $args array first parameter is the new locale
	 */
	function setLocale($args) {
		$setLocale = isset($args[0]) ? $args[0] : null;

		$site = &Request::getSite();
		$journal = &Request::getJournal();
		if ($journal != null) {
			$journalSupportedLocales = $journal->getSetting('supportedLocales');
			if (!is_array($journalSupportedLocales)) {
				$journalSupportedLocales = array();
			}
		}

		if (Locale::isLocaleValid($setLocale) && (!isset($journalSupportedLocales) || in_array($setLocale, $journalSupportedLocales)) && in_array($setLocale, $site->getSupportedLocales())) {
			$session = &Request::getSession();
			$session->setSessionVar('currentLocale', $setLocale);
		}

		if(isset($_SERVER['HTTP_REFERER'])) {
			Request::redirectUrl($_SERVER['HTTP_REFERER']);
		}

		$source = Request::getUserVar('source');
		if (isset($source) && !empty($source)) {
			Request::redirectUrl(Request::getProtocol() . '://' . Request::getServerHost() . $source, false);
		}

		Request::redirect(null, 'index');
	}

	/**
	 * Become a given role.
	 */
	function become($args) {
		parent::validate(true, true);
		$journal =& Request::getJournal();
		$user =& Request::getUser();
		if (!$user) Request::redirect(null, null, 'index');

		switch (array_shift($args)) {
			case 'author':
				$roleId = ROLE_ID_AUTHOR;
				$setting = 'allowRegAuthor';
				$deniedKey = 'user.noRoles.submitArticleRegClosed';
				break;
			case 'reviewer':
				$roleId = ROLE_ID_REVIEWER;
				$setting = 'allowRegReviewer';
				$deniedKey = 'user.noRoles.regReviewerClosed';
				break;
			default:
				Request::redirect(null, null, 'index');
		}

		if ($journal->getSetting($setting)) {
			$role = new Role();
			$role->setJournalId($journal->getJournalId());
			$role->setRoleId($roleId);
			$role->setUserId($user->getUserId());

			$roleDao =& DAORegistry::getDAO('RoleDAO');
			$roleDao->insertRole($role);
			Request::redirectUrl(Request::getUserVar('source'));
		} else {
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('message', $deniedKey);
			return $templateMgr->display('common/message.tpl');
		}
	}

	/**
	 * Validate that user is logged in.
	 * Redirects to login form if not logged in.
	 * @param $loginCheck boolean check if user is logged in
	 */
	function validate($loginCheck = true) {
		parent::validate();
		if ($loginCheck && !Validation::isLoggedIn()) {
			Validation::redirectLogin();
		}
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		parent::setupTemplate();
		Locale::requireComponents(array(LOCALE_COMPONENT_OJS_AUTHOR, LOCALE_COMPONENT_OJS_EDITOR, LOCALE_COMPONENT_OJS_MANAGER));
		$templateMgr = &TemplateManager::getManager();
		if ($subclass) {
			$templateMgr->assign('pageHierarchy', array(array(Request::url(null, 'user'), 'navigation.user')));
		}
	}

	//
	// Captcha
	//

	function viewCaptcha($args) {
		$captchaId = (int) array_shift($args);
		import('captcha.CaptchaManager');
		$captchaManager = new CaptchaManager();
		if ($captchaManager->isEnabled()) {
			$captchaDao =& DAORegistry::getDAO('CaptchaDAO');
			$captcha =& $captchaDao->getCaptcha($captchaId);
			if ($captcha) {
				$captchaManager->generateImage($captcha);
				exit();
			}
		}
		Request::redirect(null, 'user');
	}

	/**
	 * View the public user profile for a user, specified by user ID,
	 * if that user should be exposed for public view.
	 */
	function viewPublicProfile($args) {
		$this->validate(false);
		$templateMgr =& TemplateManager::getManager();
		$userId = (int) array_shift($args);

		$accountIsVisible = false;

		// Ensure that the user's profile info should be exposed:

		$commentDao =& DAORegistry::getDAO('CommentDAO');
		if ($commentDao->attributedCommentsExistForUser($userId)) {
			// At least one comment is attributed to the user
			$accountIsVisible = true;
		}

		if(!$accountIsVisible) Request::redirect(null, 'index');

		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& $userDao->getUser($userId);

		$templateMgr->assign_by_ref('user', $user);
		$templateMgr->display('user/publicProfile.tpl');
	}


	//
	// Payments
	//

	function payRenewSubscription($args) {
		$this->validate();
		$this->setupTemplate(true);

		import('payment.ojs.OJSPaymentManager');
		$paymentManager =& OJSPaymentManager::getManager();

		import('subscription.SubscriptionDAO');
		$subscriptionDAO =& DAORegistry::getDAO('SubscriptionDAO');
		$subscriptionTypeDAO =& DAORegistry::getDAO('SubscriptionTypeDAO');

		$journal =& Request::getJournal();
		if ($journal) {
			$user =& Request::getUser();
			$subscriptionId = $subscriptionDAO->getSubscriptionIdByUser($user->getUserId(), $journal->getJournalId());

			$subscriptionDAO =& DAORegistry::getDAO('SubscriptionDAO');
			$subscription =& $subscriptionDAO->getSubscription($subscriptionId);
			$subscriptionType =& $subscriptionTypeDAO->getSubscriptionType($subscription->getTypeId());

			$queuedPayment =& $paymentManager->createQueuedPayment($journal->getJournalId(), PAYMENT_TYPE_SUBSCRIPTION, $user->getUserId(), $subscriptionId, $subscriptionType->getCost(), $subscriptionType->getCurrencyCodeAlpha());
			$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

			$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
		}

	}

	function payMembership($args) {
		$this->validate();
		$this->setupTemplate();

		import('payment.ojs.OJSPaymentManager');
		$paymentManager =& OJSPaymentManager::getManager();

		$journal =& Request::getJournal();
		$user =& Request::getUser();

		$queuedPayment =& $paymentManager->createQueuedPayment($journal->getJournalId(), PAYMENT_TYPE_MEMBERSHIP, $user->getUserId(), null,  $journal->getSetting('membershipFee'));
		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

		$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);

	}
}

?>
