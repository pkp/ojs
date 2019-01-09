<?php

/**
 * @file classes/subscription/form/SubscriptionPolicyForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionPolicyForm
 * @ingroup manager_form
 *
 * @brief Form for managers to setup subscription policies.
 */

define('SUBSCRIPTION_OPEN_ACCESS_DELAY_MIN', '1');
define('SUBSCRIPTION_OPEN_ACCESS_DELAY_MAX', '60');
define('SUBSCRIPTION_EXPIRY_REMINDER_BEFORE_MONTHS_MIN', '1');
define('SUBSCRIPTION_EXPIRY_REMINDER_BEFORE_MONTHS_MAX', '12');
define('SUBSCRIPTION_EXPIRY_REMINDER_BEFORE_WEEKS_MIN', '1');
define('SUBSCRIPTION_EXPIRY_REMINDER_BEFORE_WEEKS_MAX', '3');
define('SUBSCRIPTION_EXPIRY_REMINDER_AFTER_MONTHS_MIN', '1');
define('SUBSCRIPTION_EXPIRY_REMINDER_AFTER_MONTHS_MAX', '12');
define('SUBSCRIPTION_EXPIRY_REMINDER_AFTER_WEEKS_MIN', '1');
define('SUBSCRIPTION_EXPIRY_REMINDER_AFTER_WEEKS_MAX', '3');

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
	function __construct() {
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER);

		$this->validDuration = array(0 => __('common.disabled'));
		for ($i=SUBSCRIPTION_OPEN_ACCESS_DELAY_MIN; $i<=SUBSCRIPTION_OPEN_ACCESS_DELAY_MAX; $i++) {
			$this->validDuration[$i] = __('manager.subscriptionPolicies.xMonths', array('x' => $i));
		}

		$this->validNumMonthsBeforeExpiry = array(0 => __('common.disabled'));
		for ($i=SUBSCRIPTION_EXPIRY_REMINDER_BEFORE_MONTHS_MIN; $i<=SUBSCRIPTION_EXPIRY_REMINDER_BEFORE_MONTHS_MAX; $i++) {
			$this->validNumMonthsBeforeExpiry[$i] = __('manager.subscriptionPolicies.xMonths', array('x' => $i));
		}

		$this->validNumWeeksBeforeExpiry = array(0 => __('common.disabled'));
		for ($i=SUBSCRIPTION_EXPIRY_REMINDER_BEFORE_WEEKS_MIN; $i<=SUBSCRIPTION_EXPIRY_REMINDER_BEFORE_WEEKS_MAX; $i++) {
			$this->validNumWeeksBeforeExpiry[$i] = __('manager.subscriptionPolicies.xWeeks', array('x' => $i));
		}

		$this->validNumMonthsAfterExpiry = array(0 => __('common.disabled'));
		for ($i=SUBSCRIPTION_EXPIRY_REMINDER_AFTER_MONTHS_MIN; $i<=SUBSCRIPTION_EXPIRY_REMINDER_AFTER_MONTHS_MAX; $i++) {
			$this->validNumMonthsAfterExpiry[$i] = __('manager.subscriptionPolicies.xMonths', array('x' => $i));
		}

		$this->validNumWeeksAfterExpiry = array(0 => __('common.disabled'));
		for ($i=SUBSCRIPTION_EXPIRY_REMINDER_AFTER_WEEKS_MIN; $i<=SUBSCRIPTION_EXPIRY_REMINDER_AFTER_WEEKS_MAX; $i++) {
			$this->validNumWeeksAfterExpiry[$i] = __('manager.subscriptionPolicies.xWeeks', array('x' => $i));
		}

		parent::__construct('payments/subscriptionPolicyForm.tpl');

		// If provided, subscription contact email is valid
		$this->addCheck(new FormValidatorEmail($this, 'subscriptionEmail', 'optional', 'manager.subscriptionPolicies.subscriptionContactEmailValid'));

		// If provided delayed open access duration is valid value
		$this->addCheck(new FormValidatorInSet($this, 'delayedOpenAccessDuration', 'optional', 'manager.subscriptionPolicies.delayedOpenAccessDurationValid', array_keys($this->validDuration)));

		// If provided expiry reminder months before value is valid value
		$this->addCheck(new FormValidatorInSet($this, 'numMonthsBeforeSubscriptionExpiryReminder', 'optional', 'manager.subscriptionPolicies.numMonthsBeforeSubscriptionExpiryReminderValid', array_keys($this->validNumMonthsBeforeExpiry)));

		// If provided expiry reminder weeks before value is valid value
		$this->addCheck(new FormValidatorInSet($this, 'numWeeksBeforeSubscriptionExpiryReminder', 'optional', 'manager.subscriptionPolicies.numWeeksBeforeSubscriptionExpiryReminderValid', array_keys($this->validNumWeeksBeforeExpiry)));

		// If provided expiry reminder months after value is valid value
		$this->addCheck(new FormValidatorInSet($this, 'numMonthsAfterSubscriptionExpiryReminder', 'optional', 'manager.subscriptionPolicies.numMonthsAfterSubscriptionExpiryReminderValid', array_keys($this->validNumMonthsAfterExpiry)));

		// If provided expiry reminder weeks after value is valid value
		$this->addCheck(new FormValidatorInSet($this, 'numWeeksAfterSubscriptionExpiryReminder', 'optional', 'manager.subscriptionPolicies.numWeeksAfterSubscriptionExpiryReminderValid', array_keys($this->validNumWeeksAfterExpiry)));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Fetch the form.
	 * @param $request PKPRequest
	 */
	function fetch($request) {
		$paymentManager = Application::getPaymentManager($request->getJournal());
		$templateMgr = TemplateManager::getManager();
		$templateMgr->assign(array(
			'validDuration' => $this->validDuration,
			'validNumMonthsBeforeExpiry' => $this->validNumMonthsBeforeExpiry,
			'validNumWeeksBeforeExpiry' => $this->validNumWeeksBeforeExpiry,
			'validNumMonthsAfterExpiry' => $this->validNumMonthsAfterExpiry,
			'validNumWeeksAfterExpiry' => $this->validNumWeeksAfterExpiry,
			'scheduledTasksEnabled' => (boolean) Config::getVar('general', 'scheduled_tasks'),
			'paymentsEnabled' => $paymentManager->isConfigured(),
		));

		return parent::fetch($request);
	}

	/**
	 * Initialize form data from current subscription policies.
	 */
	function initData() {
		$journalSettingsDao = DAORegistry::getDAO('JournalSettingsDAO');
		$journal = Request::getJournal();
		$journalId = $journal->getId();

		$this->_data = array(
			'subscriptionName' => $journalSettingsDao->getSetting($journalId, 'subscriptionName'),
			'subscriptionEmail' => $journalSettingsDao->getSetting($journalId, 'subscriptionEmail'),
			'subscriptionPhone' => $journalSettingsDao->getSetting($journalId, 'subscriptionPhone'),
			'subscriptionMailingAddress' => $journalSettingsDao->getSetting($journalId, 'subscriptionMailingAddress'),
			'subscriptionAdditionalInformation' => $journalSettingsDao->getSetting($journalId, 'subscriptionAdditionalInformation'),
			'delayedOpenAccessDuration' => $journalSettingsDao->getSetting($journalId, 'delayedOpenAccessDuration'),
			'delayedOpenAccessPolicy' => $journalSettingsDao->getSetting($journalId, 'delayedOpenAccessPolicy'),
			'enableOpenAccessNotification' => $journalSettingsDao->getSetting($journalId, 'enableOpenAccessNotification'),
			'subscriptionExpiryPartial' => $journalSettingsDao->getSetting($journalId, 'subscriptionExpiryPartial'),
			'enableSubscriptionOnlinePaymentNotificationPurchaseIndividual' => $journalSettingsDao->getSetting($journalId, 'enableSubscriptionOnlinePaymentNotificationPurchaseIndividual'),
			'enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional' => $journalSettingsDao->getSetting($journalId, 'enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional'),
			'enableSubscriptionOnlinePaymentNotificationRenewIndividual' => $journalSettingsDao->getSetting($journalId, 'enableSubscriptionOnlinePaymentNotificationRenewIndividual'),
			'enableSubscriptionOnlinePaymentNotificationRenewInstitutional' => $journalSettingsDao->getSetting($journalId, 'enableSubscriptionOnlinePaymentNotificationRenewInstitutional'),
			'numMonthsBeforeSubscriptionExpiryReminder' => $journalSettingsDao->getSetting($journalId, 'numMonthsBeforeSubscriptionExpiryReminder'),
			'numWeeksBeforeSubscriptionExpiryReminder' => $journalSettingsDao->getSetting($journalId, 'numWeeksBeforeSubscriptionExpiryReminder'),
			'numMonthsAfterSubscriptionExpiryReminder' => $journalSettingsDao->getSetting($journalId, 'numMonthsAfterSubscriptionExpiryReminder'),
			'numWeeksAfterSubscriptionExpiryReminder' => $journalSettingsDao->getSetting($journalId, 'numWeeksAfterSubscriptionExpiryReminder')
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('subscriptionName', 'subscriptionEmail', 'subscriptionPhone', 'subscriptionMailingAddress', 'subscriptionAdditionalInformation', 'delayedOpenAccessDuration', 'delayedOpenAccessPolicy', 'enableOpenAccessNotification', 'subscriptionExpiryPartial', 'enableSubscriptionOnlinePaymentNotificationPurchaseIndividual', 'enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional', 'enableSubscriptionOnlinePaymentNotificationRenewIndividual', 'enableSubscriptionOnlinePaymentNotificationRenewInstitutional', 'numMonthsBeforeSubscriptionExpiryReminder', 'numWeeksBeforeSubscriptionExpiryReminder', 'numWeeksAfterSubscriptionExpiryReminder', 'numMonthsAfterSubscriptionExpiryReminder'));

		$this->addCheck(new FormValidatorInSet($this, 'delayedOpenAccessDuration', 'required', 'manager.subscriptionPolicies.delayedOpenAccessDurationValid', array_keys($this->validDuration)));

		$this->addCheck(new FormValidatorInSet($this, 'numMonthsBeforeSubscriptionExpiryReminder', 'required', 'manager.subscriptionPolicies.numMonthsBeforeSubscriptionExpiryReminderValid', array_keys($this->validNumMonthsBeforeExpiry)));
		$this->addCheck(new FormValidatorInSet($this, 'numWeeksBeforeSubscriptionExpiryReminder', 'required', 'manager.subscriptionPolicies.numWeeksBeforeSubscriptionExpiryReminderValid', array_keys($this->validNumWeeksBeforeExpiry)));
		$this->addCheck(new FormValidatorInSet($this, 'numMonthsAfterSubscriptionExpiryReminder', 'required', 'manager.subscriptionPolicies.numMonthsAfterSubscriptionExpiryReminderValid', array_keys($this->validNumMonthsAfterExpiry)));
		$this->addCheck(new FormValidatorInSet($this, 'numWeeksAfterSubscriptionExpiryReminder', 'required', 'manager.subscriptionPolicies.numWeeksAfterSubscriptionExpiryReminderValid', array_keys($this->validNumWeeksAfterExpiry)));
	}

	/**
	 * Get the names of the fields for which localized settings are used
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('subscriptionAdditionalInformation', 'delayedOpenAccessPolicy');
	}

	/**
	 * Save subscription policies.
	 */
	function execute() {
		$journalSettingsDao = DAORegistry::getDAO('JournalSettingsDAO');
		$journal = Request::getJournal();
		$journalId = $journal->getId();

		$journalSettingsDao->updateSetting($journalId, 'subscriptionName', $this->getData('subscriptionName'), 'string');
		$journalSettingsDao->updateSetting($journalId, 'subscriptionEmail', $this->getData('subscriptionEmail'), 'string');
		$journalSettingsDao->updateSetting($journalId, 'subscriptionPhone', $this->getData('subscriptionPhone'), 'string');
		$journalSettingsDao->updateSetting($journalId, 'subscriptionMailingAddress', $this->getData('subscriptionMailingAddress'), 'string');
		$journalSettingsDao->updateSetting($journalId, 'subscriptionAdditionalInformation', $this->getData('subscriptionAdditionalInformation'), 'string', true); // Localized
		$journalSettingsDao->updateSetting($journalId, 'delayedOpenAccessDuration', $this->getData('delayedOpenAccessDuration'), 'int');
		$journalSettingsDao->updateSetting($journalId, 'delayedOpenAccessPolicy', $this->getData('delayedOpenAccessPolicy'), 'string', true); // Localized
		$journalSettingsDao->updateSetting($journalId, 'enableOpenAccessNotification', $this->getData('enableOpenAccessNotification') == null ? 0 : $this->getData('enableOpenAccessNotification'), 'bool');
		$journalSettingsDao->updateSetting($journalId, 'subscriptionExpiryPartial', $this->getData('subscriptionExpiryPartial') == null ? 0 : $this->getData('subscriptionExpiryPartial'), 'bool');
		$journalSettingsDao->updateSetting($journalId, 'enableSubscriptionOnlinePaymentNotificationPurchaseIndividual', $this->getData('enableSubscriptionOnlinePaymentNotificationPurchaseIndividual') == null ? 0 : $this->getData('enableSubscriptionOnlinePaymentNotificationPurchaseIndividual'), 'bool');
		$journalSettingsDao->updateSetting($journalId, 'enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional', $this->getData('enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional') == null ? 0 : $this->getData('enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional'), 'bool');
		$journalSettingsDao->updateSetting($journalId, 'enableSubscriptionOnlinePaymentNotificationRenewIndividual', $this->getData('enableSubscriptionOnlinePaymentNotificationRenewIndividual') == null ? 0 : $this->getData('enableSubscriptionOnlinePaymentNotificationRenewIndividual'), 'bool');
		$journalSettingsDao->updateSetting($journalId, 'enableSubscriptionOnlinePaymentNotificationRenewInstitutional', $this->getData('enableSubscriptionOnlinePaymentNotificationRenewInstitutional') == null ? 0 : $this->getData('enableSubscriptionOnlinePaymentNotificationRenewInstitutional'), 'bool');
		$journalSettingsDao->updateSetting($journalId, 'numMonthsBeforeSubscriptionExpiryReminder', $this->getData('numMonthsBeforeSubscriptionExpiryReminder'), 'int');
		$journalSettingsDao->updateSetting($journalId, 'numWeeksBeforeSubscriptionExpiryReminder', $this->getData('numWeeksBeforeSubscriptionExpiryReminder'), 'int');
		$journalSettingsDao->updateSetting($journalId, 'numMonthsAfterSubscriptionExpiryReminder', $this->getData('numMonthsAfterSubscriptionExpiryReminder'), 'int');
		$journalSettingsDao->updateSetting($journalId, 'numWeeksAfterSubscriptionExpiryReminder', $this->getData('numWeeksAfterSubscriptionExpiryReminder'), 'int');
	}
}


