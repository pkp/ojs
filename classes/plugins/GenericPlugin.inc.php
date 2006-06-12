<?php

/**
 * GenericPlugin.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Abstract class for generic plugins
 *
 * $Id$
 */

import('plugins.Plugin');

class GenericPlugin extends Plugin {
	/**
	 * Constructor
	 */
	function GenericPlugin() {
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
		$success = parent::register($category, $path);
		if ($success) {
			if ($this->getInstallSchemaFile()) {
				HookRegistry::register ('Installer::postInstall', array(&$this, 'updateSchema'));
			}
			if ($this->getInstallDataFile()) {
				HookRegistry::register ('Installer::postInstall', array(&$this, 'installData'));
			}
		}
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category, and should be suitable for part of a filename
	 * (ie short, no spaces, and no dependencies on cases being unique).
	 * @return String name of plugin
	 */
	function getName() {
		return 'GenericPlugin';
	}

	/**
	 * Get a description of this plugin.
	 */
	function getDescription() {
		return 'This is the base generic plugin class. It contains no concrete implementation. Its functions must be overridden by subclasses to provide actual functionality.';
	}

	/**
	 * Get the filename of the ADODB schema for this plugin.
	 * Subclasses using SQL tables should override this.
	 */
	function getInstallSchemaFile() {
		return null;
	}

	function updateSchema(&$plugin, $args) {
		$installer =& $args[0];
		$result =& $args[1];

		$schemaXMLParser = &new adoSchema($installer->dbconn, $installer->dbconn->charSet);
		$sql = $schemaXMLParser->parseSchema($this->getInstallSchemaFile());
		if ($sql) {
			$result = $installer->executeSQL($sql);
		} else {
			$installer->setError(INSTALLER_ERROR_DB, str_replace('{$file}', $this->getInstallSchemaFile(), Locale::translate('installer.installParseDBFileError')));
			$result = false;
		}
		return false;
	}

	/**
	 * Get the filename of the install data for this plugin.
	 * Subclasses using SQL tables should override this.
	 */
	function getInstallDataFile() {
		return null;
	}

	function installData(&$plugin, $args) {
		$installer =& $args[0];
		$result =& $args[1];

		$sql = $installer->dataXMLParser->parseData($this->getInstallDataFile());
		if ($sql) {
			$result = $installer->executeSQL($sql);
		} else {
			$installer->setError(INSTALLER_ERROR_DB, str_replace('{$file}', $this->getInstallDataFile(), Locale::translate('installer.installParseDBFileError')));
			$result = false;
		}
		return false;
	}

}
?>
