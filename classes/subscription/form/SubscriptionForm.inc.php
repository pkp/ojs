<?php

/**
 * SubscriptionForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package subscription.form
 *
 * Form for journal managers to create/edit subscriptions.
 *
 * $Id$
 */

import('form.Form');

class SubscriptionForm extends Form {

	/** @var subscriptionId int the ID of the subscription being edited */
	var $subscriptionId;

	/**
	 * Constructor
	 * @param subscriptionId int leave as default for new subscription
	 */
	function SubscriptionForm($subscriptionId = null, $userId = null) {

		$this->subscriptionId = isset($subscriptionId) ? (int) $subscriptionId : null;
		$this->userId = isset($userId) ? (int) $userId : null;
		$journal = &Request::getJournal();

		parent::Form('subscription/subscriptionForm.tpl');
	
		// User is provided and valid
		$this->addCheck(new FormValidator($this, 'userId', 'required', 'manager.subscriptions.form.userIdRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'userId', 'required', 'manager.subscriptions.form.userIdValid', create_function('$userId', '$userDao = &DAORegistry::getDAO(\'UserDAO\'); return $userDao->userExistsById($userId);')));

		// Ensure that user does not already have a subscription for this journal
		if ($this->subscriptionId == null) {
			$this->addCheck(new FormValidatorCustom($this, 'userId', 'required', 'manager.subscriptions.form.subscriptionExists', array(DAORegistry::getDAO('SubscriptionDAO'), 'subscriptionExistsByUser'), array($journal->getJournalId()), true));
		} else {
			$this->addCheck(new FormValidatorCustom($this, 'userId', 'required', 'manager.subscriptions.form.subscriptionExists', create_function('$userId, $journalId, $subscriptionId', '$subscriptionDao = &DAORegistry::getDAO(\'SubscriptionDAO\'); $checkId = $subscriptionDao->getSubscriptionIdByUser($userId, $journalId); return ($checkId == 0 || $checkId == $subscriptionId) ? true : false;'), array($journal->getJournalId(), $this->subscriptionId)));
		}

		// Subscription type is provided and valid
		$this->addCheck(new FormValidator($this, 'typeId', 'required', 'manager.subscriptions.form.typeIdRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'typeId', 'required', 'manager.subscriptions.form.typeIdValid', create_function('$typeId, $journalId', '$subscriptionTypeDao = &DAORegistry::getDAO(\'SubscriptionTypeDAO\'); return $subscriptionTypeDao->subscriptionTypeExistsByTypeId($typeId, $journalId);'), array($journal->getJournalId())));

		// Start date is provided and is valid	
		$this->addCheck(new FormValidator($this, 'dateStartYear', 'required', 'manager.subscriptions.form.dateStartRequired'));	
		$this->addCheck(new FormValidatorCustom($this, 'dateStartYear', 'required', 'manager.subscriptions.form.dateStartValid', create_function('$dateStartYear', '$minYear = date(\'Y\') + SUBSCRIPTION_YEAR_OFFSET_PAST; $maxYear = date(\'Y\') + SUBSCRIPTION_YEAR_OFFSET_FUTURE; return ($dateStartYear >= $minYear && $dateStartYear <= $maxYear) ? true : false;')));

		$this->addCheck(new FormValidator($this, 'dateStartMonth', 'required', 'manager.subscriptions.form.dateStartRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateStartMonth', 'required', 'manager.subscriptions.form.dateStartValid', create_function('$dateStartMonth', 'return ($dateStartMonth >= 1 && $dateStartMonth <= 12) ? true : false;')));

		$this->addCheck(new FormValidator($this, 'dateStartDay', 'required', 'manager.subscriptions.form.dateStartRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateStartDay', 'required', 'manager.subscriptions.form.dateStartValid', create_function('$dateStartDay', 'return ($dateStartDay >= 1 && $dateStartDay <= 31) ? true : false;')));

		// End date is provided and is valid	
		$this->addCheck(new FormValidator($this, 'dateEndYear', 'required', 'manager.subscriptions.form.dateEndRequired'));	
		$this->addCheck(new FormValidatorCustom($this, 'dateEndYear', 'required', 'manager.subscriptions.form.dateEndValid', create_function('$dateEndYear', '$minYear = date(\'Y\') + SUBSCRIPTION_YEAR_OFFSET_PAST; $maxYear = date(\'Y\') + SUBSCRIPTION_YEAR_OFFSET_FUTURE; return ($dateEndYear >= $minYear && $dateEndYear <= $maxYear) ? true : false;')));

		$this->addCheck(new FormValidator($this, 'dateEndMonth', 'required', 'manager.subscriptions.form.dateEndRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateEndMonth', 'required', 'manager.subscriptions.form.dateEndValid', create_function('$dateEndMonth', 'return ($dateEndMonth >= 1 && $dateEndMonth <= 12) ? true : false;')));

		$this->addCheck(new FormValidator($this, 'dateEndDay', 'required', 'manager.subscriptions.form.dateEndRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateEndDay', 'required', 'manager.subscriptions.form.dateEndValid', create_function('$dateEndDay', 'return ($dateEndDay >= 1 && $dateEndDay <= 31) ? true : false;')));

		// If provided, domain is valid
		$this->addCheck(new FormValidatorRegExp($this, 'domain', 'optional', 'manager.subscriptions.form.domainValid', '/^' .
				'[A-Z0-9]+([\-_\.][A-Z0-9]+)*' .
				'\.' .
				'[A-Z]{2,4}' .
			'$/i'));

		// If provided, IP range has IP address format; IP addresses may contain wildcards
		$this->addCheck(new FormValidatorRegExp($this, 'ipRange', 'optional', 'manager.subscriptions.form.ipRangeValid','/^' .
				// IP4 address (with or w/o wildcards) or IP4 address range (with or w/o wildcards) or CIDR IP4 address
				'((([0-9]{1,3}|[' . SUBSCRIPTION_IP_RANGE_WILDCARD . '])([.]([0-9]{1,3}|[' . SUBSCRIPTION_IP_RANGE_WILDCARD . '])){3}((\s)*[' . SUBSCRIPTION_IP_RANGE_RANGE . '](\s)*([0-9]{1,3}|[' . SUBSCRIPTION_IP_RANGE_WILDCARD . '])([.]([0-9]{1,3}|[' . SUBSCRIPTION_IP_RANGE_WILDCARD . '])){3}){0,1})|(([0-9]{1,3})([.]([0-9]{1,3})){3}([\/](([3][0-2]{0,1})|([1-2]{0,1}[0-9])))))' .
				// followed by 0 or more delimited IP4 addresses (with or w/o wildcards) or IP4 address ranges
				// (with or w/o wildcards) or CIDR IP4 addresses
				'((\s)*' . SUBSCRIPTION_IP_RANGE_SEPERATOR . '(\s)*' .
				'((([0-9]{1,3}|[' . SUBSCRIPTION_IP_RANGE_WILDCARD . '])([.]([0-9]{1,3}|[' . SUBSCRIPTION_IP_RANGE_WILDCARD . '])){3}((\s)*[' . SUBSCRIPTION_IP_RANGE_RANGE . '](\s)*([0-9]{1,3}|[' . SUBSCRIPTION_IP_RANGE_WILDCARD . '])([.]([0-9]{1,3}|[' . SUBSCRIPTION_IP_RANGE_WILDCARD . '])){3}){0,1})|(([0-9]{1,3})([.]([0-9]{1,3})){3}([\/](([3][0-2]{0,1})|([1-2]{0,1}[0-9])))))' .
				')*' .
			'$/i'));

		// Notify email flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'notifyEmail', 'optional', 'manager.subscriptions.form.notifyEmailValid', array('1')));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$journal = &Request::getJournal();

		$templateMgr->assign('subscriptionId', $this->subscriptionId);
		$templateMgr->assign('yearOffsetPast', SUBSCRIPTION_YEAR_OFFSET_PAST);
		$templateMgr->assign('yearOffsetFuture', SUBSCRIPTION_YEAR_OFFSET_FUTURE);

		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &$userDao->getUser(isset($this->userId)?$this->userId:$this->getData('userId'));

		$templateMgr->assign_by_ref('user', $user);

		$subscriptionTypeDao = &DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionTypes = &$subscriptionTypeDao->getSubscriptionTypesByJournalId($journal->getJournalId());
		$templateMgr->assign('subscriptionTypes', $subscriptionTypes);
		$templateMgr->assign('helpTopicId', 'journal.managementPages.subscriptions');
	
		parent::display();
	}
	
