<?php

/**
 * Plugin.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Abstract class for plugins
 *
 * $Id$
 */

class Plugin {
	/** @var $pluginPath String Path name to files for this plugin */
	var $pluginPath;

	/** @var $pluginCategory String Category name this plugin is registered to*/
	var $pluginCategory;

	/**
	 * Constructor
	 */
	function Plugin() {
	}

	/**
	 * Get the path this plugin's files are located in.
	 * @return String pathname
	 */
	function getPluginPath() {
		return $this->pluginPath;
	}

	/**
	 * Get the name of the category this plugin is registered to.
	 * @return String category
	 */
	function getCategory() {
		return $this->pluginCategory;
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
		$this->pluginPath = $path;
		$this->pluginCategory = $category;
		return true;
	}

	function addLocaleData($locale = null) {
		if ($locale == '') $locale = Locale::getLocale();
		$localeFile =& Locale::registerLocaleFile($locale, $this->getLocaleFilename($locale));
		return true;
	}

	function getLocaleFilename($locale) {
		return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . 'locale.xml';
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category, and should be suitable for part of a filename
	 * (ie short, no spaces, and no dependencies on cases being unique).
	 * @return String name of plugin
	 */
	function getName() {
		return 'Plugin';
	}

	function getDisplayName() {
		return $this->getName();
	}

	/**
	 * Get a description of this plugin.
	 */
	function getDescription() {
		return 'This is the base plugin class. It contains no concrete implementation. Its functions must be overridden by subclasses to provide actual functionality.';
	}

	function getTemplatePath() {
		$basePath = dirname(dirname(dirname(__FILE__)));
		return "file:$basePath/" . $this->getPluginPath() . '/';
	}

	function import($class) {
		require_once($this->getPluginPath() . '/' . str_replace('.', '/', $class) . '.inc.php');
	}

	function getSetting($journalId, $name) {
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		return $pluginSettingsDao->getSetting($journalId, $this->getName(), $name);
	}

	/**
	 * Update a plugin setting.
	 * @param $journalId int
	 * @param $name string The name of the setting
	 * @param $value mixed
	 * @param $type string optional
	 */
	function updateSetting($journalId, $name, $value, $type = null) {
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		$pluginSettingsDao->updateSetting($journalId, $this->getName(), $name, $value, $type);
	}

	/**
	 * Site-wide plugins should override this function to return true.
	 */
	function isSitePlugin() {
		return false;
	}

	/**
	 * Get a list of management actions in the form of a page => value pair.
	 * The management actions from this list are passed to the manage() function
	 * when called.
	 */
	function getManagementVerbs() {
		return null;
	}

	/**
	 * Perform a management function.
	 */
	function manage($verb, $args) {
		return false;
	}

	/**
	 * Extend the {url ...} smarty to support plugins.
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
		return $smarty->smartyUrl($params, $smarty);
	}
	
}
?>
