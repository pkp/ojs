<?php

/**
 * ImportExportPlugin.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Abstract class for import/export plugins
 *
 * $Id$
 */

class ImportExportPlugin extends Plugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		// This should not be used as this is an abstract class
		return 'ImportExportPlugin';
	}

	/**
	 * Get the display name of this plugin. This name is displayed on the
	 * Journal Manager's import/export page, for example.
	 * @return String
	 */
	function getDisplayName() {
		// This name should never be displayed because child classes
		// will override this method.
		return 'Abstract Import/Export Plugin';
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return 'This is the ImportExportPlugin base class. Its functions can be overridden by subclasses to provide import/export functionality for various formats.';
	}

	/**
	 * Display the import/export plugin UI.
	 * @param $templateMgr Object The template manager.
	 * @param $args Array The array of arguments the user supplied.
	 */
	function display(&$templateMgr, &$args) {
		// This should never be called for this class -- subclasses
		// must override this method to provide an implementation.
	}
}
?>
