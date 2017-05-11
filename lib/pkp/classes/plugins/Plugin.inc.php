<?php

/**
 * @defgroup plugins Plugins
 * Implements a plugin structure that can be used to flexibly extend PKP
 * software via the use of a set of plugin categories.
 */

/**
 * @file classes/plugins/Plugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Plugin
 * @ingroup plugins
 * @see PluginRegistry, PluginSettingsDAO
 *
 * @brief Abstract class for plugins
 *
 * For best performance, a plug-in should not be instantiated if it is
 * disabled or the current page/operation does not require the plug-in's
 * functionality.
 *
 * Newer plug-ins support enable/disable and request filter settings that
 * enable the PKP library plug-in framework to lazy-load plug-ins only
 * when their functionality is actually being required for a request.
 *
 * For backwards compatibility we need to assume that older plug-ins
 * do not support lazy-load because their register() method and hooks
 * may have side-effects required on all requests. We have no way of
 * knowing on which pages these side effects are important so we need
 * to load legacy plug-ins on all pages.
 *
 * In these cases the register() function will be called on every request
 * when the category the plug-in belongs to is being loaded. This was the
 * default behavior before plug-in lazy load was introduced.
 *
 * Plug-ins that want to enable lazy-load have to include a 'lazy-load'
 * setting in their version.xml:
 *
 *  <lazy-load>1</lazy-load>
 */


// Define the well-known file name for filter configuration data.
define('PLUGIN_FILTER_DATAFILE', 'filterConfig.xml');

abstract class Plugin {
	/** @var string Path name to files for this plugin */
	var $pluginPath;

	/** @var string Category name this plugin is registered to*/
	var $pluginCategory;

	/** @var PKPRequest the current request object */
	var $request;

	/**
	 * Constructor
	 */
	function __construct() {
	}

	/*
	 * Public Plugin API (Registration and Initialization)
	 */
	/**
	 * Load and initialize the plug-in and register plugin hooks.
	 *
	 * For backwards compatibility this method will be called whenever
	 * the plug-in's category is being loaded. If, however, registerOn()
	 * returns an array then this method will only be called when
	 * the plug-in is enabled and an entry in the result set of
	 * registerOn() matches the current request operation. An empty array
	 * matches all request operations.
	 *
	 * @param $category String Name of category plugin was registered to
	 * @param $path String The path the plugin was found in
	 * @return boolean True iff plugin registered successfully; if false,
	 * 	the plugin will not be executed.
	 */
	function register($category, $path) {
		$this->pluginPath = $path;
		$this->pluginCategory = $category;
		if ($this->getInstallSchemaFile()) {
			HookRegistry::register ('Installer::postInstall', array($this, 'updateSchema'));
		}
		if ($this->getInstallSitePluginSettingsFile()) {
			HookRegistry::register ('Installer::postInstall', array($this, 'installSiteSettings'));
		}
		if ($this->getInstallControlledVocabFiles()) {
			HookRegistry::register ('Installer::postInstall', array($this, 'installControlledVocabs'));
		}
		if ($this->getInstallEmailTemplatesFile()) {
			HookRegistry::register ('Installer::postInstall', array($this, 'installEmailTemplates'));
		}
		if ($this->getInstallEmailTemplateDataFile()) {
			HookRegistry::register ('Installer::postInstall', array($this, 'installEmailTemplateData'));
			HookRegistry::register ('PKPLocale::installLocale', array($this, 'installLocale'));
		}
		if ($this->getInstallDataFile()) {
			HookRegistry::register ('Installer::postInstall', array($this, 'installData'));
		}
		if ($this->getContextSpecificPluginSettingsFile()) {
			HookRegistry::register ($this->_getContextSpecificInstallationHook(), array($this, 'installContextSpecificSettings'));
		}
		HookRegistry::register ('Installer::postInstall', array($this, 'installFilters'));
		return true;
	}

	/**
	 * Protected methods (may be overridden by custom plugins)
	 */

	//
	// Plugin Display
	//

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category, and should be suitable for part of a filename
	 * (ie short, no spaces, and no dependencies on cases being unique).
	 *
	 * @return string name of plugin
	 */
	abstract function getName();

