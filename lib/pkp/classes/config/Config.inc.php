<?php

/**
 * @defgroup config Config
 * Implements configuration concerns such as the configuration file parser.
 */

/**
 * @file classes/config/Config.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Config
 * @ingroup config
 *
 * @brief Config class for accessing configuration parameters.
 */


/** The path to the default configuration file */
define('CONFIG_FILE', Core::getBaseDir() . DIRECTORY_SEPARATOR . 'config.inc.php');

import('lib.pkp.classes.config.ConfigParser');

class Config {
	/**
	 * Retrieve a specified configuration variable.
	 * @param $section string
	 * @param $key string
	 * @param $default mixed Optional default if the var doesn't exist
	 * @return mixed May return boolean (in case of "off"/"on"/etc), numeric, string, or null.
	 */
	static function getVar($section, $key, $default = null) {
		$configData =& Config::getData();
		return isset($configData[$section][$key]) ? $configData[$section][$key] : $default;
	}

	/**
	 * Get the current configuration data.
	 * @return array the configuration data
	 */
	static function &getData() {
		$configData =& Registry::get('configData', true, null);

		if ($configData === null) {
			// Load configuration data only once per request, implicitly
			// sets config data by ref in the registry.
			$configData = Config::reloadData();
		}

		return $configData;
	}

	/**
	 * Load configuration data from a file.
	 * The file is assumed to be formatted in php.ini style.
	 * @return array the configuration data
	 */
	static function &reloadData() {
		if (($configData =& ConfigParser::readConfig(Config::getConfigFileName())) === false) {
			fatalError(sprintf('Cannot read configuration file %s', Config::getConfigFileName()));
		}

		return $configData;
	}

	/**
	 * Set the path to the configuration file.
	 * @param $configFile string
	 */
	static function setConfigFileName($configFile) {
		// Reset the config data
		$configData = null;
		Registry::set('configData', $configData);

		// Set the config file
		Registry::set('configFile', $configFile);
	}

	/**
	 * Return the path to the configuration file.
	 * @return string
	 */
	static function getConfigFileName() {
		return Registry::get('configFile', true, CONFIG_FILE);
	}

	/**
	 * Get context base urls from config file.
	 * @return array Empty array if none is set.
	 */
	function &getContextBaseUrls() {
		$contextBaseUrls =& Registry::get('contextBaseUrls'); // Reference required.

		if (is_null($contextBaseUrls)) {
			$contextBaseUrls = array();
			$configData = Config::getData();
			// Filter the settings.
			$matches = null;
			foreach ($configData['general'] as $settingName => $settingValue) {
				if (preg_match('/base_url\[(.*)\]/', $settingName, $matches)) {
					$workingContextPath = $matches[1];
					$contextBaseUrls[$workingContextPath] = $settingValue;
				}
			}
		}

		return $contextBaseUrls;
	}
}
?>
