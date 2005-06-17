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
	 * Get the base URL to this plugin
	 */
	function getPluginUrl() {
		return Request::getPageUrl() . '/manager/importexport/plugin/' . $this->getName();
	}

	/**
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 * @param $crumbs Array ($url, $name, $isTranslated)
	 * @param $subclass boolean
	 */
	function setBreadcrumbs($crumbs = array(), $isSubclass = false) {
		$templateMgr = &TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				'user',
				'navigation.user'
			),
			array(
				'manager',
				'user.role.manager'
			),
			array (
				'manager/importexport',
				'manager.importExport'
			)
		);
		if ($isSubclass) $pageCrumbs[] = array(
			'manager/importexport/plugin/' . $this->getName(),
			$this->getDisplayName(),
			true
		);

		$templateMgr->assign('pageHierarchy', array_merge($pageCrumbs, $crumbs));
	}

	/**
	 * Display the import/export plugin UI.
	 * @param $args Array The array of arguments the user supplied.
	 */
	function display(&$args) {
		// This should never be called for this class -- subclasses
		// must override this method to provide an implementation.
	}

	/**
	 * Execute import/export tasks using the command-line interface.
	 * @param $scriptName The name of the command-line script (displayed as usage info)
	 * @param $args Parameters to the plugin
	 */ 
	function executeCLI($scriptName, &$args) {
		$this->usage();
		// Implemented by subclasses
	}

	/**
	 * Display the command-line usage information
	 */
	function usage($scriptName) {
		// Implemented by subclasses
	}
}
?>
