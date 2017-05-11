<?php

/**
 * @defgroup install Install
 * Implements a software installer, including a flexible upgrader that can
 * manage schema changes, data representation changes, etc.
 */

/**
 * @file classes/install/PKPInstall.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Install
 * @ingroup install
 * @see Installer, InstallForm
 *
 * @brief Perform system installation.
 *
 * This script will:
 *  - Create the database (optionally), and install the database tables and initial data.
 *  - Update the config file with installation parameters.
 */


import('lib.pkp.classes.install.Installer');

class PKPInstall extends Installer {

	/**
	 * Constructor.
	 * @see install.form.InstallForm for the expected parameters
	 * @param $xmlDescriptor string descriptor path
	 * @param $params array installer parameters
	 * @param $isPlugin boolean true iff a plugin is being installed
	 */
	function __construct($xmlDescriptor, $params, $isPlugin) {
		parent::__construct($xmlDescriptor, $params, $isPlugin);
	}

	/**
	 * Returns true iff this is an upgrade process.
	 */
	function isUpgrade() {
		return false;
	}

	/**
	 * Pre-installation.
	 * @return boolean
	 */
	function preInstall() {
		if(!isset($this->currentVersion)) {
			$this->currentVersion = Version::fromString('');
		}

		$this->locale = $this->getParam('locale');
		$this->installedLocales = $this->getParam('additionalLocales');
		if (!isset($this->installedLocales) || !is_array($this->installedLocales)) {
			$this->installedLocales = array();
		}
		if (!in_array($this->locale, $this->installedLocales) && AppLocale::isLocaleValid($this->locale)) {
			array_push($this->installedLocales, $this->locale);
		}

		// Connect to database
		$conn = new DBConnection(
			$this->getParam('databaseDriver'),
			$this->getParam('databaseHost'),
			$this->getParam('databaseUsername'),
			$this->getParam('databasePassword'),
			$this->getParam('createDatabase') ? null : $this->getParam('databaseName'),
			false,
			$this->getParam('connectionCharset') == '' ? false : $this->getParam('connectionCharset')
		);

		$this->dbconn =& $conn->getDBConn();

		if (!$conn->isConnected()) {
			$this->setError(INSTALLER_ERROR_DB, $this->dbconn->errorMsg());
			return false;
		}

		DBConnection::getInstance($conn);

		return parent::preInstall();
	}


	//
	// Installer actions
	//

	/**
	 * Get the names of the directories to create.
	 * @return array
	 */
	function getCreateDirectories() {
		return array('site');
	}

