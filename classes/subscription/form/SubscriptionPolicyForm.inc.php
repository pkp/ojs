<?php

/**
 * @file classes/subscription/form/SubscriptionPolicyForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionPolicyForm
 * @ingroup manager_form
 *
 * @brief Form for managers to setup subscription policies.
 */

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
	public function __construct() {
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER);

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
	 * @copydoc Form::fetch()
	 */
	public function fetch($request, $template = null, $display = false) {
		$paymentManager = Application::getPaymentManager($request->getJournal());
		$templateMgr = TemplateManager::getManager();
		$templateMgr->assign(array(
			'validNumMonthsBeforeExpiry' => $this->validNumMonthsBeforeExpiry,
			'validNumWeeksBeforeExpiry' => $this->validNumWeeksBeforeExpiry,
			'validNumMonthsAfterExpiry' => $this->validNumMonthsAfterExpiry,
			'validNumWeeksAfterExpiry' => $this->validNumWeeksAfterExpiry,
			'scheduledTasksEnabled' => (boolean) Config::getVar('general', 'scheduled_tasks'),
			'paymentsEnabled' => $paymentManager->isConfigured(),
		));

		return parent::fetch($request, $template, $display);
	}

	/**
	 * Initialize form data from current subscription policies.
	 */
	public function initData() {
		$request = Application::get()->getRequest();
		$journal = $request->getJournal();
		$this->_data = array(
			'subscriptionName' => $journal->getData('subscriptionName'),
			'subscriptionEmail' => $journal->getData('subscriptionEmail'),
			'subscriptionPhone' => $journal->getData('subscriptionPhone'),
			'subscriptionMailingAddress' => $journal->getData('subscriptionMailingAddress'),
			'subscriptionAdditionalInformation' => $journal->getData('subscriptionAdditionalInformation'),
			'enableOpenAccessNotification' => $journal->getData('enableOpenAccessNotification'),
			'subscriptionExpiryPartial' => $journal->getData('subscriptionExpiryPartial'),
			'enableSubscriptionOnlinePaymentNotificationPurchaseIndividual' => $journal->getData('enableSubscriptionOnlinePaymentNotificationPurchaseIndividual'),
			'enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional' => $journal->getData('enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional'),
			'enableSubscriptionOnlinePaymentNotificationRenewIndividual' => $journal->getData('enableSubscriptionOnlinePaymentNotificationRenewIndividual'),
			'enableSubscriptionOnlinePaymentNotificationRenewInstitutional' => $journal->getData('enableSubscriptionOnlinePaymentNotificationRenewInstitutional'),
			'numMonthsBeforeSubscriptionExpiryReminder' => $journal->getData('numMonthsBeforeSubscriptionExpiryReminder'),
			'numWeeksBeforeSubscriptionExpiryReminder' => $journal->getData('numWeeksBeforeSubscriptionExpiryReminder'),
			'numMonthsAfterSubscriptionExpiryReminder' => $journal->getData('numMonthsAfterSubscriptionExpiryReminder'),
			'numWeeksAfterSubscriptionExpiryReminder' => $journal->getData('numWeeksAfterSubscriptionExpiryReminder'),
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	public function readInputData() {
		$this->readUserVars(array('subscriptionName', 'subscriptionEmail', 'subscriptionPhone', 'subscriptionMailingAddress', 'subscriptionAdditionalInformation', 'enableOpenAccessNotification', 'subscriptionExpiryPartial', 'enableSubscriptionOnlinePaymentNotificationPurchaseIndividual', 'enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional', 'enableSubscriptionOnlinePaymentNotificationRenewIndividual', 'enableSubscriptionOnlinePaymentNotificationRenewInstitutional', 'numMonthsBeforeSubscriptionExpiryReminder', 'numWeeksBeforeSubscriptionExpiryReminder', 'numWeeksAfterSubscriptionExpiryReminder', 'numMonthsAfterSubscriptionExpiryReminder'));
	}

	/**
	 * Get the names of the fields for which localized settings are used
	 * @return array
	 */
	public function getLocaleFieldNames() {
		return array('subscriptionAdditionalInformation');
	}

	/**
	 * @copydoc Form::execute
	 */
	public function execute(...$functionArgs) {
		$request = Application::get()->getRequest();
		$journal = $request->getJournal();

		$journal->setData('subscriptionName', $this->getData('subscriptionName'));
		$journal->setData('subscriptionEmail', $this->getData('subscriptionEmail'));
		$journal->setData('subscriptionPhone', $this->getData('subscriptionPhone'));
		$journal->setData('subscriptionMailingAddress', $this->getData('subscriptionMailingAddress'));
		$journal->setData('subscriptionAdditionalInformation', $this->getData('subscriptionAdditionalInformation')); // Localized
		$journal->setData('enableOpenAccessNotification', $this->getData('enableOpenAccessNotification') == null ? 0 : $this->getData('enableOpenAccessNotification'));
		$journal->setData('subscriptionExpiryPartial', $this->getData('subscriptionExpiryPartial') == null ? 0 : $this->getData('subscriptionExpiryPartial'));
		$journal->setData('enableSubscriptionOnlinePaymentNotificationPurchaseIndividual', $this->getData('enableSubscriptionOnlinePaymentNotificationPurchaseIndividual') == null ? 0 : $this->getData('enableSubscriptionOnlinePaymentNotificationPurchaseIndividual'));
		$journal->setData('enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional', $this->getData('enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional') == null ? 0 : $this->getData('enableSubscriptionOnlinePaymentNotificationPurchaseInstitutional'));
		$journal->setData('enableSubscriptionOnlinePaymentNotificationRenewIndividual', $this->getData('enableSubscriptionOnlinePaymentNotificationRenewIndividual') == null ? 0 : $this->getData('enableSubscriptionOnlinePaymentNotificationRenewIndividual'));
		$journal->setData('enableSubscriptionOnlinePaymentNotificationRenewInstitutional', $this->getData('enableSubscriptionOnlinePaymentNotificationRenewInstitutional') == null ? 0 : $this->getData('enableSubscriptionOnlinePaymentNotificationRenewInstitutional'));
		$journal->setData('numMonthsBeforeSubscriptionExpiryReminder', $this->getData('numMonthsBeforeSubscriptionExpiryReminder'));
		$journal->setData('numWeeksBeforeSubscriptionExpiryReminder', $this->getData('numWeeksBeforeSubscriptionExpiryReminder'));
		$journal->setData('numMonthsAfterSubscriptionExpiryReminder', $this->getData('numMonthsAfterSubscriptionExpiryReminder'));
		$journal->setData('numWeeksAfterSubscriptionExpiryReminder', $this->getData('numWeeksAfterSubscriptionExpiryReminder'));

		parent::execute(...$functionArgs);

		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$journalDao->updateObject($journal);
	}
}
