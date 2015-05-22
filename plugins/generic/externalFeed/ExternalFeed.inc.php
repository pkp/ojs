<?php

/**
 * @file plugins/generic/externalFeed/ExternalFeed.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ExternalFeed
 * @ingroup plugins_generic_externalFeed
 *
 * @brief Basic class describing an external feed.
 */

define('EXTERNAL_FEED_DISPLAY_BLOCK_NONE',		0);
define('EXTERNAL_FEED_DISPLAY_BLOCK_HOMEPAGE',		1);
define('EXTERNAL_FEED_DISPLAY_BLOCK_ALL',		2);


class ExternalFeed extends DataObject {

	function ExternalFeed() {
		parent::DataObject();
	}

	//
	// Get/set methods
	//

	/**
	 * Get the ID of the external feed.
	 * @return int
	 */
	function getId() {
		return $this->getData('feedId');
	}

	/**
	 * Set the ID of the external feed.
	 * @param $feedId int
	 */
	function setId($feedId) {
		return $this->setData('feedId', $feedId);
	}

	/**
	 * Get the journal ID of the external feed.
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}

	/**
	 * Set the journal ID of the external feed.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}

	/**
	 * Get feed URL.
	 * @return int 
	 */
	function getUrl() {
		return $this->getData('url');
	}

	/**
	 * Set feed URL.
	 * @param $url string
	 */
	function setUrl($url) {
		return $this->setData('url', $url);
	}

	/**
	 * Get feed display sequence.
	 * @return float
	 */
	function getSeq() {
		return $this->getData('seq');
	}

	/**
	 * Set feed display sequence
	 * @param $sequence float
	 */
	function setSeq($seq) {
		return $this->setData('seq', $seq);
	}

	/**
	 * Get homepage display of the external feed.
	 * @return int
	 */
	function getDisplayHomepage() {
		return $this->getData('displayHomepage');
	}

	/**
	 * Set the homepage display of the external feed.
	 * @param $displayHomepage int
	 */
	function setDisplayHomepage($displayHomepage) {
		return $this->setData('displayHomepage', $displayHomepage);
	}

	/**
	 * Get block display of the external feed.
	 * @return int
	 */
	function getDisplayBlock() {
		return $this->getData('displayBlock');
	}

	/**
	 * Set the block display of the external feed.
	 * @param $displayBlock int
	 */
	function setDisplayBlock($displayBlock) {
		return $this->setData('displayBlock', $displayBlock);
	}

	/**
	 * Get limit items of the external feed.
	 * @return int
	 */
	function getLimitItems() {
		return $this->getData('limitItems');
	}

	/**
	 * Set limit items of the external feed.
	 * @param $limitItems int
	 */
	function setLimitItems($limitItems) {
		return $this->setData('limitItems', $limitItems);
	}

	/**
	 * Get recent items of the external feed.
	 * @return int
	 */
	function getRecentItems() {
		return $this->getData('recentItems');
	}

	/**
	 * Set recent items of the external feed.
	 * @param $recentItems int
	 */
	function setRecentItems($recentItems) {
		return $this->setData('recentItems', $recentItems);
	}


	/**
	 * Get the localized title
	 * @return string
	 */
	function getLocalizedTitle() {
		return $this->getLocalizedData('title');
	}

	/**
	 * Get feed title
	 * @param $locale string
	 * @return string
	 */
	function getTitle($locale) {
		return $this->getData('title', $locale);
	}

	/**
	 * Set feed title
	 * @param $title string
	 * @param $locale string
	 */
	function setTitle($title, $locale) {
		return $this->setData('title', $title, $locale);
	}
}

?>
