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

define('LOCALE_DEFAULT', 'en_US');

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
		$cacheFile = "locale/cache/$locale.php";
		
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
	 * Return the key name of the user's currently selected locale (default is "en_US" for U.S. English).
	 * @return string 
	 */
	function getLocale() {
		static $locale;
		if (!isset($locale)) {
			$locale = file_exists('locale/' . ($locale = Config::getVar('i18n', 'locale'))) && !empty($locale) ? $locale : LOCALE_DEFAULT;
		}
		return $locale;
	}
	
}

?>
