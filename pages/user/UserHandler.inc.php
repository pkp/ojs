<?php

/**
 * @file UserHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserHandler
 * @ingroup pages_user
 *
 * @brief Handle requests for user functions.
 */

// $Id$


class UserHandler extends Handler {

	/**
	 * Display user index page.
	 */
	function index() {
		UserHandler::validate();

		$sessionManager = &SessionManager::getManager();
		$session = &$sessionManager->getUserSession();

		$roleDao = &DAORegistry::getDAO('RoleDAO');

		UserHandler::setupTemplate();
		$templateMgr = &TemplateManager::getManager();

		$journal = &Request::getJournal();
		$templateMgr->assign('helpTopicId', 'user.userHome');

		if ($journal == null) {
			// Prevent variable clobbering
			unset($journal);

			// Show roles for all journals
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$journals = &$journalDao->getJournals();

			$allJournals = array();
			$journalsToDisplay = array();
			$rolesToDisplay = array();

			// Fetch the user's roles for each journal
			while ($journal =& $journals->next()) {
				$roles =& $roleDao->getRolesByUserId($session->getUserId(), $journal->getJournalId());
				if (!empty($roles)) {
					$journalsToDisplay[] = $journal;
					$rolesToDisplay[$journal->getJournalId()] = &$roles;
				}
				if ($journal->getEnabled()) $allJournals[] =& $journal;
				unset($journal);
			}

			$templateMgr->assign_by_ref('allJournals', $allJournals);
			$templateMgr->assign('showAllJournals', 1);
			$templateMgr->assign_by_ref('userJournals', $journalsToDisplay);

		} else { // Currently within a journal's context.
			// Show roles for the currently selected journal
			$roles =& $roleDao->getRolesByUserId($session->getUserId(), $journal->getJournalId());

			$journal =& Request::getJournal();
			$user =& Request::getUser();

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
				$templateMgr->assign('dateEndMembership', $user->getDateEndMembership());
			}

			$templateMgr->assign('allowRegAuthor', $journal->getSetting('allowRegAuthor'));
			$templateMgr->assign('allowRegReviewer', $journal->getSetting('allowRegReviewer'));

			$rolesToDisplay[$journal->getJournalId()] = &$roles;
			$templateMgr->assign_by_ref('userJournal', $journal);
		}

		$templateMgr->assign('isSiteAdmin', $roleDao->getRole(0, $session->getUserId(), ROLE_ID_SITE_ADMIN));
		$templateMgr->assign('userRoles', $rolesToDisplay);
		$templateMgr->display('user/index.tpl');
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
			$role =& new Role();
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
		$templateMgr = &TemplateManager::getManager();
		if ($subclass) {
			$templateMgr->assign('pageHierarchy', array(array(Request::url(null, 'user'), 'navigation.user')));
		}
	}


	//
	// Profiles
	//

	function profile() {
		import('pages.user.ProfileHandler');
		ProfileHandler::profile();
	}

	function saveProfile() {
		import('pages.user.ProfileHandler');
		ProfileHandler::saveProfile();
	}

	function changePassword() {
		import('pages.user.ProfileHandler');
		ProfileHandler::changePassword();
	}

	function savePassword() {
		import('pages.user.ProfileHandler');
		ProfileHandler::savePassword();
	}


	//
	// Registration
	//

	function register() {
		import('pages.user.RegistrationHandler');
		RegistrationHandler::register();
	}

	function registerUser() {
		import('pages.user.RegistrationHandler');
		RegistrationHandler::registerUser();
	}

	function activateUser($args) {
		import('pages.user.RegistrationHandler');
		RegistrationHandler::activateUser($args);
	}

	//
	// Email
	//

	function email($args) {
		import('pages.user.EmailHandler');
		EmailHandler::email($args);
	}

	//
	// Captcha
	//

	function viewCaptcha($args) {
		$captchaId = (int) array_shift($args);
		import('captcha.CaptchaManager');
		$captchaManager =& new CaptchaManager();
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
		UserHandler::validate(false);
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
		UserHandler::validate();
		UserHandler::setupTemplate(true);

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
		UserHandler::validate();
		UserHandler::setupTemplate();

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
