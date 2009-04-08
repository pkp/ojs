<?php

/**
 * @file TranslatorPlugin.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TranslatorPlugin
 * @ingroup plugins_generic_translator
 *
 * @brief This plugin helps with translation maintenance.
 */

// $Id$


import('classes.plugins.GenericPlugin');

class TranslatorPlugin extends GenericPlugin {
	function register($category, $path) {
		if (parent::register($category, $path)) {
			$this->addLocaleData();
			if ($this->getSetting(0, 'enabled')) {
				$this->addHelpData();
				HookRegistry::register ('LoadHandler', array(&$this, 'handleRequest'));
			}
			return true;
		}
		return false;
	}

	function handleRequest($hookName, $args) {
		$page =& $args[0];
		$op =& $args[1];
		$sourceFile =& $args[2];

		if ($page === 'translate') {
			$this->import('TranslatorHandler');
			Registry::set('plugin', $this);
			define('HANDLER_CLASS', 'TranslatorHandler');
			return true;
		}

		return false;
	}

	function getName() {
		return 'TranslatorPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.generic.translator.name');
	}

	function getDescription() {
		return Locale::translate('plugins.generic.translator.description');
	}

	function getManagementVerbs() {
		$isEnabled = $this->getSetting(0, 'enabled');

		$verbs[] = array(
			($isEnabled?'disable':'enable'),
			Locale::translate($isEnabled?'manager.plugins.disable':'manager.plugins.enable')
		);

		if ($isEnabled) $verbs[] = array(
			'translate',
			Locale::translate('plugins.generic.translator.translate')
		);

		return $verbs;
	}

 	/*
 	 * Execute a management verb on this plugin
 	 * @param $verb string
 	 * @param $args array
	 * @param $message string Location for the plugin to put a result msg
 	 * @return boolean
 	 */
	function manage($verb, $args, &$message) {
		if (!Validation::isSiteAdmin()) return false;

		switch ($verb) {
			case 'enable':
				$this->updateSetting(0, 'enabled', true);
				$message = Locale::translate('plugins.generic.translator.enabled');
				break;
			case 'disable':
				$this->updateSetting(0, 'enabled', false);
				$message = Locale::translate('plugins.generic.translator.disabled');
				break;
			case 'translate':
				Request::redirect('index', 'translate');
				break;
		}
		return false;
	}

	function isSitePlugin() {
		return true;
	}
}

?>
