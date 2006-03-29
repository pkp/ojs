<?php

/**
 * SubscriptionPolicyForm.inc.php
 *
 * Copyright (c) 2003-2006 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 *
 * Form for managers to setup subscription policies.
 *
 * $Id$
 */

define('SUBSCRIPTION_OPEN_ACCESS_DELAY_MIN', '0');
define('SUBSCRIPTION_OPEN_ACCESS_DELAY_MAX', '24');
define('SUBSCRIPTION_EXPIRY_REMINDER_BEFORE_MONTHS_MIN', '1');
define('SUBSCRIPTION_EXPIRY_REMINDER_BEFORE_MONTHS_MAX', '12');
define('SUBSCRIPTION_EXPIRY_REMINDER_BEFORE_WEEKS_MIN', '0');
define('SUBSCRIPTION_EXPIRY_REMINDER_BEFORE_WEEKS_MAX', '3');
define('SUBSCRIPTION_EXPIRY_REMINDER_AFTER_MONTHS_MIN', '1');
define('SUBSCRIPTION_EXPIRY_REMINDER_AFTER_MONTHS_MAX', '12');
define('SUBSCRIPTION_EXPIRY_REMINDER_AFTER_WEEKS_MIN', '0');
define('SUBSCRIPTION_EXPIRY_REMINDER_AFTER_WEEKS_MAX', '3');

import('form.Form');


class SubscriptionPolicyForm extends Form {

	/** @var validDuration array keys are valid open access delay months */	
	var $validDuration;

	/** @var validNumMonthsBeforeExpiry array keys are valid expiry reminder months */	
	var $validNumMonthsBeforeExpiry;

	/** @var validNumWeeksBeforeExpiry array keys are valid expiry reminder weeks */	
	var $validNumWeeksBeforeExpiry;

	/** @var validNumMonthsAfterExpiry array keys are valid expiry reminder months */	
	var $validNumMonthsAfterExpiry;

	/** @var validNumWeeksAfterExpiry array keys are valid expiry reminder weeks */	
	var $validNumWeeksAfterExpiry;

	/**
	 * Constructor
	 */
	function SubscriptionPolicyForm() {

		for ($i=SUBSCRIPTION_OPEN_ACCESS_DELAY_MIN; $i<=SUBSCRIPTION_OPEN_ACCESS_DELAY_MAX; $i++) {
			$this->validDuration[$i] = $i;
		}

		for ($i=SUBSCRIPTION_EXPIRY_REMINDER_BEFORE_MONTHS_MIN; $i<=SUBSCRIPTION_EXPIRY_REMINDER_BEFORE_MONTHS_MAX; $i++) {
			$this->validNumMonthsBeforeExpiry[$i] = $i;
		}

		for ($i=SUBSCRIPTION_EXPIRY_REMINDER_BEFORE_WEEKS_MIN; $i<=SUBSCRIPTION_EXPIRY_REMINDER_BEFORE_WEEKS_MAX; $i++) {
			$this->validNumWeeksBeforeExpiry[$i] = $i;
		}

		for ($i=SUBSCRIPTION_EXPIRY_REMINDER_AFTER_MONTHS_MIN; $i<=SUBSCRIPTION_EXPIRY_REMINDER_AFTER_MONTHS_MAX; $i++) {
			$this->validNumMonthsAfterExpiry[$i] = $i;
		}

		for ($i=SUBSCRIPTION_EXPIRY_REMINDER_AFTER_WEEKS_MIN; $i<=SUBSCRIPTION_EXPIRY_REMINDER_AFTER_WEEKS_MAX; $i++) {
			$this->validNumWeeksAfterExpiry[$i] = $i;
		}

		parent::Form('subscription/subscriptionPolicyForm.tpl');

		// If provided delayed open access flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'enableDelayedOpenAccess', 'optional', 'manager.subscriptionPolicies.delayedOpenAccessValid', array('1')));

		// If provided delayed open access duration is valid value
		$this->addCheck(new FormValidatorInSet($this, 'delayedOpenAccessDuration', 'optional', 'manager.subscriptionPolicies.delayedOpenAccessDurationValid', array_keys($this->validDuration)));

		// If provided author self-archive flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'enableAuthorSelfArchive', 'optional', 'manager.subscriptionPolicies.authorSelfArchiveValid', array('1')));

		// If provided expiry reminder months before flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'enableSubscriptionExpiryReminderBeforeMonths', 'optional', 'manager.subscriptionPolicies.enableSubscriptionExpiryReminderBeforeMonthsValid', array('1')));

		// If provided expiry reminder months before value is valid value
		$this->addCheck(new FormValidatorInSet($this, 'numMonthsBeforeSubscriptionExpiryReminder', 'optional', 'manager.subscriptionPolicies.numMonthsBeforeSubscriptionExpiryReminderValid', array_keys($this->validNumMonthsBeforeExpiry)));

		// If provided expiry reminder weeks before flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'enableSubscriptionExpiryReminderBeforeWeeks', 'optional', 'manager.subscriptionPolicies.enableSubscriptionExpiryReminderBeforeWeeksValid', array('1')));

		// If provided expiry reminder weeks before value is valid value
		$this->addCheck(new FormValidatorInSet($this, 'numWeeksBeforeSubscriptionExpiryReminder', 'optional', 'manager.subscriptionPolicies.numWeeksBeforeSubscriptionExpiryReminderValid', array_keys($this->validNumWeeksBeforeExpiry)));

		// If provided expiry reminder months after flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'enableSubscriptionExpiryReminderAfterMonths', 'optional', 'manager.subscriptionPolicies.enableSubscriptionExpiryReminderAfterMonthsValid', array('1')));

		// If provided expiry reminder months after value is valid value
		$this->addCheck(new FormValidatorInSet($this, 'numMonthsAfterSubscriptionExpiryReminder', 'optional', 'manager.subscriptionPolicies.numMonthsAfterSubscriptionExpiryReminderValid', array_keys($this->validNumMonthsAfterExpiry)));

		// If provided expiry reminder weeks after flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'enableSubscriptionExpiryReminderAfterWeeks', 'optional', 'manager.subscriptionPolicies.enableSubscriptionExpiryReminderAfterWeeksValid', array('1')));

		// If provided expiry reminder weeks after value is valid value
		$this->addCheck(new FormValidatorInSet($this, 'numWeeksAfterSubscriptionExpiryReminder', 'optional', 'manager.subscriptionPolicies.numWeeksAfterSubscriptionExpiryReminderValid', array_keys($this->validNumWeeksAfterExpiry)));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('validDuration', $this->validDuration);
		$templateMgr->assign('validNumMonthsBeforeExpiry', $this->validNumMonthsBeforeExpiry);
		$templateMgr->assign('validNumWeeksBeforeExpiry', $this->validNumWeeksBeforeExpiry);
		$templateMgr->assign('validNumMonthsAfterExpiry', $this->validNumMonthsAfterExpiry);
		$templateMgr->assign('validNumWeeksAfterExpiry', $this->validNumWeeksAfterExpiry);
	
		parent::display();
	}
	
