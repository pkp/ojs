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

define('LOCALE_REGISTRY_FILE', 'locale/locales.xml');
define('LOCALE_DEFAULT', Config::getVar('i18n', 'locale'));

class Locale {

	/**
	 * Constructor.
	 */
	function Locale() {
	}
	
	/* Deprecated. Translate a string with gettext using the selected locale.
	function translate_gettext($str) {
		if (extension_loaded('gettext')) {
			$locale = Locale::getLocale();
			setlocale(LC_ALL, $locale);
			bindtextdomain('messages', './locale');
			return gettext($str);
			
		} else {
			return $str;
		}
	}
	*/
	
	/**
	 * Translate a string using the selected locale.
	 * @param $key string
	 * @return string
	 */
	function translate($key) {
		static $localeData;

		if (!isset($localeData)) {
			// Load locale data only once per request
			$localeData = Locale::loadLocale();
		}

		$key = trim($key);
		if (empty($key)) {
			return '';
		}

		// Add some octothorpes to missing keys to make them more obvious
		return isset($localeData[$key]) ? $localeData[$key] : '##' . $key . '##';
	}
	
	/**
	 * Load localized strings for the user's current locale from an XML file (or cache, if available).
	 * TODO: Split across several XML files for easier maintainability?
	 * @return array associative array of keys and localized strings
	 */
	function &loadLocale() {
		$localeData = array();
		$locale = Locale::getLocale();
		setlocale(LC_ALL, $locale);
		
		$localeFile = "locale/$locale/locale.xml";
		$cacheFile = "locale/cache/$locale.inc.php";
		
		// Compare the cache and XML file modification times
		// TODO: Add config variable to skip this check? We can probably distribute the English cache file and skip the check by default
		if (file_exists($cacheFile) && filemtime($localeFile) < filemtime($cacheFile)) {
			// Load cached locale file
			require($cacheFile);
			
		} else {
			// Reload localization XML file
			$xmlDao = &new XMLDAO();
			$data = $xmlDao->parseStruct($localeFile, array('message'));
		
			// Build array with ($key => $string)
			if (isset($data['message'])) {
				foreach ($data['message'] as $messageData) {
					$localeData[$messageData['attributes']['key']] = $messageData['value'];
				}
			}
			
			// Cache array
			if (function_exists('var_export') && ((file_exists($cacheFile) && is_writable($cacheFile)) || is_writable(dirname($cacheFile)))) {
				// var_export is only available on PHP >= 4.2.0
				// TODO: use different (custom?) function if var_export is not supported so caching will work on older PHP versions
				$fp = fopen($cacheFile, 'w');
				fwrite($fp, '<?php $localeData = ' . var_export($localeData, true) . '; ?>');
				fclose($fp);
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
	 * Return a list of all available locales.
	 * @return array
	 */
	function &getAllLocales() {
		static $allLocales;
		
		if (!isset($locales)) {
			// Check if up-to-date cache file exists
			$cacheFile = "locale/cache/locales.inc.php";
			if (file_exists($cacheFile) && filemtime(LOCALE_REGISTRY_FILE) < filemtime($cacheFile)) {
				// Load cached locale file
				require($cacheFile);
				
			} else {
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

				// Cache array
				if (function_exists('var_export') && ((file_exists($cacheFile) && is_writable($cacheFile)) || is_writable(dirname($cacheFile)))) {
					$fp = fopen($cacheFile, 'w');
					fwrite($fp, '<?php $allLocales = ' . var_export($allLocales, true) . '; ?>');
					fclose($fp);
				}
			}
		}
		
		return $allLocales;
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
	
	// FIXME Make this more flexible by determing what to do from an XML file?
	/**
	 * Uninstall support for an existing locale.
	 * @param $locale string
	 */
	function installLocale($locale) {
		// Install default locale-specific data
		import('db.DBDataXMLParser');
		import('install.Installer');
		
		$filesToInstall = array(
			XML_DBSCRIPTS_DIR . '/data/locale/' . $locale . '/email_templates_data.xml'
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
