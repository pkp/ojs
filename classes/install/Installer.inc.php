<?php

/**
 * Installer.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package install
 *
 * Perform system installation.
 * This script will:
 *  - Create the database (optionally), and install the database tables and initial data.
 *  - Update the config file with installation parameters.
 * It can also be used for a "manual install" to retrieve the SQL statements required for installation.
 *
 * $Id$
 */

// Database installation files
define('XML_DBSCRIPTS_DIR', 'dbscripts/xml');
define('XML_INSTALL_FILE', XML_DBSCRIPTS_DIR . '/install.xml');

// Installer error codes
define('INSTALLER_ERROR_GENERAL', 1);
define('INSTALLER_ERROR_DB', 2);

import('config.ConfigParser');
import('db.DBDataXMLParser');

class Installer {

	/** @var array installation parameters */
	var $params;
	
	/** @var array XML database schema and data files used for the install */
	var $installFiles;
	
	/** @var array SQL statements for database installation */
	var $sql;
	
	/** @var string contents of the updated config file */
	var $configContents;
	
	/** @var boolean indicating if config file was written or not */
	var $wroteConfig;
	
	/** @var int error code (null | INSTALLER_ERROR_GENERAL | INSTALLER_ERROR_DB) */
	var $errorType;
	
	/** @var string the error message, if an installation error has occurred */
	var $errorMsg;
	
	/**
	 * Contructor.
	 * @see install.form.InstallForm for the expected parameters
	 * @param $params array installation parameters
	 */
	function Installer($params) {
		$this->params = $params;
	}
	
	/**
	 * Perform the installation.
	 * In the case of a manual install, only the SQL is parsed and nothing is actually installed.
	 * @return boolean
	 */
	function install() {
		$this->sql = array();
		$this->configFile = '';
		$this->wroteConfig = false;
		$this->errorType = null;
		$this->errorMsg = null;
		
		// Read installation descriptor file
		$xmlParser = &new XMLParser();
		$installTree = $xmlParser->parse(XML_INSTALL_FILE);
		if (!$installTree) {
			// Error reading installation file
			$xmlParser->destroy();
			$this->setError(INSTALLER_ERROR_GENERAL, 'installer.installFileError');
			return false;
		}
		
		// Build list of database schema and data files for installation
		$schemaFiles = array();
		$dataFiles = array();
		
		foreach ($installTree->getChildren() as $installFile) {
			// Filename substitution for the locale
			$fileName = str_replace('{$locale}', $this->getParam('locale'), $installFile->getAttribute('file'));

			switch ($installFile->getName()) {
				case 'schema':
					array_push($schemaFiles, $fileName);
					break;
				case 'data':
					array_push($dataFiles, $fileName);
					break;
			}
		}
		$xmlParser->destroy();
		
		$this->installFiles = array_merge($schemaFiles, $dataFiles);
		
		if (!$this->getParam('manualInstall') && $this->getParam('createDatabase')) {
			// Create new database
			$conn = &new DBConnection(
				$this->getParam('databaseDriver'),
				$this->getParam('databaseHost'),
				$this->getParam('databaseUsername'),
				$this->getParam('databasePassword'),
				null
			);
			
			$dbconn = &$conn->getDBConn();
			$dbdict = &NewDataDictionary($dbconn);
			list($sql) = $dbdict->CreateDatabase($this->getParam('databaseName'));
			unset($dbdict);
							
			$dbconn->execute($sql);
			if ($dbconn->errorNo() != 0) {
				$this->setError(INSTALLER_ERROR_DB, $dbconn->errorMsg());
				return false;
			}
			
			$dbconn->disconnect();
		}
		
		if ($this->getParam('manualInstall')) {
			// Do not perform database installation for manual install
			// Create connection object with the appropriate database driver for adodb-xmlschema
			$conn = &new DBConnection(
				$this->getParam('databaseDriver'),
				null,
				null,
				null,
				null
			);
			$dbconn = &$conn->getDBConn();
			
		} else {
			// Connect to database
			$conn = &new DBConnection(
				$this->getParam('databaseDriver'),
				$this->getParam('databaseHost'),
				$this->getParam('databaseUsername'),
				$this->getParam('databasePassword'),
				$this->getParam('databaseName')
			);
			$dbconn = &$conn->getDBConn();
		}
			
		// Parse database schema files
		require_once('adodb/adodb-xmlschema.inc.php');
		$schemaParser = &new adoSchema($dbconn);
		for ($i = 0, $count = count($schemaFiles); $i < $count; $i++) {
			$fileName = XML_DBSCRIPTS_DIR . '/'. $schemaFiles[$i];
			$sql = $schemaParser->parseSchema($fileName);
			if ($sql) {
				$this->sql = array_merge($this->sql, $sql);
				
			} else {
				$this->setError(INSTALLER_ERROR_DB, str_replace('{$file}', $fileName, Locale::translate('installer.installParseDBFileError')));
				return false;
			}
		}
		
		// Parse database data files
		$dataXMLParser = &new DBDataXMLParser();
		for ($i = 0, $count = count($dataFiles); $i < $count; $i++) {
			$fileName = XML_DBSCRIPTS_DIR . '/'. $dataFiles[$i];
			$sql = $dataXMLParser->parseData($fileName);
			if ($sql) {
				$this->sql = array_merge($this->sql, $sql);
				
			} else {
				$this->setError(INSTALLER_ERROR_DB, str_replace('{$file}', $fileName, Locale::translate('installer.installParseDBFileError')));
				return false;
			}
		}
		$dataXMLParser->destroy();
		
		if ($this->getParam('manualInstall')) {
			// Nothing further to do for a manual install
			$schemaParser->destroy();
			return true;
		}
		
		
		// Execute the SQL statements to install the database tables and initial data
		$result = $schemaParser->ExecuteSchema($this->sql, false);
		$schemaParser->destroy();
			
		if (!$result) {
			// Database installation failed
			$this->setError(INSTALLER_ERROR_DB, $dbconn->errorMsg());
			return false;
		}
		
		
		// Update config file
		$configParser = &new ConfigParser();
		if (!$configParser->updateConfig(
				Config::getConfigFileName(),
				array(
					'general' => array(
						'installed' => 'true'
					),
					'database' => array(
						'driver' => $this->getParam('databaseDriver'),
						'host' => $this->getParam('databaseHost'),
						'username' => $this->getParam('databaseUsername'),
						'password' => $this->getParam('databasePassword'),
						'name' => $this->getParam('databaseName')
					),
					'i18n' => array(
						'locale' => $this->getParam('locale')
					)
				)
		)) {
			// Error reading config file
			$this->setError(INSTALLER_ERROR_GENERAL, 'installer.configFileError');
			return false;
		}

		$this->configContents = $configParser->getFileContents();
		if ($configParser->writeConfig(Config::getConfigFileName())) {
			$this->wroteConfig = true;
		}
		
		return true;
	}
	
	/**
	 * Get the value of an installation parameter.
	 * @param $name
	 * @return mixed
	 */
	function getParam($name) {
		return isset($this->params[$name]) ? $this->params[$name] : null;
	}
	
	/**
	 * Get the set of XML database schema and data files used for the install.
	 * @return array
	 */
	function getInstallFiles() {
		return $this->installFiles;
	}
	
	/**
	 * Get the set of SQL statements required to perform the install.
	 * @return array
	 */
	function getSQL() {
		return $this->sql;
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
	 * Set the error type and messgae.
	 * @param $type int
	 * @param $msg string
	 */
	function setError($type, $msg) {
		$this->errorType = $type;
		$this->errorMsg = $msg;
	}
	
}

?>
