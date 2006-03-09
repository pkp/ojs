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

import('form.Form');


class SubscriptionPolicyForm extends Form {

	/** @var validDuration array keys are valid open access delay months */	
	var $validDuration;

	/**
	 * Constructor
	 */
	function SubscriptionPolicyForm() {

		$this->validDuration = range(SUBSCRIPTION_OPEN_ACCESS_DELAY_MIN, SUBSCRIPTION_OPEN_ACCESS_DELAY_MAX);

		parent::Form('subscription/subscriptionPolicyForm.tpl');

		// If provided delayed open access flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'enableDelayedOpenAccess', 'optional', 'manager.subscriptionPolicies.delayedOpenAccessValid', array('1')));

		// If provided delayed open access duration is valid value
		$this->addCheck(new FormValidatorInSet($this, 'delayedOpenAccessDuration', 'optional', 'manager.subscriptionPolicies.delayedOpenAccessDurationValid', array_keys($this->validDuration)));

		// If provided author self-archive flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'enableAuthorSelfArchive', 'optional', 'manager.subscriptionPolicies.authorSelfArchiveValid', array('1')));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('validDuration', $this->validDuration);
	
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
			'authorSelfArchivePolicy' => $journalSettingsDao->getSetting($journalId, 'authorSelfArchivePolicy')
		);
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('subscriptionAdditionalInformation', 'enableDelayedOpenAccess', 'delayedOpenAccessDuration', 'enableAuthorSelfArchive', 'authorSelfArchivePolicy'));

		// If delayed open access selected, ensure a valid duration is provided
		if ($this->_data['enableDelayedOpenAccess'] == 1) {
			$this->addCheck(new FormValidatorInSet($this, 'delayedOpenAccessDuration', 'required', 'manager.subscriptionPolicies.delayedOpenAccessDurationValid', array_keys($this->validDuration)));
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
	}
	
}

?>
