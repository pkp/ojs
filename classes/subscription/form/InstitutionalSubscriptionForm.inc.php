<?php

/**
 * @defgroup subscription_form
 */
 
/**
 * @file classes/subscription/form/InstitutionalSubscriptionForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class InstitutionalSubscriptionForm
 * @ingroup subscription_form
 *
 * @brief Form class for institutional subscription create/edits.
 */

import('classes.subscription.form.SubscriptionForm');

class InstitutionalSubscriptionForm extends SubscriptionForm {
	/**
	 * Constructor
	 * @param subscriptionId int leave as default for new subscription
	 */
	function InstitutionalSubscriptionForm($subscriptionId = null, $userId = null) {
		parent::Form('subscription/institutionalSubscriptionForm.tpl');
		parent::SubscriptionForm($subscriptionId, $userId);

		$subscriptionId = isset($subscriptionId) ? (int) $subscriptionId : null;
		$userId = isset($userId) ? (int) $userId : null;

		$journal =& Request::getJournal();
		$journalId = $journal->getId();

		if (isset($subscriptionId)) {
			$subscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO'); 
			if ($subscriptionDao->subscriptionExists($subscriptionId)) {
				$this->subscription =& $subscriptionDao->getSubscription($subscriptionId);
			}
		}

		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionTypes =& $subscriptionTypeDao->getSubscriptionTypesByInstitutional($journalId, true);
		$this->subscriptionTypes =& $subscriptionTypes->toArray();

		$subscriptionTypeCount = count($this->subscriptionTypes);
		if ($subscriptionTypeCount == 0) {
			$this->addError('typeId', __('manager.subscriptions.form.typeRequired'));
			$this->addErrorField('typeId');
		}

		// Ensure subscription type is valid
		$this->addCheck(new FormValidatorCustom($this, 'typeId', 'required', 'manager.subscriptions.form.typeIdValid', create_function('$typeId, $journalId', '$subscriptionTypeDao =& DAORegistry::getDAO(\'SubscriptionTypeDAO\'); return ($subscriptionTypeDao->subscriptionTypeExistsByTypeId($typeId, $journalId) && $subscriptionTypeDao->getSubscriptionTypeInstitutional($typeId) == 1);'), array($journal->getId())));

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
					'ipRanges' => $this->subscription->getIPRanges()
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
		$ipRangeProvided = false;
		if (is_array($ipRanges)) {
			foreach ($ipRanges as $ipRange) {
				if ($ipRange != '') {
					$ipRangeProvided = true;
					break;
				}
			}
		}

		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionType =& $subscriptionTypeDao->getSubscriptionType($this->getData('typeId'));

		// If online or print + online, domain or at least one IP range has been provided
		if ($subscriptionType->getFormat() != SUBSCRIPTION_TYPE_FORMAT_PRINT) {
			$this->addCheck(new FormValidatorCustom($this, 'domain', 'required', 'manager.subscriptions.form.domainIPRangeRequired', create_function('$domain, $ipRangeProvided', 'return ($domain != \'\' || $ipRangeProvided) ? true : false;'), array($ipRangeProvided)));
		}

		// If provided ensure IP ranges have IP address format; IP addresses may contain wildcards
		if ($ipRangeProvided) {	
			$this->addCheck(new FormValidatorArrayCustom($this, 'ipRanges', 'required', 'manager.subscriptions.form.ipRangeValid', create_function('$ipRange, $regExp', 'return String::regexp_match($regExp, $ipRange);'),
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
		if (empty($ipRanges) || empty($ipRanges[0])) {
			$ipRanges = array();
		}
		$this->subscription->setIPRanges($ipRanges);

		$institutionalSubscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		if ($insert) {
			$institutionalSubscriptionDao->insertSubscription($this->subscription);
		} else {
			$institutionalSubscriptionDao->updateSubscription($this->subscription);
		} 

		// Send notification email
		if ($this->_data['notifyEmail'] == 1) {
			$mail =& $this->_prepareNotificationEmail('SUBSCRIPTION_NOTIFY');
			$mail->send();
		} 
	}
}

?>