	/**
	 * Initialize form data from current subscription.
	 */
	function initData() {
		if (isset($this->subscriptionId)) {
			$subscriptionDao = &DAORegistry::getDAO('SubscriptionDAO');
			$subscription = &$subscriptionDao->getSubscription($this->subscriptionId);
			
			if ($subscription != null) {
				$this->_data = array(
					'userId' => $subscription->getUserId(),
					'typeId' => $subscription->getTypeId(),
					'dateStart' => $subscription->getDateStart(),
					'dateEnd' => $subscription->getDateEnd(),
					'membership' => $subscription->getMembership(),
					'domain' => $subscription->getDomain(),
					'ipRange' => $subscription->getIPRange()
				);

			} else {
				$this->subscriptionId = null;
			}
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('userId', 'typeId', 'dateStartYear', 'dateStartMonth', 'dateStartDay', 'dateEndYear', 'dateEndMonth', 'dateEndDay', 'membership', 'domain', 'ipRange', 'notifyEmail'));
		$this->_data['dateStart'] = $this->_data['dateStartYear'] . '-' . $this->_data['dateStartMonth'] . '-' . $this->_data['dateStartDay'];
		$this->_data['dateEnd'] = $this->_data['dateEndYear'] . '-' . $this->_data['dateEndMonth'] . '-' . $this->_data['dateEndDay'];

		// If subscription type requires it, membership is provided
		$subscriptionTypeDao = &DAORegistry::getDAO('SubscriptionTypeDAO');
		$needMembership = $subscriptionTypeDao->getSubscriptionTypeMembership($this->getData('typeId'));

		if ($needMembership) { 
			$this->addCheck(new FormValidator($this, 'membership', 'required', 'manager.subscriptions.form.membershipRequired'));
		}

		// If subscription type requires it, domain and/or IP range is provided
		$isInstitutional = $subscriptionTypeDao->getSubscriptionTypeInstitutional($this->getData('typeId'));

		if ($isInstitutional) { 
			$this->addCheck(new FormValidatorCustom($this, 'domain', 'required', 'manager.subscriptions.form.domainIPRangeRequired', create_function('$domain, $ipRange', 'return $domain != \'\' || $ipRange != \'\' ? true : false;'), array($this->getData('ipRange'))));
		}

		// If notify email is requested, ensure subscription contact name and email exist.
		if ($this->_data['notifyEmail'] == 1) {
			$this->addCheck(new FormValidatorCustom($this, 'notifyEmail', 'required', 'manager.subscriptions.form.subscriptionContactRequired', create_function('', '$journal = &Request::getJournal(); $journalSettingsDao = &DAORegistry::getDAO(\'JournalSettingsDAO\'); $subscriptionName = $journalSettingsDao->getSetting($journal->getJournalId(), \'subscriptionName\'); $subscriptionEmail = $journalSettingsDao->getSetting($journal->getJournalId(), \'subscriptionEmail\'); return $subscriptionName != \'\' && $subscriptionEmail != \'\' ? true : false;'), array()));
		}
	}
	
