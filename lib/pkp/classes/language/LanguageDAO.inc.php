<?php

/**
 * @file classes/language/LanguageDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LanguageDAO
 * @ingroup language
 * @see Language
 *
 * @brief Operations for retrieving and modifying Language objects.
 *
 */

import('lib.pkp.classes.language.Language');

class LanguageDAO extends DAO {

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Return the language cache.
	 * @param $locale string
	 */
	function &_getCache($locale = null) {
		if (is_null($locale)) {
			$locale = AppLocale::getLocale();
		}
		$cache =& Registry::get('languageCache-'.$locale, true, null);
		if ($cache === null) {
			$cacheManager = CacheManager::getManager();
			$cache = $cacheManager->getFileCache(
				'languages', $locale,
				array($this, '_cacheMiss')
			);
			$cacheTime = $cache->getCacheTime();
			if ($cacheTime !== null && $cacheTime < filemtime($this->getLanguageFilename($locale))) {
				$cache->flush();
			}
		}

		return $cache;
	}

	function _cacheMiss($cache, $id) {
		$allLanguages =& Registry::get('allLanguages-'.$cache->cacheId, true, null);
		if ($allLanguages === null) {
			// Add a locale load to the debug notes.
			$notes =& Registry::get('system.debug.notes');
			$locale = $cache->cacheId;
			if ($locale == null) {
				$locale = AppLocale::getLocale();
			}
			$filename = $this->getLanguageFilename($locale);
			$notes[] = array('debug.notes.languageListLoad', array('filename' => $filename));

			// Reload locale registry file
			$xmlDao = new XMLDAO();
			$data = $xmlDao->parseStruct($filename, array('language'));

			// Build array with ($charKey => array(stuff))
			if (isset($data['language'])) {
				foreach ($data['language'] as $languageData) {
					$allLanguages[$languageData['attributes']['code']] = array(
						$languageData['attributes']['name'],
					);
				}
			}
			if (is_array($allLanguages)) {
				asort($allLanguages);
			}
			$cache->setEntireCache($allLanguages);
		}
		if (isset($allLanguages[$id])) {
			return $allLanguages[$id];
		} else {
			return null;
		}
	}

	/**
	 * Get the filename of the language database
	 * @param $locale string
	 * @return string
	 */
	function getLanguageFilename($locale) {
		return "lib/pkp/locale/$locale/languages.xml";
	}

	/**
	 * Retrieve a language by code.
	 * @param $code string ISO 639-1
	 * @param $locale string
	 * @return Language
	 */
	function getLanguageByCode($code, $locale = null) {
		$cache = $this->_getCache($locale);
		return $this->_returnLanguageFromRow($code, $cache->get($code));
	}

	/**
	 * Retrieve an array of all languages.
	 * @param $locale string an optional locale to use
	 * @return array of Languages
	 */
	function getLanguages($locale = null) {
		$cache = $this->_getCache($locale);
		$returner = array();
		foreach ($cache->getContents() as $code => $entry) {
			$returner[] = $this->_returnLanguageFromRow($code, $entry);
		}
		return $returner;
	}

	/**
	 * Retrieve an array of all languages names.
	 * @param $locale an optional locale to use
	 * @return array of Languages names
	 */
	function getLanguageNames($locale = null) {
		$cache = $this->_getCache($locale);
		$returner = array();
		$cacheContents = $cache->getContents();
		if (is_array($cacheContents)) {
			foreach ($cache->getContents() as $entry) {
				$returner[] = $entry[0];
			}
		}
		return $returner;
	}

	/**
	 * Instantiate a new data object.
	 * @return Language
	 */
	function newDataObject() {
		return new Language();
	}

	/**
	 * Internal function to return a Language object from a row.
	 * @param $row array
	 * @return Language
	 */
	function &_returnLanguageFromRow($code, &$entry) {
		$language = $this->newDataObject();
		$language->setCode($code);
		$language->setName($entry[0]);

		HookRegistry::call('LanguageDAO::_returnLanguageFromRow', array(&$language, &$code, &$entry));

		return $language;
	}
}

?>
