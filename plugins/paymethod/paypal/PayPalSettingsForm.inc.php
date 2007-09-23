<?php

/**
 * @file PayPalSettingsForm.inc.php
 *
 * Copyright (c) 2006-2007 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.paymethod.paypal
 * @class PayPalSettingsForm
 *
 * Form for conference managers to edit the PayPal Settings
 * 
 */

import('form.Form');

class PayPalSettingsForm extends Form {
	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/** $var $errors string */
	var $errors;
	
	/**
	 * Constructor
	 * @param $journalId int
	 */
	function PayPalSettingsForm(&$plugin, $journalId) {
		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');
	
		$this->journalId = $journalId;
		$this->plugin =& $plugin;
	}
	
	
	/**
	 * Initialize form data from current group group.
	 */
	function initData( ) {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;
				
		$this->_data = array(
			'enabled' => $plugin->getSetting($journalId, 'enabled'),
			'paypalurl' => $plugin->getSetting($journalId, 'paypalurl'),
			'selleraccount' => $plugin->getSetting($journalId, 'selleraccount'),
		);

	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('enabled',
								'paypalurl', 
								'selleraccount'
								));
	}
	
	/**
	 * Save settings to the journal settings 
	 */	 
	function save() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;
				
		$paypalSettings = array();
		$plugin->updateSetting($journalId, 'enabled', $this->getData('enabled'));
		$plugin->updateSetting($journalId, 'paypalurl', $this->getData('paypalurl'));
		$plugin->updateSetting($journalId, 'selleraccount',$this->getData('selleraccount'));
	}
}

?>
