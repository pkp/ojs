<?php

/**
 * @file PhpAdsNewConnection.inc.php
 *
 * Copyright (c) 2003-2007 Siavash Miri and Alec Smecher
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 * @class PhpAdsNewConnection
 *
 * Abstracts the PhpAdsNew link.
 *
 * $Id: CounterPlugin.inc.php,v 1.0 2006/10/20 12:28pm
 */

class PhpAdsNewConnection {
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
	 * @var $installPath Full path to phpAdsNew installation
	 */
	function PhpAdsNewConnection(&$plugin, $installPath) {
		$this->plugin =& $plugin;
		$this->installPath = $installPath;
		$this->errors = array();
	}

	function getConfigFilename() {
		return $this->installPath . '/config.inc.php';
	}

	function isConfigured() {
		return file_exists($this->getConfigFilename());
	}

	function loadConfig() {
		$fp = null;
		$configFilename = $this->getConfigFilename();
		$fp = @fopen($configFilename, 'r');
		if (!$fp) {
			$this->errors[] = Locale::translate('plugins.generic.phpadsnew.error.configFileNotFound', array('filename' => $configFilename));
			return false;
		}

		$this->config = array();

		$contents = '';
		$matches = null;
		while (($line = fgets($fp)) !== false) $contents .= $line;
		preg_match_all('/\$phpAds_config\[\'([a-z_]+)\'\][ ]?= ([\'])?([^\n]+)\2;\n/', $contents, $matches, PREG_PATTERN_ORDER);
		foreach ($matches[1] as $key => $match) $this->config[$match] = $matches[3][$key];
		fclose($fp);
		
		$requiredConfigFields = array('dbhost', 'dbuser', 'dbpassword', 'dbname', 'url_prefix', 'table_prefix');
		if (count(array_intersect(array_keys($this->config), $requiredConfigFields)) != count($requiredConfigFields)) {
			// There was at least one missing required configuration item.
			$this->errors[] = Locale::translate('plugins.generic.phpadsnew.error.missingParameter');
			return false;
		}
		return true;
	}

	function getErrors() {
		return $this->errors;
	}

	function getAds() {
		$conn =& $this->getConnection();
		$result = mysql_query('SELECT * FROM ' . $this->config['table_prefix'] . 'zones', $conn);

		$returner = array();
		$this->plugin->import('Ad');
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$ad =& new Ad($this);
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
	 * Get a connection to the PhpAdsNew database.
	 */
	function &getConnection() {
		$dbPort = !empty($this->config['dbport'])?((int) $this->config['dbport']):3306;

		$dbHost = $this->config['dbhost'];
		if ((!isset($this->config['dblocal']) || !$this->config['dblocal']) && $dbHost{0} != ':') $dbHost .= ":$dbPort";
		$conn = null;
		@$conn = mysql_connect ($dbHost, $this->config['dbuser'], $this->config['dbpassword']);

		if (!$conn) {
			$this->errors[] = Locale::translate('plugins.generic.phpadsnew.error.dbConnectionError');
			return false;
		}
		mysql_select_db ($this->config['dbname'], $conn);
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