	/**
	 * Get the display name for this plugin.
	 *
	 * @return string
	 */
	abstract function getDisplayName();

	/**
	 * Get a description of this plugin.
	 *
	 * @return string
	 */
	abstract function getDescription();

	//
	// Plugin Behavior and Management
	//

	/**
	 * Return a number indicating the sequence in which this plugin
	 * should be registered compared to others of its category.
	 * Higher = later.
	 *
	 * @return integer
	 */
	function getSeq() {
		return 0;
	}

	/**
	 * Site-wide plugins should override this function to return true.
	 *
	 * @return boolean
	 */
	function isSitePlugin() {
		return false;
	}

	/**
	 * Perform a management function.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage A JSON-encoded response
	 */
	function manage($args, $request) {
		assert(false); // Unhandled case; this shouldn't happen.
	}

	/**
	 * Determine whether or not this plugin should be hidden from the
	 * management interface. Useful in the case of derivative plugins,
	 * i.e. when a generic plugin registers a feed plugin.
	 *
	 * @return boolean
	 */
	function getHideManagement() {
		return false;
	}

	//
	// Plugin Installation
	//

	/**
	 * Get the filename of the ADODB schema for this plugin.
	 * Subclasses using SQL tables should override this.
	 *
	 * @return string
	 */
	function getInstallSchemaFile() {
		return null;
	}

	/**
	 * Get the filename of the install data for this plugin.
	 * Subclasses using SQL tables should override this.
	 *
	 * @return string|array|null one or more data files to be installed.
	 */
	function getInstallDataFile() {
		return null;
	}

	/**
	 * Get the filename of the settings data for this plugin to install
	 * when the system is installed (i.e. site-level plugin settings).
	 * Subclasses using default settings should override this.
	 *
	 * @return string
	 */
	function getInstallSitePluginSettingsFile() {
		return null;
	}

	/**
	 * Get the filename of the controlled vocabulary for this plugin to
	 * install when the system is installed. Null if none included.
	 * @return array|null Filename of controlled vocabs XML file.
	 */
	function getInstallControlledVocabFiles() {
		return array();
	}

	/**
	 * Get the filename of the settings data for this plugin to install
	 * when a new application context (e.g. journal, conference or press)
	 * is installed.
	 *
	 * Subclasses using default settings should override this.
	 *
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return null;
	}

	/**
	 * Get the filename of the email templates for this plugin.
	 * Subclasses using email templates should override this.
	 *
	 * @return string
	 */
	function getInstallEmailTemplatesFile() {
		return null;
	}

	/**
	 * Get the filename of the email template data for this plugin.
	 * Subclasses using email templates should override this.
	 *
	 * @return string
	 */
	function getInstallEmailTemplateDataFile() {
		return null;
	}

	/**
	 * Get the filename(s) of the filter configuration data for
	 * this plugin. Subclasses using filters can override this.
	 *
	 * The default implementation establishes "well known" locations
	 * for the filter configuration. If you keep your files in these
	 * locations then there's no need to override this method.
	 *
	 * @return string|array one or more file locations.
	 */
	function getInstallFilterConfigFiles() {
		// Construct the well-known filter configuration file names.
		$filterConfigFile = $this->getPluginPath().'/filter/'.PLUGIN_FILTER_DATAFILE;
		$filterConfigFiles = array(
			'./lib/pkp/'.$filterConfigFile,
			'./'.$filterConfigFile
		);
		return $filterConfigFiles;
	}

	/*
	 * Protected helper methods (can be used by custom plugins but
	 * should not be overridden by custom plugins)
	 */
	/**
	 * Get the name of the category this plugin is registered to.
	 * @return String category
	 */
	function getCategory() {
		return $this->pluginCategory;
	}

	/**
	 * Get the path this plugin's files are located in.
	 * @return String pathname
	 */
	function getPluginPath() {
		return $this->pluginPath;
	}

