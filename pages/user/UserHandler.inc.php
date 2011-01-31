<?php

/**
 * @file pages/user/UserHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserHandler
 * @ingroup pages_user
 *
 * @brief Handle requests for user functions.
 */

// $Id$


import('classes.handler.Handler');

class UserHandler extends Handler {
	/**
	 * Constructor
	 **/
	function UserHandler() {
		parent::Handler();
	}

	/**
	 * Display user index page.
	 */
	function index() {
		$this->validate();

		$sessionManager =& SessionManager::getManager();
		$session =& $sessionManager->getUserSession();

		$roleDao =& DAORegistry::getDAO('RoleDAO');

		$this->setupTemplate();
		$templateMgr =& TemplateManager::getManager();

		$journal =& Request::getJournal();
		$templateMgr->assign('helpTopicId', 'user.userHome');
		
		$user =& Request::getUser();
		$userId = $user->getId();
		
		$setupIncomplete = array();
		$submissionsCount = array();
		$isValid = array();

		if ($journal == null) { // Curently at site level
			unset($journal);

			// Show roles for all journals
			$journalDao =& DAORegistry::getDAO('JournalDAO');
			$journals =& $journalDao->getJournals();

			// Fetch the user's roles for each journal
			while ($journal =& $journals->next()) {
				$journalId = $journal->getId();
				
				// Determine if journal setup is incomplete, to provide a message for JM
				$setupIncomplete[$journalId] = $this->checkIncompleteSetup($journal);
							
				$roles =& $roleDao->getRolesByUserId($userId, $journalId);
				if (!empty($roles)) {
					$userJournals[] =& $journal;
					$this->getRoleDataForJournal($userId, $journalId, $submissionsCount, $isValid);
				}

				unset($journal);
			}

			$templateMgr->assign_by_ref('userJournals', $userJournals);
			$templateMgr->assign('showAllJournals', 1);

		} else { // Currently within a journal's context.
			$journalId = $journal->getId();
			
			// Determine if journal setup is incomplete, to provide a message for JM
			$setupIncomplete[$journalId] = $this->checkIncompleteSetup($journal);
			
			$userJournals = array($journal);
			
			$this->getRoleDataForJournal($userId, $journalId, $submissionsCount, $isValid);
			
			$subscriptionTypeDAO =& DAORegistry::getDAO('SubscriptionTypeDAO');
			$subscriptionsEnabled = $journal->getSetting('publishingMode') ==  PUBLISHING_MODE_SUBSCRIPTION
				&& ($subscriptionTypeDAO->subscriptionTypesExistByInstitutional($journalId, false)
					|| $subscriptionTypeDAO->subscriptionTypesExistByInstitutional($journalId, true)) ? true : false;
			$templateMgr->assign('subscriptionsEnabled', $subscriptionsEnabled);

			import('classes.payment.ojs.OJSPaymentManager');
			$paymentManager =& OJSPaymentManager::getManager();
			$membershipEnabled = $paymentManager->membershipEnabled();
			$templateMgr->assign('membershipEnabled', $membershipEnabled);

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
	 * Display subscriptions page 
	 **/
	function subscriptions() {
		$this->validate();

		$journal =& Request::getJournal();
		if (!$journal) Request::redirect(null, 'user');
		if ($journal->getSetting('publishingMode') !=  PUBLISHING_MODE_SUBSCRIPTION)
			Request::redirect(null, 'user');
		
		$journalId = $journal->getId();
		$subscriptionTypeDAO =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$individualSubscriptionTypesExist = $subscriptionTypeDAO->subscriptionTypesExistByInstitutional($journalId, false);
		$institutionalSubscriptionTypesExist = $subscriptionTypeDAO->subscriptionTypesExistByInstitutional($journalId, true);
		if (!$individualSubscriptionTypesExist && !$institutionalSubscriptionTypesExist) Request::redirect(null, 'user');

		$user =& Request::getUser();
		$userId = $user->getId();

		// Subscriptions contact and additional information
		$subscriptionName = $journal->getSetting('subscriptionName');
		$subscriptionEmail = $journal->getSetting('subscriptionEmail');
		$subscriptionPhone = $journal->getSetting('subscriptionPhone');
		$subscriptionFax = $journal->getSetting('subscriptionFax');
		$subscriptionMailingAddress = $journal->getSetting('subscriptionMailingAddress');
		$subscriptionAdditionalInformation = $journal->getLocalizedSetting('subscriptionAdditionalInformation');
		// Get subscriptions and options for current journal
		if ($individualSubscriptionTypesExist) {
			$subscriptionDAO =& DAORegistry::getDAO('IndividualSubscriptionDAO');
			$userIndividualSubscription =& $subscriptionDAO->getSubscriptionByUserForJournal($userId, $journalId);
		}

		if ($institutionalSubscriptionTypesExist) {
			$subscriptionDAO =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
			$userInstitutionalSubscriptions =& $subscriptionDAO->getSubscriptionsByUserForJournal($userId, $journalId);
		}

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager =& OJSPaymentManager::getManager();
		$acceptSubscriptionPayments = $paymentManager->acceptSubscriptionPayments();

		$this->setupTemplate(true);
		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('subscriptionName', $subscriptionName);
		$templateMgr->assign('subscriptionEmail', $subscriptionEmail);
		$templateMgr->assign('subscriptionPhone', $subscriptionPhone);
		$templateMgr->assign('subscriptionFax', $subscriptionFax);
		$templateMgr->assign('subscriptionMailingAddress', $subscriptionMailingAddress);
		$templateMgr->assign('subscriptionAdditionalInformation', $subscriptionAdditionalInformation);
		$templateMgr->assign('journalTitle', $journal->getLocalizedTitle());
		$templateMgr->assign('journalPath', $journal->getPath());
		$templateMgr->assign('acceptSubscriptionPayments', $acceptSubscriptionPayments);
		$templateMgr->assign('individualSubscriptionTypesExist', $individualSubscriptionTypesExist);
		$templateMgr->assign('institutionalSubscriptionTypesExist', $institutionalSubscriptionTypesExist);
		$templateMgr->assign_by_ref('userIndividualSubscription', $userIndividualSubscription);
		$templateMgr->assign_by_ref('userInstitutionalSubscriptions', $userInstitutionalSubscriptions);
		$templateMgr->display('user/subscriptions.tpl');

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
			$journalDao =& DAORegistry::getDAO('JournalDAO');
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
	function checkIncompleteSetup($journal) {
		if($journal->getLocalizedInitials() == "" || $journal->getSetting('contactEmail') == "" || 
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

		$site =& Request::getSite();
		$journal =& Request::getJournal();
		if ($journal != null) {
			$journalSupportedLocales = $journal->getSetting('supportedLocales');
			if (!is_array($journalSupportedLocales)) {
				$journalSupportedLocales = array();
			}
		}

		if (Locale::isLocaleValid($setLocale) && (!isset($journalSupportedLocales) || in_array($setLocale, $journalSupportedLocales)) && in_array($setLocale, $site->getSupportedLocales())) {
			$session =& Request::getSession();
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
		parent::validate(true);

		$journal =& Request::getJournal();
		$user =& Request::getUser();

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
			$role->setJournalId($journal->getId());
			$role->setRoleId($roleId);
			$role->setUserId($user->getId());

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
	 * Display an authorization denied message.
	 * @param $args array
	 * @param $request Request
	 */
	function authorizationDenied($args, &$request) {
		$this->validate(true);
		$authorizationMessage = htmlentities($request->getUserVar('message'));
		$this->setupTemplate(true);
		Locale::requireComponents(array(LOCALE_COMPONENT_PKP_USER));
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('message', $authorizationMessage);
		return $templateMgr->display('common/message.tpl');
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
		$templateMgr =& TemplateManager::getManager();
		if ($subclass) {
			$templateMgr->assign('pageHierarchy', array(array(Request::url(null, 'user'), 'navigation.user')));
		}
	}

	//
	// Captcha
	//

	function viewCaptcha($args) {
		$captchaId = (int) array_shift($args);
		import('lib.pkp.classes.captcha.CaptchaManager');
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
	function purchaseSubscription($args) {
		$this->validate();

		if (empty($args)) Request::redirect(null, 'user'); 

		$journal =& Request::getJournal();
		if (!$journal) Request::redirect(null, 'user');
		if ($journal->getSetting('publishingMode') != PUBLISHING_MODE_SUBSCRIPTION)
			Request::redirect(null, 'user');

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager =& OJSPaymentManager::getManager();
		$acceptSubscriptionPayments = $paymentManager->acceptSubscriptionPayments();
		if (!$acceptSubscriptionPayments) Request::redirect(null, 'user');

		$this->setupTemplate(true);
		$user =& Request::getUser();
		$userId = $user->getId();
		$journalId = $journal->getId();

		$institutional = array_shift($args);
		if (!empty($args)) {
			$subscriptionId = (int) array_shift($args);
		}

		if ($institutional == 'institutional') {
			$institutional = true;
			import('classes.subscription.form.UserInstitutionalSubscriptionForm');
			$subscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		} else {
			$institutional = false;
			import('classes.subscription.form.UserIndividualSubscriptionForm');
			$subscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		}

		if (isset($subscriptionId)) {
			// Ensure subscription to be updated is for this user
			if (!$subscriptionDao->subscriptionExistsByUser($subscriptionId, $userId)) {
				Request::redirect(null, 'user');
			}	

			// Ensure subscription can be updated
			$subscription =& $subscriptionDao->getSubscription($subscriptionId);
			$subscriptionStatus = $subscription->getStatus();
			import('classes.subscription.Subscription');
			$validStatus = array(
				SUBSCRIPTION_STATUS_ACTIVE,
				SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT,
				SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT
			);

			if (!in_array($subscriptionStatus, $validStatus)) Request::redirect(null, 'user'); 

			if ($institutional) {
				$subscriptionForm = new UserInstitutionalSubscriptionForm($userId, $subscriptionId);
			} else {
				$subscriptionForm = new UserIndividualSubscriptionForm($userId, $subscriptionId);
			}

		} else {
			if ($institutional) {
				$subscriptionForm = new UserInstitutionalSubscriptionForm($userId);
			} else {
				// Ensure user does not already have an individual subscription
				if ($subscriptionDao->subscriptionExistsByUserForJournal($userId, $journalId)) {
					Request::redirect(null, 'user');
				}	
				$subscriptionForm = new UserIndividualSubscriptionForm($userId);
			}
		}

		$subscriptionForm->initData();
		$subscriptionForm->display();
	}

	function payPurchaseSubscription($args) {
		$this->validate();

		if (empty($args)) Request::redirect(null, 'user'); 

		$journal =& Request::getJournal();
		if (!$journal) Request::redirect(null, 'user');
		if ($journal->getSetting('publishingMode') != PUBLISHING_MODE_SUBSCRIPTION)
			Request::redirect(null, 'user');

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager =& OJSPaymentManager::getManager();
		$acceptSubscriptionPayments = $paymentManager->acceptSubscriptionPayments();
		if (!$acceptSubscriptionPayments) Request::redirect(null, 'user');

		$this->setupTemplate(true);
		$user =& Request::getUser();
		$userId = $user->getId();
		$journalId = $journal->getId();

		$institutional = array_shift($args);
		if (!empty($args)) {
			$subscriptionId = (int) array_shift($args);
		}

		if ($institutional == 'institutional') {
			$institutional = true;
			import('classes.subscription.form.UserInstitutionalSubscriptionForm');
			$subscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		} else {
			$institutional = false;
			import('classes.subscription.form.UserIndividualSubscriptionForm');
			$subscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		}

		if (isset($subscriptionId)) {
			// Ensure subscription to be updated is for this user
			if (!$subscriptionDao->subscriptionExistsByUser($subscriptionId, $userId)) {
				Request::redirect(null, 'user');
			}	

			// Ensure subscription can be updated
			$subscription =& $subscriptionDao->getSubscription($subscriptionId);
			$subscriptionStatus = $subscription->getStatus();
			import('classes.subscription.Subscription');
			$validStatus = array(
				SUBSCRIPTION_STATUS_ACTIVE,
				SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT,
				SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT
			);

			if (!in_array($subscriptionStatus, $validStatus)) Request::redirect(null, 'user'); 

			if ($institutional) {
				$subscriptionForm = new UserInstitutionalSubscriptionForm($userId, $subscriptionId);
			} else {
				$subscriptionForm = new UserIndividualSubscriptionForm($userId, $subscriptionId);
			}

		} else {
			if ($institutional) {
				$subscriptionForm = new UserInstitutionalSubscriptionForm($userId);
			} else {
				// Ensure user does not already have an individual subscription
				if ($subscriptionDao->subscriptionExistsByUserForJournal($userId, $journalId)) {
					Request::redirect(null, 'user');
				}	
				$subscriptionForm = new UserIndividualSubscriptionForm($userId);
			}
		}

		$subscriptionForm->readInputData();

		// Check for any special cases before trying to save
		if (Request::getUserVar('addIpRange')) {
			$editData = true;
			$ipRanges = $subscriptionForm->getData('ipRanges');
			$ipRanges[] = '';
			$subscriptionForm->setData('ipRanges', $ipRanges);

		} else if (($delIpRange = Request::getUserVar('delIpRange')) && count($delIpRange) == 1) {
			$editData = true;
			list($delIpRange) = array_keys($delIpRange);
			$delIpRange = (int) $delIpRange;
			$ipRanges = $subscriptionForm->getData('ipRanges');
			array_splice($ipRanges, $delIpRange, 1);
			$subscriptionForm->setData('ipRanges', $ipRanges);
		}

		if (isset($editData)) {
			$subscriptionForm->display();
		} else {
			if ($subscriptionForm->validate()) {
				$subscriptionForm->execute();
			} else {
				$subscriptionForm->display();
			}
		}
	}

	function completePurchaseSubscription($args) {
		$this->validate();

		if (count($args) != 2) Request::redirect(null, 'user'); 

		$journal =& Request::getJournal();
		if (!$journal) Request::redirect(null, 'user');
		if ($journal->getSetting('publishingMode') != PUBLISHING_MODE_SUBSCRIPTION)
			Request::redirect(null, 'user');

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager =& OJSPaymentManager::getManager();
		$acceptSubscriptionPayments = $paymentManager->acceptSubscriptionPayments();
		if (!$acceptSubscriptionPayments) Request::redirect(null, 'user');

		$this->setupTemplate(true);
		$user =& Request::getUser();
		$userId = $user->getId();
		$journalId = $journal->getId();

		$institutional = array_shift($args);
		$subscriptionId = (int) array_shift($args);

		if ($institutional == 'institutional') {
			$subscriptionDAO =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		} else {
			$subscriptionDAO =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		}

		if (!$subscriptionDAO->subscriptionExistsByUser($subscriptionId, $userId)) Request::redirect(null, 'user');

		$subscription =& $subscriptionDAO->getSubscription($subscriptionId);
		$subscriptionStatus = $subscription->getStatus();
		import('classes.subscription.Subscription');
		$validStatus = array(SUBSCRIPTION_STATUS_ACTIVE, SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT);

		if (!in_array($subscriptionStatus, $validStatus)) Request::redirect(null, 'user'); 

		$subscriptionTypeDAO =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionType =& $subscriptionTypeDAO->getSubscriptionType($subscription->getTypeId());

		$queuedPayment =& $paymentManager->createQueuedPayment($journal->getId(), PAYMENT_TYPE_PURCHASE_SUBSCRIPTION, $user->getId(), $subscriptionId, $subscriptionType->getCost(), $subscriptionType->getCurrencyCodeAlpha());
		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

		$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
	}

	function payRenewSubscription($args) {
		$this->validate();

		if (count($args) != 2) Request::redirect(null, 'user'); 

		$journal =& Request::getJournal();
		if (!$journal) Request::redirect(null, 'user');
		if ($journal->getSetting('publishingMode') != PUBLISHING_MODE_SUBSCRIPTION)
			Request::redirect(null, 'user');

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager =& OJSPaymentManager::getManager();
		$acceptSubscriptionPayments = $paymentManager->acceptSubscriptionPayments();
		if (!$acceptSubscriptionPayments) Request::redirect(null, 'user');

		$this->setupTemplate(true);
		$user =& Request::getUser();
		$userId = $user->getId();
		$journalId = $journal->getId();

		$institutional = array_shift($args);
		$subscriptionId = (int) array_shift($args);

		if ($institutional == 'institutional') {
			$subscriptionDAO =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		} else {
			$subscriptionDAO =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		}

		if (!$subscriptionDAO->subscriptionExistsByUser($subscriptionId, $userId)) Request::redirect(null, 'user');

		$subscription =& $subscriptionDAO->getSubscription($subscriptionId);

		if ($subscription->isNonExpiring()) Request::redirect(null, 'user'); 

		import('classes.subscription.Subscription');
		$subscriptionStatus = $subscription->getStatus();
		$validStatus = array(
			SUBSCRIPTION_STATUS_ACTIVE,
			SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT,
			SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT
		);

		if (!in_array($subscriptionStatus, $validStatus)) Request::redirect(null, 'user'); 

		$subscriptionTypeDAO =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionType =& $subscriptionTypeDAO->getSubscriptionType($subscription->getTypeId());

		$queuedPayment =& $paymentManager->createQueuedPayment($journal->getId(), PAYMENT_TYPE_RENEW_SUBSCRIPTION, $user->getId(), $subscriptionId, $subscriptionType->getCost(), $subscriptionType->getCurrencyCodeAlpha());
		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

		$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
	}

	function payMembership($args) {
		$this->validate();
		$this->setupTemplate();

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager =& OJSPaymentManager::getManager();

		$journal =& Request::getJournal();
		$user =& Request::getUser();

		$queuedPayment =& $paymentManager->createQueuedPayment($journal->getId(), PAYMENT_TYPE_MEMBERSHIP, $user->getId(), null,  $journal->getSetting('membershipFee'));
		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

		$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
	}
}

?>
