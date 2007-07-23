<?php

/**
 * @file InstallForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package install.form
 * @class InstallForm
 *
 * Form for system installation.
 *
 * $Id$
 */

import('install.Install');
import('site.VersionCheck');
import('form.Form');

class InstallForm extends Form {

	/** @var array locales supported by this system */
	var $supportedLocales;

	/** @var array client character sets supported by this system */
	var $supportedClientCharsets;

	/** @var array connection character sets supported by this system */
	var $supportedConnectionCharsets;

	/** @var array database character sets supported by this system */
	var $supportedDatabaseCharsets;

	/** @var array database drivers supported by this system */
	var $supportedDatabaseDrivers;
	
	/**
	 * Constructor.
	 */
	function InstallForm() {
		parent::Form('install/install.tpl');
		
		// FIXME Move the below options to an external configuration file?
		$this->supportedLocales = Locale::getAllLocales();
		
		$this->supportedClientCharsets = array (
			'utf-8' => 'Unicode (UTF-8)',
			'iso-8859-1' => 'Western (ISO-8859-1)'
		);
		
		$this->supportedConnectionCharsets = array (
			'' => Locale::translate('common.notApplicable'),
			'utf8' => 'Unicode (UTF-8)'
		);
		
		$this->supportedDatabaseCharsets = array (
			'' => Locale::translate('common.notApplicable'),
			'utf8' => 'Unicode (UTF-8)'
		);
		
		$this->supportedEncryptionAlgorithms = array (
			'md5' => 'MD5'
		);
		if (function_exists('sha1')) {
			$this->supportedEncryptionAlgorithms['sha1'] = 'SHA1';
		}
		
		$this->supportedDatabaseDrivers = array (
			// <adodb-driver> => array(<php-module>, <name>)
			'mysql' => array('mysql', 'MySQL'),
			'postgres' => array('pgsql', 'PostgreSQL'),
			'oracle' => array('oci8', 'Oracle'),
			'mssql' => array('mssql', 'MS SQL Server'),
			'fbsql' => array('fbsql', 'FrontBase'),
			'ibase' => array('ibase', 'Interbase'),
			'firebird' => array('ibase', 'Firebird'),
			'informix' => array('ifx', 'Informix'),
			'sybase' => array('sybase', 'Sybase'),
			'odbc' => array('odbc', 'ODBC'),
		);
		
		// Validation checks for this form
		$this->addCheck(new FormValidatorInSet($this, 'locale', 'required', 'installer.form.localeRequired', array_keys($this->supportedLocales)));
		$this->addCheck(new FormValidatorCustom($this, 'locale', 'required', 'installer.form.localeRequired', array('Locale', 'isLocaleValid')));
		$this->addCheck(new FormValidatorInSet($this, 'clientCharset', 'required', 'installer.form.clientCharsetRequired', array_keys($this->supportedClientCharsets)));
		$this->addCheck(new FormValidator($this, 'filesDir', 'required', 'installer.form.filesDirRequired'));
		$this->addCheck(new FormValidatorInSet($this, 'encryption', 'required', 'installer.form.encryptionRequired', array_keys($this->supportedEncryptionAlgorithms)));
		$this->addCheck(new FormValidator($this, 'adminUsername', 'required', 'installer.form.usernameRequired'));
		$this->addCheck(new FormValidatorAlphaNum($this, 'adminUsername', 'required', 'installer.form.usernameAlphaNumeric'));
		$this->addCheck(new FormValidator($this, 'adminPassword', 'required', 'installer.form.passwordRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'adminPassword', 'required', 'installer.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'adminPassword2\');'), array(&$this)));
		$this->addCheck(new FormValidatorEmail($this, 'adminEmail', 'required', 'installer.form.emailRequired'));
		$this->addCheck(new FormValidatorInSet($this, 'databaseDriver', 'required', 'installer.form.databaseDriverRequired', array_keys($this->supportedDatabaseDrivers)));
		$this->addCheck(new FormValidator($this, 'databaseName', 'required', 'installer.form.databaseNameRequired'));
		$this->addCheck(new FormValidatorPost($this));

