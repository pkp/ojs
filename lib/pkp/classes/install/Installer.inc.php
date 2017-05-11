<?php

/**
 * @file classes/install/Installer.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Installer
 * @ingroup install
 *
 * @brief Base class for install and upgrade scripts.
 */


// Database installation files
define('INSTALLER_DATA_DIR', 'dbscripts/xml');

// Installer error codes
define('INSTALLER_ERROR_GENERAL', 1);
define('INSTALLER_ERROR_DB', 2);

// Default data
define('INSTALLER_DEFAULT_LOCALE', 'en_US');

import('lib.pkp.classes.db.DBDataXMLParser');
import('lib.pkp.classes.site.Version');
import('lib.pkp.classes.site.VersionDAO');
import('lib.pkp.classes.config.ConfigParser');

require_once './lib/pkp/lib/adodb/adodb-xmlschema.inc.php';

class Installer {

	/** @var string descriptor path (relative to INSTALLER_DATA_DIR) */
	var $descriptor;

	/** @var boolean indicates if a plugin is being installed (thus modifying the descriptor path) */
	var $isPlugin;

	/** @var array installation parameters */
	var $params;

	/** @var Version currently installed version */
	var $currentVersion;

	/** @var Version version after installation */
	var $newVersion;

	/** @var ADOConnection database connection */
	var $dbconn;

	/** @var string default locale */
	var $locale;

	/** @var string available locales */
	var $installedLocales;

	/** @var DBDataXMLParser database data parser */
	var $dataXMLParser;

	/** @var array installer actions to be performed */
	var $actions;

	/** @var array SQL statements for database installation */
	var $sql;

	/** @var array installation notes */
	var $notes;

	/** @var string contents of the updated config file */
	var $configContents;

	/** @var boolean indicating if config file was written or not */
	var $wroteConfig;

	/** @var int error code (null | INSTALLER_ERROR_GENERAL | INSTALLER_ERROR_DB) */
	var $errorType;

	/** @var string the error message, if an installation error has occurred */
	var $errorMsg;

	/** @var Logger logging object */
	var $logger;


	/**
	 * Constructor.
	 * @param $descriptor string descriptor path
	 * @param $params array installer parameters
	 * @param $isPlugin boolean true iff a plugin is being installed
	 */
	function __construct($descriptor, $params = array(), $isPlugin = false) {
		// Load all plugins. If any of them use installer hooks,
		// they'll need to be loaded here.
		PluginRegistry::loadAllPlugins();
		$this->isPlugin = $isPlugin;

		// Give the HookRegistry the opportunity to override this
		// method or alter its parameters.
		if (!HookRegistry::call('Installer::Installer', array($this, &$descriptor, &$params))) {
			$this->descriptor = $descriptor;
			$this->params = $params;
			$this->actions = array();
			$this->sql = array();
			$this->notes = array();
			$this->wroteConfig = true;
		}
	}

	/**
	 * Returns true iff this is an upgrade process.
	 */
	function isUpgrade() {
		die ('ABSTRACT CLASS');
	}

	/**
	 * Destroy / clean-up after the installer.
	 */
	function destroy() {
		HookRegistry::call('Installer::destroy', array($this));
	}

	/**
	 * Pre-installation.
	 * @return boolean
	 */
	function preInstall() {
		$this->log('pre-install');
		if (!isset($this->dbconn)) {
			// Connect to the database.
			$conn = DBConnection::getInstance();
			$this->dbconn = $conn->getDBConn();

			if (!$conn->isConnected()) {
				$this->setError(INSTALLER_ERROR_DB, $this->dbconn->errorMsg());
				return false;
			}
		}

		if (!isset($this->currentVersion)) {
			// Retrieve the currently installed version
			$versionDao = DAORegistry::getDAO('VersionDAO');
			$this->currentVersion = $versionDao->getCurrentVersion();
		}

		if (!isset($this->locale)) {
			$this->locale = AppLocale::getLocale();
		}

		if (!isset($this->installedLocales)) {
			$this->installedLocales = array_keys(AppLocale::getAllLocales());
		}

		if (!isset($this->dataXMLParser)) {
			$this->dataXMLParser = new DBDataXMLParser();
			$this->dataXMLParser->setDBConn($this->dbconn);
		}

		$result = true;
		HookRegistry::call('Installer::preInstall', array($this, &$result));

		return $result;
	}