	/**
	 * Create required files directories
	 * FIXME No longer needed since FileManager will auto-create?
	 * @return boolean
	 */
	function createDirectories() {
		// Check if files directory exists and is writeable
		if (!(file_exists($this->getParam('filesDir')) &&  is_writeable($this->getParam('filesDir')))) {
			// Files upload directory unusable
			$this->setError(INSTALLER_ERROR_GENERAL, 'installer.installFilesDirError');
			return false;
		} else {
			// Create required subdirectories
			$dirsToCreate = $this->getCreateDirectories();
			import('lib.pkp.classes.file.FileManager');
			$fileManager = new FileManager();
			foreach ($dirsToCreate as $dirName) {
				$dirToCreate = $this->getParam('filesDir') . '/' . $dirName;
				if (!file_exists($dirToCreate)) {
					if (!$fileManager->mkdir($dirToCreate)) {
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
			$dirsToCreate = $this->getCreateDirectories();
			import('lib.pkp.classes.file.FileManager');
			$fileManager = new FileManager();
			foreach ($dirsToCreate as $dirName) {
				$dirToCreate = $publicFilesDir . '/' . $dirName;
				if (!file_exists($dirToCreate)) {
					if (!$fileManager->mkdir($dirToCreate)) {
						$this->setError(INSTALLER_ERROR_GENERAL, 'installer.publicFilesDirError');
						return false;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Create a new database if required.
	 * @return boolean
	 */
	function createDatabase() {
		if (!$this->getParam('createDatabase')) {
			return true;
		}

		// Get database creation sql
		$dbdict = NewDataDictionary($this->dbconn);

		if ($this->getParam('databaseCharset')) {
				$dbdict->SetCharSet($this->getParam('databaseCharset'));
		}

		list($sql) = $dbdict->CreateDatabase($this->getParam('databaseName'));
		unset($dbdict);

		if (!$this->executeSQL($sql)) {
			return false;
		}

		// Re-connect to the created database
		$this->dbconn->disconnect();

		$conn = new DBConnection(
			$this->getParam('databaseDriver'),
			$this->getParam('databaseHost'),
			$this->getParam('databaseUsername'),
			$this->getParam('databasePassword'),
			$this->getParam('databaseName'),
			true,
			$this->getParam('connectionCharset') == '' ? false : $this->getParam('connectionCharset')
		);

		DBConnection::getInstance($conn);

		$this->dbconn =& $conn->getDBConn();

		if (!$conn->isConnected()) {
			$this->setError(INSTALLER_ERROR_DB, $this->dbconn->errorMsg());
			return false;
		}

		return true;
	}

	/**
	 * Write the configuration file.
	 * @return boolean
	 */
	function createConfig() {
		return $this->updateConfig(
			array(
				'general' => array(
					'installed' => 'On',
					'base_url' => Request::getBaseUrl(),
					'enable_beacon' => $this->getParam('enableBeacon'),
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
				'oai' => array(
					'repository_id' => $this->getParam('oaiRepositoryId')
				)
			)
		);
	}

	/**
	 * Create initial required data.
	 * @return boolean
	 */
	function createData() {
		// Add initial site administrator user
		$userDao = DAORegistry::getDAO('UserDAO', $this->dbconn);
		$user = $userDao->newDataObject();
		$user->setUsername($this->getParam('adminUsername'));
		$user->setPassword(Validation::encryptCredentials($this->getParam('adminUsername'), $this->getParam('adminPassword'), $this->getParam('encryption')));
		$user->setFirstName($user->getUsername());
		$user->setLastName('');
		$user->setEmail($this->getParam('adminEmail'));
		$user->setInlineHelp(1);
		if (!$userDao->insertObject($user)) {
			$this->setError(INSTALLER_ERROR_DB, $this->dbconn->errorMsg());
			return false;
		}

		// Create an admin user group
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_DEFAULT);
		$userGroupDao = DAORegistry::getDao('UserGroupDAO', $this->dbconn);
		$adminUserGroup = $userGroupDao->newDataObject();
		$adminUserGroup->setRoleId(ROLE_ID_SITE_ADMIN);
		$adminUserGroup->setContextId(CONTEXT_ID_NONE);
		$adminUserGroup->setDefault(true);
		foreach ($this->installedLocales as $locale) {
			$name = __('default.groups.name.siteAdmin', array(), $locale);
			$namePlural = __('default.groups.plural.siteAdmin', array(), $locale);
			$adminUserGroup->setData('name', $name, $locale);
			$adminUserGroup->setData('namePlural', $namePlural, $locale);
		}
		if (!$userGroupDao->insertObject($adminUserGroup)) {
			$this->setError(INSTALLER_ERROR_DB, $this->dbconn->errorMsg());
			return false;
		}

		// Put the installer into this user group
		$userGroupDao->assignUserToGroup($user->getId(), $adminUserGroup->getId());

		// Add initial site data
		$locale = $this->getParam('locale');
		$siteDao = DAORegistry::getDAO('SiteDAO', $this->dbconn);
		$site = $siteDao->newDataObject();
		$site->setRedirect(0);
		$site->setMinPasswordLength(INSTALLER_DEFAULT_MIN_PASSWORD_LENGTH);
		$site->setPrimaryLocale($locale);
		$site->setInstalledLocales($this->installedLocales);
		$site->setSupportedLocales($this->installedLocales);
		if (!$siteDao->insertSite($site)) {
			$this->setError(INSTALLER_ERROR_DB, $this->dbconn->errorMsg());
			return false;
		}

		// Install email template list and data for each locale
		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplateDao->installEmailTemplates($emailTemplateDao->getMainEmailTemplatesFilename());
		foreach ($this->installedLocales as $locale) {
			$emailTemplateDao->installEmailTemplateData($emailTemplateDao->getMainEmailTemplateDataFilename($locale));
		}

		// Install default site settings
		$siteSettingsDao = DAORegistry::getDAO('SiteSettingsDAO');
		$siteSettingsDao->installSettings('registry/siteSettings.xml', array(
			'contactEmail' => $this->getParam('adminEmail')
		));

		return true;
	}
}

?>
