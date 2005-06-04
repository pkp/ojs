<?php

/**
 * Plugin.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Abstract class for plugins
 *
 * $Id$
 */

class Plugin {
	/**
	 * Constructor
	 */
	function Plugin() {
	}

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @param $path String The path the plugin was found in
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		return true;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'Plugin';
	}

	/**
	 * Get a description of this plugin.
	 */
	function getDescription() {
		return 'This is the base plugin class. It contains no concrete implementation. Its functions must be overridden by subclasses to provide actual functionality.';
	}
}
?>