	/**
	 * Installation.
	 * @return boolean
	 */
	function execute() {
		// Ensure that the installation will not get interrupted if it takes
		// longer than max_execution_time (php.ini). Note that this does not
		// work under safe mode.
		@set_time_limit (0);

		if (!$this->preInstall()) {
			return false;
		}

		if (!$this->parseInstaller()) {
			return false;
		}

		if (!$this->executeInstaller()) {
			return false;
		}

		if (!$this->postInstall()) {
			return false;
		}

		return $this->updateVersion();
	}

	/**
	 * Post-installation.
	 * @return boolean
	 */
	function postInstall() {
		$this->log('post-install');
		$result = true;
		HookRegistry::call('Installer::postInstall', array($this, &$result));
		return $result;
	}


	/**
	 * Record message to installation log.
	 * @param $message string
	 */
	function log($message) {
		if (isset($this->logger)) {
			call_user_func(array($this->logger, 'log'), $message);
		}
	}


	//
	// Main actions
	//

	/**
	 * Parse the installation descriptor XML file.
	 * @return boolean
	 */
	function parseInstaller() {
		// Read installation descriptor file
		$this->log(sprintf('load: %s', $this->descriptor));
		$xmlParser = new XMLParser();
		$installPath = $this->isPlugin ? $this->descriptor : INSTALLER_DATA_DIR . DIRECTORY_SEPARATOR . $this->descriptor;
		$installTree = $xmlParser->parse($installPath);
		if (!$installTree) {
			// Error reading installation file
			$xmlParser->destroy();
			$this->setError(INSTALLER_ERROR_GENERAL, 'installer.installFileError');
			return false;
		}

		$versionString = $installTree->getAttribute('version');
		if (isset($versionString)) {
			$this->newVersion = Version::fromString($versionString);
		} else {
			$this->newVersion = $this->currentVersion;
		}

		// Parse descriptor
		$this->parseInstallNodes($installTree);
		$xmlParser->destroy();

		$result = $this->getErrorType() == 0;

		HookRegistry::call('Installer::parseInstaller', array($this, &$result));
		return $result;
	}

	/**
	 * Execute the installer actions.
	 * @return boolean
	 */
	function executeInstaller() {
		$this->log(sprintf('version: %s', $this->newVersion->getVersionString(false)));
		foreach ($this->actions as $action) {
			if (!$this->executeAction($action)) {
				return false;
			}
		}

		$result = true;
		HookRegistry::call('Installer::executeInstaller', array($this, &$result));

		return $result;
	}

	/**
	 * Update the version number.
	 * @return boolean
	 */
	function updateVersion() {
		if ($this->newVersion->compare($this->currentVersion) > 0) {
			$versionDao = DAORegistry::getDAO('VersionDAO');
			if (!$versionDao->insertVersion($this->newVersion)) {
				return false;
			}
		}

		$result = true;
		HookRegistry::call('Installer::updateVersion', array($this, &$result));

		return $result;
	}


	//
	// Installer Parsing
	//

	/**
	 * Parse children nodes in the install descriptor.
	 * @param $installTree XMLNode
	 */
	function parseInstallNodes($installTree) {
		foreach ($installTree->getChildren() as $node) {
			switch ($node->getName()) {
				case 'schema':
				case 'data':
				case 'code':
				case 'note':
					$this->addInstallAction($node);
					break;
				case 'upgrade':
					$minVersion = $node->getAttribute('minversion');
					$maxVersion = $node->getAttribute('maxversion');
					if ((!isset($minVersion) || $this->currentVersion->compare($minVersion) >= 0) && (!isset($maxVersion) || $this->currentVersion->compare($maxVersion) <= 0)) {
						$this->parseInstallNodes($node);
					}
					break;
			}
		}
	}

