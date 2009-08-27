<?php

/**
 * @defgroup tools
 */

/**
 * @file install.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Copyright (c) 2009 Florian Grandel
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class installTool
 * @ingroup tools
 *
 * @brief Interactive CLI tool for installing OJS.
 */


require(dirname(__FILE__) . '/includes/cliInstallTool.inc.php');

class installTool extends CommandLineInstallTool {
	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	function installTool($argv = array()) {
		parent::CommandLineInstallTool($argv);
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
				printf("%s (%s): ", Locale::translate($prompt), $defaultValue !== '' ? $defaultValue : Locale::translate('common.none'));
			} else {
				printf("%s: ", Locale::translate($prompt));
			}

			$value = $this->readInput();

			$value = parent::sanitizeParam($value, $defaultValue);
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
  	printf("%s %s ", Locale::translate($prompt), ($default == 'N' ? '[y/N]' : '[Y/n]'));

    $value = $this->readInput();

		$this->params[$name] = parent::sanitizeParamBoolean($value, $default);
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
			printf("%s\n", Locale::translate($prompt));
			foreach ($options as $k => $v) {
				printf("  %-10s %s\n", '[' . $k . ']', $v);
			}
			if ($allowMultiple) {
				printf("  (%s)\n", Locale::translate('installer.form.separateMultiple'));
			}
			if (isset($defaultValue)) {
				printf("%s (%s): ", Locale::translate('common.select'), $defaultValue !== '' ? $defaultValue : Locale::translate('common.none'));
			} else {
				printf("%s: ", Locale::translate('common.select'));
			}

			$value = $this->readInput();

			$values = parent::sanitizeParamOptions($value, $options, $defaultValue, $allowMultiple);

			$value = implode(', ', $values);
		} while ($value === '' && $defaultValue !== '');

		if ($allowMultiple) {
			$this->params[$name] = $values;
		} else {
			$this->params[$name] = $value;
		}
	}

}

$tool = &new installTool(isset($argv) ? $argv : array());
$tool->execute();
?>
