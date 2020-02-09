<?php

/**
 * @file classes/i18n/AppLocale.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AppLocale
 * @ingroup i18n
 *
 * @brief Provides methods for loading locale data and translating strings identified by unique keys
 *
 */

import('lib.pkp.classes.i18n.PKPLocale');

class AppLocale extends PKPLocale {
	/**
	 * Get all supported UI locales for the current context.
	 * @return array
	 */
	static function getSupportedLocales() {
		static $supportedLocales;
		if (!isset($supportedLocales)) {
			if (defined('SESSION_DISABLE_INIT')) {
				$supportedLocales = AppLocale::getAllLocales();
			} elseif (($journal = self::$request->getJournal())) {
				$supportedLocales = $journal->getSupportedLocaleNames();
			} else {
				$site = self::$request->getSite();
				$supportedLocales = $site->getSupportedLocaleNames();
			}
		}
		return $supportedLocales;
	}

	/**
	 * Get all supported form locales for the current context.
	 * @return array
	 */
	static function getSupportedFormLocales() {
		static $supportedFormLocales;
		if (!isset($supportedFormLocales)) {
			if (defined('SESSION_DISABLE_INIT')) {
				$supportedFormLocales = AppLocale::getAllLocales();
			} elseif (($journal = self::$request->getJournal())) {
				$supportedFormLocales = $journal->getSupportedFormLocaleNames();
			} else {
				$site = self::$request->getSite();
				$supportedFormLocales = $site->getSupportedLocaleNames();
			}
		}
		return $supportedFormLocales;
	}

	/**
	 * Return the key name of the user's currently selected locale (default
	 * is "en_US" for U.S. English).
	 * @return string
	 */
	static function getLocale() {
		static $currentLocale;
		if (!isset($currentLocale)) {
			if (defined('SESSION_DISABLE_INIT')) {
				// If the locale is specified in the URL, allow
				// it to override. (Necessary when locale is
				// being set, as cookie will not yet be re-set)
				$locale = self::$request->getUserVar('setLocale');
				if (empty($locale) || !in_array($locale, array_keys(AppLocale::getSupportedLocales()))) $locale = self::$request->getCookieVar('currentLocale');
			} else {
				$sessionManager = SessionManager::getManager();
				$session = $sessionManager->getUserSession();
				$locale = self::$request->getUserVar('uiLocale');

				$journal = self::$request->getJournal();
				$site = self::$request->getSite();

				if (!isset($locale)) {
					$locale = $session->getSessionVar('currentLocale');
				}

				if (!isset($locale)) {
					$locale = self::$request->getCookieVar('currentLocale');
				}

				if (isset($locale)) {
					// Check if user-specified locale is supported
					if ($journal != null) {
						$locales = $journal->getSupportedLocaleNames();
					} else {
						$locales = $site->getSupportedLocaleNames();
					}

					if (!in_array($locale, array_keys($locales))) {
						unset($locale);
					}
				}

				if (!isset($locale)) {
					// Use journal/site default
					if ($journal != null) {
						$locale = $journal->getPrimaryLocale();
					}

					if (!isset($locale)) {
						$locale = $site->getPrimaryLocale();
					}
				}
			}

			if (!AppLocale::isLocaleValid($locale)) {
				$locale = LOCALE_DEFAULT;
			}

			$currentLocale = $locale;
		}
		return $currentLocale;
	}

	/**
	 * Get the stack of "important" locales, most important first.
	 * @return array
	 */
	static function getLocalePrecedence() {
		static $localePrecedence;
		if (!isset($localePrecedence)) {
			$localePrecedence = array(AppLocale::getLocale());

			$journal = self::$request->getJournal();
			if ($journal && !in_array($journal->getPrimaryLocale(), $localePrecedence)) $localePrecedence[] = $journal->getPrimaryLocale();

			$site = self::$request->getSite();
			if ($site && !in_array($site->getPrimaryLocale(), $localePrecedence)) $localePrecedence[] = $site->getPrimaryLocale();
		}
		return $localePrecedence;
	}

	/**
	 * Retrieve the primary locale of the current context.
	 * @return string
	 */
	static function getPrimaryLocale() {
		static $locale;
		if ($locale) return $locale;

		if (defined('SESSION_DISABLE_INIT')) return $locale = LOCALE_DEFAULT;

		$journal = self::$request->getJournal();

		if (isset($journal)) {
			$locale = $journal->getPrimaryLocale();
		}

		if (!isset($locale)) {
			$site = self::$request->getSite();
			$locale = $site->getPrimaryLocale();
		}

		if (!isset($locale) || !AppLocale::isLocaleValid($locale)) {
			$locale = LOCALE_DEFAULT;
		}

		return $locale;
	}

	/**
	 * Make a map of components to their respective files.
	 * @param $locale string
	 * @return array
	 */
	static function makeComponentMap($locale) {
		$baseDir = "locale/$locale/";
		return parent::makeComponentMap($locale) + array(
			LOCALE_COMPONENT_APP_COMMON => $baseDir . 'locale.po',
			LOCALE_COMPONENT_APP_AUTHOR => $baseDir . 'author.po',
			LOCALE_COMPONENT_APP_SUBMISSION => $baseDir . 'submission.po',
			LOCALE_COMPONENT_APP_EDITOR => $baseDir . 'editor.po',
			LOCALE_COMPONENT_APP_MANAGER => $baseDir . 'manager.po',
			LOCALE_COMPONENT_APP_ADMIN => $baseDir . 'admin.po',
			LOCALE_COMPONENT_APP_DEFAULT => $baseDir . 'default.po',
			LOCALE_COMPONENT_APP_API => $baseDir . 'api.po',
			LOCALE_COMPONENT_APP_EMAIL => $baseDir . 'emails.po',
		);
	}
}

