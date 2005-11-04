<?php

/**
 * Locale.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package i18n
 *
 * Internationalization class. 
 * Provides methods for loading locale data and translating strings identified by unique keys
 *
 * $Id$
 */

define('LOCALE_REGISTRY_FILE', Config::getVar('general', 'registry_dir') . '/locales.xml');
define('LOCALE_DEFAULT', Config::getVar('i18n', 'locale'));
define('LOCALE_ENCODING', Config::getVar('i18n', 'client_charset'));

class Locale {
	var $caches;

	/**
	 * Constructor.
	 */
	function Locale() {
	}

	function &_getCache($locale) {
		static $caches;
		if (!isset($caches)) {
			$caches = array();
		}

		if (!isset($caches[$locale])) {
			import('cache.CacheManager');
			$cacheManager =& CacheManager::getManager();
			$caches[$locale] =& $cacheManager->getCache(
				'locale', $locale,
				array('Locale', '_cacheMiss')
			);

			// Check to see if the cache is outdated.
			// Only some kinds of caches track cache dates;
			// if there's no date available (ie cachedate is
			// null), we have to assume it's up to date.
			$cacheTime = $caches[$locale]->getCacheTime();
			if ($cacheTime !== null && $cacheTime < filemtime(Locale::getLocaleFilename($locale))) {
				// This cache is out of date; flush it.
				$caches[$locale]->flush();
			}
		}
		return $caches[$locale];
	}

	function _cacheMiss(&$cache, $id) {
		// Keep a secondary in-memory cache of all the cache-miss
		// locales so that a couple of missed locale strings won't destroy
		// the server.
		static $missedLocales;
		$locale = $cache->getCacheId();

		$value = null;
		if (!HookRegistry::call('Locale::_cacheMiss', array(&$id, &$locale, &$value))) {
			if (!isset($missedLocales)) {
				$missedLocales = array();
			}

			if (!isset($missedLocales[$locale])) {
				$missedLocales[$locale] =& Locale::loadLocale($locale);
				$cache->setEntireCache($missedLocales[$locale]);
			}

			$value = isset($missedLocales[$locale][$id])?$missedLocales[$locale][$id]:null;
		}

		return $value;
	}

	/**
	 * Translate a string using the selected locale.
	 * Substitution works by replacing tokens like "{$foo}" with the value of
	 * the parameter named "foo" (if supplied).
	 * @param $key string
	 * @params $params array named substitution parameters
	 * @params $locale string the locale to use
	 * @return string
	 */
	function translate($key, $params = array(), $locale = null) {
		if (!isset($locale)) {
			$locale = Locale::getLocale();
		}

		
		$key = trim($key);
		if (empty($key)) {
			return '';
		}
		
		$cache =& Locale::_getCache($locale);
		$message = $cache->get($key);
		if (!isset($message)) {
			// Try to force loading the plugin locales.
			$message = Locale::_cacheMiss($cache, $key);
		}

		if (isset($message)) {
			if (!empty($params)) {
				// Substitute custom parameters
				foreach ($params as $key => $value) {
					$message = str_replace("{\$$key}", $value, $message);
				}
			}
			
			return $message;
			
		} else {
			// Add a missing key to the debug notes.
			$notes =& Registry::get('system.debug.notes');
			$notes[] = array('debug.notes.missingLocaleKey', array('key' => $key));
		
			// Add some octothorpes to missing keys to make them more obvious
			return '##' . $key . '##';
		}
	}

	/**
	 * Get the filename for the locale file given a locale name.
	 */
	function getLocaleFilename($locale) {
		return "locale/$locale/locale.xml";
	}

	/**
	 * Load localized strings for the user's current locale from an XML file.
	 * TODO: Split across several XML files for easier maintainability?
	 * @param $locale string the locale to load
	 * @return array associative array of keys and localized strings
	 */
	function &loadLocale($locale = null, $localeFile = null) {
		$localeData = array();

		if (!isset($locale)) {
			$locale = Locale::getLocale();
		}

		$sysLocale = $locale . '.' . LOCALE_ENCODING;
		if (!@setlocale(LC_ALL, $sysLocale, $locale)) {
			// For PHP < 4.3.0
			if(setlocale(LC_ALL, $sysLocale) != $sysLocale) {
				setlocale(LC_ALL, $locale);
			}
		}
		
		if ($localeFile === null) $localeFile = Locale::getLocaleFilename($locale);
		
		// Add a locale load to the debug notes.
		$notes =& Registry::get('system.debug.notes');
		$notes[] = array('debug.notes.localeLoad', array('localeFile' => $localeFile));
		
		// Reload localization XML file
		$xmlDao = &new XMLDAO();
		$data = $xmlDao->parseStruct($localeFile, array('message'));
	
		// Build array with ($key => $string)
		if (isset($data['message'])) {
			foreach ($data['message'] as $messageData) {
				$localeData[$messageData['attributes']['key']] = $messageData['value'];
			}
		}
		
		return $localeData;	
	}
	
	/**
	 * Check if a locale is valid.
	 * @param $locale string
	 * @return boolean
	 */
	function isLocaleValid($locale) {
		return isset($locale) && !empty($locale) && file_exists('locale/' . $locale . '/locale.xml');
	}
	