	/**
	 * Return the canonical template path of this plug-in
	 * @param $inCore Return the core template path if true.
	 * @return string
	 */
	function getTemplatePath($inCore = false) {
		$basePath = Core::getBaseDir();
		if ($inCore) {
			$basePath = $basePath . DIRECTORY_SEPARATOR . PKP_LIB_PATH;
		}
		return "file:$basePath" . DIRECTORY_SEPARATOR . $this->getPluginPath() . DIRECTORY_SEPARATOR;
	}

	/**
	 * Load locale data for this plugin.
	 *
	 * @param $locale string
	 * @return boolean
	 */
	function addLocaleData($locale = null) {
		if ($locale == '') $locale = AppLocale::getLocale();
		$localeFilenames = $this->getLocaleFilename($locale);
		if ($localeFilenames) {
			if (is_scalar($localeFilenames)) $localeFilenames = array($localeFilenames);
			foreach($localeFilenames as $localeFilename) {
				AppLocale::registerLocaleFile($locale, $localeFilename);
			}
			return true;
		}
		return false;
	}

	/**
	 * Retrieve a plugin setting within the given context
	 *
	 * @param $contextId int Context ID
	 * @param $name string Setting name
	 */
	function getSetting($contextId, $name) {
		if (!defined('RUNNING_UPGRADE') && !Config::getVar('general', 'installed')) return null;

		// Construct the argument list and call the plug-in settings DAO
		$arguments = array(
			$contextId,
			$this->getName(),
			$name,
		);

		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
		return call_user_func_array(array(&$pluginSettingsDao, 'getSetting'), $arguments);
	}

	/**
	 * Update a plugin setting within the given context.
	 *
	 * @param $contextId int Context ID
	 * @param $name string The name of the setting
	 * @param $value mixed Setting value
	 * @param $type string optional
	 */
	function updateSetting($contextId, $name, $value, $type = null) {

		// Construct the argument list and call the plug-in settings DAO
		$arguments = array(
			$contextId,
			$this->getName(),
			$name,
			$value,
			$type,
		);

		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
		call_user_func_array(array(&$pluginSettingsDao, 'updateSetting'), $arguments);
	}

	/**
	 * Load a PHP file from this plugin's installation directory.
	 *
	 * @param $class string
	 */
	function import($class) {
		require_once($this->getPluginPath() . '/' . str_replace('.', '/', $class) . '.inc.php');
	}

	/*
	 * Protected helper methods (for internal use only, should not
	 * be used by custom plug-ins)
	 *
	 * NB: These methods may change without notice in the future!
	 */
	/**
	 * Get the filename for the locale data for this plugin.
	 *
	 * @param $locale string
	 * @return string|array the locale file names (the scalar return value is supported for
	 *  backwards compatibility only).
	 */
	function getLocaleFilename($locale) {
		$masterLocale = MASTER_LOCALE;
		$baseLocaleFilename = $this->getPluginPath() . "/locale/$locale/locale.xml";
		$baseMasterLocaleFilename = $this->getPluginPath() . "/locale/$masterLocale/locale.xml";
		$libPkpFilename = "lib/pkp/$baseLocaleFilename";
		$masterLibPkpFilename = "lib/pkp/$baseMasterLocaleFilename";
		$filenames = array();
		if (file_exists($baseMasterLocaleFilename)) $filenames[] = $baseLocaleFilename;
		if (file_exists($masterLibPkpFilename)) $filenames[] = $libPkpFilename;
		return $filenames;
	}

