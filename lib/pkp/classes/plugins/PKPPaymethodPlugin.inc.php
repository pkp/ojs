<?php

/**
 * @file classes/plugins/PKPPaymethodPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPPaymethodPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for paymethod plugins
 */

import('lib.pkp.classes.plugins.Plugin');

abstract class PKPPaymethodPlugin extends Plugin {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Called as a plugin is registered to the registry. Subclasses over-
	 * riding this method should call the parent method first.
	 * @param $category String Name of category plugin was registered to
	 * @param $path String The path the plugin was found in
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		if (!parent::register($category, $path)) return false;
		HookRegistry::register('Template::Manager::Payment::displayPaymentSettingsForm', array($this, '_smartyDisplayPaymentSettingsForm'));
		return true;
	}

	/**
	 * @copydoc Plugin::getTemplatePath($inCore)
	 */
	function getTemplatePath($inCore = false) {
		return parent::getTemplatePath($inCore) . 'templates/';
	}

	/**
	 * Display the payment form.
	 * @param $queuedPaymentId int
	 * @param $queuedPayment QueuedPayment
	 * @param $request PKPRequest
	 */
	abstract function displayPaymentForm($queuedPaymentId, $queuedPayment, $request);

	/**
	 * Determine whether or not the payment plugin is configured for use.
	 * @return boolean
	 */
	abstract function isConfigured();

	/**
	 * This is a hook wrapper that is responsible for calling
	 * displayPaymentSettingsForm. Subclasses should override
	 * displayPaymentSettingsForm as necessary.
	 * @param $hookName string
	 * @param $args array
	 */
	function _smartyDisplayPaymentSettingsForm($hookName, $args) {
		$params =& $args[0];
		$smarty =& $args[1];
		$output =& $args[2];

		if (isset($params['plugin']) && $params['plugin'] == $this->getName()) {
			$output .= $this->displayPaymentSettingsForm($params, $smarty);
		}
		return false;
	}

	/**
	 * Display the payment settings form.
	 * @param $params array
	 * @param $smarty Smarty
	 */
	function displayPaymentSettingsForm(&$params, $smarty) {
		return $smarty->fetch($this->getTemplatePath() . 'settingsForm.tpl');
	}

	/**
	 * Fetch the settings form field names.
	 * @return array
	 */
	function getSettingsFormFieldNames() {
		return array(); // Default, usually overridden
	}

	/**
	 * Fetch the required form field names.
	 * @return array
	 */
	function getRequiredSettingsFormFieldNames() {
		return $this->getSettingsFormFieldNames();
	}

	/**
	 * Handle an incoming request from a user callback or an external
	 * payment processing system.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function handle($args, $request) {
		assert(false); // Unhandled case; should never happen.
	}
}

?>