	/**
	 * Save subscription. 
	 */
	function execute() {
		$subscriptionDao = &DAORegistry::getDAO('SubscriptionDAO');
		$journal = &Request::getJournal();
	
		if (isset($this->subscriptionId)) {
			$subscription = &$subscriptionDao->getSubscription($this->subscriptionId);
		}
		
		if (!isset($subscription)) {
			$subscription = &new Subscription();
		}
		
		$subscription->setJournalId($journal->getJournalId());
		$subscription->setUserId($this->getData('userId'));
		$subscription->setTypeId($this->getData('typeId'));
		$subscription->setDateStart($this->getData('dateStartYear') . '-' . $this->getData('dateStartMonth'). '-' . $this->getData('dateStartDay'));
		$subscription->setDateEnd($this->getData('dateEndYear') . '-' . $this->getData('dateEndMonth'). '-' . $this->getData('dateEndDay'));
		$subscription->setMembership($this->getData('membership') ? $this->getData('membership') : null);
		$subscription->setDomain($this->getData('domain') ? $this->getData('domain') : null);
		$subscription->setIPRange($this->getData('ipRange') ? $this->getData('ipRange') : null);

		// Update or insert subscription
		if ($subscription->getSubscriptionId() != null) {
			$subscriptionDao->updateSubscription($subscription);
		} else {
			$subscriptionDao->insertSubscription($subscription);
		}

		if ($this->getData('notifyEmail')) {
			// Send user subscription notification email
			$userDao = &DAORegistry::getDAO('UserDAO');
			$subscriptionTypeDao = &DAORegistry::getDAO('SubscriptionTypeDAO');
			$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');

			$journalName = $journal->getTitle();
			$journalId = $journal->getJournalId();
			$user = &$userDao->getUser($this->getData('userId'));
			$subscriptionType = &$subscriptionTypeDao->getSubscriptionType($this->getData('typeId'));

			$subscriptionName = $journalSettingsDao->getSetting($journalId, 'subscriptionName');
			$subscriptionEmail = $journalSettingsDao->getSetting($journalId, 'subscriptionEmail');
			$subscriptionPhone = $journalSettingsDao->getSetting($journalId, 'subscriptionPhone');
			$subscriptionFax = $journalSettingsDao->getSetting($journalId, 'subscriptionFax');
			$subscriptionMailingAddress = $journalSettingsDao->getSetting($journalId, 'subscriptionMailingAddress');
			$subscriptionContactSignature = $subscriptionName;

			if ($subscriptionMailingAddress != '') {
				$subscriptionContactSignature .= "\n" . $subscriptionMailingAddress;
			}
			if ($subscriptionPhone != '') {
				$subscriptionContactSignature .= "\n" . Locale::Translate('user.phone') . ': ' . $subscriptionPhone;
			}
			if ($subscriptionFax != '') {
				$subscriptionContactSignature .= "\n" . Locale::Translate('user.fax') . ': ' . $subscriptionFax;
			}

			$subscriptionContactSignature .= "\n" . Locale::Translate('user.email') . ': ' . $subscriptionEmail;

			$paramArray = array(
				'subscriberName' => $user->getFullName(),
				'journalName' => $journalName,
				'subscriptionType' => $subscriptionType->getSummaryString(),
				'username' => $user->getUsername(),
				'subscriptionContactSignature' => $subscriptionContactSignature 
			);

			import('mail.MailTemplate');
			$mail = &new MailTemplate('SUBSCRIPTION_NOTIFY');
			$mail->setFrom($subscriptionEmail, $subscriptionName);
			$mail->assignParams($paramArray);
			$mail->addRecipient($user->getEmail(), $user->getFullName());
			$mail->send();
		}
	}
	
}

?>
