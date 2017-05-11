<?php

/**
 * @file classes/install/form/InstallForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class InstallForm
 * @ingroup install
 * @see Install
 *
 * @brief Form for system installation.
 */

import('classes.install.Install');
import('lib.pkp.classes.install.form.MaintenanceForm');

class InstallForm extends MaintenanceForm {

	/** @var array locales supported by this system */
	var $supportedLocales;

	/** @var array locale completeness booleans */
	var $localesComplete;

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
	 * @param $request PKPRequest
	 */
	function __construct($request) {
		parent::__construct($request, 'install/install.tpl');

		// FIXME Move the below options to an external configuration file?
		$this->supportedLocales = AppLocale::getAllLocales();
		$this->localesComplete = array();
		foreach ($this->supportedLocales as $key => $name) {
			$this->localesComplete[$key] = AppLocale::isLocaleComplete($key);
		}

		$this->supportedClientCharsets = array (
			'utf-8' => 'Unicode (UTF-8)',
			'iso-8859-1' => 'Western (ISO-8859-1)'
		);

		$this->supportedConnectionCharsets = array (
			'' => __('common.notApplicable'),
			'utf8' => 'Unicode (UTF-8)'
		);

		$this->supportedDatabaseCharsets = array (
			'' => __('common.notApplicable'),
			'utf8' => 'Unicode (UTF-8)'
		);

		$this->supportedDatabaseDrivers = array (
			// <adodb-driver> => array(<php-module>, <name>)
			'mysql' => array('mysql', 'MySQL'),
			'mysqli' => array('mysqli', 'MySQLi'),
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
		$this->addCheck(new FormValidatorCustom($this, 'locale', 'required', 'installer.form.localeRequired', array('AppLocale', 'isLocaleValid')));
		$this->addCheck(new FormValidatorInSet($this, 'clientCharset', 'required', 'installer.form.clientCharsetRequired', array_keys($this->supportedClientCharsets)));
		$this->addCheck(new FormValidator($this, 'filesDir', 'required', 'installer.form.filesDirRequired'));
		$this->addCheck(new FormValidator($this, 'adminUsername', 'required', 'installer.form.usernameRequired'));
		$this->addCheck(new FormValidatorUsername($this, 'adminUsername', 'required', 'installer.form.usernameAlphaNumeric'));
		$this->addCheck(new FormValidator($this, 'adminPassword', 'required', 'installer.form.passwordRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'adminPassword', 'required', 'installer.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'adminPassword2\');'), array($this)));
		$this->addCheck(new FormValidatorEmail($this, 'adminEmail', 'required', 'installer.form.emailRequired'));
		$this->addCheck(new FormValidatorInSet($this, 'databaseDriver', 'required', 'installer.form.databaseDriverRequired', array_keys($this->supportedDatabaseDrivers)));
		$this->addCheck(new FormValidator($this, 'databaseName', 'required', 'installer.form.databaseNameRequired'));
	}

	/**
	 * @copydoc Form::display
	 */
	function display($template = null) {
		import('lib.pkp.classes.xslt.XSLTransformer');
		$templateMgr = TemplateManager::getManager($this->_request);
		$templateMgr->assign(array(
			'localeOptions' => $this->supportedLocales,
			'localesComplete' => $this->localesComplete,
			'clientCharsetOptions' => $this->supportedClientCharsets,
			'connectionCharsetOptions' => $this->supportedConnectionCharsets,
			'databaseCharsetOptions' => $this->supportedDatabaseCharsets,
			'allowFileUploads' => get_cfg_var('file_uploads') ? __('common.yes') : __('common.no'),
			'maxFileUploadSize' => get_cfg_var('upload_max_filesize'),
			'databaseDriverOptions' => $this->checkDBDrivers(),
			'supportsMBString' => PKPString::hasMBString() ? __('common.yes') : __('common.no'),
			'phpIsSupportedVersion' => version_compare(PHP_REQUIRED_VERSION, PHP_VERSION) != 1,
			'xslEnabled' => XSLTransformer::checkSupport(),
			'xslRequired' => REQUIRES_XSL,
			'phpRequiredVersion' => PHP_REQUIRED_VERSION,
			'phpVersion' => PHP_VERSION,
		));

		parent::display();
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$docRoot = dirname($_SERVER['DOCUMENT_ROOT']);
		if (Core::isWindows()) {
			// Replace backslashes with slashes for the default files directory.
			$docRoot = str_replace('\\', '/', $docRoot);
		}

		// Add a trailing slash for paths that aren't filesystem root
		if ($docRoot !== '/') $docRoot .= '/';

		$this->_data = array(
			'locale' => AppLocale::getLocale(),
			'additionalLocales' => array(),
			'clientCharset' => 'utf-8',
			'connectionCharset' => '',
			'databaseCharset' => '',
			'filesDir' =>  $docRoot . 'files',
			'databaseDriver' => 'mysql',
			'databaseHost' => 'localhost',
			'databaseUsername' => Application::getName(),
			'databasePassword' => '',
			'databaseName' => Application::getName(),
			'createDatabase' => 1,
			'oaiRepositoryId' => Application::getName() . '.' . $this->_request->getServerHost(),
			'enableBeacon' => true,
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
			'enableBeacon',
		));

		if ($this->getData('additionalLocales') == null || !is_array($this->getData('additionalLocales'))) {
			$this->setData('additionalLocales', array());
		}
	}

	/**
	 * Perform installation.
	 */
	function execute() {
		$templateMgr = TemplateManager::getManager($this->_request);
		$installer = new Install($this->_data);

		if ($installer->execute()) {
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
}

?>
