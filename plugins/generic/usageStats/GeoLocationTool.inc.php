<?php

/**
 * @file plugins/generic/usageStats/GeoLocationTool.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
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
	 * If we cannot find the database file, an empty object will be constructed.
	 * Use the method isPresent() to check if the database file is present before use.
	 */
	function GeoLocationTool() {
		$geoLocationDbFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . "GeoLiteCity.dat";
		if (file_exists($geoLocationDbFile)) {
			$isDbFilePresent = true;
			$this->_geoLocationTool = geoip_open($geoLocationDbFile, GEOIP_STANDARD);
			include('lib' . DIRECTORY_SEPARATOR . 'geoIp' . DIRECTORY_SEPARATOR . 'geoipregionvars.php');
			$this->_regionName = $GEOIP_REGION_NAME;
		} else {
			$isDbFilePresent = false;
		}
		
		$this->_isDbFilePresent = $isDbFilePresent;
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
	function getGeoLocation($ip) {
		// If no geolocation tool, the geo database file is missing.
		if (!$this->_geoLocationTool) return array(null, null, null);

		$record = geoip_record_by_addr($this->_geoLocationTool, $ip);

		if (!$record) {
			return array(null, null, null);
		}

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

	/**
	 * Identify if the geolocation database tool is available for use.
	 * @return boolean
	 */
	function isPresent() {
		return $this->_isDbFilePresent;
	}

	/**
	 * Get all country codes.
	 * @return mixed array or null
	 */
	function getAllCountryCodes() {
		if (!$this->_geoLocationTool) return null;

		$tool =& $this->_geoLocationTool;
		$countryCodes = $tool->GEOIP_COUNTRY_CODES;

		// Overwrite the first empty record with the code to
		// unknow country.
		$countryCodes[0] = STATISTICS_UNKNOWN_COUNTRY_ID;
		return $countryCodes;
	}

	/**
	 * Return the 3 letters version of country codes
	 * based on the passed 2 letters version.
	 * @param $countryCode string
	 * @return mixed string or null
	 */
	function get3LettersCountryCode($countryCode) {
		return $this->_getCountryCodeOnList($countryCode, 'GEOIP_COUNTRY_CODES3');
	}

	/**
	 * Return the 2 letter version of country codes
	 * based on the passed 3 letters version.
	 * @param $countryCode3 string
	 * @return mixed string or null
	 */
	function get2LettersCountryCode($countryCode3) {
		return $this->_getCountryCodeOnList($countryCode3, 'GEOIP_COUNTRY_CODES');
	}

	/**
	 * Get regions by country.
	 * @param $countryId int
	 * @return array
	 */
	function getRegions($countryId) {
		$regions = array();
		$database = $this->_regionName;
		if (isset($database[$countryId])) {
			$regions = $database[$countryId];
		}

		return $regions;
	}

	/**
	 * Get the passed country code inside the passed
	 * list.
	 * @param $countryCode The 2 letters country code.
	 * @param $countryCodeList array Any geoip country
	 * code list.
	 * @return mixed String or null.
	 */
	function _getCountryCodeOnList($countryCode, $countryCodeListName) {
		$returner = null;

		if (!$this->_geoLocationTool) return $returner;
		$tool =& $this->_geoLocationTool;

		if (isset($tool->$countryCodeListName)) {
			$countryCodeList = $tool->$countryCodeListName;
		} else {
			return $returner;
		}

		$countryCodesIndex = $tool->GEOIP_COUNTRY_CODE_TO_NUMBER;
		$countryCodeIndex = null;

		if (isset($countryCodesIndex[$countryCode])) {
			$countryCodeIndex = $countryCodesIndex[$countryCode];
		}

		if ($countryCodeIndex) {
			if (isset($countryCodeList[$countryCodeIndex])) {
				$returner = $countryCodeList[$countryCodeIndex];
			}
		}

		return $returner;
	}
}

?>
