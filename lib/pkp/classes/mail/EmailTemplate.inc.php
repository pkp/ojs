<?php

/**
 * @file classes/mail/EmailTemplate.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BaseEmailTemplate
 * @ingroup mail
 * @see EmailTemplateDAO
 *
 * @brief Describes basic email template properties.
 */


/**
 * Email template base class.
 */
class BaseEmailTemplate extends DataObject {

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Get/set methods
	//

	/**
	 * Get association type.
	 * @return int
	 */
	function getAssocType() {
		return $this->getData('assocType');
	}

	/**
	 * Set association type.
	 * @param $assocType int
	 */
	function setAssocType($assocType) {
		$this->setData('assocType', $assocType);
	}

	/**
	 * Get ID of journal/conference/...
	 * @return int
	 */
	function getAssocId() {
		return $this->getData('assocId');
	}

	/**
	 * Set ID of journal/conference/...
	 * @param $assocId int
	 */
	function setAssocId($assocId) {
		$this->setData('assocId', $assocId);
	}

	/**
	 * Determine whether or not this is a custom email template
	 * (ie one that was created by the journal/conference/... manager and
	 * is not part of the system upon installation)
	 */
	function isCustomTemplate() {
		return false;
	}

	/**
	 * Get sender role ID.
	 */
	function getFromRoleId() {
		return $this->getData('fromRoleId');
	}

	/**
	 * Set sender role ID.
	 * @param $fromRoleId int
	 */
	function setFromRoleId($fromRoleId) {
		$this->setData('fromRoleId', $fromRoleId);
	}

	/**
	 * Get recipient role ID.
	 */
	function getToRoleId() {
		return $this->getData('toRoleId');
	}

	/**
	 * Set recipient role ID.
	 * @param $toRoleId int
	 */
	function setToRoleId($toRoleId) {
		$this->setData('toRoleId', $toRoleId);
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
		$this->setData('emailId', $emailId);
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
		$this->setData('emailKey', $emailKey);
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
		$this->setData('enabled', $enabled);
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
		$this->setData('canDisable', $canDisable);
	}

}


/**
 * Email template with data for all supported locales.
 */
class LocaleEmailTemplate extends BaseEmailTemplate {

	/** @var array of localized email template data */
	var $localeData;

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
		$this->localeData = array();
	}

	/**
	 * Set whether or not this is a custom template.
	 */
	function setCustomTemplate($isCustomTemplate) {
		$this->isCustomTemplate = $isCustomTemplate;
	}

	/**
	 * Determine whether or not this is a custom email template
	 * (ie one that was created by the journal/conference/... manager and
	 * is not part of the system upon installation)
	 */
	function isCustomTemplate() {
		return $this->isCustomTemplate;
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
	 * Get description of email template.
	 * @param $locale string
	 * @return string
	 */
	function getDescription($locale) {
		return isset($this->localeData[$locale]['description']) ? $this->localeData[$locale]['description'] : '';
	}

	/**
	 * Set description of email template.
	 * @param $locale string
	 * @param $description string
	 */
	function setDescription($locale, $description) {
		$this->localeData[$locale]['description'] = $description;
	}

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
	function __construct() {
		parent::__construct();
	}

	/**
	 * Set whether or not this is a custom template.
	 */
	function setCustomTemplate($isCustomTemplate) {
		$this->isCustomTemplate = $isCustomTemplate;
	}

	/**
	 * Determine whether or not this is a custom email template
	 * (ie one that was created by the journal/conference/... manager and
	 * is not part of the system upon installation)
	 */
	function isCustomTemplate() {
		return $this->isCustomTemplate;
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
		$this->setData('locale', $locale);
	}

	/**
	 * Get subject of email template.
	 * @return string
	 */
	function getSubject() {
		return $this->getData('subject');
	}

	/**
	 * Set subject of email.
	 * @param $subject string
	 */
	function setSubject($subject) {
		$this->setData('subject', $subject);
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
		$this->setData('body', $body);
	}
}

?>
