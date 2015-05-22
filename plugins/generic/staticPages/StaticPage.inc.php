<?php

/**
 * @file plugins/generic/staticPages/StaticPage.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.staticPages
 * @class StaticPage
 *
 */

class StaticPage extends DataObject {
	//
	// Get/set methods
	//

	/**
	 * Get journal id
	 * @return string
	 */
	function getJournalId(){
		return $this->getData('journalId');
	}

	/**
	 * Set journal Id
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}


	/**
	 * Set page title
	 * @param string string
	 * @param locale
	 */
	function setTitle($title, $locale) {
		return $this->setData('title', $title, $locale);
	}

	/**
	 * Get page title
	 * @param locale
	 * @return string
	 */
	function getTitle($locale) {
		return $this->getData('title', $locale);
	}

	/**
	 * Get Localized page title
	 * @return string
	 */
	function getStaticPageTitle() {
		return $this->getLocalizedData('title');
	}

	/**
	 * Set page content
	 * @param $content string
	 * @param locale
	 */
	function setContent($content, $locale) {
		return $this->setData('content', $content, $locale);
	}

	/**
	 * Get content
	 * @param locale
	 * @return string
	 */
	function getContent($locale) {
		return $this->getData('content', $locale);
	}

	/**
	 * Get "localized" content
	 * @return string
	 */
	function getStaticPageContent() {
		return $this->getLocalizedData('content');
	}

	/**
	 * Get page path string
	 * @return string
	 */
	function getPath() {
		return $this->getData('path');
	}

	 /**
	  * Set page path string
	  * @param $path string
	  */
	function setPath($path) {
		return $this->setData('path', $path);
	}

	/**
	 * Get ID of page.
	 * @return int
	 */
	function getStaticPageId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getId();
	}

	/**
	 * Set ID of page.
	 * @param $staticPageId int
	 */
	function setStaticPageId($staticPageId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setId($staticPageId);
	}
}

?>
