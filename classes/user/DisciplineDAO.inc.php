<?php

/**
 * @file DisciplineDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package user
 * @class DisciplineDAO
 *
 * Provides methods for loading localized academic disciplines.
 *
 * $Id$
 */

class DisciplineDAO extends DAO {
	var $cache;

	/**
	 * Constructor.
	 */
	function DisciplineDAO() {
	}

	/**
	 * Get the filename of the disciplines registry file for the given locale.
	 * @param $locale string Name of locale (optional)
	 */
	function getFilename($locale = null) {
		if ($locale === null) $locale = Locale::getLocale();
		return Config::getVar('general', 'registry_dir') . "/locale/$locale/disciplines.xml";
	}

	function &_getDisciplineCache($locale = null) {
		static $caches;

		if (!isset($locale)) $locale = Locale::getLocale();

		if (!isset($caches)) {
			$caches = array();
		}
		
		if (!isset($caches[$locale])) {
			import('cache.CacheManager');
			$cacheManager =& CacheManager::getManager();
			$caches[$locale] =& $cacheManager->getFileCache(
				'discipline', $locale,
				array(&$this, '_disciplineCacheMiss')
			);

			// Check to see if the data is outdated
			$cacheTime = $caches[$locale]->getCacheTime();
			if ($cacheTime !== null && $cacheTime < filemtime($this->getFilename())) {
				$caches[$locale]->flush();
			}
		}
		return $caches[$locale];
	}

	function _disciplineCacheMiss(&$cache, $id) {
		static $disciplines;
		if (!isset($disciplines)) {
			$disciplines = array();
		}

		if (!isset($disciplines[$id])) {
			// Reload country registry file
			$xmlDao = &new XMLDAO();
			$data = $xmlDao->parseStruct($this->getFilename(), array('disciplines', 'discipline'));
	
			if (isset($data['disciplines'])) {
				foreach ($data['discipline'] as $disciplineData) {
					$disciplines[$id][$disciplineData['attributes']['code']] = $disciplineData['attributes']['name'];
				}
			}
			asort($disciplines[$id]);
			$cache->setEntireCache($disciplines[$id]);
		}
		return null;
	}

	/**
	 * Return a list of all disciplines.
	 * @param $locale string Name of locale (optional)
	 * @return array
	 */
	function &getDisciplines($locale = null) {
		$cache =& $this->_getDisciplineCache($locale);
		return $cache->getContents();
	}
	
	/**
	 * Return a translated discipline, given a code.
	 * @param $locale string Name of locale (optional)
	 * @return array
	 */
	function getDiscipline($code, $locale = null) {
		$cache =& $this->_getDisciplineCache($locale);
		return $cache->get($code);
	}
}

?>