	/**
	 * Callback used to install data files.
	 *
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function installData($hookName, $args) {
		$installer =& $args[0];
		$result =& $args[1];

		// Treat single and multiple data files uniformly.
		$dataFiles = $this->getInstallDataFile();
		if (is_scalar($dataFiles)) $dataFiles = array($dataFiles);

		// Install all data files.
		foreach($dataFiles as $dataFile) {
			$sql = $installer->dataXMLParser->parseData($dataFile);
			if ($sql) {
				$result = $installer->executeSQL($sql);
			} else {
				AppLocale::requireComponents(LOCALE_COMPONENT_PKP_INSTALLER);
				$installer->setError(INSTALLER_ERROR_DB, str_replace('{$file}', $this->getInstallDataFile(), __('installer.installParseDBFileError')));
				$result = false;
			}
			if (!$result) return false;
		}
		return false;
	}

	/**
	 * Callback used to install settings on system install.
	 *
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function installSiteSettings($hookName, $args) {
		// All contexts are set to zero for site-wide plug-in settings
		$application = PKPApplication::getApplication();
		$contextDepth = $application->getContextDepth();
		if ($contextDepth >0) {
			$arguments = array_fill(0, $contextDepth, 0);
		} else {
			$arguments = array();
		}
		$arguments[] = $this->getName();
		$arguments[] = $this->getInstallSitePluginSettingsFile();
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
		call_user_func_array(array(&$pluginSettingsDao, 'installSettings'), $arguments);

		return false;
	}

	/**
	 * Callback used to install controlled vocabularies on system install.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function installControlledVocabs($hookName, $args) {
		// All contexts are set to zero for site-wide plug-in settings
		$controlledVocabDao = DAORegistry::getDAO('ControlledVocabDAO');
		foreach ($this->getInstallControlledVocabFiles() as $file) {
			$controlledVocabDao->installXML($file);
		}
		return false;
	}
	/**
	 * Callback used to install settings on new context
	 * (e.g. journal, conference or press) creation.
	 *
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function installContextSpecificSettings($hookName, $args) {
		// Only applications that have at least one context can
		// install context specific settings.
		$application = PKPApplication::getApplication();
		$contextDepth = $application->getContextDepth();
		if ($contextDepth > 0) {
			$context =& $args[1];

			// Make sure that this is really a new context
			$isNewContext = isset($args[3]) ? $args[3] : true;
			if (!$isNewContext) return false;

			// Install context specific settings
			$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
			switch ($contextDepth) {
				case 1:
					$pluginSettingsDao->installSettings($context->getId(), $this->getName(), $this->getContextSpecificPluginSettingsFile());
					break;

				case 2:
					$pluginSettingsDao->installSettings($context->getId(), 0, $this->getName(), $this->getContextSpecificPluginSettingsFile());
					break;

				default:
					// No application can have a context depth > 2
					assert(false);
			}
		}
		return false;
	}

	/**
	 * Callback used to install email templates.
	 *
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function installEmailTemplates($hookName, $args) {
		$installer =& $args[0]; /* @var $installer Installer */
		$result =& $args[1];

		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO'); /* @var $emailTemplateDao EmailTemplateDAO */
		$sql = $emailTemplateDao->installEmailTemplates($this->getInstallEmailTemplatesFile(), true, null, true);

