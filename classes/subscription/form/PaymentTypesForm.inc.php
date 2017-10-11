<?php

/**
 * @file classes/subscription/form/PaymentTypesForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaymentTypesForm
 * @ingroup subscription
 *
 * @brief Permit configuration of the various payment types.
 */

import('lib.pkp.classes.form.Form');

class PaymentTypesForm extends Form {
	/** @var array the setting names */
	var $settings;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct('subscriptions/paymentTypesForm.tpl');

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER);

		$this->settings = array(
			'publicationFee' => 'float',
			'purchaseArticleFeeEnabled' => 'bool',
			'purchaseArticleFee' => 'float',
			'purchaseIssueFeeEnabled' => 'bool',
			'purchaseIssueFee' => 'float',
			'membershipFee' => 'float',
			'restrictOnlyPdf' => 'bool',
		);

		$this->addCheck(new FormValidatorCustom($this, 'publicationFee', 'optional', 'manager.payment.form.numeric', create_function('$publicationFee', 'return is_numeric($publicationFee) && $publicationFee >= 0;')));
		$this->addCheck(new FormValidatorCustom($this, 'purchaseArticleFee', 'optional', 'manager.payment.form.numeric', create_function('$purchaseArticleFee', 'return is_numeric($purchaseArticleFee) && $purchaseArticleFee >= 0;')));
		$this->addCheck(new FormValidatorCustom($this, 'purchaseIssueFee', 'optional', 'manager.payment.form.numeric', create_function('$purchaseIssueFee', 'return is_numeric($purchaseIssueFee) && $purchaseIssueFee >= 0;')));
		$this->addCheck(new FormValidatorCustom($this, 'membershipFee', 'optional', 'manager.payment.form.numeric', create_function('$membershipFee', 'return is_numeric($membershipFee) && $membershipFee >= 0;')));
	}

	/**
	 * Initialize form data from current group group.
	 */
	function initData($journal) {
		$this->_data = array(
			'journalPaymentsEnabled' => $journal->getSetting('journalPaymentsEnabled'),
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array_keys($this->settings));
	}

	/**
	 * Save settings
	 */
	function save() {
		$settingsDao =& DAORegistry::getDAO('JournalSettingsDAO');

		foreach ($this->_data as $name => $value) {
			$isLocalized = in_array($name, $this->getLocaleFieldNames());
			$settingsDao->updateSetting(
				$journal->getId(),
				$name,
				$value,
				$this->settings[$name],
				$isLocalized
			);
		}
	}
}

?>
