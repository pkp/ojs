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

import('install.Installer');

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
		
		$this->supportedEncryptionAlgorithms = array (
			'md5' => 'MD5'
		);
		if (function_exists('sha1')) {
			$this->supportedEncryptionAlgorithms['sha1'] = 'SHA1';
		}
		
		$this->supportedDatabaseDrivers = array (
			'mysql' => 'MySQL',
			'postgres' => 'PostgreSQL',
			'oracle' => 'Oracle',
			'mssql' => 'MS SQL Server'
		);
		
		// Validation checks for this form
		$this->addCheck(new FormValidatorInSet(&$this, 'locale', 'required', 'installer.form.localeRequired', array_keys($this->supportedLocales)));
		$this->addCheck(new FormValidator(&$this, 'filesDir', 'required', 'installer.form.filesDirRequired'));
		$this->addCheck(new FormValidatorInSet(&$this, 'encryption', 'required', 'installer.form.encryptionRequired', array_keys($this->supportedEncryptionAlgorithms)));
		$this->addCheck(new FormValidator(&$this, 'username', 'required', 'user.profile.form.usernameRequired'));
		$this->addCheck(new FormValidatorAlphaNum(&$this, 'username', 'required', 'user.register.form.usernameAlphaNumeric'));
		$this->addCheck(new FormValidator(&$this, 'password', 'required', 'user.profile.form.passwordRequired'));
			$this->addCheck(new FormValidatorCustom(&$this, 'password', 'required', 'user.register.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));
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
		$templateMgr->assign('encryptionOptions', $this->supportedEncryptionAlgorithms);
		$templateMgr->assign('databaseDriverOptions', $this->supportedDatabaseDrivers);

		parent::display();
	}
	
	/**
	 * Initialize form data.
	 */
	function initData() {
		$this->_data = array(
			'locale' => 'en_US',
			'encryption' => 'md5',
			'filesDir' =>  getcwd() . '/files',
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
			'encryption',
			'username',
			'password',
			'password2',
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
				
			}
			if (!$installer->wroteConfig()) {
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
