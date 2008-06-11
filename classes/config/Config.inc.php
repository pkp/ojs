<?php

/**
 * @file Config.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package config
 * @class Config
 *
 * Config class for accessing configuration parameters.
 *
 * $Id$
 */

/** The path to the configuration file */
define('CONFIG_FILE', Core::getBaseDir() . DIRECTORY_SEPARATOR . 'config.inc.php');

import('config.ConfigParser');

class Config {

	/**
	 * Retrieve a specified configuration variable.
	 * @param $section string
	 * @param $key string
	 * @return string
	 */
	function getVar($section, $key) {
		$configData = &Config::getData();
		return isset($configData[$section][$key]) ? $configData[$section][$key] : null;
	}

	/**
	 * Get the current configuration data.
	 * @return array the configuration data
	 */
	function &getData() {
		static $configData;

		if (!isset($configData)) {
			// Load configuration data only once per request
			$configData = Config::reloadData();
		}

		return $configData;
	}

	/**
	 * Load configuration data from a file.
	 * The file is assumed to be formatted in php.ini style.
	 * @return array the configuration data
	 */
	function &reloadData() {
		if (($configData = &ConfigParser::readConfig(CONFIG_FILE)) === false) {
			fatalError(sprintf('Cannot read configuration file %s', CONFIG_FILE));
		}

		return $configData;
	}

	/**
	 * Return the path to the configuration file.
	 * @return string
	 */
	function getConfigFileName() {
		return CONFIG_FILE;
	}

}
?>
