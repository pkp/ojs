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
	/**
	 * Constructor.
	 */
	function Locale() {
	}

	/**
	 * Add additional locale keys to the current locale database
	 * @param $additionalLocaleData Array Additional key=>value locale data
	 */
	function addLocaleData($locale, &$additionalLocaleData) {
		$localeData = &Locale::getLocaleData($locale);
		$localeData[$locale] = array_merge($localeData[$locale], $additionalLocaleData);
	}

	/**
	 * Get current locale data by reference. (Load it if necessary.)
	 */
	function &getLocaleData($locale) {
		static $localeData = array();
		if (!isset($localeData[$locale])) {
			// Load locale data only once per request
			$localeData[$locale] = Locale::loadLocale($locale);
		}

		return $localeData;
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

		$localeData = &Locale::getLocaleData($locale);
		
		$key = trim($key);
		if (empty($key)) {
			return '';
		}
		
		if (isset($localeData[$locale][$key])) {
			$message = $localeData[$locale][$key];
			
			if (!empty($params)) {
				// Substitute custom parameters
				foreach ($params as $key => $value) {
					$message = str_replace("{\$$key}", $value, $message);
				}
			}
			
			return $message;
			
		} else {
			// Add some octothorpes to missing keys to make them more obvious
			return '##' . $key . '##';
		}
	}
	
	/**
	 * Load localized strings for the user's current locale from an XML file (or cache, if available).
	 * TODO: Split across several XML files for easier maintainability?
	 * @param $locale string the locale to load
	 * @return array associative array of keys and localized strings
	 */
	function &loadLocale($locale = null, $localeFile = null, $cacheFile = null) {
		$localeData = array();
		
		if (!isset($locale)) {
			$locale = Locale::getLocale();
		}
		
		setlocale(LC_ALL, $locale . '.' . LOCALE_ENCODING, $locale);
		
		if ($localeFile === null) $localeFile = "locale/$locale/locale.xml";
		if ($cacheFile === null) $cacheFile = "locale/cache/$locale.inc.php";
		
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
			if ((file_exists($cacheFile) && is_writable($cacheFile)) || (!file_exists($cacheFile) && is_writable(dirname($cacheFile)))) {
				// var_export is only available on PHP >= 4.2.0
				// TODO: use different (custom?) function if var_export is not supported so caching will work on older PHP versions
				$fp = fopen($cacheFile, 'w');
				if (function_exists('var_export')) {
					fwrite($fp, '<?php $localeData = ' . var_export($localeData, true) . '; ?>');
				} else {
					fwrite($fp, '<?php $localeData = ' . $xmlDao->custom_var_export($localeData, true) . '; ?>');			
				}
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
	
	/**
	 * Return a list of all available locales.
	 * @return array
	 */
	function &getAllLocales() {
		static $allLocales;
		
		if (!isset($allLocales)) {
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
				if ((file_exists($cacheFile) && is_writable($cacheFile)) || (!file_exists($cacheFile) && is_writable(dirname($cacheFile)))) {
					$fp = fopen($cacheFile, 'w');
					if (function_exists('var_export')) {
						fwrite($fp, '<?php $allLocales = ' . var_export($allLocales, true) . '; ?>');
					} else {
						fwrite($fp, '<?php $allLocales = ' . $xmlDao->custom_var_export($allLocales, true) . '; ?>');			
					}					
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
