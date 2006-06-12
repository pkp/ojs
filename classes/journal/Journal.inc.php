<?php

/**
 * Journal.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package journal
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
	function getLocale() {
		return $this->getSetting('primaryLocale');
	}
	
	/**
	 * Return associative array of all locales supported by the site.
	 * These locales are used to provide a language toggle on the main site pages.
	 * @return array
	 */
	function &getSupportedLocaleNames() {
		static $supportedLocales;
		
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
		
			asort($supportedLocales);
		}
		
		return $supportedLocales;
	}
	
	/**
	 * Get "localized" journal page title (if applicable).
	 * param $home boolean get homepage title
	 * @return string
	 */
	function getJournalPageHeaderTitle($home = false) {
		// FIXME this is evil
		$alternateLocaleNum = Locale::isAlternateJournalLocale($this->getData('journalId'));
		$prefix = $home ? 'home' : 'page';
		switch ($alternateLocaleNum) {
			case 1:
				$type = $this->getSetting($prefix . 'HeaderTitleTypeAlt1');
				if ($type) {
					$title = $this->getSetting($prefix . 'HeaderTitleImageAlt1');
				}
				if (!isset($title)) {
					$title = $this->getSetting($prefix . 'HeaderTitleAlt1');
				}
				break;
			case 2:
				$type = $this->getSetting($prefix . 'HeaderTitleTypeAlt2');
				if ($type) {
					$title = $this->getSetting($prefix . 'HeaderTitleImageAlt2');
				}
				if (!isset($title)) {
					$title = $this->getSetting($prefix . 'HeaderTitleAlt2');
				}
				break;
		}
		
		if (isset($title) && !empty($title)) {
			return $title;
			
		} else {
			$type = $this->getSetting($prefix . 'HeaderTitleType');
			if ($type) {
				$title = $this->getSetting($prefix . 'HeaderTitleImage');
			}
			if (!isset($title)) {
				$title = $this->getSetting($prefix . 'HeaderTitle');
			}
			
			return $title;
		}
	}
	
	/**
	 * Get "localized" journal page logo (if applicable).
	 * param $home boolean get homepage logo
	 * @return string
	 */
	function getJournalPageHeaderLogo($home = false) {
		// FIXME this is evil
		$alternateLocaleNum = Locale::isAlternateJournalLocale($this->getData('journalId'));
		$prefix = $home ? 'home' : 'page';
		switch ($alternateLocaleNum) {
			case 1:
				$logo = $this->getSetting($prefix . 'HeaderLogoImageAlt1');
				break;
			case 2:
				$logo = $this->getSetting($prefix . 'HeaderLogoImageAlt2');
				break;
		}
		
		if (isset($logo) && !empty($logo)) {
			return $logo;
			
		} else {
			return $this->getSetting($prefix . 'HeaderLogoImage');
		}
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get title of journal
	 * @return string
	 */
	 function getTitle() {
	 	return $this->getData('title');
	}
	
	/**
	* Set title of journal
	* @param $title string
	*/
	function setTitle($title) {
		return $this->setData('title',$title);
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
	 * Get description of journal.
	 * @return string
	 */
	function getDescription() {
		return $this->getData('description');
	}
	
	/**
	 * Set description of journal.
	 * @param $description string
	 */
	function setDescription($description) {
		return $this->setData('description', $description);
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
	
	/**
	 * Retrieve a journal setting value.
	 * @param $name
	 * @return mixed
	 */
	function &getSetting($name) {
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$setting = &$journalSettingsDao->getSetting($this->getData('journalId'), $name);
		return $setting;
	}

	/**
	 * Update a journal setting value.
	 */
	function updateSetting($name, $value, $type = null) {
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		return $journalSettingsDao->updateSetting($this->getJournalId(), $name, $value, $type);
	}
}

?>
