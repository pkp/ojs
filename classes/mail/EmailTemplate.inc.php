<?php

/**
 * EmailTemplate.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package mail
 *
 * EmailTemplate class.
 * Describes basic email template properties.
 *
 * $Id$
 */

/**
 * Email template base class.
 */
class BaseEmailTemplate extends DataObject {

	/**
	 * Constructor.
	 */
	function BaseEmailTemplate() {
		parent::DataObject();
	}
	
	//
	// Get/set methods
	//
	
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
	 * Get ID of email template.
	 * @return int
	 */
	function getEmailId() {
		return $this->getData('emailId');
	}
	
	/**
	 * Set ID of email template.
	 * @param $emailId int
	 */
	function setEmailId($emailId) {
		return $this->setData('emailId', $emailId);
	}
	
	/**
	 * Get key of email template.
	 * @return string
	 */
	function getEmailKey() {
		return $this->getData('emailKey');
	}
	
	/**
	 * Set key of email template.
	 * @param $emailKey string
	 */
	function setEmailKey($emailKey) {
		return $this->setData('emailKey', $emailKey);
	}
	
	/**
	 * Get the enabled setting of email template.
	 * @return boolean
	 */
	function getEnabled() {
		return $this->getData('enabled');
	}
	
	/**
	 * Set the enabled setting of email template.
	 * @param $enabled boolean
	 */
	function setEnabled($enabled) {
		return $this->setData('enabled', $enabled);
	}
	
	/**
	 * Check if email template is allowed to be disabled.
	 * @return boolean
	 */
	function getCanDisable() {
		return $this->getData('canDisable');
	}
	
	/**
	 * Set whether or not email template is allowed to be disabled.
	 * @param $canDisable boolean
	 */
	function setCanDisable($canDisable) {
		return $this->setData('canDisable', $canDisable);
	}
	
}


/**
 * Email template with data for all supported locales.
 */
class LocaleEmailTemplate extends BaseEmailTemplate {

	/** @var $localeData array of localized email template data */
	var $localeData;

	/**
	 * Constructor.
	 */
	function LocaleEmailTemplate() {
		parent::BaseEmailTemplate();
		$this->localeData = array();
	}
	
	/**
	 * Add a new locale to store data for.
	 * @param $locale string
	 */
	function addLocale($locale) {
		$this->localeData[$locale] = array();
	}
	
	/**
	 * Get set of supported locales for this template.
	 * @return array
	 */
	function getLocales() {
		return array_keys($this->localeData);
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get subject of email template.
	 * @param $locale string
	 * @return string
	 */
	function getSubject($locale) {
		return isset($this->localeData[$locale]['subject']) ? $this->localeData[$locale]['subject'] : '';
	}
	
	/**
	 * Set subject of email template.
	 * @param $locale string
	 * @param $subject string
	 */
	function setSubject($locale, $subject) {
		$this->localeData[$locale]['subject'] = $subject;
	}
	
	/**
	 * Get body of email template.
	 * @param $locale string
	 * @return string
	 */
	function getBody($locale) {
		return isset($this->localeData[$locale]['body']) ? $this->localeData[$locale]['body'] : '';
	}
	
	/**
	 * Set body of email template.
	 * @param $locale string
	 * @param $body string
	 */
	function setBody($locale, $body) {
		$this->localeData[$locale]['body'] = $body;
	}
}


/**
 * Email template for a specific locale.
 */
class EmailTemplate extends BaseEmailTemplate {

	/**
	 * Constructor.
	 */
	function EmailTemplate() {
		parent::BaseEmailTemplate();
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get locale of email template.
	 * @return string
	 */
	function getLocale() {
		return $this->getData('locale');
	}
	
	/**
	 * Set locale of email template.
	 * @param $locale string
	 */
	function setLocale($locale) {
		return $this->setData('locale', $locale);
	}
	
	/**
	 * Get subject of email template.
	 * @return string
	 */
	function getSubject() {
		return $this->getData('subject');
	}
	
	/**
	 * Set subject of journal.
	 * @param $subject string
	 */
	function setSubject($subject) {
		return $this->setData('subject', $subject);
	}
	
	/**
	 * Get body of email template.
	 * @return string
	 */
	function getBody() {
		return $this->getData('body');
	}
	
	/**
	 * Set body of email template.
	 * @param $body string
	 */
	function setBody($body) {
		return $this->setData('body', $body);
	}

}

?>
