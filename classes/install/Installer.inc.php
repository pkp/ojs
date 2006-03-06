<?php

/**
 * Installer.inc.php
 *
 * Copyright (c) 2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package install
 *
 * Base class for install and upgrade scripts.
 *
 * $Id$
 */

// Database installation files
define('INSTALLER_DATA_DIR', 'dbscripts/xml');
define('INSTALLER_DOCS_DIR', 'docs');

// Installer error codes
define('INSTALLER_ERROR_GENERAL', 1);
define('INSTALLER_ERROR_DB', 2);

// Default data
define('INSTALLER_DEFAULT_LOCALE', 'en_US');

import('db.DBDataXMLParser');
import('site.Version');
import('site.VersionDAO');
import('config.ConfigParser');

require_once('adodb/adodb-xmlschema.inc.php'); // FIXME?

class Installer {
	
	/** @var string descriptor path (relative to INSTALLER_DATA_DIR) */
	var $descriptor;

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
	
	/** @var adoSchema database schema parser */
	var $schemaXMLParser;
	
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
	 */
	function Installer($descriptor, $params = array()) {
		// Give the HookRegistry the opportunity to override this
		// method or alter its parameters.
		if (!HookRegistry::call('Installer::Installer', array(&$this, &$descriptor, &$params))) {
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
		if (isset($this->dataXMLParser)) {
			$this->dataXMLParser->destroy();
		}
		
		if (isset($this->schemaXMLParser)) {
			$this->schemaXMLParser->destroy();
		}
		HookRegistry::call('Installer::destroy', array(&$this));
	}
	
	/**
	 * Pre-installation.
	 * @return boolean
	 */
	function preInstall() {
		if (!isset($this->dbconn)) {
			// Connect to the database.
			$conn = &DBConnection::getInstance();
			$this->dbconn = &$conn->getDBConn();
			
			if (!$conn->isConnected()) {
				$this->setError(INSTALLER_ERROR_DB, $this->dbconn->errorMsg());
				return false;
			}
		}
		
		if (!isset($this->currentVersion)) {
			// Retrieve the currently installed version
			$versionDao = &DAORegistry::getDAO('VersionDAO');
			$this->currentVersion = &$versionDao->getCurrentVersion();
		}
		
		if (!isset($this->locale)) {
			$this->locale = Locale::getLocale();
		}
		
		if (!isset($this->installedLocales)) {
			$this->installedLocales = array_keys(Locale::getAllLocales());
		}
		
		if (!isset($this->schemaXMLParser)) {
			require_once('adodb/adodb-xmlschema.inc.php');
			$this->schemaXMLParser = &new adoSchema($this->dbconn, $this->dbconn->charSet);
		}
		
		if (!isset($this->dataXMLParser)) {
			$this->dataXMLParser = &new DBDataXMLParser();
			$this->dataXMLParser->setDBConn($this->dbconn);
		}

		$result = true;
		HookRegistry::call('Installer::preInstall', array(&$this, &$result));

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
		$result = true;
		HookRegistry::call('Installer::postInstall', array(&$this, &$result));
		return $result;
	}
	
	
	/**
	 * Record message to installation log.
	 * @var $message string
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
		$xmlParser = &new XMLParser();
		$installTree = $xmlParser->parse(INSTALLER_DATA_DIR . '/' . $this->descriptor);
		if (!$installTree) {
			// Error reading installation file
			$xmlParser->destroy();
			$this->setError(INSTALLER_ERROR_GENERAL, 'installer.installFileError');
			return false;
		}
		
		$versionString = $installTree->getAttribute('version');
		if (isset($versionString)) {
			$this->newVersion = &Version::fromString($versionString);
			$this->newVersion->setCurrent(1);
		} else {
			$this->newVersion = $this->currentVersion;
		}	
		
		// Parse descriptor
		$this->parseInstallNodes($installTree);
		$xmlParser->destroy();
		
		$result = $this->getErrorType() == 0;

		HookRegistry::call('Installer::parseInstaller', array(&$this, &$result));
		return $result;
	}
	
	/**
	 * Execute the installer actions.
	 * @return boolean
	 */
	function executeInstaller() {
		$this->log(sprintf('version: %s', $this->newVersion->getVersionString()));
		foreach ($this->actions as $action) {
			if (!$this->executeAction($action)) {
				return false;
			}
		}

		$result = true;
		HookRegistry::call('Installer::executeInstaller', array(&$this, &$result));

		return $result;
	}
	
	/**
	 * Update the version number.
	 * @return boolean
	 */
	function updateVersion() {
		if ($this->newVersion->compare($this->currentVersion) > 0) {
			if ($this->getParam('manualInstall')) {
				// FIXME Would be better to have a mode where $dbconn->execute() saves the query
				return $this->executeSQL(sprintf('INSERT INTO versions (major, minor, revision, build, date_installed, current) VALUES (%d, %d, %d, %d, NOW(), 1)', $this->newVersion->getMajor(), $this->newVersion->getMinor(), $this->newVersion->getRevision(), $this->newVersion->getBuild()));
			} else {
				$versionDao = &DAORegistry::getDAO('VersionDAO');
				if (!$versionDao->insertVersion($this->newVersion)) {
					return false;
				}
			}
		}
		
		$result = true;
		HookRegistry::call('Installer::updateVersion', array(&$this, &$result));

		return $result;
	}
	
	
	//
	// Installer Parsing
	//
	
	/**
	 * Parse children nodes in the install descriptor.
	 * @param $installTree XMLNode
	 */
	function parseInstallNodes(&$installTree) {
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
	function addInstallAction(&$node) {
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
			if (!file_exists(INSTALLER_DATA_DIR . '/'. $newFileName)) {
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
				$this->log(sprintf('schema: %s', $action['file']));
				$sql = $this->schemaXMLParser->parseSchema(INSTALLER_DATA_DIR . '/'. $action['file']);
				if ($sql) {
					return $this->executeSQL($sql);
				} else {
					$this->setError(INSTALLER_ERROR_DB, str_replace('{$file}', $fileName, Locale::translate('installer.installParseDBFileError')));
					return false;
				}
				break;
			case 'data':
				$this->log(sprintf('data: %s', $action['file']));
				$sql = $this->dataXMLParser->parseData(INSTALLER_DATA_DIR . '/'. $action['file']);
				if ($sql) {
					return $this->executeSQL($sql);
				} else {
					$this->setError(INSTALLER_ERROR_DB, str_replace('{$file}', INSTALLER_DATA_DIR . '/'. $action['file'], Locale::translate('installer.installParseDBFileError')));
					return false;
				}
				break;
			case 'code':
				$this->log(sprintf('code: %s %s::%s', isset($action['file']) ? $action['file'] : 'Installer', isset($action['attr']['class']) ? $action['attr']['class'] : 'Installer', $action['attr']['function']));
				// FIXME Don't execute code with "manual install" ???
				if (isset($action['file'])) {
					require_once($action['file']);
				}
				if (isset($action['attr']['class'])) {
					return call_user_func(array($action['attr']['class'], $action['attr']['function']), $this);
				} else {
					return call_user_func(array(&$this, $action['attr']['function']));
				}
				break;
			case 'note':
				$this->log(sprintf('note: %s', $action['file']));
				$this->notes[] = join('', file(INSTALLER_DOCS_DIR . '/' . $action['file']));
				break;
		}
		
		return true;
	}
	
	/**
	 * Execute an SQL statement.
	 * @var $sql mixed
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
			if ($this->getParam('manualInstall')) {
				$this->sql[] = $sql;
				
			} else {
				$this->dbconn->execute($sql);
				if ($this->dbconn->errorNo() != 0) {
					$this->setError(INSTALLER_ERROR_DB, $this->dbconn->errorMsg());
					return false;
				}
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
		$configParser = &new ConfigParser();
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
	function &getCurrentVersion() {
		return $this->currentVersion;
	}
	
	/**
	 * Return new version after installation.
	 * @return Version
	 */
	function &getNewVersion() {
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
				return Locale::translate($this->getErrorMsg());
		}
	}
	
	/**
	 * Set the error type and messgae.
	 * @param $type int
	 * @param $msg string
	 */
	function setError($type, $msg) {
		$this->errorType = $type;
		$this->errorMsg = $msg;
	}
	
	/**
	 * Set the logger for this installer.
	 * @var $logger Logger
	 */
	function setLogger(&$logger) {
		$this->logger = $logger;
	}

}

?>
