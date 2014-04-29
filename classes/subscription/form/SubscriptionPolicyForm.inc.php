<?php

/**
 * @file classes/subscription/form/SubscriptionPolicyForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionPolicyForm
 * @ingroup manager_form
 *
 * @brief Form for managers to setup subscription policies.
 */

define('SUBSCRIPTION_OPEN_ACCESS_DELAY_MIN', '0');
define('SUBSCRIPTION_OPEN_ACCESS_DELAY_MAX', '24');

import('lib.pkp.classes.form.Form');


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

		parent::Form('subscription/subscriptionPolicyForm.tpl');

		// If provided, subscription contact email is valid
		$this->addCheck(new FormValidatorEmail($this, 'subscriptionEmail', 'optional', 'manager.subscriptionPolicies.subscriptionContactEmailValid'));

		// If provided delayed open access duration is valid value
		$this->addCheck(new FormValidatorInSet($this, 'delayedOpenAccessDuration', 'optional', 'manager.subscriptionPolicies.delayedOpenAccessDurationValid', array_keys($this->validDuration)));

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('validDuration', $this->validDuration);
		$templateMgr->assign('validWeeks', array(1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6, 7=>7, 8=>8));

		parent::display();
	}

	/**
	 * Initialize form data from current subscription policies.
	 */
	function initData() {
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$journal =& Request::getJournal();
		$journalId = $journal->getId();

		$this->_data = array(
			'subscriptionName' => $journalSettingsDao->getSetting($journalId, 'subscriptionName'),
			'subscriptionEmail' => $journalSettingsDao->getSetting($journalId, 'subscriptionEmail'),
			'subscriptionPhone' => $journalSettingsDao->getSetting($journalId, 'subscriptionPhone'),
			'subscriptionFax' => $journalSettingsDao->getSetting($journalId, 'subscriptionFax'),
			'subscriptionMailingAddress' => $journalSettingsDao->getSetting($journalId, 'subscriptionMailingAddress'),
			'subscriptionAdditionalInformation' => $journalSettingsDao->getSetting($journalId, 'subscriptionAdditionalInformation'),
			'enableDelayedOpenAccess' => $journalSettingsDao->getSetting($journalId, 'enableDelayedOpenAccess'),
			'delayedOpenAccessDuration' => $journalSettingsDao->getSetting($journalId, 'delayedOpenAccessDuration'),
			'delayedOpenAccessPolicy' => $journalSettingsDao->getSetting($journalId, 'delayedOpenAccessPolicy'),
			'enableOpenAccessNotification' => $journalSettingsDao->getSetting($journalId, 'enableOpenAccessNotification'),
			'enableAuthorSelfArchive' => $journalSettingsDao->getSetting($journalId, 'enableAuthorSelfArchive'),
			'authorSelfArchivePolicy' => $journalSettingsDao->getSetting($journalId, 'authorSelfArchivePolicy'),
			'subscriptionExpiryPartial' => $journalSettingsDao->getSetting($journalId, 'subscriptionExpiryPartial'),
			'enableSubscriptionOnlinePaymentNotificationPurchaseIndividual' => $journalSettingsDao->getSetting($journalId, 'enableSubscriptionOnlinePaymentNotificationPurchaseIndividual'),
			'enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional' => $journalSettingsDao->getSetting($journalId, 'enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional'),
			'enableSubscriptionOnlinePaymentNotificationRenewIndividual' => $journalSettingsDao->getSetting($journalId, 'enableSubscriptionOnlinePaymentNotificationRenewIndividual'),
			'enableSubscriptionOnlinePaymentNotificationRenewInstitutional' => $journalSettingsDao->getSetting($journalId, 'enableSubscriptionOnlinePaymentNotificationRenewInstitutional'),
			'enableSubscriptionExpiryReminderBeforeWeeks' => $journalSettingsDao->getSetting($journalId, 'enableSubscriptionExpiryReminderBeforeWeeks'),
			'numWeeksBeforeSubscriptionExpiryReminder' => $journalSettingsDao->getSetting($journalId, 'numWeeksBeforeSubscriptionExpiryReminder'),
			'enableSubscriptionExpiryReminderAfterWeeks' => $journalSettingsDao->getSetting($journalId, 'enableSubscriptionExpiryReminderAfterWeeks'),
			'numWeeksAfterSubscriptionExpiryReminder' => $journalSettingsDao->getSetting($journalId, 'numWeeksAfterSubscriptionExpiryReminder')
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('subscriptionName', 'subscriptionEmail', 'subscriptionPhone', 'subscriptionFax', 'subscriptionMailingAddress', 'subscriptionAdditionalInformation', 'enableDelayedOpenAccess', 'delayedOpenAccessDuration', 'delayedOpenAccessPolicy', 'enableOpenAccessNotification', 'enableAuthorSelfArchive', 'authorSelfArchivePolicy', 'subscriptionExpiryPartial', 'enableSubscriptionOnlinePaymentNotificationPurchaseIndividual', 'enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional', 'enableSubscriptionOnlinePaymentNotificationRenewIndividual', 'enableSubscriptionOnlinePaymentNotificationRenewInstitutional', 'enableSubscriptionExpiryReminderBeforeWeeks', 'numWeeksBeforeSubscriptionExpiryReminder', 'enableSubscriptionExpiryReminderAfterWeeks', 'numWeeksAfterSubscriptionExpiryReminder'));

		// If delayed open access selected, ensure a valid duration is provided
		if ($this->_data['enableDelayedOpenAccess'] == 1) {
			$this->addCheck(new FormValidatorInSet($this, 'delayedOpenAccessDuration', 'required', 'manager.subscriptionPolicies.delayedOpenAccessDurationValid', array_keys($this->validDuration)));
		}
	}

	/**
	 * Get the names of the fields for which localized settings are used
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('subscriptionAdditionalInformation', 'delayedOpenAccessPolicy', 'authorSelfArchivePolicy');
	}

	/**
	 * Save subscription policies. 
	 */
	function execute() {
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$journal =& Request::getJournal();
		$journalId = $journal->getId();

		$journalSettingsDao->updateSetting($journalId, 'subscriptionName', $this->getData('subscriptionName'), 'string');
		$journalSettingsDao->updateSetting($journalId, 'subscriptionEmail', $this->getData('subscriptionEmail'), 'string');
		$journalSettingsDao->updateSetting($journalId, 'subscriptionPhone', $this->getData('subscriptionPhone'), 'string');
		$journalSettingsDao->updateSetting($journalId, 'subscriptionFax', $this->getData('subscriptionFax'), 'string');
		$journalSettingsDao->updateSetting($journalId, 'subscriptionMailingAddress', $this->getData('subscriptionMailingAddress'), 'string');
		$journalSettingsDao->updateSetting($journalId, 'subscriptionAdditionalInformation', $this->getData('subscriptionAdditionalInformation'), 'string', true); // Localized
		$journalSettingsDao->updateSetting($journalId, 'enableDelayedOpenAccess', $this->getData('enableDelayedOpenAccess') == null ? 0 : $this->getData('enableDelayedOpenAccess'), 'bool');
		$journalSettingsDao->updateSetting($journalId, 'delayedOpenAccessDuration', $this->getData('delayedOpenAccessDuration'), 'int');
		$journalSettingsDao->updateSetting($journalId, 'delayedOpenAccessPolicy', $this->getData('delayedOpenAccessPolicy'), 'string', true); // Localized
		$journalSettingsDao->updateSetting($journalId, 'enableOpenAccessNotification', $this->getData('enableOpenAccessNotification') == null ? 0 : $this->getData('enableOpenAccessNotification'), 'bool');
		$journalSettingsDao->updateSetting($journalId, 'enableAuthorSelfArchive', $this->getData('enableAuthorSelfArchive') == null ? 0 : $this->getData('enableAuthorSelfArchive'), 'bool');
		$journalSettingsDao->updateSetting($journalId, 'authorSelfArchivePolicy', $this->getData('authorSelfArchivePolicy'), 'string', true); // Localized
		$journalSettingsDao->updateSetting($journalId, 'subscriptionExpiryPartial', $this->getData('subscriptionExpiryPartial') == null ? 0 : $this->getData('subscriptionExpiryPartial'), 'bool');
		$journalSettingsDao->updateSetting($journalId, 'enableSubscriptionOnlinePaymentNotificationPurchaseIndividual', $this->getData('enableSubscriptionOnlinePaymentNotificationPurchaseIndividual') == null ? 0 : $this->getData('enableSubscriptionOnlinePaymentNotificationPurchaseIndividual'), 'bool');
		$journalSettingsDao->updateSetting($journalId, 'enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional', $this->getData('enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional') == null ? 0 : $this->getData('enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional'), 'bool');
		$journalSettingsDao->updateSetting($journalId, 'enableSubscriptionOnlinePaymentNotificationRenewIndividual', $this->getData('enableSubscriptionOnlinePaymentNotificationRenewIndividual') == null ? 0 : $this->getData('enableSubscriptionOnlinePaymentNotificationRenewIndividual'), 'bool');
		$journalSettingsDao->updateSetting($journalId, 'enableSubscriptionOnlinePaymentNotificationRenewInstitutional', $this->getData('enableSubscriptionOnlinePaymentNotificationRenewInstitutional') == null ? 0 : $this->getData('enableSubscriptionOnlinePaymentNotificationRenewInstitutional'), 'bool');
		$journalSettingsDao->updateSetting($journalId, 'enableSubscriptionExpiryReminderBeforeWeeks', $this->getData('enableSubscriptionExpiryReminderBeforeWeeks') == null ? 0 : $this->getData('enableSubscriptionExpiryReminderBeforeWeeks'), 'bool');
		$journalSettingsDao->updateSetting($journalId, 'numWeeksBeforeSubscriptionExpiryReminder', $this->getData('numWeeksBeforeSubscriptionExpiryReminder'), 'int');
		$journalSettingsDao->updateSetting($journalId, 'enableSubscriptionExpiryReminderAfterWeeks', $this->getData('enableSubscriptionExpiryReminderAfterWeeks') == null ? 0 : $this->getData('enableSubscriptionExpiryReminderAfterWeeks'), 'bool');
		$journalSettingsDao->updateSetting($journalId, 'numWeeksAfterSubscriptionExpiryReminder', $this->getData('numWeeksAfterSubscriptionExpiryReminder'), 'int');
	}
}

?>
