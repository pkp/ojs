<?php

/**
 * @file CustomThemePlugin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CustomThemePlugin
 * @ingroup plugins_themes_uncommon
 *
 * @brief "Custom" theme plugin
 */

// $Id$


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

	function getDisplayName() {
		return Locale::translate('plugins.theme.custom.name');
	}

	function getDescription() {
		return Locale::translate('plugins.theme.custom.description');
	}

	function getStylesheetFilename() {
		return 'custom.css';
	}

	function getManagementVerbs() {
		return array(array('settings', Locale::translate('plugins.theme.custom.settings')));
	}

	/**
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 * @param $subclass boolean
	 */
	function setBreadcrumbs($isSubclass = false) {
		$templateMgr = &TemplateManager::getManager();
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
		if ($isSubclass) $pageCrumbs[] = array(
			Request::url(null, 'manager', 'plugins'),
			'manager.plugins'
		);

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
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

	function manage($verb) {
		if ($verb != 'settings') return false;

		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();

		$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
		$templateMgr->setCacheability(CACHEABILITY_MUST_REVALIDATE);

		$this->import('CustomThemeSettingsForm');
		$form = &new CustomThemeSettingsForm($this, $journal->getJournalId());
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
}

?>
