<?php

/**
 * @defgroup config
 */
 
/**
 * @file classes/config/Config.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Config
 * @ingroup config
 *
 * @brief Config class for accessing configuration parameters.
 */

// $Id$


/** The path to the default configuration file */
define('CONFIG_FILE', Core::getBaseDir() . DIRECTORY_SEPARATOR . 'config.inc.php');

import('config.ConfigParser');

class Config {
	static $_configData = null;
	static $_configFile = CONFIG_FILE;

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
		if (is_null(self::$_configData)) {
			// Load configuration data only once per request
			self::$_configData = Config::reloadData();
		}

		return self::$_configData;
	}

	/**
	 * Load configuration data from a file.
	 * The file is assumed to be formatted in php.ini style.
	 * @return array the configuration data
	 */
	function &reloadData() {
		if (($configData = &ConfigParser::readConfig(Config::getConfigFileName())) === false) {
			fatalError(sprintf('Cannot read configuration file %s', Config::getConfigFileName()));
		}

		return $configData;
	}

	/**
	 * Set the path to the configuration file.
	 * @param $configFile string
	 */
	function setConfigFileName($configFile) {
		self::$_configFile = $configFile;
		self::$_configData = null;
	}

	/**
	 * Return the path to the configuration file.
	 * @return string
	 */
	function getConfigFileName() {
		return self::$_configFile;
	}

}
?>
