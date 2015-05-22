<?php

/**
 * @file plugins/generic/openAds/OpenAdsConnection.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2009 Siavash Miri and Alec Smecher
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OpenAdsConnection
 * @ingroup plugins_generic_openAds
 *
 * @brief Abstracts the OpenAds link.
 */


class OpenAdsConnection {
	/** @var $installPath string Path to config file */
	var $installPath;

	/** @var $config array Configuration array */
	var $config;

	/** @var $errors array List of error messages */
	var $errors;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor.
	 * @var $plugin object
	 * @var $installPath Full path to OpenAds installation
	 */
	function OpenAdsConnection(&$plugin, $installPath) {
		$this->plugin =& $plugin;
		$this->installPath = $installPath;
		$this->errors = array();
	}

	function getConfigFilename() {
		$variablesScriptPath = $this->installPath . '/variables.php';
		if (!file_exists($variablesScriptPath)) return null;
		require_once($variablesScriptPath);
		return $this->installPath . '/var/' . Request::getServerHost() . '.conf.php';
	}

	function isConfigured() {
		return file_exists($this->getConfigFilename());
	}

	function loadConfig() {
		$fp = null;
		$config = @parse_ini_file($this->getConfigFilename(), true);
		if (!$config) {
			$this->errors[] = __('plugins.generic.openads.error.configFileNotFound', array('filename' => $configFilename));
			return false;
		}

		if (	!isset($config['database']['type']) ||
			$config['database']['type'] != 'mysql' ||
			!isset($config['database']['username']) ||
			!isset($config['database']['password']) ||
			!isset($config['database']['name']) ||
			!isset($config['table']['prefix'])
		) {
			// There was at least one missing required configuration item.
			$this->errors[] = __('plugins.generic.openads.error.missingParameter');
			return false;
		}

		$this->config = $config;

		return true;
	}

	function getErrors() {
		return $this->errors;
	}

	function getAds() {
		$conn =& $this->getConnection();
		$result = mysql_query('SELECT * FROM ' . $this->config['table']['prefix'] . 'zones', $conn);

		$returner = array();
		$this->plugin->import('Ad');
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$ad = new Ad($this);
			$ad->setName($row['zonename']);
			$ad->setAdId($row['zoneid']);
			$returner[] =& $ad;
			unset($ad);
		}

		mysql_free_result($result);
		mysql_close($conn);
		return $returner;
	}

	/**
	 * Get a connection to the OpenAds database.
	 */
	function &getConnection() {
		$dbPort = !empty($this->config['database']['port'])?((int) $this->config['database']['port']):3306;

		$dbHost = $this->config['database']['host'];
		$conn = mysql_connect ($dbHost . ':' . $dbPort, $this->config['database']['username'], $this->config['database']['password']);

		if (!$conn) {
			$this->errors[] = __('plugins.generic.openads.error.dbConnectionError');
			$returner = false;
			return $returner;
		}
		mysql_select_db ($this->config['database']['name'], $conn);
		return $conn;
	}

	function getAdHtml($adId) {
		if (!$adId) return '';
		require_once($this->installPath . '/phpadsnew.inc.php');
		if (!isset($phpAds_context)) $phpAds_context = array();
		$result = view_raw ("zone:$adId", 0, '', '', '0', $phpAds_context);
		return $result['html'];
	}
}

?>
