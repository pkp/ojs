<?php

/**
 * @file Journal.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package journal
 * @class Journal
 *
 * Journal class.
 * Describes basic journal properties.
 *
 * $Id$
 */

class Journal extends DataObject {
	/**
	 * Constructor.
	 */
	function Journal() {
		parent::DataObject();
	}
	
	/**
	 * Get the base URL to the journal.
	 * @return string
	 */
	function getUrl() {
		return Request::url($this->getPath());
	}
	
	/**
	 * Return the primary locale of this journal.
	 * @return string
	 */
	function getPrimaryLocale() {
		return $this->getData('primaryLocale');
	}
	
	/**
	 * Set the primary locale of this journal.
	 * @param $locale string
	 */
	function setPrimaryLocale($primaryLocale) {
		return $this->setData('primaryLocale', $primaryLocale);
	}
	
	/**
	 * Return associative array of all locales supported by the site.
	 * These locales are used to provide a language toggle on the main site pages.
	 * @return array
	 */
	function &getSupportedLocaleNames() {
		$supportedLocales =& $this->getData('supportedLocales');
		
		if (!isset($supportedLocales)) {
			$supportedLocales = array();
			$localeNames = &Locale::getAllLocales();

			$locales = $this->getSetting('supportedLocales');
			if (!isset($locales) || !is_array($locales)) {
				$locales = array();
			}
						
			foreach ($locales as $localeKey) {
				$supportedLocales[$localeKey] = $localeNames[$localeKey];
			}
		
			//asort($supportedLocales);
		}
		
		return $supportedLocales;
	}
	
	/**
	 * Get "localized" journal page title (if applicable).
	 * param $home boolean get homepage title
	 * @return string
	 */
	function getJournalPageHeaderTitle($home = false) {
		$prefix = $home ? 'home' : 'page';
		$typeArray = $this->getSetting($prefix . 'HeaderTitleType');
		$imageArray = $this->getSetting($prefix . 'HeaderTitleImage');
		$titleArray = $this->getSetting($prefix . 'HeaderTitle');

		$title = null;

		foreach (array(Locale::getLocale(), Locale::getPrimaryLocale()) as $locale) {
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
	function getJournalPageHeaderLogo($home = false) {
		$prefix = $home ? 'home' : 'page';
		$logoArray = $this->getSetting($prefix . 'HeaderLogoImage');
		foreach (array(Locale::getLocale(), Locale::getPrimaryLocale()) as $locale) {
			if (isset($logoArray[$locale])) return $logoArray[$locale];
		}
		return null;
	}
	
	//
	// Get/set methods
	//

	/**
	 * Get the localized title of the journal.
	 * @return string
	 */
	function getJournalTitle() {
		return $this->getLocalizedSetting('title');
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
	 function getJournalInitials() {
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
	 * Get enabled flag of journal
	 * @return int
	 */
	 function getEnabled() {
	 	return $this->getData('enabled');
	}
	
	/**
	* Set enabled flag of journal
	* @param $enabled int
	*/
	function setEnabled($enabled) {
		return $this->setData('enabled',$enabled);
	}
	
	/**
	 * Get ID of journal.
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}
	
	/**
	 * Set ID of journal.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}
	
	/**
	 * Get the localized description of the journal.
	 * @return string
	 */
	function getJournalDescription() {
		return $this->getDescription(Locale::getLocale());
	}

	/**
	 * Get description of journal.
	 * @param $locale string
	 * @return string
	 */
	function getDescription($locale) {
		return $this->getSetting('description', $locale);
	}
	
	/**
	 * Get path to journal (in URL).
	 * @return string
	 */
	function getPath() {
		return $this->getData('path');
	}
	
	/**
	 * Set path to journal (in URL).
	 * @param $path string
	 */
	function setPath($path) {
		return $this->setData('path', $path);
	}
	
	/**
	 * Get sequence of journal in site table of contents.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}
	
	/**
	 * Set sequence of journal in site table of contents.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}
	
	/**
	 * Retrieve array of journal settings.
	 * @return array
	 */
	function &getSettings() {
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$settings = &$journalSettingsDao->getJournalSettings($this->getData('journalId'));
		return $settings;
	}

	function &getLocalizedSetting($name) {
		$returner = $this->getSetting($name, Locale::getLocale());
		if ($returner === null) {
			unset($returner);
			$returner = $this->getSetting($name, Locale::getPrimaryLocale());
		}
		return $returner;
	}

	/**
	 * Retrieve a journal setting value.
	 * @param $name string
	 * @param $locale string
	 * @return mixed
	 */
	function &getSetting($name, $locale = null) {
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$setting = &$journalSettingsDao->getSetting($this->getData('journalId'), $name, $locale);
		return $setting;
	}

	/**
	 * Update a journal setting value.
	 * @param $name string
	 * @param $value string
	 * @param $type string optional
	 * @param $isLocalized boolean optional
	 */
	function updateSetting($name, $value, $type = null, $isLocalized = false) {
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		return $journalSettingsDao->updateSetting($this->getJournalId(), $name, $value, $type, $isLocalized);
	}
}

?>
