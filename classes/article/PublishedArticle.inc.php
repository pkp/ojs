<?php

/**
 * PublishedArticle.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article
 *
 * Published article class.
 *
 * $Id$
 */

class PublishedArticle extends Article {

	/**
	 * Constructor.
	 */
	function PublishedArticle() {
		parent::Article();
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get ID of published article.
	 * @return int
	 */
	function getPubId() {
		return $this->getData('pubId');
	}
	
	/**
	 * Set ID of published article.
	 * @param $pubId int
	 */
	function setPubId($pubId) {
		return $this->setData('pubId', $pubId);
	}
	
	/**
	 * Get ID of the issue this article is in.
	 * @return int
	 */
	function getIssueId() {
		return $this->getData('issueId');
	}
	
	/**
	 * Set ID of the issue this article is in.
	 * @param $issueId int
	 */
	function setIssueId($issueId) {
		return $this->setData('issueId', $issueId);
	}
	
	/**
	 * Get sequence of article in table of contents.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}
	
	/**
	 * Set sequence of article in table of contents.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}
	
}

?>
