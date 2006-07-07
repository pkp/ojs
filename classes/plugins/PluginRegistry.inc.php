<?php

/**
 * PluginRegistry.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
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
		if (!$plugins) $plugins = array();

		if (isset($plugins[$category])) $plugins[$category][$plugin->getName()] = &$plugin;
		else $plugins[$category] = array($plugin->getName() => &$plugin);
		Registry::set('plugins', $plugins);
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
	function &loadCategory ($category) {
		// Check if the category is already loaded. If so, don't
		// load it again.
		if (($plugins = &PluginRegistry::getPlugins($category))!=null) return $plugins;

		$categoryDir = 'plugins/' . $category;
		if (is_dir($categoryDir)) {
			$handle = opendir($categoryDir);
			while (($file = readdir($handle)) !== false) {
				if ($file != '.' && $file != '..') {
					$pluginPath = "$categoryDir/$file";

					// If the plugin is returned when we try to
					// include $pluginPath/index.php, register it;
					// note that there may be valid cases where
					// errors must be suppressed (e.g. the source
					// is in a CVS tree; in this case the CVS
					// directory will throw an error.)
					$plugin = @include("$pluginPath/index.php");
					if ($plugin) PluginRegistry::register($category, $plugin, $pluginPath);
				}
			}
			closedir($handle);
		}
		$plugins = &PluginRegistry::getPlugins($category);
		return $plugins;
	}

	/**
	 * Get a list of the various plugin categories available.
	 */
	function getCategories() {
		return array(
			'generic',
			'auth',
			'importexport',
			'gateways'
		);
	}
}
?>
