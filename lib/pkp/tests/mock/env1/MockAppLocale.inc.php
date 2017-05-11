<?php

/**
 * @file tests/mock/env1/MockAppLocale.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AppLocale
 * @ingroup tests_mock_env1
 *
 * @brief Mock implementation of the Locale class
 */

define('LOCALE_REGISTRY_FILE', 'lib/pkp/tests/registry/locales.xml');
define('LOCALE_ENCODING', 'utf-8');

import('lib.pkp.classes.i18n.PKPLocale');

class AppLocale extends PKPLocale {
	static
		$primaryLocale = 'en_US',
		$supportedLocales = array('en_US' => 'English/America'),
		$translations = array();

	/*
	 * method required during setup of
	 * the PKP application framework
	 */
	static function initialize($request) {
		// do nothing
	}

	/*
	 * method required during setup of
	 * the PKP application framework
	 * @return string test locale
	 */
	static function getLocale() {
		return 'en_US';
	}

	/*
	 * method required during setup of
	 * the PKP application framework
	 */
	static function registerLocaleFile($locale, $filename, $addToTop = false) {
		// do nothing
	}

	/**
	 * method required during setup of
	 * the PKP templating engine and application framework
	 */
	static function requireComponents() {
		// do nothing
	}

	/**
	 * Mocked method
	 * @return array a test array of locales
	 */
	static function getLocalePrecedence() {
		return array('en_US', 'fr_FR');
	}

	/**
	 * Mocked method
	 * @param $key string
	 * @param $params array named substitution parameters
	 * @param $locale string the locale to use
	 * @return string
	 */
	static function translate($key, $params = array(), $locale = null) {
		if (isset(self::$translations[$key])) {
			return self::$translations[$key];
		}
		return "##$key##";
	}

	/**
	 * Setter to configure a custom
	 * primary locale for testing.
	 * @param $primaryLocale string
	 */
	static function setPrimaryLocale($primaryLocale) {
		self::$primaryLocale = $primaryLocale;
	}

	/**
	 * Mocked method
	 * @return string
	 */
	static function getPrimaryLocale() {
		return self::$primaryLocale;
	}

	/**
	 * Setter to configure a custom
	 * primary locale for testing.
	 * @param $supportedLocales array
	 *  example array(
	 *   'en_US' => 'English',
	 *   'de_DE' => 'German'
	 *  )
	 */
	static function setSupportedLocales($supportedLocales) {
		self::$supportedLocales = $supportedLocales;
	}

	/**
	 * Mocked method
	 * @return array
	 */
	static function getSupportedLocales() {
		return self::$supportedLocales;
	}

	/**
	 * Mocked method
	 * @return array
	 */
	static function getSupportedFormLocales() {
		return array('en_US');
	}

	/**
	 * Set translation keys to be faked.
	 * @param $translations array
	 */
	static function setTranslations($translations) {
		self::$translations = $translations;
	}
}
?>
