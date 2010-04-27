<?php

/**
 * @file plugins/generic/sword/SwordPlugin.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SwordPlugin
 * @ingroup plugins_generic_sword
 *
 * @brief SWORD deposit plugin class
 */

// $Id$


define('SWORD_DEPOSIT_TYPE_AUTOMATIC',		1);
define('SWORD_DEPOSIT_TYPE_OPTIONAL_SELECTION',	2);
define('SWORD_DEPOSIT_TYPE_OPTIONAL_FIXED',	3);
define('SWORD_DEPOSIT_TYPE_MANAGER',		4);

import('classes.plugins.GenericPlugin');

class SwordPlugin extends GenericPlugin {
	/**
	 * Get the symbolic name of this plugin
	 * @return string
	 */
	function getName() {
		return 'SwordPlugin';
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return Locale::translate('plugins.generic.sword.displayName');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return Locale::translate('plugins.generic.sword.description');
	}

	function register($category, $path) {
		if (parent::register($category, $path)) {
			HookRegistry::register('PluginRegistry::loadCategory', array(&$this, 'callbackLoadCategory'));
			if ($this->getEnabled()) {
			}
			$this->addLocaleData();
			return true;
		}
		return false;
	}

	/**
	 * Check whether or not this plugin is enabled
	 * @return boolean
	 */
	function getEnabled() {
		$journal =& Request::getJournal();
		$journalId = $journal?$journal->getId():0;
		return $this->getSetting($journalId, 'enabled');
	}

	/**
	 * Register as a block plugin, even though this is a generic plugin.
	 * This will allow the plugin to behave as a block plugin, i.e. to
	 * have layout tasks performed on it.
	 * @param $hookName string
	 * @param $args array
	 */
	function callbackLoadCategory($hookName, $args) {
		$category =& $args[0];
		$plugins =& $args[1];
		switch ($category) {
			case 'importexport':
				$this->import('SwordImportExportPlugin');
				$importExportPlugin = new SwordImportExportPlugin();
				$plugins[$importExportPlugin->getSeq()][$importExportPlugin->getPluginPath()] =& $importExportPlugin;
				break;
		}
		return false;
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array(
				'disable',
				Locale::translate('manager.plugins.disable')
			);
			$verbs[] = array(
				'settings',
				Locale::translate('plugins.generic.sword.settings')
			);
		} else {
			$verbs[] = array(
				'enable',
				Locale::translate('manager.plugins.enable')
			);
		}
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
		$returner = true;
		$journal =& Request::getJournal();

		switch ($verb) {
			case 'settings':
				Locale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON,  LOCALE_COMPONENT_PKP_MANAGER));
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));

				$this->import('SettingsForm');
				$form = new SettingsForm($this, $journal->getId());

				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						Request::redirect(null, null, 'plugins');
					} else {
						$form->display();
					}
				} else {
					$form->initData();
					$form->display();
				}
				break;
			case 'enable':
				$this->updateSetting($journal->getId(), 'enabled', true);
				$message = Locale::translate('plugins.generic.sword.enabled');
				$returner = false;
				break;
			case 'disable':
				$this->updateSetting($journal->getId(), 'enabled', false);
				$message = Locale::translate('plugins.generic.sword.disabled');
				$returner = false;
				break;
			case 'createDepositPoint':
			case 'editDepositPoint':
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));

				$depositPointId = array_shift($args);
				if ($depositPointId == '') $depositPointId = null;
				else $depositPointId = (int) $depositPointId;
				$this->import('DepositPointForm');
				$form = new DepositPointForm($this, $journal->getId(), $depositPointId);

				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						Request::redirect(null, null, null, array('generic', 'SwordPlugin', 'settings'));
					} else {
						$form->display();
					}
				} else {
					$form->initData();
					$form->display();
				}
				break;
			case 'deleteDepositPoint':
				$journalId = $journal->getId();
				$depositPointId = (int) array_shift($args);
				$depositPoints = $this->getSetting($journalId, 'depositPoints');
				unset($depositPoints[$depositPointId]);
				$this->updateSetting($journalId, 'depositPoints', $depositPoints);
				Request::redirect(null, null, null, array('generic', 'SwordPlugin', 'settings'));
				break;
		}

		return $returner;
	}

	function getTypeMap() {
		return array(
			SWORD_DEPOSIT_TYPE_AUTOMATIC => 'plugins.generic.sword.depositPoints.type.automatic',
			SWORD_DEPOSIT_TYPE_OPTIONAL_SELECTION => 'plugins.generic.sword.depositPoints.type.optionalSelection',
			SWORD_DEPOSIT_TYPE_OPTIONAL_FIXED => 'plugins.generic.sword.depositPoints.type.optionalFixed',
			SWORD_DEPOSIT_TYPE_MANAGER => 'plugins.generic.sword.depositPoints.type.manager'
		);
	}
}

?>
