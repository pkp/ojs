<?php

/**
 * @file classes/cliTool/InstallTool.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class installTool
 * @ingroup tools
 *
 * @brief CLI tool for installing a PKP app.
 */


import('classes.install.Install');
import('lib.pkp.classes.install.form.InstallForm');
import('lib.pkp.classes.site.Version');
import('lib.pkp.classes.site.VersionCheck');

class InstallTool extends CommandLineTool {

	/** @var array installation parameters */
	var $params;

	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	function __construct($argv = array()) {
		parent::__construct($argv);
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
		$installer = new Install($this->params);
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

			$newVersion =& $installer->getNewVersion();
			printf("Successfully installed version %s\n", $newVersion->getVersionString(false));

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
		$installForm = new InstallForm(null); // Request object not available to CLI

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

		// Administrator Account
		$this->printTitle('installer.administratorAccount');
		$this->readParam('adminUsername', 'user.username');
		@`/bin/stty -echo`;
		do {
			$this->readParam('adminPassword', 'user.password');
			printf("\n");
			$this->readParam('adminPassword2', 'user.repeatPassword');
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

		$this->readParamBoolean('enableBeacon', 'installer.beacon.enable', 'Y');

		printf("\n*** ");
	}

	/**
	 * Print input section title.
	 * @param $title string
	 */
	function printTitle($title) {
		printf("\n%s\n%s\n%s\n", str_repeat('-', 80), __($title), str_repeat('-', 80));
	}

	/**
	 * Read a line of user input.
	 * @return string
	 */
	function readInput() {
		$value = trim(fgets(STDIN));
		if ($value === false || feof(STDIN)) {
			printf("\n");
			exit(0);
		}
		return $value;
	}

	/**
	 * Read a string parameter.
	 * @param $name string
	 * @param $prompt string
	 * @param $defaultValue string
	 */
	function readParam($name, $prompt, $defaultValue = null) {
		do {
			if (isset($defaultValue)) {
				printf("%s (%s): ", __($prompt), $defaultValue !== '' ? $defaultValue : __('common.none'));
			} else {
				printf("%s: ", __($prompt));
			}

			$value = $this->readInput();

			if ($value === '' && isset($defaultValue)) {
				$value = $defaultValue;
			}
		} while ($value === '' && $defaultValue !== '');
		$this->params[$name] =  $value;
	}

	/**
	 * Prompt user for yes/no input.
	 * @param $name string
	 * @param $prompt string
	 * @param $default string default value, 'Y' or 'N'
	 */
	function readParamBoolean($name, $prompt, $default = 'N') {
		if ($default == 'N') {
			printf("%s [y/N] ", __($prompt));
			$value = $this->readInput();
			$this->params[$name] = (int)(strtolower(substr(trim($value), 0, 1)) == 'y');
		} else {
			printf("%s [Y/n] ", __($prompt));
			$value = $this->readInput();
			$this->params[$name] = (int)(strtolower(substr(trim($value), 0, 1)) != 'n');
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
		do {
			printf("%s\n", __($prompt));
			foreach ($options as $k => $v) {
				printf("  %-10s %s\n", '[' . $k . ']', $v);
			}
			if ($allowMultiple) {
				printf("  (%s)\n", __('installer.form.separateMultiple'));
			}
			if (isset($defaultValue)) {
				printf("%s (%s): ", __('common.select'), $defaultValue !== '' ? $defaultValue : __('common.none'));
			} else {
				printf("%s: ", __('common.select'));
			}

			$value = $this->readInput();

			if ($value === '' && isset($defaultValue)) {
				$value = $defaultValue;
			}

			$values = array();
			if ($value !== '') {
				if ($allowMultiple) {
					$values = ($value === '' ? array() : preg_split('/\s*,\s*/', $value));
				} else {
					$values = array($value);
				}
				foreach ($values as $k) {
					if (!isset($options[$k])) {
						$value = '';
						break;
					}
				}
			}
		} while ($value === '' && $defaultValue !== '');

		if ($allowMultiple) {
			$this->params[$name] = $values;
		} else {
			$this->params[$name] = $value;
		}
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
