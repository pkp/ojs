<?php

/**
 * @file pages/user/UserHandler.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
		$user = $request->getUser();
		$templateMgr = TemplateManager::getManager($request);
		if (!$journal || !$user || $journal->getData('publishingMode') !=  PUBLISHING_MODE_SUBSCRIPTION) {
			$request->redirect(null, 'index');
		}

		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /* @var $subscriptionTypeDao SubscriptionTypeDAO */
		$individualSubscriptionTypesExist = $subscriptionTypeDao->subscriptionTypesExistByInstitutional($journal->getId(), false);
		$institutionalSubscriptionTypesExist = $subscriptionTypeDao->subscriptionTypesExistByInstitutional($journal->getId(), true);
		if (!$individualSubscriptionTypesExist && !$institutionalSubscriptionTypesExist) $request->redirect(null, 'index');

		// Subscriptions contact and additional information
		// Get subscriptions and options for current journal
		if ($individualSubscriptionTypesExist) {
			$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /* @var $subscriptionDao IndividualSubscriptionDAO */
			$userIndividualSubscription = $subscriptionDao->getByUserIdForJournal($user->getId(), $journal->getId());
			$templateMgr->assign('userIndividualSubscription', $userIndividualSubscription);
		}

		if ($institutionalSubscriptionTypesExist) {
			$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /* @var $subscriptionDao InstitutionalSubscriptionDAO */
			$userInstitutionalSubscriptions = $subscriptionDao->getByUserIdForJournal($user->getId(), $journal->getId());
			$templateMgr->assign('userInstitutionalSubscriptions', $userInstitutionalSubscriptions);
		}

		$paymentManager = Application::getPaymentManager($journal);

		$this->setupTemplate($request);

		$templateMgr->assign(array(
			'subscriptionName' => $journal->getData('subscriptionName'),
			'subscriptionEmail' => $journal->getData('subscriptionEmail'),
			'subscriptionPhone' => $journal->getData('subscriptionPhone'),
			'subscriptionMailingAddress' => $journal->getData('subscriptionMailingAddress'),
			'subscriptionAdditionalInformation' => $journal->getLocalizedData('subscriptionAdditionalInformation'),
			'journalTitle' => $journal->getLocalizedName(),
			'journalPath' => $journal->getPath(),
			'individualSubscriptionTypesExist' => $individualSubscriptionTypesExist,
			'institutionalSubscriptionTypesExist' => $institutionalSubscriptionTypesExist,
			'paymentsEnabled' => $paymentManager->isConfigured(),
		));
		$templateMgr->display('frontend/pages/userSubscriptions.tpl');

	}

	/**
	 * Determine if the journal's setup has been sufficiently completed.
	 * @param $journal Object
	 * @return boolean True iff setup is incomplete
	 */
	function _checkIncompleteSetup($journal) {
		if($journal->getLocalizedAcronym() == '' || $journal->getData('contactEmail') == '' ||
		   $journal->getData('contactName') == '' || $journal->getLocalizedData('abbreviation') == '') {
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
		$journal = $request->getJournal();
		if (empty($args) || !$journal || $journal->getData('publishingMode') != PUBLISHING_MODE_SUBSCRIPTION) {
			$request->redirect(null, 'index');
		}

		$paymentManager = Application::getPaymentManager($journal);
		$acceptSubscriptionPayments = $paymentManager->isConfigured();
		if (!$acceptSubscriptionPayments) $request->redirect(null, 'index');

		$this->setupTemplate($request);
		$user = $request->getUser();

		$institutional = array_shift($args);
		if (!empty($args)) {
			$subscriptionId = (int) array_shift($args);
		}

		if ($institutional == 'institutional') {
			$institutional = true;
			import('classes.subscription.form.UserInstitutionalSubscriptionForm');
			$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /* @var $subscriptionDao InstitutionalSubscriptionDAO */
		} else {
			$institutional = false;
			import('classes.subscription.form.UserIndividualSubscriptionForm');
			$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /* @var $subscriptionDao IndividualSubscriptionDAO */
		}

		if (isset($subscriptionId)) {
			// Ensure subscription to be updated is for this user
			if (!$subscriptionDao->subscriptionExistsByUser($subscriptionId, $user->getId())) {
				$request->redirect(null, 'index');
			}

			// Ensure subscription can be updated
			$subscription = $subscriptionDao->getById($subscriptionId);
			$subscriptionStatus = $subscription->getStatus();
			import('classes.subscription.Subscription');
			$validStatus = array(
				SUBSCRIPTION_STATUS_ACTIVE,
				SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT,
				SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT
			);

			if (!in_array($subscriptionStatus, $validStatus)) $request->redirect(null, 'index');

			if ($institutional) {
				$subscriptionForm = new UserInstitutionalSubscriptionForm($request, $user->getId(), $subscriptionId);
			} else {
				$subscriptionForm = new UserIndividualSubscriptionForm($request, $user->getId(), $subscriptionId);
			}

		} else {
			if ($institutional) {
				$subscriptionForm = new UserInstitutionalSubscriptionForm($request, $user->getId());
			} else {
				// Ensure user does not already have an individual subscription
				if ($subscriptionDao->subscriptionExistsByUserForJournal($user->getId(), $journal->getId())) {
					$request->redirect(null, 'index');
				}
				$subscriptionForm = new UserIndividualSubscriptionForm($request, $user->getId());
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

		if (empty($args)) $request->redirect(null, 'index');

		$journal = $request->getJournal();
		if (!$journal) $request->redirect(null, 'index');
		if ($journal->getData('publishingMode') != PUBLISHING_MODE_SUBSCRIPTION) $request->redirect(null, 'index');

		$paymentManager = Application::getPaymentManager($journal);
		$acceptSubscriptionPayments = $paymentManager->isConfigured();
		if (!$acceptSubscriptionPayments) $request->redirect(null, 'index');

		$this->setupTemplate($request);
		$user = $request->getUser();

		$institutional = array_shift($args);
		if (!empty($args)) {
			$subscriptionId = (int) array_shift($args);
		}

		if ($institutional == 'institutional') {
			$institutional = true;
			import('classes.subscription.form.UserInstitutionalSubscriptionForm');
			$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /* @var $subscriptionDao InstitutionalSubscriptionDAO */
		} else {
			$institutional = false;
			import('classes.subscription.form.UserIndividualSubscriptionForm');
			$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /* @var $subscriptionDao IndividualSubscriptionDAO */
		}

		if (isset($subscriptionId)) {
			// Ensure subscription to be updated is for this user
			if (!$subscriptionDao->subscriptionExistsByUser($subscriptionId, $user->getId())) {
				$request->redirect(null, 'index');
			}

			// Ensure subscription can be updated
			$subscription = $subscriptionDao->getById($subscriptionId);
			$subscriptionStatus = $subscription->getStatus();
			import('classes.subscription.Subscription');
			$validStatus = array(
				SUBSCRIPTION_STATUS_ACTIVE,
				SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT,
				SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT
			);

			if (!in_array($subscriptionStatus, $validStatus)) $request->redirect(null, 'index');

			if ($institutional) {
				$subscriptionForm = new UserInstitutionalSubscriptionForm($request, $user->getId(), $subscriptionId);
			} else {
				$subscriptionForm = new UserIndividualSubscriptionForm($request, $user->getId(), $subscriptionId);
			}

		} else {
			if ($institutional) {
				$subscriptionForm = new UserInstitutionalSubscriptionForm($request, $user->getId());
			} else {
				// Ensure user does not already have an individual subscription
				if ($subscriptionDao->subscriptionExistsByUserForJournal($user->getId(), $journal->getId())) {
					$request->redirect(null, 'index');
				}
				$subscriptionForm = new UserIndividualSubscriptionForm($request, $user->getId());
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
		$journal = $request->getJournal();
		if (!$journal || count($args) != 2 || $journal->getData('publishingMode') != PUBLISHING_MODE_SUBSCRIPTION) {
			$request->redirect(null, 'index');
		}

		$paymentManager = Application::getPaymentManager($journal);
		$acceptSubscriptionPayments = $paymentManager->isConfigured();
		if (!$acceptSubscriptionPayments) $request->redirect(null, 'index');

		$this->setupTemplate($request);
		$user = $request->getUser();
		$institutional = array_shift($args);
		$subscriptionId = (int) array_shift($args);

		if ($institutional == 'institutional') {
			$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /* @var $subscriptionDao InstitutionalSubscriptionDAO */
		} else {
			$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /* @var $subscriptionDao IndividualSubscriptionDAO */
		}

		if (!$subscriptionDao->subscriptionExistsByUser($subscriptionId, $user->getId())) $request->redirect(null, 'index');

		$subscription = $subscriptionDao->getById($subscriptionId);
		$subscriptionStatus = $subscription->getStatus();
		import('classes.subscription.Subscription');
		$validStatus = array(SUBSCRIPTION_STATUS_ACTIVE, SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT);

		if (!in_array($subscriptionStatus, $validStatus)) $request->redirect(null, 'index');

		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /* @var $subscriptionTypeDao SubscriptionTypeDAO */
		$subscriptionType = $subscriptionTypeDao->getById($subscription->getTypeId());

		$queuedPayment = $paymentManager->createQueuedPayment($request, PAYMENT_TYPE_PURCHASE_SUBSCRIPTION, $user->getId(), $subscriptionId, $subscriptionType->getCost(), $subscriptionType->getCurrencyCodeAlpha());
		$paymentManager->queuePayment($queuedPayment);

		$paymentForm = $paymentManager->getPaymentForm($queuedPayment);
		$paymentForm->display($request);
	}

	/**
	 * Pay the "renew subscription" fee.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function payRenewSubscription($args, $request) {
		$this->validate(null, $request);
		$journal = $request->getJournal();
		if (count($args) != 2 || !$journal || $journal->getData('publishingMode') != PUBLISHING_MODE_SUBSCRIPTION) {
			$request->redirect(null, 'index');
		}

		$paymentManager = Application::getPaymentManager($journal);
		$acceptSubscriptionPayments = $paymentManager->isConfigured();
		if (!$acceptSubscriptionPayments) $request->redirect(null, 'index');

		$this->setupTemplate($request);
		$user = $request->getUser();
		$institutional = array_shift($args);
		$subscriptionId = (int) array_shift($args);

		if ($institutional == 'institutional') {
			$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /* @var $subscriptionDao InstitutionalSubscriptionDAO */
		} else {
			$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /* @var $subscriptionDao IndividualSubscriptionDAO */
		}

		if (!$subscriptionDao->subscriptionExistsByUser($subscriptionId, $user->getId())) $request->redirect(null, 'index');

		$subscription = $subscriptionDao->getById($subscriptionId);

		if ($subscription->isNonExpiring()) $request->redirect(null, 'index');

		import('classes.subscription.Subscription');
		$subscriptionStatus = $subscription->getStatus();
		$validStatus = array(
			SUBSCRIPTION_STATUS_ACTIVE,
			SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT,
			SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT
		);

		if (!in_array($subscriptionStatus, $validStatus)) $request->redirect(null, 'index');

		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /* @var $subscriptionTypeDao SubscriptionTypeDAO */
		$subscriptionType = $subscriptionTypeDao->getById($subscription->getTypeId());

		$queuedPayment = $paymentManager->createQueuedPayment($request, PAYMENT_TYPE_RENEW_SUBSCRIPTION, $user->getId(), $subscriptionId, $subscriptionType->getCost(), $subscriptionType->getCurrencyCodeAlpha());
		$paymentManager->queuePayment($queuedPayment);

		$paymentForm = $paymentManager->getPaymentForm($queuedPayment);
		$paymentForm->display($request);
	}

	/**
	 * Pay for a membership.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function payMembership($args, $request) {
		$this->validate(null, $request);
		$this->setupTemplate($request);
		$journal = $request->getJournal();
		$user = $request->getUser();

		$paymentManager = Application::getPaymentManager($journal);

		$queuedPayment = $paymentManager->createQueuedPayment($request, PAYMENT_TYPE_MEMBERSHIP, $user->getId(), null,  $journal->getData('membershipFee'));
		$paymentManager->queuePayment($queuedPayment);

		$paymentForm = $paymentManager->getPaymentForm($queuedPayment);
		$paymentForm->display($request);
	}
}


