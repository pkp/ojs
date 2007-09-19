<?php

/**
 * @file Site.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package site
 * @class Site
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
	 * Get localized site title.
	 */
	function getSiteTitle() {
		return $this->getLocalizedData('title');
	}

	/**
	 * Get site title.
	 * @param $locale string
	 * @return string
	 */
	function getTitle($locale) {
		return $this->getData('title', $locale);
	}

	/**
	 * Set site title.
	 * @param $title string
	 * @param $locale string
	 */
	function setTitle($title, $locale) {
		return $this->setData('title', $title, $locale);
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
	 * Get localized site intro.
	 */
	function getSiteIntro() {
		return $this->getLocalizedData('intro');
	}

	/**
	 * Get site introduction.
	 * @param $locale string
	 * @return string
	 */
	function getIntro($locale) {
		return $this->getData('intro', $locale);
	}

	/**
	 * Set site introduction.
	 * @param $intro string
	 * @param $locale string
	 */
	function setIntro($intro, $locale) {
		return $this->setData('intro', $intro, $locale);
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
	 * Get localized site about statement.
	 */
	function getSiteAbout() {
		return $this->getLocalizedData('about');
	}

	/**
	 * Get site about description.
	 * @param $locale string
	 * @return string
	 */
	function getAbout($locale) {
		return $this->getData('about', $locale);
	}

	/**
	 * Set site about description.
	 * @param $about string
	 * @param $locale string
	 */
	function setAbout($about, $locale) {
		return $this->setData('about', $about, $locale);
	}

	/**
	 * Get localized site contact name.
	 */
	function getSiteContactName() {
		return $this->getLocalizedData('contactName');
	}

	/**
	 * Get site contact name.
	 * @param $locale string
	 * @return string
	 */
	function getContactName($locale) {
		return $this->getData('contactName', $locale);
	}

	/**
	 * Set site contact name.
	 * @param $contactName string
	 * @param $locale string
	 */
	function setContactName($contactName, $locale) {
		return $this->setData('contactName', $contactName, $locale);
	}

	/**
	 * Get localized site contact email.
	 */
	function getSiteContactEmail() {
		return $this->getLocalizedData('contactEmail');
	}

	/**
	 * Get site contact email.
	 * @param $locale string
	 * @return string
	 */
	function getContactEmail($locale) {
		return $this->getData('contactEmail', $locale);
	}

	/**
	 * Set site contact email.
	 * @param $contactEmail string
	 * @param $locale string
	 */
	function setContactEmail($contactEmail, $locale) {
		return $this->setData('contactEmail', $contactEmail, $locale);
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
	function getPrimaryLocale() {
		return $this->getData('primaryLocale');
	}

	/**
	 * Set primary locale.
	 * @param $primaryLocale string
	 */
	function setPrimaryLocale($primaryLocale) {
		return $this->setData('primaryLocale', $primaryLocale);
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
	 * Get the local name under which the site-wide locale file is stored.
	 * @return string
	 */
	function getSiteStyleFilename() {
		return 'sitestyle.css';
	}
}

?>
