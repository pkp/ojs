<?php

/**
 * @defgroup tools
 */

/**
 * @file unattendedInstall.php
 *
 * Copyright (c) 2009 Florian Grandel
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class unattendedInstallTool
 * @ingroup tools
 *
 * @brief Non-Interactive CLI tool for installing OJS. Parameters are passed in
 *        through environment variables.
 */

// Environment variable prefix
define('INSTALLER_ENV_PREFIX', 'OJS_');

require(dirname(__FILE__) . '/includes/cliInstallTool.inc.php');

class unattendedInstallTool extends CommandLineInstallTool {
	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	function unattendedInstallTool($argv = array()) {
		parent::CommandLineInstallTool($argv);
	}

	/**
	 * Read a parameter from the shell environment.
	 * @param $name string
   * @return string the content of the environment variable
   *                or null on error
	 */
	function readEnvironment($name) {
	  $env = getenv(INSTALLER_ENV_PREFIX . strtoupper($name));
	  return ($env === false ? '' : $env);
	}

	/**
	 * Read a string parameter.
	 * @param $name string
	 * @param $prompt string
	 * @param $defaultValue string
	 */
	function readParam($name, $prompt, $defaultValue = null) {
	  // in unattended mode we don't verify the password.
	  if ($name == 'adminPassword2') {
	    $value = $this->readEnvironment('adminPassword');
	  } else {
	    $value = $this->readEnvironment($name);
	  }

		$value = parent::sanitizeParam($value, $defaultValue);

		if (strpos($name, 'Password', 0) !== false) {
		  $display = '***';
		} else {
		  $display = ($value !== '' ? $value : Locale::translate('common.none'));
		}
    printf("%s: %s\n", Locale::translate($prompt), $display);

		$this->params[$name] =  $value;
	}

	/**
	 * Prompt user for yes/no input.
	 * @param $name string
	 * @param $prompt string
	 * @param $default string default value, 'Y' or 'N'
	 */
	function readParamBoolean($name, $prompt, $default = 'N') {
    $value = $this->readEnvironment($name);

    $value = parent::sanitizeParamBoolean($value, $default);

    printf("%s: %s\n", Locale::translate($prompt), ($value ? 'Y' : 'N'));

    $this->params[$name] = $value;
	}

	/**
	 * Read a parameter from a set of options.
	 * @param $name string
	 * @param $prompt string
	 * @param $options array
	 * @param $defaultOption string
	 */
	function readParamOptions($name, $prompt, $options, $defaultValue = null, $allowMultiple = false) {
    $value = $this->readEnvironment($name);

    $values = parent::sanitizeParamOptions($value, $options, $defaultValue, $allowMultiple);

    $value = implode(', ', $values);

    printf("%s: %s\n", Locale::translate($prompt), $value);

    if ($allowMultiple) {
      $this->params[$name] = $values;
    } else {
      $this->params[$name] = $value;
    }
	}

}

$tool = &new unattendedInstallTool(isset($argv) ? $argv : array());
$tool->execute();
?>
