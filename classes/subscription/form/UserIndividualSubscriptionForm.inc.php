<?php

/**
 * @file classes/subscription/form/UserIndividualSubscriptionForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserIndividualSubscriptionForm
 * @ingroup subscription
 *
 * @brief Form class for user purchase of individual subscription.
 */

import('lib.pkp.classes.form.Form');

class UserIndividualSubscriptionForm extends Form {
	/** @var PKPRequest */
	var $request;

	/** @var userId int the user associated with the subscription */
	var $userId;

	/** @var subscription the subscription being purchased */
	var $subscription;

	/** @var subscriptionTypes Array subscription types */
	var $subscriptionTypes;

	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $userId int
	 * @param $subscriptionId int
	 */
	function __construct($request, $userId = null, $subscriptionId = null) {
		parent::__construct('frontend/pages/purchaseIndividualSubscription.tpl');

		$this->userId = isset($userId) ? (int) $userId : null;
		$this->subscription = null;
		$this->request = $request;

		$subscriptionId = isset($subscriptionId) ? (int) $subscriptionId : null;

		if (isset($subscriptionId)) {
			$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
			if ($subscriptionDao->subscriptionExists($subscriptionId)) {
				$this->subscription = $subscriptionDao->getById($subscriptionId);
			}
		}

		$journal = $this->request->getJournal();
		$journalId = $journal->getId();

		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionTypes = $subscriptionTypeDao->getByInstitutional($journalId, false, false);
		$this->subscriptionTypes = $subscriptionTypes->toAssociativeArray();

		// Ensure subscription type is valid
		$this->addCheck(new FormValidatorCustom($this, 'typeId', 'required', 'user.subscriptions.form.typeIdValid', function($typeId) use ($journalId) {
			$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
			return ($subscriptionTypeDao->subscriptionTypeExistsByTypeId($typeId, $journalId) && $subscriptionTypeDao->getSubscriptionTypeInstitutional($typeId) == 0) && $subscriptionTypeDao->getSubscriptionTypeDisablePublicDisplay($typeId) == 0;
		}));

		// Ensure that user does not already have a subscription for this journal
		if (!isset($subscriptionId)) {
			$this->addCheck(new FormValidatorCustom($this, 'userId', 'required', 'user.subscriptions.form.subscriptionExists', array(DAORegistry::getDAO('IndividualSubscriptionDAO'), 'subscriptionExistsByUserForJournal'), array($journalId), true));
		} else {
			$this->addCheck(new FormValidatorCustom($this, 'userId', 'required', 'user.subscriptions.form.subscriptionExists', function($userId) use ($journalId, $subscriptionId) {
				$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
				$checkId = $subscriptionDao->getByUserIdForJournal($userId, $journalId);
				return ($checkId == 0 || $checkId == $subscriptionId) ? true : false;
			}));
		}

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Initialize form data from current subscription.
	 */
	function initData() {
		if (isset($this->subscription)) {
			$subscription = $this->subscription;

			$this->_data = array(
				'typeId' => $subscription->getTypeId(),
				'membership' => $subscription->getMembership()
			);
		}
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = TemplateManager::getManager($this->request);
		$templateMgr->assign(array(
			'subscriptionId' => $this->subscription?$this->subscription->getId():null,
			'subscriptionTypes' => array_map(
				function($subscriptionType) {return $subscriptionType->getLocalizedName() . ' (' . $subscriptionType->getCost() . ' ' . $subscriptionType->getCurrencyCodeAlpha() . ')';},
				$this->subscriptionTypes
			),
		));
		parent::display($this->request);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('typeId', 'membership'));

		// If subscription type requires it, membership is provided
		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$needMembership = $subscriptionTypeDao->getSubscriptionTypeMembership($this->getData('typeId'));

		if ($needMembership) {
			$this->addCheck(new FormValidator($this, 'membership', 'required', 'user.subscriptions.form.membershipRequired'));
		}
	}

	/**
	 * Create/update individual subscription.
	 */
	function execute() {
		$journal = $this->request->getJournal();
		$journalId = $journal->getId();
		$typeId = $this->getData('typeId');
		$individualSubscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionType = $subscriptionTypeDao->getById($typeId, $journalId);
		$nonExpiring = $subscriptionType->getNonExpiring();
		$today = date('Y-m-d');
		$insert = false;

		if (!isset($this->subscription)) {
			$subscription = $individualSubscriptionDao->newDataObject();
			$subscription->setJournalId($journalId);
			$subscription->setUserId($this->userId);
			$subscription->setReferenceNumber(null);
			$subscription->setNotes(null);

			$insert = true;
		} else {
			$subscription = $this->subscription;
		}

		$paymentManager = Application::getPaymentManager($journal);
		$paymentPlugin = $paymentManager->getPaymentPlugin();

		if ($paymentPlugin->getName() == 'ManualPayment') {
			$subscription->setStatus(SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT);
		} else {
			$subscription->setStatus(SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT);
		}

		$subscription->setTypeId($typeId);
		$subscription->setMembership($this->getData('membership') ? $this->getData('membership') : null);
		$subscription->setDateStart($nonExpiring ? null : $today);
		$subscription->setDateEnd($nonExpiring ? null : $today);

		if ($subscription->getId()) {
			$individualSubscriptionDao->updateObject($subscription);
		} else {
			$individualSubscriptionDao->insertObject($subscription);
		}

		$queuedPayment = $paymentManager->createQueuedPayment($this->request, PAYMENT_TYPE_PURCHASE_SUBSCRIPTION, $this->userId, $subscription->getId(), $subscriptionType->getCost(), $subscriptionType->getCurrencyCodeAlpha());
		$paymentManager->queuePayment($queuedPayment);

		$paymentForm = $paymentManager->getPaymentForm($queuedPayment);
		$paymentForm->display($this->request);
	}
}
