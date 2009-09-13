<?php

/**
 * @defgroup tools
 */

/**
 * @file cliInstallTool.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Copyright (c) 2009 Florian Grandel
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CommandLineInstallTool
 * @ingroup tools
 *
 * @brief Abstract CLI tool for installing OJS. Extended by command line installers.
 */

require(dirname(__FILE__) . '/cliTool.inc.php');

import('install.Install');
import('install.form.InstallForm');
import('site.Version');
import('site.VersionCheck');

class CommandLineInstallTool extends CommandLineTool {

	/** @var $params array installation parameters */
	var $params;

	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	function CommandLineInstallTool($argv = array()) {
		parent::CommandLineTool($argv);
	}

	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Install tool\n"
			. "Usage: {$this->scriptName}\n";
	}

	/**
	 * Execute the script.
	 */
	function execute() {
		if ($this->readParams()) {
			$this->install();
		}
	}

	/**
	 * Perform installation.
	 */
	function install() {
		$installer = &new Install($this->params);
		$installer->setLogger($this);

		if ($installer->execute()) {
			if (count($installer->getNotes()) > 0) {
				printf("\nRelease Notes\n");
				printf("----------------------------------------\n");
				foreach ($installer->getNotes() as $note) {
					printf("%s\n\n", $note);
				}
			}

			if (!$installer->wroteConfig()) {
					printf("\nNew config.inc.php:\n");
					printf("----------------------------------------\n");
					echo $installer->getConfigContents();
					printf("----------------------------------------\n");
			}

			if ($this->params['manualInstall']) {
				if (count($installer->getSQL()) > 0) {
					printf("\nSQL\n");
					printf("----------------------------------------\n");
					foreach ($installer->getSQL() as $sql) {
						printf("%s\n\n", $sql);
					}
				}

			} else {
				$newVersion = &$installer->getNewVersion();
				printf("Successfully installed version %s\n", $newVersion->getVersionString());
			}

		} else {
			printf("ERROR: Installation failed: %s\n", $installer->getErrorString());
		}
	}

	/**
	 * Read installation parameters from stdin.
	 * FIXME: May want to implement an abstract "CLIForm" class handling input/validation.
	 * FIXME: Use readline if available?
	 */
	function readParams() {
		printf("%s\n", Locale::translate('installer.ojsInstallation'));

		$installForm = &new InstallForm();

		// Locale Settings
		$this->printTitle('installer.localeSettings');
		$this->readParamOptions('locale', 'locale.primary', $installForm->supportedLocales, 'en_US');
		$this->readParamOptions('additionalLocales', 'installer.additionalLocales', $installForm->supportedLocales, '', true);
		$this->readParamOptions('clientCharset', 'installer.clientCharset', $installForm->supportedClientCharsets, 'utf-8');
		$this->readParamOptions('connectionCharset', 'installer.connectionCharset', $installForm->supportedConnectionCharsets, '');
		$this->readParamOptions('databaseCharset', 'installer.databaseCharset', $installForm->supportedDatabaseCharsets, '');

		// File Settings
		$this->printTitle('installer.fileSettings');
		$this->readParam('filesDir', 'installer.filesDir');
		$this->readParamBoolean('skipFilesDir', 'installer.skipFilesDir');

		// Security Settings
		$this->printTitle('installer.securitySettings');
		$this->readParamOptions('encryption', 'installer.encryption', $installForm->supportedEncryptionAlgorithms, 'md5');

		// Administrator Account
		$this->printTitle('installer.administratorAccount');
		$this->readParam('adminUsername', 'user.username');
		@`/bin/stty -echo`;
		$this->readParam('adminPassword', 'user.password');
		printf("\n");
		do {
			$this->readParam('adminPassword2', 'user.register.repeatPassword');
			printf("\n");
		} while ($this->params['adminPassword'] != $this->params['adminPassword2']);
		@`/bin/stty echo`;
		$this->readParam('adminEmail', 'user.email');

		// Database Settings
		$this->printTitle('installer.databaseSettings');
		$this->readParamOptions('databaseDriver', 'installer.databaseDriver', $installForm->checkDBDrivers());
		$this->readParam('databaseHost', 'installer.databaseHost', '');
		$this->readParam('databaseUsername', 'installer.databaseUsername', '');
		$this->readParam('databasePassword', 'installer.databasePassword', '');
		$this->readParam('databaseName', 'installer.databaseName');
		$this->readParamBoolean('createDatabase', 'installer.createDatabase', 'Y');

		// Miscellaneous Settings
		$this->printTitle('installer.miscSettings');
		$this->readParam('oaiRepositoryId', 'installer.oaiRepositoryId');
		$this->readParamBoolean('manualInstall', 'installer.manualInstall');

		printf("\n*** ");
		$this->readParamBoolean('install', 'installer.installOJS');

		return $this->params['install'];
	}

	/**
	 * Print input section title.
	 * @param $title string
	 */
	function printTitle($title) {
		printf("\n%s\n%s\n%s\n", str_repeat('-', 80), Locale::translate($title), str_repeat('-', 80));
	}

	/**
	 * Read a string parameter.
	 * @param $name string
	 * @param $prompt string
	 * @param $defaultValue string
	 */
	function readParam($name, $prompt, $defaultValue = null) {
	  // abstract method to by implemented by sub-classes
	  assert(false);
	}

  /**
   * Interpret input as a string. Set to default if empty.
   * @param $value string
   * @param $defaultValue string
   * @return string
   */
  function sanitizeParam($value, $defaultValue = 'N') {
	  if ($value === '' && isset($defaultValue)) {
      $value = $defaultValue;
    }

    return $value;
  }

	/**
	 * Prompt user for yes/no input.
	 * @param $name string
	 * @param $prompt string
	 * @param $default string default value, 'Y' or 'N'
	 */
	function readParamBoolean($name, $prompt, $default = 'N') {
    // abstract method to by implemented by sub-classes
    assert(false);
	}

  /**
   * Interpret input as a boolean value. Set to default if empty.
   * @param $value string
   * @param $default string default value, 'Y' or 'N'
   * @return int
   */
  function sanitizeParamBoolean($value, $default = 'N') {
	  $value = strtolower(substr(trim($value), 0, 1));

    if ($default == 'N') {
      return (int)($value == 'y');
    } else {
      return (int)($value != 'n');
    }
  }

	/**
	 * Read a parameter from a set of options.
	 * @param $name string
	 * @param $prompt string
	 * @param $options array
	 * @param $defaultOption string
	 */
	function readParamOptions($name, $prompt, $options, $defaultValue = null, $allowMultiple = false) {
    // abstract method to by implemented by sub-classes
    assert(false);
	}

  /**
   * Interpret input as a set of options. Set to default if empty.
   * @param $value string
   * @param $options array
   * @param $defaultOption string
   * @return array
   */
  function sanitizeParamOptions($value, $options, $defaultValue = null, $allowMultiple = false) {
	  if ($value === '' && isset($defaultValue)) {
      $value = $defaultValue;
    }

    $values = array();
    if ($value !== '') {
      if ($allowMultiple) {
        $values = preg_split('/\s*,\s*/', $value);
      } else {
        $values = array($value);
      }
      foreach ($values as $k) {
        if (!isset($options[$k])) {
          $values = array();
          break;
        }
      }
    }

    return $values;
  }

	/**
	 * Log install message to stdout.
	 * @param $message string
	 */
	function log($message) {
		printf("[%s]\n", $message);
	}

}
?>
