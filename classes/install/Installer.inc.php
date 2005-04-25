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

// Default data
define('INSTALLER_DEFAULT_LOCALE', 'en_US');
define('INSTALLER_DEFAULT_SITE_TITLE', 'common.openJournalSystems');
define('INSTALLER_DEFAULT_MIN_PASSWORD_LENGTH', 6);

import('config.ConfigParser');
import('db.DBDataXMLParser');
import('site.Version');
import('site.VersionDAO');

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

		if (!$this->getParam('skipFilesDir')) {
			// Check if files directory exists and is writeable
			if (!(file_exists($this->getParam('filesDir')) &&  is_writeable($this->getParam('filesDir')))) {
				// Files upload directory unusable
				$this->setError(INSTALLER_ERROR_GENERAL, 'installer.installFilesDirError');
				return false;
			} else {
					// Create required subdirectories
					$dirsToCreate = array('site', 'journals');
					foreach ($dirsToCreate as $dirName) {
						$dirToCreate = $this->getParam('filesDir') . '/' . $dirName;
						if (!file_exists($dirToCreate)) {
							import('file.FileManager');
							if (!FileManager::mkdir($dirToCreate)) {
								$this->setError(INSTALLER_ERROR_GENERAL, 'installer.installFilesDirError');
								return false;
							}
						}
					}
			}
				
			// Check if public files directory exists and is writeable
			$publicFilesDir = Config::getVar('files', 'public_files_dir');
			if (!(file_exists($publicFilesDir) &&  is_writeable($publicFilesDir))) {
				// Public files upload directory unusable
				$this->setError(INSTALLER_ERROR_GENERAL, 'installer.publicFilesDirError');
				return false;
			} else {
				// Create required subdirectories
				$dirsToCreate = array('site', 'journals');
				foreach ($dirsToCreate as $dirName) {
					$dirToCreate = $publicFilesDir . '/' . $dirName;
					if (!file_exists($dirToCreate)) {
						import('file.FileManager');
						if (!FileManager::mkdir($dirToCreate)) {
							$this->setError(INSTALLER_ERROR_GENERAL, 'installer.publicFilesDirError');
							return false;
						}
					}
				}
			}
		}
		
		// Set locales to install
		$locale = $this->getParam('locale');
		$installedLocales = $this->getParam('additionalLocales');
		if (!isset($installedLocales) || !is_array($installedLocales)) {
			$installedLocales = array();
		}
		if (!in_array($locale, $installedLocales) && Locale::isLocaleValid($locale)) {
			array_push($installedLocales, $locale);
		}
		
		// Build list of database schema and data files for installation
		$schemaFiles = array();
		$dataFiles = array();
		
		// Get version information
		$versionString = $installTree->getAttribute('version');
		if (!isset($versionString)) {
			$versionString = '0.0.0.0';
		}
		$version = &Version::fromString($versionString);
		$version->setCurrent(true);
		
		foreach ($installTree->getChildren() as $installFile) {
			// Filename substitution for locales
			if (strstr($installFile->getAttribute('file'), '{$installedLocale}')) {
				$fileNames = array();
				foreach ($installedLocales as $thisLocale) {
					$fileName = str_replace('{$installedLocale}', $thisLocale, $installFile->getAttribute('file'));

					if (file_exists(XML_DBSCRIPTS_DIR . '/'. $fileName)) {
						array_push($fileNames, $fileName);
					}
				}
				
				switch ($installFile->getName()) {
					case 'schema':
						$schemaFiles = array_merge($schemaFiles, $fileNames);
						break;
					case 'data':
						$dataFiles = array_merge($dataFiles, $fileNames);
						break;
				}
				
			} else {
				$fileName = str_replace('{$locale}', $this->getParam('locale'), $installFile->getAttribute('file'));
				if (!file_exists(XML_DBSCRIPTS_DIR . '/'. $fileName)) {
					// Use version from default locale if data file is not available in the selected locale
					$fileName = str_replace('{$locale}', INSTALLER_DEFAULT_LOCALE, $installFile->getAttribute('file'));
				}
	
				switch ($installFile->getName()) {
					case 'schema':
						array_push($schemaFiles, $fileName);
						break;
					case 'data':
						array_push($dataFiles, $fileName);
						break;
				}
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
				null,
				true,
				$this->getParam('connectionCharset') == '' ? false : $this->getParam('connectionCharset')
			);
			
			$dbconn = &$conn->getDBConn();
			
			if (!$conn->isConnected()) {
				$this->setError(INSTALLER_ERROR_DB, $dbconn->errorMsg());
				return false;
			}
			
			$dbdict = &NewDataDictionary($dbconn);
			if ($this->getParam('databaseCharset') != '') {
				$dbdict->SetCharSet($this->getParam('databaseCharset'));
			}
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
			
			if ($this->getParam('createDatabase')) {
				// Get database creation sql
				$dbdict = &NewDataDictionary($dbconn);
				if ($this->getParam('databaseCharset') != '') {
					$dbdict->SetCharSet($this->getParam('databaseCharset'));
				}
				list($sql) = $dbdict->CreateDatabase($this->getParam('databaseName'));
				unset($dbdict);
				array_push($this->sql, $sql);
			}
			
		} else {
			// Connect to database
			$conn = &new DBConnection(
				$this->getParam('databaseDriver'),
				$this->getParam('databaseHost'),
				$this->getParam('databaseUsername'),
				$this->getParam('databasePassword'),
				$this->getParam('databaseName'),
				true,
				$this->getParam('connectionCharset') == '' ? false : $this->getParam('connectionCharset')
			);
			
			$dbconn = &$conn->getDBConn();
			
			if (!$conn->isConnected()) {
				$this->setError(INSTALLER_ERROR_DB, $dbconn->errorMsg());
				return false;
			}
		}
			
		// Parse database schema files
		require_once('adodb/adodb-xmlschema.inc.php');
		$schemaParser = &new adoSchema($dbconn, $this->getParam('databaseCharset') == '' ? false : $this->getParam('databaseCharset'));
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
			// Add insert statements for default data
			// FIXME use ADODB data dictionary?
			array_push($this->sql, sprintf('INSERT INTO versions (major, minor, revision, build, date_installed, current) VALUES (%d, %d, %d, %d, NOW(), 1)', $version->getMajor(), $version->getMinor(), $version->getRevision(), $version->getBuild()));
			array_push($this->sql, sprintf('INSERT INTO site (title, locale, installed_locales) VALUES (\'%s\', \'%s\', \'%s\')', addslashes(Locale::translate(INSTALLER_DEFAULT_SITE_TITLE)), $this->getParam('locale'), join(':', $installedLocales)));
			array_push($this->sql, sprintf('INSERT INTO users (username, password) VALUES (\'%s\', \'%s\')', $this->getParam('adminUsername'), Validation::encryptCredentials($this->getParam('adminUsername'), $this->getParam('adminPassword'), $this->getParam('encryption'))));
			array_push($this->sql, sprintf('INSERT INTO roles (journal_id, user_id, role_id) VALUES (%d, %d, %d)', 0, 1, ROLE_ID_SITE_ADMIN));
			
			// Nothing further to do for a manual install
			$schemaParser->destroy();
			
		} else {
			// Execute the SQL statements to install the database tables and initial data
			$result = $schemaParser->ExecuteSchema($this->sql, false);
			$schemaParser->destroy();
				
			if (!$result) {
				// Database installation failed
				$this->setError(INSTALLER_ERROR_DB, $dbconn->errorMsg());
				return false;
			}
			
			
			// Add version information
			$versionDao = &DAORegistry::getDAO('VersionDAO', $dbconn);
			if (!$versionDao->insertVersion($version)) {
				$this->setError(INSTALLER_ERROR_DB, $dbconn->errorMsg());
				return false;
			}
			
			
			// Add initial site data
			$siteDao = &DAORegistry::getDAO('SiteDAO', $dbconn);
			$site = &new Site();
			$site->setTitle(Locale::translate(INSTALLER_DEFAULT_SITE_TITLE));
			$site->setJournalRedirect(0);
			$site->setMinPasswordLength(INSTALLER_DEFAULT_MIN_PASSWORD_LENGTH);
			$site->setlocale($this->getParam('locale'));
			$site->setInstalledLocales($installedLocales);
			if (!$siteDao->insertSite($site)) {
				$this->setError(INSTALLER_ERROR_DB, $dbconn->errorMsg());
				return false;
			}
			
			
			// Add initial site administrator user
			$userDao = &DAORegistry::getDAO('UserDAO', $dbconn);
			$user = &new User();
			$user->setUsername($this->getParam('adminUsername'));
			$user->setPassword(Validation::encryptCredentials($this->getParam('adminUsername'), $this->getParam('adminPassword'), $this->getParam('encryption')));
			$user->setFirstName('');
			$user->setLastName('');
			$user->setEmail('');
			if (!$userDao->insertUser($user)) {
				$this->setError(INSTALLER_ERROR_DB, $dbconn->errorMsg());
				return false;
			}
			
			$roleDao = &DAORegistry::getDao('RoleDAO', $dbconn);
			$role = &new Role();
			$role->setJournalId(0);
			$role->setUserId($user->getUserId());
			$role->setRoleId(ROLE_ID_SITE_ADMIN);
			if (!$roleDao->insertRole($role)) {
				$this->setError(INSTALLER_ERROR_DB, $dbconn->errorMsg());
				return false;
			}
		}
		
		
		// Update config file
		$configParser = &new ConfigParser();
		if (!$configParser->updateConfig(
				Config::getConfigFileName(),
				array(
					'general' => array(
						'installed' => 'On'
					),
					'database' => array(
						'driver' => $this->getParam('databaseDriver'),
						'host' => $this->getParam('databaseHost'),
						'username' => $this->getParam('databaseUsername'),
						'password' => $this->getParam('databasePassword'),
						'name' => $this->getParam('databaseName')
					),
					'i18n' => array(
						'locale' => $this->getParam('locale'),
						'client_charset' => $this->getParam('clientCharset'),
						'connection_charset' => $this->getParam('connectionCharset') == '' ? 'Off' : $this->getParam('connectionCharset'),
						'database_charset' => $this->getParam('databaseCharset') == '' ? 'Off' : $this->getParam('databaseCharset')
					),
					'files' => array(
						'files_dir' => $this->getParam('filesDir')
					),
					'security' => array(
						'encryption' => $this->getParam('encryption')
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