	/**
	 * Add an installer action from the descriptor.
	 * @param $node XMLNode
	 */
	function addInstallAction($node) {
		$fileName = $node->getAttribute('file');

		if (!isset($fileName)) {
			$this->actions[] = array('type' => $node->getName(), 'file' => null, 'attr' => $node->getAttributes());

		} else if (strstr($fileName, '{$installedLocale}')) {
			// Filename substitution for locales
			foreach ($this->installedLocales as $thisLocale) {
				$newFileName = str_replace('{$installedLocale}', $thisLocale, $fileName);
				$this->actions[] = array('type' => $node->getName(), 'file' => $newFileName, 'attr' => $node->getAttributes());
			}

		} else {
			$newFileName = str_replace('{$locale}', $this->locale, $fileName);
			if (!file_exists($newFileName)) {
				// Use version from default locale if data file is not available in the selected locale
				$newFileName = str_replace('{$locale}', INSTALLER_DEFAULT_LOCALE, $fileName);
			}

			$this->actions[] = array('type' => $node->getName(), 'file' => $newFileName, 'attr' => $node->getAttributes());
		}
	}


	//
	// Installer Execution
	//

	/**
	 * Execute a single installer action.
	 * @param $action array
	 * @return boolean
	 */
	function executeAction($action) {
		switch ($action['type']) {
			case 'schema':
				$fileName = $action['file'];
				$this->log(sprintf('schema: %s', $action['file']));

				require_once './lib/pkp/lib/adodb/adodb-xmlschema.inc.php';
				$schemaXMLParser = new adoSchema($this->dbconn);
				$dict = $schemaXMLParser->dict;
				$dict->SetCharSet($this->dbconn->charSet);
				$sql = $schemaXMLParser->parseSchema($fileName);
				$schemaXMLParser->destroy();

				if ($sql) {
					return $this->executeSQL($sql);
				} else {
					$this->setError(INSTALLER_ERROR_DB, str_replace('{$file}', $fileName, __('installer.installParseDBFileError')));
					return false;
				}
				break;
			case 'data':
				$fileName = $action['file'];
				$condition = isset($action['attr']['condition'])?$action['attr']['condition']:null;
				$includeAction = true;
				if ($condition) {
					$funcName = create_function('$installer,$action', $condition);
					$includeAction = $funcName($this, $action);
				}
				$this->log('data: ' . $action['file'] . ($includeAction?'':' (skipped)'));
				if (!$includeAction) break;

				$sql = $this->dataXMLParser->parseData($fileName);
				// We might get an empty SQL if the upgrade script has
				// been executed before.
				if ($sql) {
					return $this->executeSQL($sql);
				}
				break;
			case 'code':
				$condition = isset($action['attr']['condition'])?$action['attr']['condition']:null;
				$includeAction = true;
				if ($condition) {
					$funcName = create_function('$installer,$action', $condition);
					$includeAction = $funcName($this, $action);
				}
				$this->log(sprintf('code: %s %s::%s' . ($includeAction?'':' (skipped)'), isset($action['file']) ? $action['file'] : 'Installer', isset($action['attr']['class']) ? $action['attr']['class'] : 'Installer', $action['attr']['function']));
				if (!$includeAction) return true; // Condition not met; skip the action.

				if (isset($action['file'])) {
					require_once($action['file']);
				}
				if (isset($action['attr']['class'])) {
					return call_user_func(array($action['attr']['class'], $action['attr']['function']), $this, $action['attr']);
				} else {
					return call_user_func(array($this, $action['attr']['function']), $this, $action['attr']);
				}
				break;
			case 'note':
				$this->log(sprintf('note: %s', $action['file']));
				$this->notes[] = join('', file($action['file']));
				break;
		}

		return true;
	}

	/**
	 * Execute an SQL statement.
	 * @param $sql mixed
	 * @return boolean
	 */
	function executeSQL($sql) {
		if (is_array($sql)) {
			foreach($sql as $stmt) {
				if (!$this->executeSQL($stmt)) {
					return false;
				}
			}
		} else {
			$this->dbconn->execute($sql);
			if ($this->dbconn->errorNo() != 0) {
				$this->setError(INSTALLER_ERROR_DB, $this->dbconn->errorMsg());
				return false;
			}
		}

		return true;
	}

