<?php

/**
 * Journal.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
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
	
	//
	// Get/set methods
	//
	
	/**
	 * Get enabled flag of journal
	 * @return int
	 */
	 function getEnabled() {
	 	return $this->getData('enabled');
	}
	
	/**
	* Set enabled falg of journal
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
	 * Get title of journal.
	 * @return string
	 */
	function getTitle() {
		return $this->getData('title');
	}
	
	/**
	 * Set title of journal.
	 * @param $title string
	 */
	function setTitle($title) {
		return $this->setData('title', $title);
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
		return $journalSettingsDao->getJournalSettings($this->getData('journalId'));
	}
	
	/**
	 * Retrieve a journal setting value.
	 * @param $name
	 * @return mixed
	 */
	function getSetting($name) {
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		return $journalSettingsDao->getSetting($this->getData('journalId'), $name);
	}
	
}

?>
