<?php

/**
 * InstallForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package install.form
 *
 * Form for system installation.
 *
 * $Id$
 */

import('install.installer');

class InstallForm extends Form {

	/** @var array locales supported by this system */
	var $supportedLocales;

	/** @var array database drivers supported by this system */
	var $supportedDatabaseDrivers;
	
	/**
	 * Constructor.
	 */
	function InstallForm() {
		parent::Form('install/install.tpl');
		
		$this->supportedLocales = array (
			'en_US' => 'English'
		);
		
		$this->supportedDatabaseDrivers = array (
			'mysql' => 'MySQL',
			'postgres' => 'PostgreSQL',
			'oracle' => 'Oracle',
			'mssql' => 'MS SQL Server'
		);
		
		// Validation checks for this form
		$this->addCheck(new FormValidatorInSet(&$this, 'locale', 'required', 'installer.form.localeRequired', array_keys($this->supportedLocales)));
		$this->addCheck(new FormValidator(&$this, 'filesDir', 'required', 'installer.form.filesDirRequired'));
		$this->addCheck(new FormValidatorInSet(&$this, 'databaseDriver', 'required', 'installer.form.databaseDriverRequired', array_keys($this->supportedDatabaseDrivers)));
		$this->addCheck(new FormValidator(&$this, 'databaseHost', 'required', 'installer.form.databaseHostRequired'));
		$this->addCheck(new FormValidator(&$this, 'databaseUsername', 'required', 'installer.form.databaseUsernameRequired'));
		$this->addCheck(new FormValidator(&$this, 'databaseName', 'required', 'installer.form.databaseNameRequired'));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('localeOptions', $this->supportedLocales);
		$templateMgr->assign('databaseDriverOptions', $this->supportedDatabaseDrivers);

		parent::display();
	}
	
	/**
	 * Initialize form data.
	 */
	function initData() {
		$this->_data = array(
			'locale' => 'en_US',
			'filesDir' =>  Config::getVar('general', 'files_dir'),
			'databaseDriver' => 'mysql',
			'databaseHost' => 'localhost',
			'databaseUsername' => 'root',
			'databasePassword' => '',
			'databaseName' => 'ojs',
			'createDatabase' => 1
		);
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'locale',
			'filesDir',
			'databaseDriver',
			'databaseHost',
			'databaseUsername',
			'databasePassword',
			'databaseName',
			'createDatabase',
			'manualInstall'
		));
	}
	
	/**
	 * Perform installation.
	 */
	function execute() {
		$templateMgr = &TemplateManager::getManager();
		$installer = &new Installer($this->_data);
		
		if ($installer->install()) {
			if ($this->getData('manualInstall')) {
				// Display SQL statements that would have been executed during installation
				$templateMgr->assign(array('manualInstall' => true, 'installSql' => $installer->getSQL()));
				
			} else if (!$installer->wroteConfig()) {
				// Display config file contents for manual replacement
				$templateMgr->assign(array('writeConfigFailed' => true, 'configFileContents' => $installer->getConfigContents()));
			}
			
			$templateMgr->display('install/installComplete.tpl');
			
		} else {
			switch ($installer->getErrorType()) {
				case INSTALLER_ERROR_DB:
					$this->dbInstallError($installer->getErrorMsg());
					break;
				default:
					$this->installError($installer->getErrorMsg());
					break;
			}
		}
	}
	
	/**
	 * Fail with a generic installation error.
	 * @param $errorMsg string
	 */
	function installError($errorMsg) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign(array('isInstallError' => true, 'errorMsg' => $errorMsg));
		$this->display();
	}
	
	/**
	 * Fail with a database installation error.
	 * @param $errorMsg string
	 */
	function dbInstallError($errorMsg) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign(array('isInstallError' => true, 'dbErrorMsg' => $errorMsg));
		$this->display();
	}
	
}

?>
