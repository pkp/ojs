<?php

/**
 * @file classes/payments/ojs/form/PaymentSettingsForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PayMethodSettingsForm
 * @ingroup payments 
 *
 * @brief Form for managers to modify Payment Plugin settings
 *
 */

import('lib.pkp.classes.form.Form');

class PayMethodSettingsForm extends Form {
	/** @var $errors string */
	var $errors;

	/** @var $plugins array */
	var $plugins;

	/**
	 * Constructor
	 */
	function PayMethodSettingsForm() {
		parent::Form('payments/payMethodSettingsForm.tpl');

		// Load the plugins.
		$this->plugins =& PluginRegistry::loadCategory('paymethod');

		// Add form checks
		$this->addCheck(new FormValidatorInSet($this, 'paymentMethodPluginName', 'optional', 'manager.payment.paymentPluginInvalid', array_keys($this->plugins)));

	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('paymentMethodPlugins', $this->plugins);
		parent::display();
	}

	/**
	 * Initialize form data from current group group.
	 */
	function initData() {
		$journal =& Request::getJournal();

		// Allow the current selection to supercede the stored value
		$paymentMethodPluginName = Request::getUserVar('paymentMethodPluginName');
		if (empty($paymentMethodPluginName) || !in_array($paymentMethodPluginName, array_keys($this->plugins))) {
			$paymentMethodPluginName = $journal->getSetting('paymentMethodPluginName');
		}

		$this->_data = array(
			'paymentMethodPluginName' => $paymentMethodPluginName
		);

		if (isset($this->plugins[$paymentMethodPluginName])) {
			$plugin =& $this->plugins[$paymentMethodPluginName];
			foreach ($plugin->getSettingsFormFieldNames() as $field) {
				$this->_data[$field] = $plugin->getSetting($journal->getId(), $field);
			}
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'paymentMethodPluginName'
		));

		$paymentMethodPluginName = $this->getData('paymentMethodPluginName');
		if (isset($this->plugins[$paymentMethodPluginName])) {
			$plugin =& $this->plugins[$paymentMethodPluginName];
			$this->readUserVars($plugin->getSettingsFormFieldNames());
		}

	}

	/**
	 * Save settings
	 */
	function execute() {
		$journal =& Request::getJournal();
		// Save the general settings for the form
		foreach (array('paymentMethodPluginName') as $journalSettingName) {
			$journal->updateSetting($journalSettingName, $this->getData($journalSettingName));
		}

		// Save the specific settings for the plugin
		$paymentMethodPluginName = $this->getData('paymentMethodPluginName');
		if (isset($this->plugins[$paymentMethodPluginName])) {
			$plugin =& $this->plugins[$paymentMethodPluginName];
			foreach ($plugin->getSettingsFormFieldNames() as $field) {
				$plugin->updateSetting($journal->getId(), $field, $this->getData($field));
			}
		}
	}
}

?>
