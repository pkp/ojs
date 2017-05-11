<?php

/**
 * @file classes/plugins/PluginRegistry.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PluginRegistry
 * @ingroup plugins
 * @see Plugin
 *
 * @brief Registry class for managing plugins.
 */


define('PLUGINS_PREFIX', 'plugins/');

class PluginRegistry {
	//
	// Public methods
	//
	/**
	 * Return all plugins in the given category as an array, or, if the
	 * category is not specified, all plugins in an associative array of
	 * arrays by category.
	 * @param $category String the name of the category to retrieve
	 */
	static function &getPlugins($category = null) {
		$plugins =& Registry::get('plugins');
		if ($category !== null) return $plugins[$category];
		return $plugins;
	}

	/**
	 * Get all plugins in a single array.
	 */
	static function &getAllPlugins() {
		$plugins =& PluginRegistry::getPlugins();
		$allPlugins = array();
		if (is_array($plugins)) foreach ($plugins as $list) {
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
	static function register($category, &$plugin, $path) {
		$pluginName = $plugin->getName();
		$plugins =& PluginRegistry::getPlugins();
		if (!$plugins) $plugins = array();

		// If the plugin was already loaded, do not load it again.
		if (isset($plugins[$category][$pluginName])) return false;

		// Allow the plugin to register.
		if (!$plugin->register($category, $path)) return false;

		if (isset($plugins[$category])) $plugins[$category][$pluginName] =& $plugin;
		else $plugins[$category] = array($pluginName => &$plugin);
		Registry::set('plugins', $plugins);
		return true;
	}

	/**
	 * Get a plugin by name.
	 * @param $category String category name
	 * @param $name String plugin name
	 */
	static function &getPlugin($category, $name) {
		$plugins =& PluginRegistry::getPlugins();
		$plugin = @$plugins[$category][$name];
		return $plugin;
	}

	/**
	 * Load all plugins for a given category.
	 * @param $category string The name of the category to load
	 * @param $enabledOnly boolean if true load only enabled
	 *  plug-ins (db-installation required), otherwise look on
	 *  disk and load all available plug-ins (no db required).
	 * @param $mainContextId integer To identify enabled plug-ins
	 *  we need a context. This context is usually taken from the
	 *  request but sometimes there is no context in the request
	 *  (e.g. when executing CLI commands). Then the main context
	 *  can be given as an explicit ID.
	 */
	static function &loadCategory ($category, $enabledOnly = false, $mainContextId = null) {
		$plugins = array();
		$categoryDir = PLUGINS_PREFIX . $category;
		if (!is_dir($categoryDir)) return $plugins;

		if ($enabledOnly && Config::getVar('general', 'installed')) {
			// Get enabled plug-ins from the database.
			$application = PKPApplication::getApplication();
			$products =& $application->getEnabledProducts('plugins.'.$category, $mainContextId);
			foreach ($products as $product) {
				$file = $product->getProduct();
				$plugin =& PluginRegistry::_instantiatePlugin($category, $categoryDir, $file, $product->getProductClassname());
				if ($plugin && is_object($plugin)) {
					$plugins[$plugin->getSeq()]["$categoryDir/$file"] =& $plugin;
					unset($plugin);
				}
			}
		} else {
			// Get all plug-ins from disk. This does not require
			// any database access and can therefore be used during
			// first-time installation.
			$handle = opendir($categoryDir);
			while (($file = readdir($handle)) !== false) {
				if ($file == '.' || $file == '..') continue;
				$plugin =& PluginRegistry::_instantiatePlugin($category, $categoryDir, $file);
				if ($plugin && is_object($plugin)) {
					$plugins[$plugin->getSeq()]["$categoryDir/$file"] =& $plugin;
					unset($plugin);
				}
			}
			closedir($handle);
		}

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
		$plugins =& PluginRegistry::getPlugins($category);

		// Fire a hook after all plugins of a category have been loaded, so they
		// are able to interact if required
		HookRegistry::call('PluginRegistry::categoryLoaded::' . $category, array(&$plugins));

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
	static function &loadPlugin($category, $pathName) {
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
	 *
	 * NB: The categories are returned in the order in which they
	 * have to be registered and/or installed. Plug-ins in categories
	 * later in the list may depend on plug-ins in earlier
	 * categories.
	 *
	 * @return array
	 */
	static function getCategories() {
		$application = PKPApplication::getApplication();
		$categories = $application->getPluginCategories();
		HookRegistry::call('PluginRegistry::getCategories', array(&$categories));
		return $categories;
	}

	/**
	 * Load all plugins in the system and return them in a single array.
	 * @param $enabledOnly boolean load only enabled plug-ins
	 */
	static function &loadAllPlugins($enabledOnly = false) {
		// Retrieve and register categories (order is significant).
		foreach (PluginRegistry::getCategories() as $category) {
			PluginRegistry::loadCategory($category, $enabledOnly);
		}
		$allPlugins =& PluginRegistry::getAllPlugins();
		return $allPlugins;
	}


	//
	// Private helper methods
	//
	/**
	 * Instantiate a plugin.
	 *
	 * This method can be called statically.
	 *
	 * @param $category string
	 * @param $categoryDir string
	 * @param $file string
	 * @param $classToCheck string set null to maintain pre-2.3.x backwards compatibility
	 * @return Plugin
	 */
	static function &_instantiatePlugin($category, $categoryDir, $file, $classToCheck = null) {
		if(!is_null($classToCheck) && !preg_match('/[a-zA-Z0-9]+/', $file)) fatalError('Invalid product name "'.$file.'"!');

		$pluginPath = "$categoryDir/$file";
		$plugin = null;

		// Try the plug-in wrapper first for backwards
		// compatibility.
		$pluginWrapper = "$pluginPath/index.php";
		if (file_exists($pluginWrapper)) {
			$plugin = include($pluginWrapper);
			if ($classToCheck) {
				assert(is_a($plugin, $classToCheck));
			}
		} else {
			// Try the well-known plug-in class name next.
			$pluginClassName = ucfirst($file).ucfirst($category).'Plugin';
			$pluginClassFile = $pluginClassName.'.inc.php';
			if (file_exists("$pluginPath/$pluginClassFile")) {
				// Try to instantiate the plug-in class.
				$pluginPackage = 'plugins.'.$category.'.'.$file;
				$plugin =& instantiate($pluginPackage.'.'.$pluginClassName, $pluginClassName, $pluginPackage, 'register');
			}
		}

		// Make sure that the plug-in inherits from the right class.
		if (is_object($plugin)) {
			assert(is_a($plugin, 'Plugin'));
		} else {
			assert(is_null($plugin));
		}

		return $plugin;
	}
}

?>