	/**
	 * Initialize form data from current subscription policies.
	 */
	function initData() {
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		$this->_data = array(
			'subscriptionAdditionalInformation' => $journalSettingsDao->getSetting($journalId, 'subscriptionAdditionalInformation'),
			'enableDelayedOpenAccess' => $journalSettingsDao->getSetting($journalId, 'enableDelayedOpenAccess'),
			'delayedOpenAccessDuration' => $journalSettingsDao->getSetting($journalId, 'delayedOpenAccessDuration'),
			'enableAuthorSelfArchive' => $journalSettingsDao->getSetting($journalId, 'enableAuthorSelfArchive'),
			'authorSelfArchivePolicy' => $journalSettingsDao->getSetting($journalId, 'authorSelfArchivePolicy'),
			'enableSubscriptionExpiryReminderBeforeMonths' => $journalSettingsDao->getSetting($journalId, 'enableSubscriptionExpiryReminderBeforeMonths'),
			'numMonthsBeforeSubscriptionExpiryReminder' => $journalSettingsDao->getSetting($journalId, 'numMonthsBeforeSubscriptionExpiryReminder'),
			'enableSubscriptionExpiryReminderBeforeWeeks' => $journalSettingsDao->getSetting($journalId, 'enableSubscriptionExpiryReminderBeforeWeeks'),
			'numWeeksBeforeSubscriptionExpiryReminder' => $journalSettingsDao->getSetting($journalId, 'numWeeksBeforeSubscriptionExpiryReminder'),
			'enableSubscriptionExpiryReminderAfterMonths' => $journalSettingsDao->getSetting($journalId, 'enableSubscriptionExpiryReminderAfterMonths'),
			'numMonthsAfterSubscriptionExpiryReminder' => $journalSettingsDao->getSetting($journalId, 'numMonthsAfterSubscriptionExpiryReminder'),
			'enableSubscriptionExpiryReminderAfterWeeks' => $journalSettingsDao->getSetting($journalId, 'enableSubscriptionExpiryReminderAfterWeeks'),
			'numWeeksAfterSubscriptionExpiryReminder' => $journalSettingsDao->getSetting($journalId, 'numWeeksAfterSubscriptionExpiryReminder')
		);
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('subscriptionAdditionalInformation', 'enableDelayedOpenAccess', 'delayedOpenAccessDuration', 'enableAuthorSelfArchive', 'authorSelfArchivePolicy', 'enableSubscriptionExpiryReminderBeforeMonths', 'numMonthsBeforeSubscriptionExpiryReminder', 'enableSubscriptionExpiryReminderBeforeWeeks', 'numWeeksBeforeSubscriptionExpiryReminder', 'enableSubscriptionExpiryReminderAfterWeeks', 'numWeeksAfterSubscriptionExpiryReminder', 'enableSubscriptionExpiryReminderAfterMonths', 'numMonthsAfterSubscriptionExpiryReminder'));

