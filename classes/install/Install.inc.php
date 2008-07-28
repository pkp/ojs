<?php

/**
 * @file classes/install/Install.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
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
 * It can also be used for a "manual install" to retrieve the SQL statements required for installation.
 */

// $Id$


// Default installation data
define('INSTALLER_DEFAULT_SITE_TITLE', 'common.openJournalSystems');
define('INSTALLER_DEFAULT_MIN_PASSWORD_LENGTH', 6);

import('install.PKPInstall');

class Install extends PKPInstall {

	/**
	 * Constructor.
	 * @see install.form.InstallForm for the expected parameters
	 * @param $params array installation parameters
	 */
	function Install($params) {
		parent::PKPInstall('install.xml', $params);
	}

	//
	// Installer actions
	//

	/**
	 * Get the names of the directories to create.
	 * @return array
	 */
	function getCreateDirectories() {
		$directories = parent::getCreateDirectories();
		$directories[] = 'journals';
		return $directories;
	}

	/**
	 * Create initial required data.
	 * @return boolean
	 */
	function createData() {
		if ($this->getParam('manualInstall')) {
			// Add insert statements for default data
			// FIXME use ADODB data dictionary?
			$this->executeSQL(sprintf('INSERT INTO site (primary_locale, installed_locales) VALUES (\'%s\', \'%s\')', $this->getParam('locale'), join(':', $this->installedLocales)));
			$this->executeSQL(sprintf('INSERT INTO site_settings (setting_name, setting_type, setting_value, locale) VALUES (\'%s\', \'%s\', \'%s\', \'%s\')', 'title', 'string', addslashes(Locale::translate(INSTALLER_DEFAULT_SITE_TITLE)), $this->getParam('locale')));
			$this->executeSQL(sprintf('INSERT INTO site_settings (setting_name, setting_type, setting_value, locale) VALUES (\'%s\', \'%s\', \'%s\', \'%s\')', 'contactName', 'string', addslashes(Locale::translate(INSTALLER_DEFAULT_SITE_TITLE)), $this->getParam('locale')));
			$this->executeSQL(sprintf('INSERT INTO site_settings (setting_name, setting_type, setting_value, locale) VALUES (\'%s\', \'%s\', \'%s\', \'%s\')', 'contactEmail', 'string', addslashes($this->getParam('adminEmail')), $this->getParam('locale')));
			$this->executeSQL(sprintf('INSERT INTO users (user_id, username, first_name, last_name, password, email, date_registered, date_last_login) VALUES (%d, \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\')', 1, $this->getParam('adminUsername'), $this->getParam('adminUsername'), $this->getParam('adminUsername'), Validation::encryptCredentials($this->getParam('adminUsername'), $this->getParam('adminPassword'), $this->getParam('encryption')), $this->getParam('adminEmail'), Core::getCurrentDate(), Core::getCurrentDate()));
			$this->executeSQL(sprintf('INSERT INTO roles (journal_id, user_id, role_id) VALUES (%d, %d, %d)', 0, 1, ROLE_ID_SITE_ADMIN));

		} else {
			// Add initial site data
			$locale = $this->getParam('locale');
			$siteDao = &DAORegistry::getDAO('SiteDAO', $this->dbconn);
			$site = &new Site();
			$site->setTitle(Locale::translate(INSTALLER_DEFAULT_SITE_TITLE), $locale);
			$site->setRedirect(0);
			$site->setMinPasswordLength(INSTALLER_DEFAULT_MIN_PASSWORD_LENGTH);
			$site->setPrimaryLocale($locale);
			$site->setInstalledLocales($this->installedLocales);
			$site->setSupportedLocales($this->installedLocales);
			$site->setContactName($site->getTitle($locale), $locale);
			$site->setContactEmail($this->getParam('adminEmail'), $locale);
			if (!$siteDao->insertSite($site)) {
				$this->setError(INSTALLER_ERROR_DB, $this->dbconn->errorMsg());
				return false;
			}

			// Add initial site administrator user
			$userDao = &DAORegistry::getDAO('UserDAO', $this->dbconn);
			$user = &new User();
			$user->setUsername($this->getParam('adminUsername'));
			$user->setPassword(Validation::encryptCredentials($this->getParam('adminUsername'), $this->getParam('adminPassword'), $this->getParam('encryption')));
			$user->setFirstName($user->getUsername());
			$user->setLastName('');
			$user->setEmail($this->getParam('adminEmail'));
			if (!$userDao->insertUser($user)) {
				$this->setError(INSTALLER_ERROR_DB, $this->dbconn->errorMsg());
				return false;
			}

			$roleDao = &DAORegistry::getDao('RoleDAO', $this->dbconn);
			$role = &new Role();
			$role->setJournalId(0);
			$role->setUserId($user->getUserId());
			$role->setRoleId(ROLE_ID_SITE_ADMIN);
			if (!$roleDao->insertRole($role)) {
				$this->setError(INSTALLER_ERROR_DB, $this->dbconn->errorMsg());
				return false;
			}
		}

		return true;
	}
}

?>
