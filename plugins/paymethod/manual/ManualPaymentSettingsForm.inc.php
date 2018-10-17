<?php

/**
 * @file plugins/payment/manual/ManualPaymentSettingsForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManualPaymentSettingsForm
 * @ingroup plugins_payment_manual
 *
 * @brief Form for managers to configure manual payments.
 */

import('lib.pkp.classes.form.Form');

class ManualPaymentSettingsForm extends Form {

	/** @var int Associated context ID */
	private $_contextId;

	/** @var ManualPaymentPlugin Manual payment plugin */
	private $_plugin;

	/**
	 * Constructor
	 * @param $plugin ManualPaymentPlugin Manual payment plugin
	 * @param $contextId int Context ID
	 */
	function __construct($plugin, $contextId) {
		$this->_contextId = $contextId;
		$this->_plugin = $plugin;

		parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$contextId = $this->_contextId;
		$plugin = $this->_plugin;

		$this->setData('manualInstructions', $plugin->getSetting($contextId, 'manualInstructions'));
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('manualInstructions'));
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin = $this->_plugin;
		$contextId = $this->_contextId;
		$plugin->updateSetting($contextId, 'manualInstructions', $this->getData('manualInstructions'));
	}
}