		if ($sql === false) {
			// The template file seems to be invalid.
			$installer->setError(INSTALLER_ERROR_DB, str_replace('{$file}', $this->getInstallDataFile(), __('installer.installParseEmailTemplatesFileError')));
			$result = false;
		} else {
			// Are there any yet uninstalled email templates?
			assert(is_array($sql));
			if (!empty($sql)) {
				// Install templates.
				$result = $installer->executeSQL($sql);
			}
		}
		return false;
	}

	/**
	 * Callback used to install email template data.
	 *
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function installEmailTemplateData($hookName, $args) {
		$installer =& $args[0];
		$result =& $args[1];

		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO');
		foreach ($installer->installedLocales as $locale) {
			$filename = str_replace('{$installedLocale}', $locale, $this->getInstallEmailTemplateDataFile());
			if (!file_exists($filename)) continue;
			$sql = $emailTemplateDao->installEmailTemplateData($filename, true);
			if ($sql) {
				$result = $installer->executeSQL($sql);
			} else {
				$installer->setError(INSTALLER_ERROR_DB, str_replace('{$file}', $this->getInstallDataFile(), __('installer.installParseEmailTemplatesFileError')));
				$result = false;
			}
		}
		return false;
	}

	/**
	 * Callback used to install email template data on locale install.
	 *
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function installLocale($hookName, $args) {
		$locale =& $args[0];
		$filename = str_replace('{$installedLocale}', $locale, $this->getInstallEmailTemplateDataFile());
		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplateDao->installEmailTemplateData($filename);
		return false;
	}

	/**
	 * Callback used to install filters.
	 * @param $hookName string
	 * @param $args array
	 */
	function installFilters($hookName, $args) {
		$installer =& $args[0]; /* @var $installer Installer */
		$result =& $args[1]; /* @var $result boolean */

		// Get the filter configuration file name(s).
		$filterConfigFiles = $this->getInstallFilterConfigFiles();
		if (is_scalar($filterConfigFiles)) $filterConfigFiles = array($filterConfigFiles);

		// Run through the config file positions and see
		// whether one of these exists and needs to be installed.
		foreach($filterConfigFiles as $filterConfigFile) {
			// Is there a filter configuration?
			if (!file_exists($filterConfigFile)) continue;

			// Install the filter configuration.
			$result = $installer->installFilterConfig($filterConfigFile);
			if (!$result) {
				// The filter configuration file seems to be invalid.
				$installer->setError(INSTALLER_ERROR_DB, str_replace('{$file}', $filterConfigFile, __('installer.installParseFilterConfigFileError')));
			}
		}

		// Do not stop installation.
		return false;
	}

	/**
	 * Called during the install process to install the plugin schema,
	 * if applicable.
	 *
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function updateSchema($hookName, $args) {
		$installer =& $args[0];
		$result =& $args[1];

		$schemaXMLParser = new adoSchema($installer->dbconn);
		$dict =& $schemaXMLParser->dict;
		$dict->SetCharSet($installer->dbconn->charSet);
		$sql = $schemaXMLParser->parseSchema($this->getInstallSchemaFile());
		if ($sql) {
			$result = $installer->executeSQL($sql);
		} else {
			$installer->setError(INSTALLER_ERROR_DB, str_replace('{$file}', $this->getInstallSchemaFile(), __('installer.installParseDBFileError')));
			$result = false;
		}
		return false;
	}

	/**
	 * Extend the {url ...} smarty to support plugins.
	 *
	 * @param $params array
	 * @param $smarty Smarty
	 * @return string
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
	 * Get the current version of this plugin
	 *
	 * @return Version
	 */
	function getCurrentVersion() {
		$versionDao = DAORegistry::getDAO('VersionDAO');
		$pluginPath = $this->getPluginPath();
		$product = basename($pluginPath);
		$category = basename(dirname($pluginPath));
		$installedPlugin = $versionDao->getCurrentVersion('plugins.'.$category, $product, true);

		if ($installedPlugin) {
			return $installedPlugin;
		} else {
			return false;
		}
	}

	/**
	 * Get the current request object
	 * @return PKPRequest
	 */
	function &getRequest() {
		if (!$this->request) {
			$this->request =& Registry::get('request');
		}
		return $this->request;
	}

	/*
	 * Private helper methods
	 */
	/**
	 * The application specific context installation hook.
	 *
	 * @return string
	 */
	function _getContextSpecificInstallationHook() {
		$application = PKPApplication::getApplication();

		if ($application->getContextDepth() == 0) return null;

		$contextList = $application->getContextList();
		return ucfirst(array_shift($contextList)).'SiteSettingsForm::execute';
	}

	/**
	 * Get a list of link actions for plugin management.
	 * @param request PKPRequest
	 * @param $actionArgs array The list of action args to be included in request URLs.
	 * @return array List of LinkActions
	 */
	function getActions($request, $actionArgs) {
		return array();
	}

	/**
	 * Determine whether the plugin can be enabled.
	 * @return boolean
	 */
	function getCanEnable() {
		return false;
	}

	/**
	 * Determine whether the plugin can be disabled.
	 * @return boolean
	 */
	function getCanDisable() {
		return false;
	}

	/**
	 * Determine whether the plugin is enabled.
	 * @return boolean
	 */
	function getEnabled() {
		return true;
	}

	/**
	 * Retrieve a namespace used when attaching JavaScript data to $.pkp.plugins
	 * @return string
	 */
	function getJavascriptNameSpace() {
		return '$.pkp.plugins.' . strtolower(get_class($this));
	}
}

?>
