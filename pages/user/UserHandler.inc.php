<?php

/**
 * @file pages/user/UserHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserHandler
 * @ingroup pages_user
 *
 * @brief Handle requests for user functions.
 */

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
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, &$request) {
		$this->validate();

		$sessionManager =& SessionManager::getManager();
		$session =& $sessionManager->getUserSession();

		$roleDao =& DAORegistry::getDAO('RoleDAO');

		$this->setupTemplate($request);
		$templateMgr =& TemplateManager::getManager();

		$journal =& $request->getJournal();
		$templateMgr->assign('helpTopicId', 'user.userHome');

		$user =& $request->getUser();
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
				$setupIncomplete[$journalId] = $this->_checkIncompleteSetup($journal);

				$roles =& $roleDao->getRolesByUserId($userId, $journalId);
				if (!empty($roles)) {
					$userJournals[] =& $journal;
					$this->_getRoleDataForJournal($userId, $journalId, $submissionsCount, $isValid);
				}

				unset($journal);
			}

			$templateMgr->assign_by_ref('userJournals', $userJournals);
			$templateMgr->assign('showAllJournals', 1);

		} else { // Currently within a journal's context.
			$journalId = $journal->getId();

			// Determine if journal setup is incomplete, to provide a message for JM
			$setupIncomplete[$journalId] = $this->_checkIncompleteSetup($journal);

			$userJournals = array($journal);

			$this->_getRoleDataForJournal($userId, $journalId, $submissionsCount, $isValid);

			$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
			$subscriptionsEnabled = $journal->getSetting('publishingMode') ==  PUBLISHING_MODE_SUBSCRIPTION
				&& ($subscriptionTypeDao->subscriptionTypesExistByInstitutional($journalId, false)
					|| $subscriptionTypeDao->subscriptionTypesExistByInstitutional($journalId, true)) ? true : false;
			$templateMgr->assign('subscriptionsEnabled', $subscriptionsEnabled);

			import('classes.payment.ojs.OJSPaymentManager');
			$paymentManager = new OJSPaymentManager($request);
			$acceptGiftPayments = $paymentManager->acceptGiftPayments();
			$templateMgr->assign('acceptGiftPayments', $acceptGiftPayments);
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
	 * Display user gifts page
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function gifts($args, $request) {
		$this->validate();

		$journal =& $request->getJournal();
		if (!$journal) $request->redirect(null, 'user');

		// Ensure gift payments are enabled
		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$acceptGiftPayments = $paymentManager->acceptGiftPayments();
		if (!$acceptGiftPayments) $request->redirect(null, 'user');

		$acceptGiftSubscriptionPayments = $paymentManager->acceptGiftSubscriptionPayments();
		$journalId = $journal->getId();
		$user =& $request->getUser();
		$userId = $user->getId();

		// Get user's redeemed and unreedemed gift subscriptions
		$giftDao =& DAORegistry::getDAO('GiftDAO');
		$giftSubscriptions =& $giftDao->getGiftsByTypeAndRecipient(
			ASSOC_TYPE_JOURNAL,
			$journalId,
			GIFT_TYPE_SUBSCRIPTION,
			$userId
		);

		$this->setupTemplate($request, true);
		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('journalTitle', $journal->getLocalizedTitle());
		$templateMgr->assign('journalPath', $journal->getPath());
		$templateMgr->assign('acceptGiftSubscriptionPayments', $acceptGiftSubscriptionPayments);
		$templateMgr->assign_by_ref('giftSubscriptions', $giftSubscriptions);
		$templateMgr->display('user/gifts.tpl');

	}

	/**
	 * User redeems a gift
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function redeemGift($args, $request) {
		$this->validate();

		if (empty($args)) $request->redirect(null, 'user');

		$journal =& $request->getJournal();
		if (!$journal) $request->redirect(null, 'user');

		// Ensure gift payments are enabled
		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$acceptGiftPayments = $paymentManager->acceptGiftPayments();
		if (!$acceptGiftPayments) $request->redirect(null, 'user');

		$journalId = $journal->getId();
		$user =& $request->getUser();
		$userId = $user->getId();
		$giftId = isset($args[0]) ? (int) $args[0] : 0;

		// Try to redeem the gift
		$giftDao =& DAORegistry::getDAO('GiftDAO');
		$status = $giftDao->redeemGift(
			ASSOC_TYPE_JOURNAL,
			$journalId,
			$userId,
			$giftId
		);

		// Report redeem status to user
		import('classes.notification.NotificationManager');
		$notificationManager = new NotificationManager();

		switch ($status) {
			case GIFT_REDEEM_STATUS_SUCCESS:
				$notificationType = NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_SUCCESS;
				break;
			case GIFT_REDEEM_STATUS_ERROR_NO_GIFT_TO_REDEEM:
				$notificationType = NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_NO_GIFT_TO_REDEEM;
				break;
			case GIFT_REDEEM_STATUS_ERROR_GIFT_ALREADY_REDEEMED:
				$notificationType = NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_GIFT_ALREADY_REDEEMED;
				break;
			case GIFT_REDEEM_STATUS_ERROR_GIFT_INVALID:
				$notificationType = NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_GIFT_INVALID;
				break;
			case GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_TYPE_INVALID:
				$notificationType = NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_TYPE_INVALID;
				break;
			case GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_NON_EXPIRING:
				$notificationType = NOTIFICATION_TYPE_GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_NON_EXPIRING;
				break;
			default:
				$notificationType = NOTIFICATION_TYPE_NO_GIFT_TO_REDEEM;
		}

		$user =& $request->getUser();

		$notificationManager->createTrivialNotification($user->getId(), $notificationType);
		$request->redirect(null, 'user', 'gifts');
	}

	/**
	 * Display subscriptions page
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function subscriptions($args, &$request) {
		$this->validate();

		$journal =& $request->getJournal();
		if (!$journal) $request->redirect(null, 'user');
		if ($journal->getSetting('publishingMode') !=  PUBLISHING_MODE_SUBSCRIPTION) $request->redirect(null, 'user');

		$journalId = $journal->getId();
		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$individualSubscriptionTypesExist = $subscriptionTypeDao->subscriptionTypesExistByInstitutional($journalId, false);
		$institutionalSubscriptionTypesExist = $subscriptionTypeDao->subscriptionTypesExistByInstitutional($journalId, true);
		if (!$individualSubscriptionTypesExist && !$institutionalSubscriptionTypesExist) $request->redirect(null, 'user');

		$user =& $request->getUser();
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
			$subscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
			$userIndividualSubscription =& $subscriptionDao->getSubscriptionByUserForJournal($userId, $journalId);
		}

		if ($institutionalSubscriptionTypesExist) {
			$subscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
			$userInstitutionalSubscriptions =& $subscriptionDao->getSubscriptionsByUserForJournal($userId, $journalId);
		}

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$acceptSubscriptionPayments = $paymentManager->acceptSubscriptionPayments();

		$this->setupTemplate($request, true);
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
	function _getRoleDataForJournal($userId, $journalId, &$submissionsCount, &$isValid) {
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
	function _checkIncompleteSetup($journal) {
		if($journal->getLocalizedInitials() == "" || $journal->getSetting('contactEmail') == "" ||
		   $journal->getSetting('contactName') == "" || $journal->getLocalizedSetting('abbreviation') == "") {
			return true;
		} else return false;
	}

	/**
	 * Change the locale for the current user.
	 * @param $args array first parameter is the new locale
	 */
	function setLocale($args, $request) {
		$setLocale = array_shift($args);

		$site =& $request->getSite();
		$journal =& $request->getJournal();
		if ($journal != null) {
			$journalSupportedLocales = $journal->getSetting('supportedLocales');
			if (!is_array($journalSupportedLocales)) {
				$journalSupportedLocales = array();
			}
		}

		if (AppLocale::isLocaleValid($setLocale) && (!isset($journalSupportedLocales) || in_array($setLocale, $journalSupportedLocales)) && in_array($setLocale, $site->getSupportedLocales())) {
			$session =& $request->getSession();
			$session->setSessionVar('currentLocale', $setLocale);
		}

		if(isset($_SERVER['HTTP_REFERER'])) {
			$request->redirectUrl($_SERVER['HTTP_REFERER']);
		}

		$source = $request->getUserVar('source');
		if (isset($source) && !empty($source)) {
			$request->redirectUrl($request->getProtocol() . '://' . $request->getServerHost() . $source, false);
		}

		$request->redirect(null, 'index');
	}

	/**
	 * Become a given role.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function become($args, &$request) {
		parent::validate(true);

		$journal =& $request->getJournal();
		$user =& $request->getUser();

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
				$request->redirect(null, null, 'index');
		}

		if ($journal->getSetting($setting)) {
			$role = new Role();
			$role->setJournalId($journal->getId());
			$role->setRoleId($roleId);
			$role->setUserId($user->getId());

			$roleDao =& DAORegistry::getDAO('RoleDAO');
			$roleDao->insertRole($role);
			$request->redirectUrl($request->getUserVar('source'));
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
		$this->setupTemplate($request, true);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_USER);
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
	 * @param $request PKPRequest
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate(&$request, $subclass = false) {
		parent::setupTemplate();
		AppLocale::requireComponents(LOCALE_COMPONENT_OJS_AUTHOR, LOCALE_COMPONENT_OJS_EDITOR, LOCALE_COMPONENT_OJS_MANAGER);
		$templateMgr =& TemplateManager::getManager();
		if ($subclass) {
			$templateMgr->assign('pageHierarchy', array(array($request->url(null, 'user'), 'navigation.user')));
		}
	}

	//
	// Captcha
	//

	function viewCaptcha($args, $request) {
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
		$request->redirect(null, 'user');
	}

	/**
	 * View the public user profile for a user, specified by user ID,
	 * if that user should be exposed for public view.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewPublicProfile($args, &$request) {
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

		if(!$accountIsVisible) $request->redirect(null, 'index');

		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& $userDao->getUser($userId);

		$this->setupTemplate($request, false);
		$templateMgr->assign_by_ref('user', $user);
		$templateMgr->display('user/publicProfile.tpl');
	}


	//
	// Payments
	//
	/**
	 * Purchase a subscription.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function purchaseSubscription($args, &$request) {
		$this->validate();

		if (empty($args)) $request->redirect(null, 'user');

		$journal =& $request->getJournal();
		if (!$journal) $request->redirect(null, 'user');
		if ($journal->getSetting('publishingMode') != PUBLISHING_MODE_SUBSCRIPTION) $request->redirect(null, 'user');

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$acceptSubscriptionPayments = $paymentManager->acceptSubscriptionPayments();
		if (!$acceptSubscriptionPayments) $request->redirect(null, 'user');

		$this->setupTemplate($request, true);
		$user =& $request->getUser();
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
				$request->redirect(null, 'user');
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

			if (!in_array($subscriptionStatus, $validStatus)) $request->redirect(null, 'user');

			if ($institutional) {
				$subscriptionForm = new UserInstitutionalSubscriptionForm($request, $userId, $subscriptionId);
			} else {
				$subscriptionForm = new UserIndividualSubscriptionForm($request, $userId, $subscriptionId);
			}

		} else {
			if ($institutional) {
				$subscriptionForm = new UserInstitutionalSubscriptionForm($request, $userId);
			} else {
				// Ensure user does not already have an individual subscription
				if ($subscriptionDao->subscriptionExistsByUserForJournal($userId, $journalId)) {
					$request->redirect(null, 'user');
				}
				$subscriptionForm = new UserIndividualSubscriptionForm($request, $userId);
			}
		}

		$subscriptionForm->initData();
		$subscriptionForm->display();
	}

	/**
	 * Pay for a subscription purchase.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function payPurchaseSubscription($args, &$request) {
		$this->validate();

		if (empty($args)) $request->redirect(null, 'user');

		$journal =& $request->getJournal();
		if (!$journal) $request->redirect(null, 'user');
		if ($journal->getSetting('publishingMode') != PUBLISHING_MODE_SUBSCRIPTION) $request->redirect(null, 'user');

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$acceptSubscriptionPayments = $paymentManager->acceptSubscriptionPayments();
		if (!$acceptSubscriptionPayments) $request->redirect(null, 'user');

		$this->setupTemplate($request, true);
		$user =& $request->getUser();
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
				$request->redirect(null, 'user');
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

			if (!in_array($subscriptionStatus, $validStatus)) $request->redirect(null, 'user');

			if ($institutional) {
				$subscriptionForm = new UserInstitutionalSubscriptionForm($request, $userId, $subscriptionId);
			} else {
				$subscriptionForm = new UserIndividualSubscriptionForm($request, $userId, $subscriptionId);
			}

		} else {
			if ($institutional) {
				$subscriptionForm = new UserInstitutionalSubscriptionForm($request, $userId);
			} else {
				// Ensure user does not already have an individual subscription
				if ($subscriptionDao->subscriptionExistsByUserForJournal($userId, $journalId)) {
					$request->redirect(null, 'user');
				}
				$subscriptionForm = new UserIndividualSubscriptionForm($request, $userId);
			}
		}

		$subscriptionForm->readInputData();

		// Check for any special cases before trying to save
		if ($request->getUserVar('addIpRange')) {
			$editData = true;
			$ipRanges = $subscriptionForm->getData('ipRanges');
			$ipRanges[] = '';
			$subscriptionForm->setData('ipRanges', $ipRanges);

		} else if (($delIpRange = $request->getUserVar('delIpRange')) && count($delIpRange) == 1) {
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

	/**
	 * Complete the purchase subscription process.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function completePurchaseSubscription($args, &$request) {
		$this->validate();

		if (count($args) != 2) $request->redirect(null, 'user');

		$journal =& $request->getJournal();
		if (!$journal) $request->redirect(null, 'user');
		if ($journal->getSetting('publishingMode') != PUBLISHING_MODE_SUBSCRIPTION) $request->redirect(null, 'user');

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$acceptSubscriptionPayments = $paymentManager->acceptSubscriptionPayments();
		if (!$acceptSubscriptionPayments) $request->redirect(null, 'user');

		$this->setupTemplate($request, true);
		$user =& $request->getUser();
		$userId = $user->getId();
		$journalId = $journal->getId();

		$institutional = array_shift($args);
		$subscriptionId = (int) array_shift($args);

		if ($institutional == 'institutional') {
			$subscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		} else {
			$subscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		}

		if (!$subscriptionDao->subscriptionExistsByUser($subscriptionId, $userId)) $request->redirect(null, 'user');

		$subscription =& $subscriptionDao->getSubscription($subscriptionId);
		$subscriptionStatus = $subscription->getStatus();
		import('classes.subscription.Subscription');
		$validStatus = array(SUBSCRIPTION_STATUS_ACTIVE, SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT);

		if (!in_array($subscriptionStatus, $validStatus)) $request->redirect(null, 'user');

		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionType =& $subscriptionTypeDao->getSubscriptionType($subscription->getTypeId());

		$queuedPayment =& $paymentManager->createQueuedPayment($journal->getId(), PAYMENT_TYPE_PURCHASE_SUBSCRIPTION, $user->getId(), $subscriptionId, $subscriptionType->getCost(), $subscriptionType->getCurrencyCodeAlpha());
		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

		$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
	}

	/**
	 * Pay the "renew subscription" fee.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function payRenewSubscription($args, &$request) {
		$this->validate();

		if (count($args) != 2) $request->redirect(null, 'user');

		$journal =& $request->getJournal();
		if (!$journal) $request->redirect(null, 'user');
		if ($journal->getSetting('publishingMode') != PUBLISHING_MODE_SUBSCRIPTION) $request->redirect(null, 'user');

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$acceptSubscriptionPayments = $paymentManager->acceptSubscriptionPayments();
		if (!$acceptSubscriptionPayments) $request->redirect(null, 'user');

		$this->setupTemplate($request, true);
		$user =& $request->getUser();
		$userId = $user->getId();
		$journalId = $journal->getId();

		$institutional = array_shift($args);
		$subscriptionId = (int) array_shift($args);

		if ($institutional == 'institutional') {
			$subscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		} else {
			$subscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		}

		if (!$subscriptionDao->subscriptionExistsByUser($subscriptionId, $userId)) $request->redirect(null, 'user');

		$subscription =& $subscriptionDao->getSubscription($subscriptionId);

		if ($subscription->isNonExpiring()) $request->redirect(null, 'user');

		import('classes.subscription.Subscription');
		$subscriptionStatus = $subscription->getStatus();
		$validStatus = array(
			SUBSCRIPTION_STATUS_ACTIVE,
			SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT,
			SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT
		);

		if (!in_array($subscriptionStatus, $validStatus)) $request->redirect(null, 'user');

		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionType =& $subscriptionTypeDao->getSubscriptionType($subscription->getTypeId());

		$queuedPayment =& $paymentManager->createQueuedPayment($journal->getId(), PAYMENT_TYPE_RENEW_SUBSCRIPTION, $user->getId(), $subscriptionId, $subscriptionType->getCost(), $subscriptionType->getCurrencyCodeAlpha());
		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

		$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
	}

	/**
	 * Pay for a membership.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function payMembership($args, &$request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);

		$journal =& $request->getJournal();
		$user =& $request->getUser();

		$queuedPayment =& $paymentManager->createQueuedPayment($journal->getId(), PAYMENT_TYPE_MEMBERSHIP, $user->getId(), null,  $journal->getSetting('membershipFee'));
		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

		$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
	}
}

?>
