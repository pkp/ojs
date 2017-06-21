<?php

/**
 * @file pages/user/UserHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserHandler
 * @ingroup pages_user
 *
 * @brief Handle requests for user functions.
 */

import('lib.pkp.pages.user.PKPUserHandler');

class UserHandler extends PKPUserHandler {

	/**
	 * Display subscriptions page
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function subscriptions($args, $request) {
		$this->validate(null, $request);

		$journal = $request->getJournal();
		if (!$journal) $request->redirect(null, 'dashboard');
		if ($journal->getSetting('publishingMode') !=  PUBLISHING_MODE_SUBSCRIPTION) $request->redirect(null, 'dashboard');

		$journalId = $journal->getId();
		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$individualSubscriptionTypesExist = $subscriptionTypeDao->subscriptionTypesExistByInstitutional($journalId, false);
		$institutionalSubscriptionTypesExist = $subscriptionTypeDao->subscriptionTypesExistByInstitutional($journalId, true);
		if (!$individualSubscriptionTypesExist && !$institutionalSubscriptionTypesExist) $request->redirect(null, 'dashboard');

		$user = $request->getUser();
		$userId = $user->getId();
		$templateMgr = TemplateManager::getManager($request);

		// Subscriptions contact and additional information
		$subscriptionName = $journal->getSetting('subscriptionName');
		$subscriptionEmail = $journal->getSetting('subscriptionEmail');
		$subscriptionPhone = $journal->getSetting('subscriptionPhone');
		$subscriptionMailingAddress = $journal->getSetting('subscriptionMailingAddress');
		$subscriptionAdditionalInformation = $journal->getLocalizedSetting('subscriptionAdditionalInformation');
		// Get subscriptions and options for current journal
		if ($individualSubscriptionTypesExist) {
			$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
			$userIndividualSubscription = $subscriptionDao->getSubscriptionByUserForJournal($userId, $journalId);
			$templateMgr->assign('userIndividualSubscription', $userIndividualSubscription);
		}

		if ($institutionalSubscriptionTypesExist) {
			$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
			$userInstitutionalSubscriptions = $subscriptionDao->getSubscriptionsByUserForJournal($userId, $journalId);
			$templateMgr->assign('userInstitutionalSubscriptions', $userInstitutionalSubscriptions);
		}

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$acceptSubscriptionPayments = $paymentManager->acceptSubscriptionPayments();

		$this->setupTemplate($request);

		$templateMgr->assign(array(
			'subscriptionName' => $subscriptionName,
			'subscriptionEmail' => $subscriptionEmail,
			'subscriptionPhone' => $subscriptionPhone,
			'subscriptionMailingAddress' => $subscriptionMailingAddress,
			'subscriptionAdditionalInformation' => $subscriptionAdditionalInformation,
			'journalTitle' => $journal->getLocalizedName(),
			'journalPath' => $journal->getPath(),
			'acceptSubscriptionPayments' => $acceptSubscriptionPayments,
			'individualSubscriptionTypesExist' => $individualSubscriptionTypesExist,
			'institutionalSubscriptionTypesExist' => $institutionalSubscriptionTypesExist,
			'journalPaymentsEnabled' => $paymentManager->isConfigured(),
		));
		$templateMgr->display('user/subscriptions.tpl');

	}

	/**
	 * Determine if the journal's setup has been sufficiently completed.
	 * @param $journal Object
	 * @return boolean True iff setup is incomplete
	 */
	function _checkIncompleteSetup($journal) {
		if($journal->getLocalizedAcronym() == '' || $journal->getSetting('contactEmail') == '' ||
		   $journal->getSetting('contactName') == '' || $journal->getLocalizedSetting('abbreviation') == '') {
			return true;
		} else return false;
	}

