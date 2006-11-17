<?php

/**
 * Site.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package site
 *
 * Site class.
 * Describes system-wide site properties.
 *
 * $Id$
 */

class Site extends DataObject {

	/**
	 * Constructor.
	 */
	function Site() {
		parent::DataObject();
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
			
			$locales = $this->getSupportedLocales();
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
	 * Get site title.
	 * @return string
	 */
	function getTitle() {
		return $this->getData('title');
	}
	
	/**
	 * Set site title.
	 * @param $title string
	 */
	function setTitle($title) {
		return $this->setData('title', $title);
	}
	
	/**
	 * Get original site stylesheet filename.
	 * @return string
	 */
	function getOriginalStyleFilename() {
		return $this->getData('originalStyleFilename');
	}
	
	/**
	 * Set original site stylesheet filename.
	 * @param $originalStyleFilename string
	 */
	function setOriginalStyleFilename($originalStyleFilename) {
		return $this->setData('originalStyleFilename', $originalStyleFilename);
	}
	
	/**
	 * Get site introduction.
	 * @return string
	 */
	function getIntro() {
		return $this->getData('intro');
	}
	
	/**
	 * Set site introduction.
	 * @param $intro string
	 */
	function setIntro($intro) {
		return $this->setData('intro', $intro);
	}
	
	/**
	 * Get journal redirect.
	 * @return int
	 */
	function getJournalRedirect() {
		return $this->getData('journalRedirect');
	}
	
	/**
	 * Set journal redirect.
	 * @param $journalRedirect int
	 */
	function setJournalRedirect($journalRedirect) {
		return $this->setData('journalRedirect', (int)$journalRedirect);
	}
	
	/**
	 * Get site about description.
	 * @return string
	 */
	function getAbout() {
		return $this->getData('about');
	}
	
	/**
	 * Set site about description.
	 * @param $about string
	 */
	function setAbout($about) {
		return $this->setData('about', $about);
	}
	
	/**
	 * Get site contact name.
	 * @return string
	 */
	function getContactName() {
		return $this->getData('contactName');
	}
	
	/**
	 * Set site contact name.
	 * @param $contactName string
	 */
	function setContactName($contactName) {
		return $this->setData('contactName', $contactName);
	}
	
	/**
	 * Get site contact email.
	 * @return string
	 */
	function getContactEmail() {
		return $this->getData('contactEmail');
	}
	
	/**
	 * Set site contact email.
	 * @param $contactEmail string
	 */
	function setContactEmail($contactEmail) {
		return $this->setData('contactEmail', $contactEmail);
	}
	
	/**
	 * Get minimum password length.
	 * @return int
	 */
	function getMinPasswordLength() {
		return $this->getData('minPasswordLength');
	}
	
	/**
	 * Set minimum password length.
	 * @param $minPasswordLength int
	 */
	function setMinPasswordLength($minPasswordLength) {
		return $this->setData('minPasswordLength', $minPasswordLength);
	}
	
	/**
	 * Get primary locale.
	 * @return string
	 */
	function getLocale() {
		return $this->getData('locale');
	}
	
	/**
	 * Set primary locale.
	 * @param $locale string
	 */
	function setLocale($locale) {
		return $this->setData('locale', $locale);
	}
	
	/**
	 * Get installed locales.
	 * @return array
	 */
	function getInstalledLocales() {
		$locales = $this->getData('installedLocales');
		return isset($locales) ? $locales : array();
	}
	
	/**
	 * Set installed locales.
	 * @param $installedLocales array
	 */
	function setInstalledLocales($installedLocales) {
		return $this->setData('installedLocales', $installedLocales);
	}
	
	/**
	 * Get array of all supported locales (for static text).
	 * @return array
	 */
	function getSupportedLocales() {
		$locales = $this->getData('supportedLocales');
		return isset($locales) ? $locales : array();
	}
	
	/**
	 * Set array of all supported locales (for static text).
	 * @param $supportedLocales array
	 */
	function setSupportedLocales($supportedLocales) {
		return $this->setData('supportedLocales', $supportedLocales);
	}
	
	/**
	 * Get whether working locales are used in user profiles or not.
	 * @return string
	 */
	function getProfileLocalesEnabled() {
		return $this->getData('profileLocalesEnabled');
	}
	
	/**
	 * Set whether working locales are used in user profiles or not.
	 * @param $profileLocalesEnabled boolean
	 */
	function setProfileLocalesEnabled($profileLocalesEnabled) {
		return $this->setData('profileLocalesEnabled', $profileLocalesEnabled);
	}

	function getSiteStyleFilename() {
		return 'sitestyle.css';
	}
}

?>