		// Give plugins the opportunity to alter installer behavior.
		PluginRegistry::loadAllPlugins();
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('localeOptions', $this->supportedLocales);
		$templateMgr->assign('clientCharsetOptions', $this->supportedClientCharsets);
		$templateMgr->assign('connectionCharsetOptions', $this->supportedConnectionCharsets);
		$templateMgr->assign('databaseCharsetOptions', $this->supportedDatabaseCharsets);
		$templateMgr->assign('encryptionOptions', $this->supportedEncryptionAlgorithms);
		$templateMgr->assign('databaseDriverOptions', $this->checkDBDrivers());
		$templateMgr->assign('supportsMBString', String::hasMBString() ? Locale::translate('common.yes') : Locale::translate('common.no'));
		$templateMgr->assign('version', VersionCheck::getCurrentCodeVersion());

		parent::display();
	}
	
	/**
	 * Initialize form data.
	 */
	function initData() {
		$cwd = getcwd();
		if (Core::isWindows()) {
			// Replace backslashes with slashes for the default files directory.
			$cwd = str_replace('\\', '/', $cwd);
		}

		$this->_data = array(
			'locale' => Locale::getLocale(),
			'additionalLocales' => array(),
			'clientCharset' => 'utf-8',
			'connectionCharset' => '',
			'databaseCharset' => '',
			'encryption' => 'md5',
			'filesDir' =>  $cwd . '/files',
			'skipFilesDir' =>  0,			
			'databaseDriver' => 'mysql',
			'databaseHost' => 'localhost',
			'databaseUsername' => 'root',
			'databasePassword' => '',
			'databaseName' => 'ojs',
			'createDatabase' => 1,
			'oaiRepositoryId' => 'ojs.' . Request::getServerHost()
		);
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'locale',
			'additionalLocales',
			'clientCharset',
			'connectionCharset',
			'databaseCharset',
			'filesDir',
			'skipFilesDir',			
			'encryption',
			'adminUsername',
			'adminPassword',
			'adminPassword2',
			'adminEmail',
			'databaseDriver',
			'databaseHost',
			'databaseUsername',
			'databasePassword',
			'databaseName',
			'createDatabase',
			'oaiRepositoryId',
			'manualInstall'
		));
		
		if ($this->getData('additionalLocales') == null || !is_array($this->getData('additionalLocales'))) {
			$this->setData('additionalLocales', array());
		}
	}
	
	/**
	 * Perform installation.
	 */
	function execute() {
		$templateMgr = &TemplateManager::getManager();
		$installer = &new Install($this->_data);
		
		if ($installer->execute()) {
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
		
		$installer->destroy();
	}
	
	/**
	 * Check if database drivers have the required PHP module loaded.
	 * The names of drivers that appear to be unavailable are bracketed.
	 * @return array 
	 */
	function checkDBDrivers() {
		$dbDrivers = array();
		foreach ($this->supportedDatabaseDrivers as $driver => $info) {
			list($module, $name) = $info;
			if (!extension_loaded($module)) {
				$name = '[ ' . $name . ' ]';
			}
			$dbDrivers[$driver] = $name;
		}
		return $dbDrivers;
	}
	
	/**
	 * Fail with a generic installation error.
	 * @param $errorMsg string
	 */
	function installError($errorMsg) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign(array('isInstallError' => true, 'errorMsg' => $errorMsg));
		error_log($errorMsg);
		$this->display();
	}
	
	/**
	 * Fail with a database installation error.
	 * @param $errorMsg string
	 */
	function dbInstallError($errorMsg) {
		$templateMgr = &TemplateManager::getManager();
		if (empty($errorMsg)) $errorMsg = Locale::translate('common.error.databaseErrorUnknown');
		$templateMgr->assign(array('isInstallError' => true, 'dbErrorMsg' => $errorMsg));
		error_log($errorMsg);
		$this->display();
	}
	
}

?>
