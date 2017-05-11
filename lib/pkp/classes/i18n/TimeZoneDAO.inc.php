<?php

/**
 * @file classes/i18n/TimeZoneDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TimeZoneDAO
 * @package i18n
 *
 * @brief Provides methods for loading localized time zone name data.
 *
 */


class TimeZoneDAO extends DAO {
	var $cache;

	/**
	 * Constructor.
	 */
	function __construct() {
		// Parent constructor intentionally not called
	}

	/**
	 * Get the filename of the time zone registry file for the given locale
	 */
	function getFilename() {
		return "lib/pkp/registry/timeZones.xml";
	}

	function &_getTimeZoneCache() {
		$cache =& Registry::get('allTimeZones', true, null);
		if ($cache === null) {
			$cacheManager = CacheManager::getManager();
			$cache = $cacheManager->getFileCache(
				'timeZone', 'list',
				array($this, '_timeZoneCacheMiss')
			);

			// Check to see if the data is outdated
			$cacheTime = $cache->getCacheTime();
			if ($cacheTime !== null && $cacheTime < filemtime($this->getFilename())) {
				$cache->flush();
			}
		}
		return $cache;
	}

	function _timeZoneCacheMiss($cache, $id) {
		$timeZones =& Registry::get('allTimeZonesData', true, null);
		if ($timeZones === null) {
			// Reload time zone registry file
			$xmlDao = new XMLDAO();
			$data = $xmlDao->parseStruct($this->getFilename(), array('timezones', 'entry'));
			$timeZones = array();
			if (isset($data['timezones'])) {
				foreach ($data['entry'] as $timeZoneData) {
					$timeZones[$timeZoneData['attributes']['key']] = $timeZoneData['attributes']['name'];
				}
			}
			asort($timeZones);
			$cache->setEntireCache($timeZones);
		}
		return null;
	}

	/**
	 * Return a list of all time zones.
	 * @return array
	 */
	function &getTimeZones() {
		$cache =& $this->_getTimeZoneCache();
		return $cache->getContents();
	}
}

?>
