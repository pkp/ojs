<?php

/**
 * @file plugins/payment/paypal/PaypalPaymentSettingsForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaypalPaymentSettingsForm
 * @ingroup plugins_payment_paypal
 *
 * @brief Form for managers to configure paypal payments.
 */

import('lib.pkp.classes.form.Form');

class PaypalPaymentSettingsForm extends Form {

	/** @var int Associated context ID */
	private $_contextId;

	/** @var PaypalPaymentPlugin Paypal payment plugin */
	private $_plugin;

	/**
	 * Constructor
	 * @param $plugin PaypalPaymentPlugin Paypal payment plugin
	 * @param $contextId int Context ID
	 */
	function __construct($plugin, $contextId) {
		$this->_contextId = $contextId;
		$this->_plugin = $plugin;

		parent::__construct($plugin->getTemplatePath() . 'settingsForm.tpl');
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$contextId = $this->_contextId;
		$plugin = $this->_plugin;

		foreach (array('testMode', 'accountName') as $settingName) {
			$this->setData($settingName, $plugin->getSetting($contextId, $settingName));
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('testMode', 'accountName'));
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin = $this->_plugin;
		$contextId = $this->_contextId;
		foreach (array('testMode', 'accountName') as $settingName) {
			$plugin->updateSetting($contextId, $settingName, $this->getData($settingName));
		}
	}
}

?>
