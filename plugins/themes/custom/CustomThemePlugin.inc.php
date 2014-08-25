<?php

/**
 * @file plugins/themes/custom/CustomThemePlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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

	/**
	 * Get the display name of this plugin.
	 * @return String display name of plugin
	 */
	function getDisplayName() {
		return __('plugins.theme.custom.name');
	}

	/**
	 * Get the description of this plugin.
	 * @return String description of plugin
	 */
	function getDescription() {
		return __('plugins.theme.custom.description');
	}

	/**
	 * Get the filename of this plugin's stylesheet.
	 * @return String stylesheet filename
	 */
	function getStylesheetFilename() {
		return 'custom.css';
	}

	/**
	 * Get the file path to this plugin's stylesheet.
	 * @return String stylesheet path
	 */
	function getStylesheetPath() {
		$journal =& Request::getJournal();
		if ($this->getSetting($journal->getId(), 'customThemePerJournal')) {
			import('classes.file.PublicFileManager');
			$fileManager = new PublicFileManager();
			return $fileManager->getJournalFilesPath($journal->getId());
		} else {
			return $this->getPluginPath();
		}
	}
	
	/**
	 * Get the available management verbs.
	 * @return array key-value pairs
	 */
	function getManagementVerbs() {
		return array(array('settings', __('plugins.theme.custom.settings')));
	}

	/**
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 * @param $subclass boolean
	 */
	function setBreadcrumbs($isSubclass = false) {
		$templateMgr =& TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				Request::url(null, 'user'),
				'navigation.user'
			),
			array(
				Request::url(null, 'manager'),
				'user.role.manager'
			)
		);
		$pageCrumbs[] = array(
			Request::url(null, 'manager', 'plugins'),
			'manager.plugins'
		);
		$pageCrumbs[] = array(
			Request::url(null, 'manager', 'plugins', 'themes'),
			'plugins.categories.themes'
		);

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}

	/**
	 * Extend the {url ...} smarty to support this plugin.
	 * @param $params array
	 * @param $smarty object reference
	 * @return string
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
	 * Manage the theme.
	 * @param $verb string management action
	 */
	function manage($verb) {
		if ($verb != 'settings') return false;

		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();

		$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
		$templateMgr->setCacheability(CACHEABILITY_MUST_REVALIDATE);

		$this->import('CustomThemeSettingsForm');
		$form = new CustomThemeSettingsForm($this, $journal->getId());
		if (Request::getUserVar('save')) {
			$form->readInputData();
			if ($form->validate()) {
				$form->execute();
				Request::redirect(null, 'manager', 'plugin', array('themes', 'CustomThemePlugin', 'settings'));
			} else {
				$this->setBreadCrumbs(true);
				$form->display();
			}
		} else {
			$this->setBreadCrumbs(true);
			$form->initData();
			$form->display();
		}

		return true;
	}
	
	/**
	 * Activate the theme.
	 * @param $templateMgr object reference
	 */
	function activate(&$templateMgr) {
		// Overrides parent::activate because path needs to be changed.
		if (($stylesheetFilename = $this->getStylesheetFilename()) != null) {
			$path = Request::getBaseUrl() . '/' . $this->getStylesheetPath() . '/' . $stylesheetFilename;
			$templateMgr->addStyleSheet($path);
		}
	}
}

?>
