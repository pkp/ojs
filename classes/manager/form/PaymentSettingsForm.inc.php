<?php

/**
 * @file PaymentSettingsForm.inc.php
 *
 * Copyright (c) 2006 Gunther Eysenbach, Juan Pablo Alperin
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 * @class PaymentSettingsForm
 *
 * Form for journal managers to modify Payment costs and settings
 * 
 */

import('form.Form');

class PaymentSettingsForm extends Form {
	/** @var validCurrencies array keys are valid subscription type currencies */	
	var $validCurrencies;
	
	/** @var array the setting names */	
	var $settings;
	
	/** $var $errors string */
	var $errors;
	
	/**
	 * Constructor
	 * @param $journalId int
	 */
	function PaymentSettingsForm() {

		parent::Form('manager/payments/paymentSettings.tpl');
	
		$this->settings = array(
							'journalPaymentsEnabled' => 'bool',
							'currency' => 'string',
							'submissionFee' => 'float', 
							'publicationFee' => 'float', 
							'fastTrackFee' => 'float', 
						  	'payPerViewFee' => 'float', 
							'membershipFee' => 'float', 
							'restrictOnlyPdf' => 'bool', 
						  	'acceptSubscriptionPayments' => 'bool',
							'acceptDonationPayments' => 'bool'
		);
		
		$this->addCheck(new FormValidatorCustom($this, 'submissionFee', 'optional', 'manager.payment.form.numeric', create_function('$submissionFee', 'return is_numeric($submissionFee) && $submissionFee >= 0;')));
		$this->addCheck(new FormValidatorCustom($this, 'publicationFee', 'optional', 'manager.payment.form.numeric', create_function('$publicationFee', 'return is_numeric($publicationFee) && $publicationFee >= 0;')));
		$this->addCheck(new FormValidatorCustom($this, 'fastTrackFee', 'optional', 'manager.payment.form.numeric', create_function('$fastTrackFee', 'return is_numeric($fastTrackFee) && $fastTrackFee >= 0;')));
		$this->addCheck(new FormValidatorCustom($this, 'payPerViewFee', 'optional', 'manager.payment.form.numeric', create_function('$payPerViewFee', 'return is_numeric($payPerViewFee) && $payPerViewFee >= 0;')));
		$this->addCheck(new FormValidatorCustom($this, 'membershipFee', 'optional', 'manager.payment.form.numeric', create_function('$membershipFee', 'return is_numeric($membershipFee) && $membershipFee >= 0;')));
	
		// grab valid currencies and add Validator	
		$currencyDao = &DAORegistry::getDAO('CurrencyDAO');
		$currencies = &$currencyDao->getCurrencies();
		$this->validCurrencies = array();
		while (list(, $currency) = each($currencies)) {
			$this->validCurrencies[$currency->getCodeAlpha()] = $currency->getName() . ' (' . $currency->getCodeAlpha() . ')';
		}
	
		// Currency is provided and is valid value
		$this->addCheck(new FormValidator($this, 'currency', 'required', 'manager.subscriptionTypes.form.currencyRequired'));	
		$this->addCheck(new FormValidatorInSet($this, 'currency', 'required', 'manager.subscriptionTypes.form.currencyValid', array_keys($this->validCurrencies)));

	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('validCurrencies', $this->validCurrencies);
		parent::display();
	}
	
	/**
	 * Initialize form data from current group group.
	 */
	function initData() {
		$journal = &Request::getJournal();
		foreach ($this->settings as $settingName => $settingType) {
			$this->_data[$settingName] = $journal->getSetting($settingName);
		}			
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(	'journalPaymentsEnabled',
									'currency',
									'submissionFee', 
									'publicationFee', 
									'fastTrackFee', 
								  	'payPerViewFee', 
									'membershipFee', 
									'restrictOnlyPdf', 
								  	'acceptSubscriptionPayments',
									'acceptDonationPayments'
								  ));
	}
	
	/**
	 * Save settings 
	 */	 
	function save() {
		$journal = &Request::getJournal();
		$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		
		foreach ($this->_data as $name => $value) {
			$settingsDao->updateSetting(
				$journal->getJournalId(),
				$name,
				$value,
				$this->settings[$name]
			);
		}
		
	}

}
?>