	/**
	 * Setup common template variables.
	 * @param $request PKPRequest
	 */
	function setupTemplate($request = null) {
		parent::setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_AUTHOR, LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_APP_MANAGER, LOCALE_COMPONENT_PKP_GRID);
	}


	//
	// Payments
	//
	/**
	 * Purchase a subscription.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function purchaseSubscription($args, $request) {
		$this->validate(null, $request);

		if (empty($args)) $request->redirect(null, 'dashboard');

		$journal = $request->getJournal();
		if (!$journal) $request->redirect(null, 'dashboard');
		if ($journal->getSetting('publishingMode') != PUBLISHING_MODE_SUBSCRIPTION) $request->redirect(null, 'dashboard');

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$acceptSubscriptionPayments = $paymentManager->acceptSubscriptionPayments();
		if (!$acceptSubscriptionPayments) $request->redirect(null, 'dashboard');

		$this->setupTemplate($request);
		$user = $request->getUser();
		$userId = $user->getId();
		$journalId = $journal->getId();

		$institutional = array_shift($args);
		if (!empty($args)) {
			$subscriptionId = (int) array_shift($args);
		}

		if ($institutional == 'institutional') {
			$institutional = true;
			import('classes.subscription.form.UserInstitutionalSubscriptionForm');
			$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		} else {
			$institutional = false;
			import('classes.subscription.form.UserIndividualSubscriptionForm');
			$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		}

		if (isset($subscriptionId)) {
			// Ensure subscription to be updated is for this user
			if (!$subscriptionDao->subscriptionExistsByUser($subscriptionId, $userId)) {
				$request->redirect(null, 'dashboard');
			}

			// Ensure subscription can be updated
			$subscription = $subscriptionDao->getSubscription($subscriptionId);
			$subscriptionStatus = $subscription->getStatus();
			import('classes.subscription.Subscription');
			$validStatus = array(
				SUBSCRIPTION_STATUS_ACTIVE,
				SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT,
				SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT
			);

			if (!in_array($subscriptionStatus, $validStatus)) $request->redirect(null, 'dashboard');

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
					$request->redirect(null, 'dashboard');
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
	function payPurchaseSubscription($args, $request) {
		$this->validate(null, $request);

		if (empty($args)) $request->redirect(null, 'dashboard');

		$journal = $request->getJournal();
		if (!$journal) $request->redirect(null, 'dashboard');
		if ($journal->getSetting('publishingMode') != PUBLISHING_MODE_SUBSCRIPTION) $request->redirect(null, 'dashboard');

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$acceptSubscriptionPayments = $paymentManager->acceptSubscriptionPayments();
		if (!$acceptSubscriptionPayments) $request->redirect(null, 'dashboard');

		$this->setupTemplate($request);
		$user = $request->getUser();
		$userId = $user->getId();
		$journalId = $journal->getId();

		$institutional = array_shift($args);
		if (!empty($args)) {
			$subscriptionId = (int) array_shift($args);
		}

		if ($institutional == 'institutional') {
			$institutional = true;
			import('classes.subscription.form.UserInstitutionalSubscriptionForm');
			$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		} else {
			$institutional = false;
			import('classes.subscription.form.UserIndividualSubscriptionForm');
			$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		}

		if (isset($subscriptionId)) {
			// Ensure subscription to be updated is for this user
			if (!$subscriptionDao->subscriptionExistsByUser($subscriptionId, $userId)) {
				$request->redirect(null, 'dashboard');
			}

			// Ensure subscription can be updated
			$subscription = $subscriptionDao->getSubscription($subscriptionId);
			$subscriptionStatus = $subscription->getStatus();
			import('classes.subscription.Subscription');
			$validStatus = array(
				SUBSCRIPTION_STATUS_ACTIVE,
				SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT,
				SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT
			);

			if (!in_array($subscriptionStatus, $validStatus)) $request->redirect(null, 'dashboard');

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
					$request->redirect(null, 'dashboard');
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
	function completePurchaseSubscription($args, $request) {
		$this->validate(null, $request);

		if (count($args) != 2) $request->redirect(null, 'dashboard');

		$journal = $request->getJournal();
		if (!$journal) $request->redirect(null, 'dashboard');
		if ($journal->getSetting('publishingMode') != PUBLISHING_MODE_SUBSCRIPTION) $request->redirect(null, 'dashboard');

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$acceptSubscriptionPayments = $paymentManager->acceptSubscriptionPayments();
		if (!$acceptSubscriptionPayments) $request->redirect(null, 'dashboard');

		$this->setupTemplate($request);
		$user = $request->getUser();
		$userId = $user->getId();
		$institutional = array_shift($args);
		$subscriptionId = (int) array_shift($args);

		if ($institutional == 'institutional') {
			$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		} else {
			$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		}

		if (!$subscriptionDao->subscriptionExistsByUser($subscriptionId, $userId)) $request->redirect(null, 'dashboard');

		$subscription = $subscriptionDao->getSubscription($subscriptionId);
		$subscriptionStatus = $subscription->getStatus();
		import('classes.subscription.Subscription');
		$validStatus = array(SUBSCRIPTION_STATUS_ACTIVE, SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT);

		if (!in_array($subscriptionStatus, $validStatus)) $request->redirect(null, 'dashboard');

		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionType = $subscriptionTypeDao->getSubscriptionType($subscription->getTypeId());

		$queuedPayment = $paymentManager->createQueuedPayment($journal->getId(), PAYMENT_TYPE_PURCHASE_SUBSCRIPTION, $user->getId(), $subscriptionId, $subscriptionType->getCost(), $subscriptionType->getCurrencyCodeAlpha());
		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

		$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
	}

	/**
	 * Pay the "renew subscription" fee.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function payRenewSubscription($args, $request) {
		$this->validate(null, $request);

		if (count($args) != 2) $request->redirect(null, 'dashboard');

		$journal = $request->getJournal();
		if (!$journal) $request->redirect(null, 'dashboard');
		if ($journal->getSetting('publishingMode') != PUBLISHING_MODE_SUBSCRIPTION) $request->redirect(null, 'dashboard');

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$acceptSubscriptionPayments = $paymentManager->acceptSubscriptionPayments();
		if (!$acceptSubscriptionPayments) $request->redirect(null, 'dashboard');

		$this->setupTemplate($request);
		$user = $request->getUser();
		$userId = $user->getId();
		$institutional = array_shift($args);
		$subscriptionId = (int) array_shift($args);

		if ($institutional == 'institutional') {
			$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		} else {
			$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		}

		if (!$subscriptionDao->subscriptionExistsByUser($subscriptionId, $userId)) $request->redirect(null, 'dashboard');

		$subscription = $subscriptionDao->getSubscription($subscriptionId);

		if ($subscription->isNonExpiring()) $request->redirect(null, 'dashboard');

		import('classes.subscription.Subscription');
		$subscriptionStatus = $subscription->getStatus();
		$validStatus = array(
			SUBSCRIPTION_STATUS_ACTIVE,
			SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT,
			SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT
		);

		if (!in_array($subscriptionStatus, $validStatus)) $request->redirect(null, 'dashboard');

		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionType = $subscriptionTypeDao->getSubscriptionType($subscription->getTypeId());

		$queuedPayment = $paymentManager->createQueuedPayment($journal->getId(), PAYMENT_TYPE_RENEW_SUBSCRIPTION, $user->getId(), $subscriptionId, $subscriptionType->getCost(), $subscriptionType->getCurrencyCodeAlpha());
		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

		$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
	}

	/**
	 * Pay for a membership.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function payMembership($args, $request) {
		$this->validate(null, $request);

		$this->setupTemplate($request);

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);

		$journal = $request->getJournal();
		$user = $request->getUser();

		$queuedPayment = $paymentManager->createQueuedPayment($journal->getId(), PAYMENT_TYPE_MEMBERSHIP, $user->getId(), null,  $journal->getSetting('membershipFee'));
		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

		$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
	}
}

?>
