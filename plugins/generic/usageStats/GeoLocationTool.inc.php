<?php

/**
 * @file plugins/generic/usageStats/GeoLocationTool.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GeoLocationTool
 * @ingroup plugins_generic_usageStats
 *
 * @brief Geo location by ip wrapper class.
 */

/** GeoIp tool for geo location based on ip */
include('lib' . DIRECTORY_SEPARATOR . 'geoIp' . DIRECTORY_SEPARATOR . 'geoipcity.inc');

class GeoLocationTool {

	var $_geoLocationTool;

	var $_regionName;

	var $_isDbFilePresent;

	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	function GeoLocationTool() {
		$geoLocationDbFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . "GeoLiteCity.dat";
		if (file_exists($geoLocationDbFile)) {
			$_isDbFilePresent = true;
			$this->_geoLocationTool = geoip_open($geoLocationDbFile, GEOIP_STANDARD);
			include('lib' . DIRECTORY_SEPARATOR . 'geoIp' . DIRECTORY_SEPARATOR . 'geoipregionvars.php');
			$this->_regionName = $GEOIP_REGION_NAME;
		} else {
			$_isDbFilePresent = false;
		}
	}

	//
	// Public methods.
	//
	/**
	 * Return country code and city name for the passed
	 * ip address.
	 * @param $ip string
	 * @return array
	 */
	public function getGeoLocation($ip) {
		// If no geolocation tool, the geo database file is missing.
		if (!$this->_geoLocationTool) return array(null, null, null);

		$record = geoip_record_by_addr($this->_geoLocationTool, $ip);

		$regionName = null;
		if(isset($this->_regionName[$record->country_code][$record->region])) {
			$regionName = $this->_regionName[$record->country_code][$record->region];
		}

		return array(
			$record->country_code,
			utf8_encode($record->city),
			$record->region
		);
	}
}

?>
