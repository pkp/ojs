<?php

/**
 * @file Plugin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 * @class Plugin
 *
 * Abstract class for plugins
 *
 * $Id$
 */

class Plugin {
	/** @var $pluginPath String Path name to files for this plugin */
	var $pluginPath;

	/** @var $pluginCategory String Category name this plugin is registered to*/
	var $pluginCategory;

	/**
	 * Constructor
	 */
	function Plugin() {
	}

	/**
	 * Get the path this plugin's files are located in.
	 * @return String pathname
	 */
	function getPluginPath() {
		return $this->pluginPath;
	}

	/**
	 * Get the name of the category this plugin is registered to.
	 * @return String category
	 */
	function getCategory() {
		return $this->pluginCategory;
	}

	/**
	 * Return a number indicating the sequence in which this plugin
	 * should be registered compared to others of its category.
	 * Higher = later.
	 */
	function getSeq() {
		return 0;
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
		$this->pluginPath = $path;
		$this->pluginCategory = $category;
		if ($this->getInstallSchemaFile()) {
			HookRegistry::register ('Installer::postInstall', array(&$this, 'updateSchema'));
		}
		if ($this->getInstallSitePluginSettingsFile()) {
			HookRegistry::register ('Installer::postInstall', array(&$this, 'installSiteSettings'));
		}
		if ($this->getNewJournalPluginSettingsFile()) {
			HookRegistry::register ('JournalSiteSettingsForm::execute', array(&$this, 'installJournalSettings'));
		}
		if ($this->getInstallDataFile()) {
			HookRegistry::register ('Installer::postInstall', array(&$this, 'installData'));
		}
		return true;
	}

	/**
	 * Load locale data for this plugin.
	 * @param $locale string
	 * @return boolean
	 */
	function addLocaleData($locale = null) {
		if ($locale == '') $locale = Locale::getLocale();
		$localeFilename = $this->getLocaleFilename($locale);
		if ($localeFilename) {
			Locale::registerLocaleFile($locale, $this->getLocaleFilename($locale));
			return true;
		}
		return false;
	}

	/**
	 * Get the filename for the locale data for this plugin.
	 * @param $locale string
	 * @return string
	 */
	function getLocaleFilename($locale) {
		return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . 'locale.xml';
	}

	/**
	 * Add help data for this plugin.
	 * @param $locale string
	 * @return boolean
	 */
	function addHelpData($locale = null) {
		if ($locale == '') $locale = Locale::getLocale();
		$help =& Help::getHelp();
		import('help.PluginHelpMappingFile');
		$pluginHelpMapping =& new PluginHelpMappingFile($this);
		$help->addMappingFile($pluginHelpMapping);
		return true;
	}

	/**
	 * Get the path and filename of the help mapping file, if this
	 * plugin includes help files.
	 * @return string
	 */
	function getHelpMappingFilename() {
		return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'help.xml';
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category, and should be suitable for part of a filename
	 * (ie short, no spaces, and no dependencies on cases being unique).
	 * @return String name of plugin
	 */
	function getName() {
		return 'Plugin';
	}

	/**
	 * Get the display name for this plugin.
	 * @return string
	 */
	function getDisplayName() {
		return $this->getName();
	}

	/**
	 * Get a description of this plugin.
	 */
	function getDescription() {
		return 'This is the base plugin class. It contains no concrete implementation. Its functions must be overridden by subclasses to provide actual functionality.';
	}

	function getTemplatePath() {
		$basePath = dirname(dirname(dirname(__FILE__)));
		return "file:$basePath/" . $this->getPluginPath() . '/';
	}

	/**
	 * Load a PHP file from this plugin's installation directory.
	 * @param $class string
	 */
	function import($class) {
		require_once($this->getPluginPath() . '/' . str_replace('.', '/', $class) . '.inc.php');
	}

	function getSetting($journalId, $name) {
		if (!Config::getVar('general', 'installed')) return null;
		if (defined('RUNNING_UPGRADE')) {
			// Bug #2504: Make sure plugin_settings table is not
			// used if it's not available.
			$versionDao =& DAORegistry::getDAO('VersionDAO');
			$version =& $versionDao->getCurrentVersion();
			if ($version->compare('2.1.0') < 0) return null;
		}
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		return $pluginSettingsDao->getSetting($journalId, $this->getName(), $name);
	}

	/**
	 * Update a plugin setting.
	 * @param $journalId int
	 * @param $name string The name of the setting
	 * @param $value mixed
	 * @param $type string optional
	 */
	function updateSetting($journalId, $name, $value, $type = null) {
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		$pluginSettingsDao->updateSetting($journalId, $this->getName(), $name, $value, $type);
	}

	/**
	 * Site-wide plugins should override this function to return true.
	 */
	function isSitePlugin() {
		return false;
	}

	/**
	 * Get a list of management actions in the form of a page => value pair.
	 * The management actions from this list are passed to the manage() function
	 * when called.
	 */
	function getManagementVerbs() {
		return null;
	}

	/**
	 * Perform a management function.
	 */
	function manage($verb, $args) {
		return false;
	}

	/**
	 * Extend the {url ...} smarty to support plugins.
	 */
	function smartyPluginUrl($params, &$smarty) {
		$path = array($this->getCategory(), $this->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}
		return $smarty->smartyUrl($params, $smarty);
	}

	/**
	 * Get the filename of the ADODB schema for this plugin.
	 * Subclasses using SQL tables should override this.
	 * @return string
	 */
	function getInstallSchemaFile() {
		return null;
	}

	/**
	 * Called during the install process to install the plugin schema,
	 * if applicable.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function updateSchema($hookName, $args) {
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
	 * Get the filename of the settings data for this plugin to install
	 * when a journal is created (i.e. journal-level plugin settings).
	 * Subclasses using default settings should override this.
	 * @return string
	 */
	function getNewJournalPluginSettingsFile() {
		return null;
	}

	/**
	 * Callback used to install settings on journal creation.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function installJournalSettings($hookName, $args) {
		$journal =& $args[1];
		$isNewJournal = $args[3];

		if (!$isNewJournal) return false;

		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		$pluginSettingsDao->installSettings($journal->getJournalId(), $this->getName(), $this->getNewJournalPluginSettingsFile());

		return false;
	}



	/**
	 * Get the filename of the settings data for this plugin to install
	 * when the system is installed (i.e. site-level plugin settings).
	 * Subclasses using default settings should override this.
	 * @return string
	 */
	function getInstallSitePluginSettingsFile() {
		return null;
	}

	/**
	 * Callback used to install settings on system install.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function installSiteSettings($hookName, $args) {
		$installer =& $args[0];
		$result =& $args[1];

		// Settings are only installed during automated installs. FIXME!
		if (!$installer->getParam('manualInstall')) {
			$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
			$pluginSettingsDao->installSettings(0, $this->getName(), $this->getInstallSitePluginSettingsFile());
		}

		return false;
	}

	/**
	 * Get the filename of the install data for this plugin.
	 * Subclasses using SQL tables should override this.
	 * @return string
	 */
	function getInstallDataFile() {
		return null;
	}

	/**
	 * Callback used to install data files.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function installData($hookName, $args) {
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
