<?php

/**
 * SampleImportExportPlugin.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Sample import/export plugin
 *
 * $Id$
 */

import('classes.plugins.ImportExportPlugin');

class SampleImportExportPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param @category String Name of category plugin was registered to
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
		// This should not be used as this is an abstract class
		return 'SampleImportExportPlugin';
	}

	function getDisplayName() {
		return 'Sample Import/Export Plugin';
	}

	function getDescription() {
		return 'This plugin provides an implementation example to be used when developing import/export plugins. It\'s not useful in and of itself.';
	}

	function display(&$templateMgr, &$args) {
		echo "Display. Args: ";
		foreach ($args as $arg) {
			echo "$arg ";
		}
	}
}

?>
