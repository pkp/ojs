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
	 * Get site about description.
	 * @return string
	 */
	function getAbout() {
		return $this->getData('about');
	}
	
	/**
	 * Set site about description.
	 * @param $intro string
	 */
	function setAbout($about) {
		return $this->setData('about', $about);
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
	
}

?>
