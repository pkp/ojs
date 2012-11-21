<?php

/**
 * @defgroup journal
 */

/**
 * @file classes/journal/Journal.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Journal
 * @ingroup journal
 * @see JournalDAO
 *
 * @brief Describes basic journal properties.
 */


define('PUBLISHING_MODE_OPEN', 0);
define('PUBLISHING_MODE_SUBSCRIPTION', 1);
define('PUBLISHING_MODE_NONE', 2);

import('lib.pkp.classes.core.Context');

class Journal extends Context {
	/**
	 * Constructor.
	 */
	function Journal() {
		parent::Context();
	}

	/**
	 * Return associative array of all locales supported for the submissions.
	 * These locales are used to provide a language toggle on the submission setp1 and the galley edit page.
	 * @return array
	 */
	function &getSupportedSubmissionLocaleNames() {
		$supportedLocales =& $this->getData('supportedSubmissionLocales');

		if (!isset($supportedLocales)) {
			$supportedLocales = array();
			$localeNames =& AppLocale::getAllLocales();

			$locales = $this->getSetting('supportedSubmissionLocales');
			if (empty($locales)) $locales = array($this->getPrimaryLocale());

			foreach ($locales as $localeKey) {
				$supportedLocales[$localeKey] = $localeNames[$localeKey];
			}
		}

		return $supportedLocales;
	}

	/**
	 * Get "localized" journal page title (if applicable).
	 * param $home boolean get homepage title
	 * @return string
	 */
	function getLocalizedPageHeaderTitle($home = false) {
		$prefix = $home ? 'home' : 'page';
		$typeArray = $this->getSetting($prefix . 'HeaderTitleType');
		$imageArray = $this->getSetting($prefix . 'HeaderTitleImage');
		$titleArray = $this->getSetting($prefix . 'HeaderTitle');

		$title = null;

		foreach (array(AppLocale::getLocale(), AppLocale::getPrimaryLocale()) as $locale) {
			if (isset($typeArray[$locale]) && $typeArray[$locale]) {
				if (isset($imageArray[$locale])) $title = $imageArray[$locale];
			}
			if (empty($title) && isset($titleArray[$locale])) $title = $titleArray[$locale];
			if (!empty($title)) return $title;
		}
		return null;
	}

	/**
	 * Get "localized" journal page logo (if applicable).
	 * param $home boolean get homepage logo
	 * @return string
	 */
	function getLocalizedPageHeaderLogo($home = false) {
		$prefix = $home ? 'home' : 'page';
		$logoArray = $this->getSetting($prefix . 'HeaderLogoImage');
		foreach (array(AppLocale::getLocale(), AppLocale::getPrimaryLocale()) as $locale) {
			if (isset($logoArray[$locale])) return $logoArray[$locale];
		}
		return null;
	}

	/**
	 * Get localized favicon
	 * @return string
	 */
	function getLocalizedFavicon() {
		$faviconArray = $this->getSetting('journalFavicon');
		foreach (array(AppLocale::getLocale(), AppLocale::getPrimaryLocale()) as $locale) {
			if (isset($faviconArray[$locale])) return $faviconArray[$locale];
		}
		return null;
	}

	//
	// Get/set methods
	//

	/**
	 * Get the localized title of the journal.
	 * @param $preferredLocale string
	 * @return string
	 */
	function getLocalizedTitle($preferredLocale = null) {
		return $this->getLocalizedSetting('title', $preferredLocale);
	}

	/**
	 * Get title of journal
	 * @param $locale string
	 * @return string
	 */
	function getTitle($locale) {
		return $this->getSetting('title', $locale);
	}

	/**
	 * Get localized initials of journal
	 * @return string
	 */
	function getLocalizedInitials() {
		return $this->getLocalizedSetting('initials');
	}

	/**
	 * Get the initials of the journal.
	 * @param $locale string
	 * @return string
	 */
	function getInitials($locale) {
		return $this->getSetting('initials', $locale);
	}

	/**
	 * Get the association type for this context.
	 * @return int
	 */
	function getAssocType() {
		return ASSOC_TYPE_JOURNAL;
	}

	/**
	 * Get the settings DAO for this context object.
	 * @return DAO
	 */
	static function getSettingsDAO() {
		return DAORegistry::GetDAO('JournalSettingsDAO');
	}
}

?>