	/**
	 * Return the key name of the user's currently selected locale (default is "en_US" for U.S. English).
	 * @return string 
	 */
	function getLocale() {
		static $currentLocale;
		if (!isset($currentLocale)) {
			if (defined('SESSION_DISABLE_INIT')) {
				$locale = Request::getCookieVar('currentLocale');
			
			} else {
				$sessionManager = &SessionManager::getManager();
				$session = &$sessionManager->getUserSession();
				$locale = $session->getSessionVar('currentLocale');
			
				$journal = &Request::getJournal();
				$site = &Request::getSite();
			
				if (!isset($locale)) {
					$locale = Request::getCookieVar('currentLocale');
				}
				
				if (isset($locale)) {
					// Check if user-specified locale is supported
					if ($journal != null) {
						$locales = &$journal->getSupportedLocaleNames();
					} else {
						$locales = &$site->getSupportedLocaleNames();
					}
					
					if (!in_array($locale, array_keys($locales))) {
						unset($locale);
					}
				}
				
				if (!isset($locale)) {
					// Use journal/site default
					if ($journal != null) {
						$locale = $journal->getLocale();
					}
					
					if (!isset($locale)) {
						$locale = $site->getLocale();
					}
				}
			}
			
			if (!Locale::isLocaleValid($locale)) {
				$locale = LOCALE_DEFAULT;
			}
			
			$currentLocale = $locale;
		}
		return $currentLocale;
	}
	
	
	/**
	 * Retrieve the primary locale of the current context.
	 * @return string
	 */
	function getPrimaryLocale() {
		$journal = &Request::getJournal();
		
		if (isset($journal)) {
			$locale = $journal->getLocale();
		}
		
		if (!isset($locale)) {
			$site = &Request::getSite();
			$locale = $site->getLocale();
		}
		
		if (!isset($locale) || !Locale::isLocaleValid($locale)) {
			$locale = LOCALE_DEFAULT;
		}
		
		return $locale;
	}

	function &_getAllLocalesCache() {
		static $cache;
		if (!isset($cache)) {
			import('cache.CacheManager');
			$cacheManager =& CacheManager::getManager();
			$cache =& $cacheManager->getFileCache(
				'locale', 'list',
				array('Locale', '_allLocalesCacheMiss')
			);

			// Check to see if the data is outdated
			$cacheTime = $cache->getCacheTime();
			if ($cacheTime !== null && $cacheTime < filemtime(LOCALE_REGISTRY_FILE)) {
				$cache->flush();
			}
		}
		return $cache;
	}

	function _allLocalesCacheMiss(&$cache, $id) {
		static $allLocales;
		if (!isset($allLocales)) {
			// Add a locale load to the debug notes.
			$notes =& Registry::get('system.debug.notes');
			$notes[] = array('debug.notes.localeListLoad', array('localeList' => LOCALE_REGISTRY_FILE));

			// Reload locale registry file
			$xmlDao = &new XMLDAO();
			$data = $xmlDao->parseStruct(LOCALE_REGISTRY_FILE, array('locale'));
	
			// Build array with ($localKey => $localeName)
			if (isset($data['locale'])) {
				foreach ($data['locale'] as $localeData) {
					$allLocales[$localeData['attributes']['key']] = $localeData['attributes']['name'];
				}
			}
			asort($allLocales);
			$cache->setEntireCache($allLocales);
		}
		return null;
	}

	/**
	 * Return a list of all available locales.
	 * @return array
	 */
	function &getAllLocales() {
		$cache =& Locale::_getAllLocalesCache();
		return $cache->getContents();
	}
	
	/**
	 * Check if the current locale is one of the journal's alternate locales.
	 * @return int the alternate # (or 0, if no match).
	 */
	function isAlternateJournalLocale($journalId) {
		static $alternateLocaleNum;
		
		if (!isset($alternateLocaleNum)) {
			$localeNum = 0;
			$locale = Locale::getLocale();
			
			$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
			$alternateLocale1 = $journalSettingsDao->getSetting($journalId, 'alternateLocale1');
			if (isset($alternateLocale1)) {
				if ($alternateLocale1 == $locale) {
					$localeNum = 1;
				} else {
					$alternateLocale2 = $journalSettingsDao->getSetting($journalId, 'alternateLocale2');
					if ($alternateLocale2 == $locale) {
						$localeNum = 2;
					}
				}
			}
			
			$alternateLocaleNum = $localeNum;
		}
		
		return $alternateLocaleNum;
	}
	
	// FIXME Make this more flexible by determining what to do from an XML file?
	/**
	 * Uninstall support for an existing locale.
	 * @param $locale string
	 */
	function installLocale($locale) {
		// Install default locale-specific data
		import('db.DBDataXMLParser');
		
		$filesToInstall = array(
			'dbscripts/xml/data/locale/' . $locale . '/email_templates_data.xml'
		);
		
		$dataXMLParser = &new DBDataXMLParser();
		foreach ($filesToInstall as $fileName) {
			if (file_exists($fileName)) {
				$sql = $dataXMLParser->parseData($fileName);
				$dataXMLParser->executeData();
			}
		}
		$dataXMLParser->destroy();
	}
	
	/**
	 * Install support for a new locale.
	 * @param $locale string
	 */
	function uninstallLocale($locale) {
		// Delete locale-specific data
		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplateDao->deleteEmailTemplatesByLocale($locale);
		$emailTemplateDao->deleteDefaultEmailTemplatesByLocale($locale);
	}
	
	/**
	 * Reload locale-specific data.
	 * @param $locale string
	 */
	function reloadLocale($locale) {
		Locale::uninstallLocale($locale);
		Locale::installLocale($locale);
	}
	
}

?>
