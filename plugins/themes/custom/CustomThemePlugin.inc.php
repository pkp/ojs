<?php

/**
 * @file plugins/themes/custom/CustomThemePlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CustomThemePlugin
 * @ingroup plugins_themes_uncommon
 *
 * @brief "Custom" theme plugin
 */

import('classes.plugins.ThemePlugin');

class CustomThemePlugin extends ThemePlugin {
	/**
	 * Register the plugin, if enabled; note that this plugin
	 * runs under both Journal and Site contexts.
	 * @param $category string
	 * @param $path string
	 * @return boolean
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			HookRegistry::register ('Installer::postInstall', array($this, 'checkOldStyleLocation'));
			$this->addLocaleData();
			return true;
		}
		return false;
	}
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'CustomThemePlugin';
	}

	function getDisplayName() {
		return __('plugins.theme.custom.name');
	}

	function getDescription() {
		return __('plugins.theme.custom.description');
	}

	/**
	 * Get the stylesheet filename.
	 * @param $includingNonexistent boolean optional True if the function
	 * should return a filename even if the file doesn't exist.
	 */
	function getStylesheetFilename($includingNonexistent = false) {
		$journal = $this->getRequest()->getJournal();
		$journalId = (int) ($journal?$journal->getId():0);
		$filename = 'css/custom-' . $journalId . '.css';
		if ($includingNonexistent || file_exists($this->getPluginPath() . '/' . $filename)) {
			return $filename;
		}
		return null;
	}

	function getManagementVerbs() {
		return array(array('settings', __('plugins.theme.custom.settings')));
	}

	/**
	 * Extend the {url ...} smarty to support this plugin.
	 */
	function smartyPluginUrl($params, &$smarty) {
		$path = array($this->getCategory(), $this->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}

		if (!empty($params['id'])) {
			$params['path'] = array_merge($params['path'], array($params['id']));
			unset($params['id']);
		}
		return $smarty->smartyUrl($params, $smarty);
	}

 	/**
	 * @see PKPPlugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams, &$pluginModalContent = null) {
		if ($verb != 'settings') return false;

		$request = $this->getRequest();
		$journal = $request->getJournal();
		$templateMgr = TemplateManager::getManager($request);

		$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));
		$templateMgr->setCacheability(CACHEABILITY_MUST_REVALIDATE);

		$this->import('CustomThemeSettingsForm');
		$form = new CustomThemeSettingsForm($this, $journal->getId());
		if ($request->getUserVar('save')) {
			$form->readInputData();
			if ($form->validate()) {
				$form->execute();
				$request->redirect(null, 'manager', 'plugin', array('themes', 'CustomThemePlugin', 'settings'));
			} else {
				$form->display($request);
			}
		} else {
			$form->initData();
			$form->display($request);
		}

		return true;
	}

	/**
	 * Callback used to potentially upgrade the location of the CSS file.
	 *
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function checkOldStyleLocation($hookName, $args) {
		$installer =& $args[0];
		$result =& $args[1];

		$sourceFilename = $this->getPluginPath() . '/custom.css';

		// Check that migration needs to occur
		if (!file_exists($sourceFilename)) {
			return false;
		}

		// Check that the target dir exists and is writable
		$targetDir = $this->getPluginPath() . '/css';
		if (!is_dir($targetDir)) {
			if (!mkdir($targetDir)) {
				$installer->log("WARNING: Could not create \"$targetDir\". You will need to migrate your custom theme plugin stylesheets manually.");
				return false;
			}
		}
		if (!is_writable($targetDir)) {
			$installer->log("WARNING: Cannot write to \"$targetDir\". You will need to migrate your custom theme plugin stylesheets manually.");
			return false;
		}

		// Duplicate the stylesheet for journals that use this plugin.
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journals = $journalDao->getAll();
		$success = true;
		while ($journal = $journals->next()) {
			if ($journal->getSetting('journalTheme') == 'custom') {
				if (!copy($sourceFilename, $targetDir . '/custom-' . ((int) $journal->getId()) . '.css')) {
					$success = false;
				}
			}
		}
		if ($success) {
			if (!unlink($this->getPluginPath() . '/custom.css')) {
				$installer->log("WARNING: The custom theme plugin custom.css file could not be removed. Please remove " . $sourceFilename . " manually.");
			}
		} else {
			$installer->log("WARNING: One or more custom theme stylesheets could not be migrated.\n");
		}

		return false;
	}
}

?>
