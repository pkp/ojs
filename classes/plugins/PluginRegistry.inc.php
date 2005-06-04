<?php

/**
 * PluginRegistry.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Registry class for managing plugins.
 *
 * $Id$
 */

import('plugins.Plugin');

class PluginRegistry {
	/**
	 * Return all plugins in the given category as an array, or, if the
	 * category is not specified, all plugins in an associative array of
	 * arrays by category.
	 * @param $category String the name of the category to retrieve
	 */
	function &getPlugins($category = null) {
		$plugins = &Registry::get('plugins');
		if ($category !== null) return $plugins[$category];
		return $plugins;
	}

	/**
	 * Register a plugin with the registry in the given category.
	 * @param $category String the name of the category to extend
	 * @param $plugin The instantiated plugin to add
	 * @param $path The path the plugin was found in
	 * @return boolean True IFF the plugin was registered successfully
	 */
	function register($category, $plugin, $path) {
		if (!$plugin->register($category, $path)) {
			return false;
		}
		$plugins = &PluginRegistry::getPlugins();
		if (!$plugins) {
			// The plugin system hasn't been initialized yet;
			// Create an empty array in the Registry for its use.
			$plugins = array();
			Registry::set('plugins', &$plugins);
		}

		if (isset($plugins[$category])) $plugins[$category][$plugin->getName()] = &$plugin;
		else $plugins[$category] = array($plugin->getName() => &$plugin);
		return true;
	}

	/**
	 * Get a plugin by name.
	 * @param $category String category name
	 * @param $name String plugin name
	 */
	function &getPlugin ($category, $name) {
		$plugins = &PluginRegistry::getPlugins();
		$plugin = @$plugins[$category][$name];
		return $plugin;
	}

	/**
	 * Load all plugins for a given category.
	 * @param $category String The name of the category to load
	 */
	function loadCategory ($category) {
		// Check if the category is already loaded. If so, don't
		// load it again.
		if (PluginRegistry::getPlugins($category)!=null) return;

		$categoryDir = "plugins/$category";
		$handle = opendir($categoryDir);
		while (($file = readdir($handle)) !== false) {
			if ($file != '.' && $file != '..') {
				$pluginPath = "$categoryDir/$file";
				$plugin = require("$pluginPath/index.php");
				PluginRegistry::register($category, $plugin, $pluginPath);
			}
		}
		closedir($handle);
	}
}
?>