	/**
	 * Update the specified configuration parameters.
	 * @param $configParams arrays
	 * @return boolean
	 */
	function updateConfig($configParams) {
		// Update config file
		$configParser = new ConfigParser();
		if (!$configParser->updateConfig(Config::getConfigFileName(), $configParams)) {
			// Error reading config file
			$this->setError(INSTALLER_ERROR_GENERAL, 'installer.configFileError');
			return false;
		}

		$this->configContents = $configParser->getFileContents();
		if (!$configParser->writeConfig(Config::getConfigFileName())) {
			$this->wroteConfig = false;
		}

		return true;
	}


	//
	// Accessors
	//

	/**
	 * Get the value of an installation parameter.
	 * @param $name
	 * @return mixed
	 */
	function getParam($name) {
		return isset($this->params[$name]) ? $this->params[$name] : null;
	}

	/**
	 * Return currently installed version.
	 * @return Version
	 */
	function getCurrentVersion() {
		return $this->currentVersion;
	}

	/**
	 * Return new version after installation.
	 * @return Version
	 */
	function getNewVersion() {
		return $this->newVersion;
	}

	/**
	 * Get the set of SQL statements required to perform the install.
	 * @return array
	 */
	function getSQL() {
		return $this->sql;
	}

	/**
	 * Get the set of installation notes.
	 * @return array
	 */
	function getNotes() {
		return $this->notes;
	}

	/**
	 * Get the contents of the updated configuration file.
	 * @return string
	 */
	function getConfigContents() {
		return $this->configContents;
	}

	/**
	 * Check if installer was able to write out new config file.
	 * @return boolean
	 */
	function wroteConfig() {
		return $this->wroteConfig;
	}

	/**
	 * Return the error code.
	 * Valid return values are:
	 *   - 0 = no error
	 *   - INSTALLER_ERROR_GENERAL = general installation error.
	 *   - INSTALLER_ERROR_DB = database installation error
	 * @return int
	 */
	function getErrorType() {
		return isset($this->errorType) ? $this->errorType : 0;
	}

	/**
	 * The error message, if an error has occurred.
	 * In the case of a database error, an unlocalized string containing the error message is returned.
	 * For any other error, a localization key for the error message is returned.
	 * @return string
	 */
	function getErrorMsg() {
		return $this->errorMsg;
	}

	/**
	 * Return the error message as a localized string.
	 * @return string.
	 */
	function getErrorString() {
		switch ($this->getErrorType()) {
			case INSTALLER_ERROR_DB:
				return 'DB: ' . $this->getErrorMsg();
			default:
				return __($this->getErrorMsg());
		}
	}

	/**
	 * Set the error type and messgae.
	 * @param $type int
	 * @param $msg string Text message (INSTALLER_ERROR_DB) or locale key (otherwise)
	 */
	function setError($type, $msg) {
		$this->errorType = $type;
		$this->errorMsg = $msg;
	}

	/**
	 * Set the logger for this installer.
	 * @param $logger Logger
	 */
	function setLogger($logger) {
		$this->logger = $logger;
	}

	/**
	 * Clear the data cache files (needed because of direct tinkering
	 * with settings tables)
	 * @return boolean
	 */
	function clearDataCache() {
		$cacheManager = CacheManager::getManager();
		$cacheManager->flush(null, CACHE_TYPE_FILE);
		$cacheManager->flush(null, CACHE_TYPE_OBJECT);
		return true;
	}

	/**
	 * Set the current version for this installer.
	 * @param $version Version
	 */
	function setCurrentVersion($version) {
		$this->currentVersion = $version;
	}

	/**
	 * For upgrade: install email templates and data
	 * @param $installer object
	 * @param $attr array Attributes: array containing
	 * 		'key' => 'EMAIL_KEY_HERE',
	 * 		'locales' => 'en_US,fr_CA,...'
	 */
	function installEmailTemplate($installer, $attr) {
		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplateDao->installEmailTemplates($emailTemplateDao->getMainEmailTemplatesFilename(), false, $attr['key']);
		foreach (explode(',', $attr['locales']) as $locale) {
			$emailTemplateDao->installEmailTemplateData($emailTemplateDao->getMainEmailTemplateDataFilename($locale), false, $attr['key']);
		}
		return true;
	}

