<?php

/**
 * Config.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package config
 *
 * Config class for accessing configuration parameters.
 *
 * $Id$
 */

/** The path to the configuration file */
define('CONFIG_FILE', Core::getBaseDir() . '/' . 'config.inc.php');

class Config {

	/**
	 * Retrieve a specified configuration variable.
	 * @param $section string
	 * @param $key string
	 * @return string
	 */
	function getVar($section, $key) {
		static $configData;
		
		if (!isset($configData)) {
			// Load configuration data only once per request
			$configData = Config::reloadData();
		}
		
		return isset($configData[$section][$key]) ? $configData[$section][$key] : null;
	}
	
	/**
	 * Load configuration data from a file.
	 * The file is assumed to be formatted in php.ini style.
	 * @return array the configuration data (see http://php.net/parse_ini_file for the array format)
	 */
	function &reloadData() {
		if (!file_exists(CONFIG_FILE) || !is_readable(CONFIG_FILE)) {
			die(sprintf('Cannot read configuration file %s', CONFIG_FILE));
		}
		
		return parse_ini_file(CONFIG_FILE, true);
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
