<?php

/**
 * @file ImportExportPlugin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 * @class ImportExportPlugin
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
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 * @param $crumbs Array ($url, $name, $isTranslated)
	 * @param $subclass boolean
	 */
	function setBreadcrumbs($crumbs = array(), $isSubclass = false) {
		$templateMgr = &TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				Request::url(null, 'user'),
				'navigation.user'
			),
			array(
				Request::url(null, 'manager'),
				'user.role.manager'
			),
			array (
				Request::url(null, 'manager', 'importexport'),
				'manager.importExport'
			)
		);
		if ($isSubclass) $pageCrumbs[] = array(
			Request::url(null, 'manager', 'importexport', array('plugin', $this->getName())),
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
		$templateManager =& TemplateManager::getManager();
		$templateManager->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
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

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		return array(
			array(
				'importexport',
				Locale::translate('manager.importExport')
			)
		);
	}

	/**
	 * Perform management functions
	 */
	function manage($verb, $args) {
		if ($verb === 'importexport') {
			Request::redirect(null, 'manager', 'importexport', array('plugin', $this->getName()));
		}
		return false;
	}

	/**
	 * Extend the {url ...} smarty to support import/export plugins.
	 */
	function smartyPluginUrl($params, &$smarty) {
		$path = array('plugin', $this->getName());
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
