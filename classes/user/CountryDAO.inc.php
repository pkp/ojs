<?php

/**
 * CountryDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package user
 *
 * Provides methods for loading localized country name data.
 *
 * $Id$
 */

class CountryDAO extends DAO {
	var $cache;

	/**
	 * Constructor.
	 */
	function CountryDAO() {
	}

	/**
	 * Get the filename of the countries registry file for the given locale.
	 * @param $locale string Name of locale (optional)
	 */
	function getFilename($locale = null) {
		if ($locale === null) $locale = Locale::getLocale();
		return Config::getVar('general', 'registry_dir') . "/locale/$locale/countries.xml";
	}

	function &_getCountryCache($locale = null) {
		static $caches;

		if (!isset($locale)) $locale = Locale::getLocale();

		if (!isset($caches)) {
			$caches = array();
		}
		
		if (!isset($caches[$locale])) {
			import('cache.CacheManager');
			$cacheManager =& CacheManager::getManager();
			$caches[$locale] =& $cacheManager->getFileCache(
				'country', $locale,
				array(&$this, '_countryCacheMiss')
			);

			// Check to see if the data is outdated
			$cacheTime = $caches[$locale]->getCacheTime();
			if ($cacheTime !== null && $cacheTime < filemtime($this->getFilename())) {
				$caches[$locale]->flush();
			}
		}
		return $caches[$locale];
	}

	function _countryCacheMiss(&$cache, $id) {
		static $countries;
		if (!isset($countries)) {
			$countries = array();
		}

		if (!isset($countries[$id])) {
			// Reload country registry file
			$xmlDao = &new XMLDAO();
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