		// If delayed open access selected, ensure a valid duration is provided
		if ($this->_data['enableDelayedOpenAccess'] == 1) {
			$this->addCheck(new FormValidatorInSet($this, 'delayedOpenAccessDuration', 'required', 'manager.subscriptionPolicies.delayedOpenAccessDurationValid', array_keys($this->validDuration)));
		}

		// If expiry reminder before months is selected, ensure a valid month value is provided
		if ($this->_data['enableSubscriptionExpiryReminderBeforeMonths'] == 1) {
			$this->addCheck(new FormValidatorInSet($this, 'numMonthsBeforeSubscriptionExpiryReminder', 'required', 'manager.subscriptionPolicies.numMonthsBeforeSubscriptionExpiryReminderValid', array_keys($this->validNumMonthsBeforeExpiry)));
		}

		// If expiry reminder before weeks is selected, ensure a valid week value is provided
		if ($this->_data['enableSubscriptionExpiryReminderBeforeWeeks'] == 1) {
			$this->addCheck(new FormValidatorInSet($this, 'numWeeksBeforeSubscriptionExpiryReminder', 'required', 'manager.subscriptionPolicies.numWeeksBeforeSubscriptionExpiryReminderValid', array_keys($this->validNumWeeksBeforeExpiry)));
		}

		// If expiry reminder after months is selected, ensure a valid month value is provided
		if ($this->_data['enableSubscriptionExpiryReminderAfterMonths'] == 1) {
			$this->addCheck(new FormValidatorInSet($this, 'numMonthsAfterSubscriptionExpiryReminder', 'required', 'manager.subscriptionPolicies.numMonthsAfterSubscriptionExpiryReminderValid', array_keys($this->validNumMonthsAfterExpiry)));
		}

		// If expiry reminder after weeks is selected, ensure a valid week value is provided
		if ($this->_data['enableSubscriptionExpiryReminderAfterWeeks'] == 1) {
			$this->addCheck(new FormValidatorInSet($this, 'numWeeksAfterSubscriptionExpiryReminder', 'required', 'manager.subscriptionPolicies.numWeeksAfterSubscriptionExpiryReminderValid', array_keys($this->validNumWeeksAfterExpiry)));
		}
	}
	
	/**
	 * Save subscription policies. 
	 */
	function execute() {
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();
	
		$journalSettingsDao->updateSetting($journalId, 'subscriptionAdditionalInformation', $this->getData('subscriptionAdditionalInformation'), 'string');
		$journalSettingsDao->updateSetting($journalId, 'enableDelayedOpenAccess', $this->getData('enableDelayedOpenAccess') == null ? 0 : $this->getData('enableDelayedOpenAccess'), 'int');
		$journalSettingsDao->updateSetting($journalId, 'delayedOpenAccessDuration', $this->getData('delayedOpenAccessDuration'), 'int');
		$journalSettingsDao->updateSetting($journalId, 'enableAuthorSelfArchive', $this->getData('enableAuthorSelfArchive') == null ? 0 : $this->getData('enableAuthorSelfArchive'), 'int');
		$journalSettingsDao->updateSetting($journalId, 'authorSelfArchivePolicy', $this->getData('authorSelfArchivePolicy'), 'string');
		$journalSettingsDao->updateSetting($journalId, 'enableSubscriptionExpiryReminderBeforeMonths', $this->getData('enableSubscriptionExpiryReminderBeforeMonths') == null ? 0 : $this->getData('enableSubscriptionExpiryReminderBeforeMonths'), 'int');
		$journalSettingsDao->updateSetting($journalId, 'numMonthsBeforeSubscriptionExpiryReminder', $this->getData('numMonthsBeforeSubscriptionExpiryReminder'), 'int');
		$journalSettingsDao->updateSetting($journalId, 'enableSubscriptionExpiryReminderBeforeWeeks', $this->getData('enableSubscriptionExpiryReminderBeforeWeeks') == null ? 0 : $this->getData('enableSubscriptionExpiryReminderBeforeWeeks'), 'int');
		$journalSettingsDao->updateSetting($journalId, 'numWeeksBeforeSubscriptionExpiryReminder', $this->getData('numWeeksBeforeSubscriptionExpiryReminder'), 'int');
		$journalSettingsDao->updateSetting($journalId, 'enableSubscriptionExpiryReminderAfterMonths', $this->getData('enableSubscriptionExpiryReminderAfterMonths') == null ? 0 : $this->getData('enableSubscriptionExpiryReminderAfterMonths'), 'int');
		$journalSettingsDao->updateSetting($journalId, 'numMonthsAfterSubscriptionExpiryReminder', $this->getData('numMonthsAfterSubscriptionExpiryReminder'), 'int');
		$journalSettingsDao->updateSetting($journalId, 'enableSubscriptionExpiryReminderAfterWeeks', $this->getData('enableSubscriptionExpiryReminderAfterWeeks') == null ? 0 : $this->getData('enableSubscriptionExpiryReminderAfterWeeks'), 'int');
		$journalSettingsDao->updateSetting($journalId, 'numWeeksAfterSubscriptionExpiryReminder', $this->getData('numWeeksAfterSubscriptionExpiryReminder'), 'int');
	}
	
}

?>
