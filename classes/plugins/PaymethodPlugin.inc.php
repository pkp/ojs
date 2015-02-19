<?php

/**
 * @file classes/plugins/PaymethodPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaymethodPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for paymethod plugins
 */

import('classes.plugins.Plugin');

class PaymethodPlugin extends Plugin {
	/**
	 * Constructor
	 */
	function PaymethodPlugin() {
		parent::Plugin();
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
		$success = parent::register($category, $path);
		if ($success) {
			HookRegistry::register('Template::Manager::Payment::displayPaymentSettingsForm', array(&$this, '_smartyDisplayPaymentSettingsForm'));
		}
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category, and should be suitable for part of a filename
	 * (ie short, no spaces, and no dependencies on cases being unique).
	 * @return String name of plugin
	 */
	function getName() {
		assert(false); // Should always be overridden
	}

	/**
	 * Get a description of this plugin.
	 */
	function getDescription() {
		assert(false); // Should always be overridden
	}

	/**
	 * Get the Template path for this plugin.
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates' . DIRECTORY_SEPARATOR ;
	}

	/**
	 * Display the payment form.
	 * @param $queuedPaymentId int
	 * @param $key string
	 * @param $queuedPayment QueuedPayment
	 * @param $request PKPRequest
	 */
	function displayPaymentForm($queuedPaymentId, $key, &$queuedPayment, $request) {
		assert(false); // Should always be overridden
	}

	/**
	 * Determine whether or not the payment plugin is configured for use.
	 * @return boolean
	 */
	function isConfigured() {
		return false; // Abstract; should be implemented in subclasses
	}

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
	function displayPaymentSettingsForm(&$params, &$smarty) {
		return $smarty->fetch($this->getTemplatePath() . 'settingsForm.tpl');
	}

	/**
	 * Fetch the settings form field names.
	 * @return array
	 */
	function getSettingsFormFieldNames() {
		return array(); // Subclasses should override
	}

	/**
	 * Handle an incoming request from a user callback or an external
	 * payment processing system.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function handle($args, &$request) {
		// Subclass should override.
		$request->redirect(null, null, 'index');
	}
}

?>