	/**
	 * Install the given filter configuration file.
	 * @param $filterConfigFile string
	 * @return boolean true when successful, otherwise false
	 */
	function installFilterConfig($filterConfigFile) {
		static $filterHelper = false;

		// Parse the filter configuration.
		$xmlParser = new XMLParser();
		$tree = $xmlParser->parse($filterConfigFile);

		// Validate the filter configuration.
		if (!$tree) {
			$xmlParser->destroy();
			return false;
		}

		// Get the filter helper.
		if ($filterHelper === false) {
			import('lib.pkp.classes.filter.FilterHelper');
			$filterHelper = new FilterHelper();
		}

		// Are there any filter groups to be installed?
		$filterGroupsNode = $tree->getChildByName('filterGroups');
		if (is_a($filterGroupsNode, 'XMLNode')) {
			$filterHelper->installFilterGroups($filterGroupsNode);
		}

		// Are there any filters to be installed?
		$filtersNode = $tree->getChildByName('filters');
		if (is_a($filtersNode, 'XMLNode')) {
			foreach ($filtersNode->getChildren() as $filterNode) { /* @var $filterNode XMLNode */
				$filterHelper->configureFilter($filterNode);
			}
		}

		// Get rid of the parser.
		$xmlParser->destroy();
		return true;
	}

	/**
	 * Check to see whether a column exists.
	 * Used in installer XML in conditional checks on <data> nodes.
	 * @param $tableName string
	 * @param $columnName string
	 * @return boolean
	 */
	function columnExists($tableName, $columnName) {
		$siteDao = DAORegistry::getDAO('SiteDAO');
		$dataSource = $siteDao->getDataSource();
		$dict = NewDataDictionary($dataSource);

		// Make sure the table exists
		$tables = $dict->MetaTables('TABLES', false);
		if (!in_array($tableName, $tables)) return false;

		// Check to see whether it contains the specified column.
		// Oddly, MetaColumnNames doesn't appear to be available.
		$columns = $dict->MetaColumns($tableName);
		foreach ($columns as $column) {
			if ($column->name == $columnName) return true;
		}
		return false;
	}

	/**
	 * Check to see whether a table exists.
	 * Used in installer XML in conditional checks on <data> nodes.
	 * @param $tableName string
	 * @return boolean
	 */
	function tableExists($tableName) {
		$siteDao = DAORegistry::getDAO('SiteDAO');
		$dataSource = $siteDao->getDataSource();
		$dict = NewDataDictionary($dataSource);

		// Check whether the table exists.
		$tables = $dict->MetaTables('TABLES', false);
		return in_array($tableName, $tables);
	}

	/**
	 * Insert or update plugin data in versions
	 * and plugin_settings tables.
	 * @return boolean
	 */
	function addPluginVersions() {
		$versionDao = DAORegistry::getDAO('VersionDAO');
		import('lib.pkp.classes.site.VersionCheck');
		$fileManager = new FileManager();
		$categories = PluginRegistry::getCategories();
		foreach ($categories as $category) {
			PluginRegistry::loadCategory($category);
			$plugins = PluginRegistry::getPlugins($category);
			if (is_array($plugins)) {
				foreach ($plugins as $plugin) {
					$versionFile = $plugin->getPluginPath() . '/version.xml';

					if ($fileManager->fileExists($versionFile)) {
						$versionInfo = VersionCheck::parseVersionXML($versionFile);
						$pluginVersion = $versionInfo['version'];
					} else {
						$pluginVersion = new Version(
							1, 0, 0, 0, // Major, minor, revision, build
							Core::getCurrentDate(), // Date installed
							1,	// Current
							'plugins.'.$category, // Type
							basename($plugin->getPluginPath()), // Product
							'',	// Class name
							0,	// Lazy load
							$plugin->isSitePlugin()	// Site wide
						);
					}
					$versionDao->insertVersion($pluginVersion, true);
				}
			}
		}

		return true;
	}

	/**
	 * Fail the upgrade.
	 * @param $installer Installer
	 * @param $attr array Attributes
	 * @return boolean
	 */
	function abort($installer, $attr) {
		$installer->setError(INSTALLER_ERROR_GENERAL, $attr['message']);
		return false;
	}
}

?>
