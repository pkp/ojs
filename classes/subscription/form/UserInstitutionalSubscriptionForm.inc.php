<?php

/**
 * @defgroup subscription_form
 */
 
/**
 * @file classes/subscription/form/UserInstitutionalSubscriptionForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserInstitutionalSubscriptionForm
 * @ingroup subscription_form
 *
 * @brief Form class for user purchase of institutional subscription.
 */

import('lib.pkp.classes.form.Form');

class UserInstitutionalSubscriptionForm extends Form {
	/** @var $request PKPRequest */
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
	function UserInstitutionalSubscriptionForm($request, $userId = null, $subscriptionId = null) {
		parent::Form('subscription/userInstitutionalSubscriptionForm.tpl');

		$this->userId = isset($userId) ? (int) $userId : null;
		$this->subscription = null;
		$this->request =& $request;

		$subscriptionId = isset($subscriptionId) ? (int) $subscriptionId : null;

		if (isset($subscriptionId)) {
			$subscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO'); 
			if ($subscriptionDao->subscriptionExists($subscriptionId)) {
				$this->subscription =& $subscriptionDao->getSubscription($subscriptionId);
			}
		}

		$journal =& $this->request->getJournal();
		$journalId = $journal->getId();

		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionTypes =& $subscriptionTypeDao->getSubscriptionTypesByInstitutional($journalId, true, false);
		$this->subscriptionTypes =& $subscriptionTypes->toArray();

		// Ensure subscription type is valid
		$this->addCheck(new FormValidatorCustom($this, 'typeId', 'required', 'user.subscriptions.form.typeIdValid', create_function('$typeId, $journalId', '$subscriptionTypeDao =& DAORegistry::getDAO(\'SubscriptionTypeDAO\'); return ($subscriptionTypeDao->subscriptionTypeExistsByTypeId($typeId, $journalId) && $subscriptionTypeDao->getSubscriptionTypeInstitutional($typeId) == 1) && $subscriptionTypeDao->getSubscriptionTypeDisablePublicDisplay($typeId) == 0;'), array($journal->getId())));


		// Ensure institution name is provided
		$this->addCheck(new FormValidator($this, 'institutionName', 'required', 'user.subscriptions.form.institutionNameRequired'));

		// If provided, domain is valid
		$this->addCheck(new FormValidatorRegExp($this, 'domain', 'optional', 'user.subscriptions.form.domainValid', '/^' .
				'[A-Z0-9]+([\-_\.][A-Z0-9]+)*' .
				'\.' .
				'[A-Z]{2,4}' .
			'$/i'));
		// Form was POSTed
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data from current subscription.
	 */
	function initData() {
		if (isset($this->subscription)) {
			$subscription =& $this->subscription;
			$this->_data = array(
				'institutionName' => $subscription->getInstitutionName(),
				'institutionMailingAddress' => $subscription->getInstitutionMailingAddress(),
				'domain' => $subscription->getDomain(),
				'ipRanges' => $subscription->getIPRanges()
			);
		}
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		if (isset($this->subscription)) {
			$subscriptionId = $this->subscription->getId();
		} else {
			$subscriptionId = null;
		}

		$templateMgr->assign('subscriptionId', $subscriptionId);
		$templateMgr->assign_by_ref('subscriptionTypes', $this->subscriptionTypes);
		parent::display();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('typeId', 'membership', 'institutionName', 'institutionMailingAddress', 'domain', 'ipRanges')); 

		// If subscription type requires it, membership is provided
		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
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
		$this->addCheck(new FormValidatorCustom($this, 'domain', 'required', 'user.subscriptions.form.domainIPRangeRequired', create_function('$domain, $ipRangeProvided', 'return ($domain != \'\' || $ipRangeProvided) ? true : false;'), array($ipRangeProvided)));

		// If provided ensure IP ranges have IP address format; IP addresses may contain wildcards
		if ($ipRangeProvided) {	
			import('classes.subscription.InstitutionalSubscription');
			$this->addCheck(new FormValidatorArrayCustom($this, 'ipRanges', 'required', 'user.subscriptions.form.ipRangeValid', create_function('$ipRange, $regExp', 'return String::regexp_match($regExp, $ipRange);'),
				array(
					'/^' .
					// IP4 address (with or w/o wildcards) or IP4 address range (with or w/o wildcards) or CIDR IP4 address
					'((([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5]|[' . SUBSCRIPTION_IP_RANGE_WILDCARD . '])([.]([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5]|[' . SUBSCRIPTION_IP_RANGE_WILDCARD . '])){3}((\s)*[' . SUBSCRIPTION_IP_RANGE_RANGE . '](\s)*([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5]|[' . SUBSCRIPTION_IP_RANGE_WILDCARD . '])([.]([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5]|[' . SUBSCRIPTION_IP_RANGE_WILDCARD . '])){3}){0,1})|(([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5])([.]([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5])){3}([\/](([3][0-2]{0,1})|([1-2]{0,1}[0-9])))))' .
					'$/i'
				),
				false,
				array(),
				false		
			));
		}
	}

	/**
	 * Create institutional subscription. 
	 */
	function execute() {
		$journal =& $this->request->getJournal();
		$journalId = $journal->getId();
		$typeId = $this->getData('typeId');
		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$nonExpiring = $subscriptionTypeDao->getSubscriptionTypeNonExpiring($typeId);
		$today = date('Y-m-d');
		$insert = false;

		if (!isset($this->subscription)) {
			import('classes.subscription.InstitutionalSubscription');
			$subscription = new InstitutionalSubscription();
			$subscription->setJournalId($journalId);
			$subscription->setUserId($this->userId);
			$subscription->setReferenceNumber(null);
			$subscription->setNotes(null);

			$insert = true;
		} else {
			$subscription =& $this->subscription;
		}

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($this->request);
		$paymentPlugin =& $paymentManager->getPaymentPlugin();
		
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

		$institutionalSubscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		if ($insert) {
			$institutionalSubscriptionDao->insertSubscription($subscription);
		} else {
			$institutionalSubscriptionDao->updateSubscription($subscription);
		}

		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionType =& $subscriptionTypeDao->getSubscriptionType($this->getData('typeId'));

		$queuedPayment =& $paymentManager->createQueuedPayment($journalId, PAYMENT_TYPE_PURCHASE_SUBSCRIPTION, $this->userId, $subscription->getId(), $subscriptionType->getCost(), $subscriptionType->getCurrencyCodeAlpha());
		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

		$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
	}
}

?>
