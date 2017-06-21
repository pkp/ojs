<?php

/**
 * @file classes/plugins/PaymethodPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaymethodPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for paymethod plugins
 */

import('lib.pkp.classes.plugins.PKPPaymethodPlugin');

abstract class PaymethodPlugin extends PKPPaymethodPlugin {

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
}

?>
