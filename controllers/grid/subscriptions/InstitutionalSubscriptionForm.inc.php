<?php

/**
 * @file controllers/grid/subscriptions/InstitutionalSubscriptionForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class InstitutionalSubscriptionForm
 * @ingroup subscription
 *
 * @brief Form class for institutional subscription create/edits.
 */

import('classes.subscription.form.SubscriptionForm');

class InstitutionalSubscriptionForm extends SubscriptionForm {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $subscriptionId int leave as default for new subscription
	 */
	function __construct($request, $subscriptionId = null) {
		parent::__construct('payments/institutionalSubscriptionForm.tpl', $subscriptionId);

		$subscriptionId = isset($subscriptionId) ? (int) $subscriptionId : null;
		$userId = isset($userId) ? (int) $userId : null;

		$journal = $request->getJournal();
		$journalId = $journal->getId();

		if (isset($subscriptionId)) {
			$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); 
			if ($subscriptionDao->subscriptionExists($subscriptionId)) {
				$this->subscription = $subscriptionDao->getById($subscriptionId);
			}
		}

		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionTypeIterator = $subscriptionTypeDao->getByInstitutional($journalId, true);
		$this->subscriptionTypes = array();
		while ($subscriptionType = $subscriptionTypeIterator->next()) {
			$this->subscriptionTypes[$subscriptionType->getId()] = $subscriptionType->getSummaryString();
		}

		if (count($this->subscriptionTypes) == 0) {
			$this->addError('typeId', __('manager.subscriptions.form.typeRequired'));
			$this->addErrorField('typeId');
		}

		// Ensure subscription type is valid
		$this->addCheck(new FormValidatorCustom($this, 'typeId', 'required', 'manager.subscriptions.form.typeIdValid', function($typeId) use ($journalId) {
			$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
			return ($subscriptionTypeDao->subscriptionTypeExistsByTypeId($typeId, $journalId) && $subscriptionTypeDao->getSubscriptionTypeInstitutional($typeId) == 1);
		}));

		// Ensure institution name is provided
		$this->addCheck(new FormValidator($this, 'institutionName', 'required', 'manager.subscriptions.form.institutionNameRequired'));

		// If provided, domain is valid
		$this->addCheck(new FormValidatorRegExp($this, 'domain', 'optional', 'manager.subscriptions.form.domainValid', '/^' .
				'[A-Z0-9]+([\-_\.][A-Z0-9]+)*' .
				'\.' .
				'[A-Z]{2,4}' .
			'$/i'));
	}

	/**
	 * Initialize form data from current subscription.
	 */
	function initData() {
		parent::initData();

		if (isset($this->subscription)) {
			$this->_data = array_merge(
				$this->_data,
				array(
					'institutionName' => $this->subscription->getInstitutionName(),
					'institutionMailingAddress' => $this->subscription->getInstitutionMailingAddress(),
					'domain' => $this->subscription->getDomain(),
					'ipRanges' => join($this->subscription->getIPRanges(), "\r\n"),
				)
			);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		parent::readInputData();

		$this->readUserVars(array('institutionName', 'institutionMailingAddress', 'domain', 'ipRanges'));

		// Check if IP range has been provided
		$ipRanges = $this->getData('ipRanges');
		$ipRangeProvided = !empty(trim($ipRanges));

		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionType = $subscriptionTypeDao->getById($this->getData('typeId'));

		// If online or print + online, domain or at least one IP range has been provided
		if ($subscriptionType->getFormat() != SUBSCRIPTION_TYPE_FORMAT_PRINT) {
			$this->addCheck(new FormValidatorCustom($this, 'domain', 'required', 'manager.subscriptions.form.domainIPRangeRequired', function($domain) use ($ipRangeProvided) {
				return ($domain != '' || $ipRangeProvided) ? true : false;
			}));
		}

		// If provided ensure IP ranges have IP address format; IP addresses may contain wildcards
		if ($ipRangeProvided) {	
			$this->addCheck(new FormValidatorCustom($this, 'ipRanges', 'required', 'manager.subscriptions.form.ipRangeValid', function($ipRanges) {
				foreach (explode("\r\n", trim($ipRanges)) as $ipRange) if (!PKPString::regexp_match(
					'/^' .
					// IP4 address (with or w/o wildcards) or IP4 address range (with or w/o wildcards) or CIDR IP4 address
					'((([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5]|[' . SUBSCRIPTION_IP_RANGE_WILDCARD . '])([.]([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5]|[' . SUBSCRIPTION_IP_RANGE_WILDCARD . '])){3}((\s)*[' . SUBSCRIPTION_IP_RANGE_RANGE . '](\s)*([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5]|[' . SUBSCRIPTION_IP_RANGE_WILDCARD . '])([.]([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5]|[' . SUBSCRIPTION_IP_RANGE_WILDCARD . '])){3}){0,1})|(([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5])([.]([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5])){3}([\/](([3][0-2]{0,1})|([1-2]{0,1}[0-9])))))' .
					'$/i',
					trim($ipRange))
				) return false; return true;
			}));
		}
	}

	/**
	 * Save institutional subscription. 
	 */
	function execute() {
		$insert = false;
		if (!isset($this->subscription)) {
			import('classes.subscription.InstitutionalSubscription');
			$this->subscription = new InstitutionalSubscription();
			$insert = true;
		}

		parent::execute();

		$this->subscription->setInstitutionName($this->getData('institutionName'));
		$this->subscription->setInstitutionMailingAddress($this->getData('institutionMailingAddress'));
		$this->subscription->setDomain($this->getData('domain'));

		$ipRanges = $this->getData('ipRanges');
		$ipRanges = explode("\r\n", trim($ipRanges));
		$this->subscription->setIPRanges($ipRanges);

		$institutionalSubscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		if ($insert) {
			$institutionalSubscriptionDao->insertObject($this->subscription);
		} else {
			$institutionalSubscriptionDao->updateObject($this->subscription);
		} 

		// Send notification email
		if ($this->_data['notifyEmail'] == 1) {
			$mail = $this->_prepareNotificationEmail('SUBSCRIPTION_NOTIFY');
			if (!$mail->send()) {
				import('classes.notification.NotificationManager');
				$notificationMgr = new NotificationManager();
				$request = Application::get()->getRequest();
				$notificationMgr->createTrivialNotification($request->getUser()->getId(), NOTIFICATION_TYPE_ERROR, array('contents' => __('email.compose.error')));
			}
		} 
	}
}


