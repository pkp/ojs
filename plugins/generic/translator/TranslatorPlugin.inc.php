<?php

/**
 * TranslatorPlugin.inc.php
 *
 * This plugin helps with translation maintenance.
 *
 * $Id$
 */
 
import('classes.plugins.GenericPlugin');

class TranslatorPlugin extends GenericPlugin {
	function register($category, $path) {
		if (!Config::getVar('general', 'installed')) return false;
		if (parent::register($category, $path)) {
			$this->addLocaleData();
			if ($this->getSetting(0, 'enabled')) {
				HookRegistry::register ('LoadHandler', array(&$this, 'handleRequest'));
				HookRegistry::register ('TinyMCEPlugin::getDisableTemplates', array(&$this, 'addTinyMCEException'));
			}
			return true;
		}
		return false;
	}

	function addTinyMCEException($hookName, $args) {
		$disableTemplates =& $args[1];
		$disableTemplates[] = $this->getTemplatePath() . 'errors.tpl';
		$disableTemplates[] = $this->getTemplatePath() . 'editMiscFile.tpl';
		$disableTemplates[] = $this->getTemplatePath() . 'localeFile.tpl';
		$disableTemplates[] = $this->getTemplatePath() . 'editEmail.tpl';
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

	function manage($verb, $args) {
		if (!Validation::isSiteAdmin()) return false;

		switch ($verb) {
			case 'enable':
				$this->updateSetting(0, 'enabled', true);
				break;
			case 'disable':
				$this->updateSetting(0, 'enabled', false);
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
