<?php

/**
 * Site.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
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
		return $this->setData('journalRedirect', $journalRedirect);
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
	
}

?>
