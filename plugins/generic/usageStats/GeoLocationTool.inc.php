<?php

/**
 * @file plugins/generic/usageStats/GeoLocationTool.php
 *
 * Copyright (c) 2003-2012 John Willinsky
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

	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	function GeoLocationTool() {
		$this->_geoLocationTool = geoip_open(dirname(__FILE__) . DIRECTORY_SEPARATOR . "GeoLiteCity.dat", GEOIP_STANDARD);
		include('lib' . DIRECTORY_SEPARATOR . 'geoIp' . DIRECTORY_SEPARATOR . 'geoipregionvars.php');
		$this->_regionName = $GEOIP_REGION_NAME;
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
		$record = geoip_record_by_addr($this->_geoLocationTool, $ip);

		$regionName = null;
		if(isset($this->_regionName[$record->country_code][$record->region])) {
			$regionName = $this->_regionName[$record->country_code][$record->region];
		}

		return array(
			$record->country_code,
			$record->city,
			$record->region
		);
	}
}

?>