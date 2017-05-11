<?php

/**
 * @file classes/i18n/CountryDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CountryDAO
 * @package i18n
 *
 * @brief Provides methods for loading localized country name data.
 *
 */


class CountryDAO extends DAO {
	var $cache;

	/**
	 * Constructor.
	 */
	function __construct() {
		// Parent constructor intentionally not called
	}

	/**
	 * Get the filename of the countries registry file for the given locale.
	 * @param $locale string Name of locale (optional)
	 */
	function getFilename($locale = null) {
		if ($locale === null) $locale = AppLocale::getLocale();
		return "lib/pkp/locale/$locale/countries.xml";
	}

	function &_getCountryCache($locale = null) {
		$caches =& Registry::get('allCountries', true, array());

		if (!isset($locale)) $locale = AppLocale::getLocale();

		if (!isset($caches[$locale])) {
			$cacheManager = CacheManager::getManager();
			$caches[$locale] = $cacheManager->getFileCache(
				'country', $locale,
				array($this, '_countryCacheMiss')
			);

			// Check to see if the data is outdated
			$cacheTime = $caches[$locale]->getCacheTime();
			if ($cacheTime !== null && $cacheTime < filemtime($this->getFilename())) {
				$caches[$locale]->flush();
			}
		}
		return $caches[$locale];
	}

	function _countryCacheMiss($cache, $id) {
		$countries =& Registry::get('allCountriesData', true, array());

		if (!isset($countries[$id])) {
			// Reload country registry file
			$xmlDao = new XMLDAO();
			$data = $xmlDao->parseStruct($this->getFilename(), array('countries', 'country'));

			if (isset($data['countries'])) {
				foreach ($data['country'] as $countryData) {
					$countries[$id][$countryData['attributes']['code']] = $countryData['attributes']['name'];
				}
			}
			asort($countries[$id]);
			$cache->setEntireCache($countries[$id]);
		}
		return null;
	}

	/**
	 * Return a list of all countries.
	 * @param $locale string Name of locale (optional)
	 * @return array
	 */
	function &getCountries($locale = null) {
		$cache =& $this->_getCountryCache($locale);
		return $cache->getContents();
	}

	/**
	 * Return a translated country name, given a code.
	 * @param $locale string Name of locale (optional)
	 * @return array
	 */
	function getCountry($code, $locale = null) {
		$cache =& $this->_getCountryCache($locale);
		return $cache->get($code);
	}
}

?>
