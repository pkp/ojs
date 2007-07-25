<?php

/**
 * @file PluginRegistry.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 * @class PluginRegistry
 *
 * Registry class for managing plugins.
 *
 * $Id$
 */

import('plugins.Plugin');

define('PLUGINS_PREFIX', 'plugins/');

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
	 * Get all plugins in a single array.
	 */
	function &getAllPlugins() {
		$plugins =& PluginRegistry::getPlugins();
		$allPlugins = array();
		if (is_array($plugins)) foreach ($plugins as $category => $list) {
			if (is_array($list)) $allPlugins += $list;
		}
		return $allPlugins;
	}

	/**
	 * Register a plugin with the registry in the given category.
	 * @param $category String the name of the category to extend
	 * @param $plugin The instantiated plugin to add
	 * @param $path The path the plugin was found in
	 * @return boolean True IFF the plugin was registered successfully
	 */
	function register($category, &$plugin, $path) {
		$pluginName = $plugin->getName();
		$plugins =& PluginRegistry::getPlugins();
		if (!$plugins) $plugins = array();

		// If the plugin was already loaded, do not load it again.
		if (isset($plugins[$category][$pluginName])) return false;

		// Allow the plugin to register.
		if (!$plugin->register($category, $path)) return false;

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
	 * @param $forceLoad boolean Whether or not to force loading of the
	 * category (since if e.g. a single plugin is already registered, the
	 * current set will be returned rather than attempting to load others)
	 */
	function &loadCategory ($category, $forceLoad = false) {
		$plugins = array();
		$categoryDir = PLUGINS_PREFIX . $category;
		if (!is_dir($categoryDir)) return $plugins;

		$handle = opendir($categoryDir);
		while (($file = readdir($handle)) !== false) {
			if ($file == '.' || $file == '..') continue;
			$pluginPath = "$categoryDir/$file";

			// If the plugin is returned when we try to
			// include $pluginPath/index.php, register it;
			// note that there may be valid cases where
			// errors must be suppressed (e.g. "CVS").
			$plugin = @include("$pluginPath/index.php");
			if ($plugin && is_object($plugin)) {
				$plugins[$plugin->getSeq()][$pluginPath] =& $plugin;
				unset($plugin);
			}
		}
		closedir($handle);

		// If anyone else wants to jump category, here is the chance.
		HookRegistry::call('PluginRegistry::loadCategory', array(&$category, &$plugins));

		// Register the plugins in sequence.
		ksort($plugins);
		foreach ($plugins as $seq => $junk1) {
			foreach ($plugins[$seq] as $pluginPath => $junk2) {
				PluginRegistry::register($category, $plugins[$seq][$pluginPath], $pluginPath);
			}
		}
		unset($plugins);

		// Return the list of successfully-registered plugins.
		$plugins = &PluginRegistry::getPlugins($category);
		return $plugins;
	}

	/**
	 * Load a specific plugin from a category by path name.
	 * Similar to loadCategory, except that it only loads a single plugin
	 * within a category rather than loading all.
	 * @param $category string
	 * @param $pathName string
	 * @return object
	 */
	function &loadPlugin($category, $pathName) {
		$pluginPath = PLUGINS_PREFIX . $category . '/' . $pathName;
		$plugin = null;
		if (!file_exists($pluginPath . '/index.php')) return $plugin;

		$plugin = @include("$pluginPath/index.php");
		if ($plugin && is_object($plugin)) {
			PluginRegistry::register($category, $plugin, $pluginPath);
		}
		return $plugin;
	}

	/**
	 * Get a list of the various plugin categories available.
	 */
	function getCategories() {
		$categories = array(
			'generic',
			'auth',
			'importexport',
			'gateways',
			'blocks',
			'citationFormats',
			'themes'
		);
		HookRegistry::call('PluginRegistry::getCategories', array(&$categories));
		return $categories;
	}

	/**
	 * Load all plugins in the system and return them in a single array.
	 */
	function &loadAllPlugins() {
		foreach (PluginRegistry::getCategories() as $category) {
			PluginRegistry::loadCategory($category);
		}
		return PluginRegistry::getAllPlugins();
	}
}
?>
