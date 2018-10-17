<?php

/**
 * @file classes/subscription/form/UserInstitutionalSubscriptionForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserInstitutionalSubscriptionForm
 * @ingroup subscription
 *
 * @brief Form class for user purchase of institutional subscription.
 */

import('lib.pkp.classes.form.Form');

class UserInstitutionalSubscriptionForm extends Form {
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
		parent::__construct('frontend/pages/purchaseInstitutionalSubscription.tpl');

		$this->userId = isset($userId) ? (int) $userId : null;
		$this->subscription = null;
		$this->request = $request;

		$subscriptionId = isset($subscriptionId) ? (int) $subscriptionId : null;

		if (isset($subscriptionId)) {
			$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
			if ($subscriptionDao->subscriptionExists($subscriptionId)) {
				$this->subscription = $subscriptionDao->getById($subscriptionId);
			}
		}

		$journal = $this->request->getJournal();
		$journalId = $journal->getId();

		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionTypes = $subscriptionTypeDao->getByInstitutional($journalId, true, false);
		$this->subscriptionTypes = $subscriptionTypes->toArray();

		// Ensure subscription type is valid
		$this->addCheck(new FormValidatorCustom($this, 'typeId', 'required', 'user.subscriptions.form.typeIdValid', function($typeId) use ($journalId) {
			$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
			return ($subscriptionTypeDao->subscriptionTypeExistsByTypeId($typeId, $journalId) && $subscriptionTypeDao->getSubscriptionTypeInstitutional($typeId) == 1) && $subscriptionTypeDao->getSubscriptionTypeDisablePublicDisplay($typeId) == 0;
		}));

		// Ensure institution name is provided
		$this->addCheck(new FormValidator($this, 'institutionName', 'required', 'user.subscriptions.form.institutionNameRequired'));

		// If provided, domain is valid
		$this->addCheck(new FormValidatorRegExp($this, 'domain', 'optional', 'user.subscriptions.form.domainValid', '/^' .
				'[A-Z0-9]+([\-_\.][A-Z0-9]+)*' .
				'\.' .
				'[A-Z]{2,4}' .
			'$/i'));

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
				'institutionName' => $subscription->getInstitutionName(),
				'institutionMailingAddress' => $subscription->getInstitutionMailingAddress(),
				'domain' => $subscription->getDomain(),
				'ipRanges' => $subscription->getIPRanges()
			);
		}
	}

	/**
	 * @copydoc Form::display
	 */
	function display($request = null, $template = null) {
		if (is_null($request)) {
			$request = $this->request;
		}
		$templateMgr = TemplateManager::getManager($this->request);
		$templateMgr->assign(array(
			'subscriptionId' => $this->subscription?$this->subscription->getId():null,
			'subscriptionTypes' => $this->subscriptionTypes,
		));
		parent::display($request, $template);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('typeId', 'membership', 'institutionName', 'institutionMailingAddress', 'domain', 'ipRanges'));

		// If subscription type requires it, membership is provided
		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$needMembership = $subscriptionTypeDao->getSubscriptionTypeMembership($this->getData('typeId'));

		if ($needMembership) {
			$this->addCheck(new FormValidator($this, 'membership', 'required', 'user.subscriptions.form.membershipRequired'));
		}

		// Check if IP range has been provided
		$ipRanges = $this->getData('ipRanges');
		$ipRangeProvided = false;
		if (is_array($ipRanges)) {
			foreach ($ipRanges as $ipRange) {
				if ($ipRange != '') {
					$ipRangeProvided = true;
					break;
				}
			}
		}

		// Domain or at least one IP range has been provided
		$this->addCheck(new FormValidatorCustom($this, 'domain', 'required', 'user.subscriptions.form.domainIPRangeRequired', function($domain) use ($ipRangeProvided) {
			return ($domain != '' || $ipRangeProvided) ? true : false;
		}));

		// If provided ensure IP ranges have IP address format; IP addresses may contain wildcards
		if ($ipRangeProvided) {
			import('classes.subscription.InstitutionalSubscription');
			$this->addCheck(new FormValidatorArrayCustom($this, 'ipRanges', 'required', 'user.subscriptions.form.ipRangeValid', function($ipRange) {
				return PKPString::regexp_match(
					'/^' .
					// IP4 address (with or w/o wildcards) or IP4 address range (with or w/o wildcards) or CIDR IP4 address
					'((([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5]|[' . SUBSCRIPTION_IP_RANGE_WILDCARD . '])([.]([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5]|[' . SUBSCRIPTION_IP_RANGE_WILDCARD . '])){3}((\s)*[' . SUBSCRIPTION_IP_RANGE_RANGE . '](\s)*([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5]|[' . SUBSCRIPTION_IP_RANGE_WILDCARD . '])([.]([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5]|[' . SUBSCRIPTION_IP_RANGE_WILDCARD . '])){3}){0,1})|(([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5])([.]([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5])){3}([\/](([3][0-2]{0,1})|([1-2]{0,1}[0-9])))))' .
					'$/i',
					$ipRange
				);
			}));
		}
	}

	/**
	 * Create institutional subscription.
	 */
	function execute() {
		$journal = $this->request->getJournal();
		$journalId = $journal->getId();
		$typeId = $this->getData('typeId');
		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$institutionalSubscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		$subscriptionType = $subscriptionTypeDao->getById($typeId);
		$nonExpiring = $subscriptionType->getNonExpiring();
		$today = date('Y-m-d');

		if (!isset($this->subscription)) {
			$subscription = $institutionalSubscriptionDao->newDataObject();
			$subscription->setJournalId($journalId);
			$subscription->setUserId($this->userId);
			$subscription->setReferenceNumber(null);
			$subscription->setNotes(null);
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
		$subscription->setInstitutionName($this->getData('institutionName'));
		$subscription->setInstitutionMailingAddress($this->getData('institutionMailingAddress'));
		$subscription->setDomain($this->getData('domain'));
		$subscription->setIPRanges($this->getData('ipRanges'));

		if ($subscription->getId()) {
			$institutionalSubscriptionDao->updateObject($subscription);
		} else {
			$institutionalSubscriptionDao->insertObject($subscription);
		}

		$queuedPayment = $paymentManager->createQueuedPayment($this->request, PAYMENT_TYPE_PURCHASE_SUBSCRIPTION, $this->userId, $subscription->getId(), $subscriptionType->getCost(), $subscriptionType->getCurrencyCodeAlpha());
		$paymentManager->queuePayment($queuedPayment);

		$paymentForm = $paymentManager->getPaymentForm($queuedPayment);
		$paymentForm->display($this->request);
	}
}
