<?php

/**
 * Section.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package journal
 *
 * Section class.
 * Describes basic section properties.
 *
 * $Id$
 */

class Section extends DataObject {

	/**
	 * Constructor.
	 */
	function Section() {
		parent::DataObject();
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get ID of section.
	 * @return int
	 */
	function getSectionId() {
		return $this->getData('sectionId');
	}
	
	/**
	 * Set ID of section.
	 * @param $sectionId int
	 */
	function setSectionId($sectionId) {
		return $this->setData('sectionId', $sectionId);
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
	 * Get title of section.
	 * @return string
	 */
	function getTitle() {
		return $this->getData('title');
	}
	
	/**
	 * Set title of section.
	 * @param $title string
	 */
	function setTitle($title) {
		return $this->setData('title', $title);
	}
	
	/**
	 * Get section title abbreviation.
	 * @return string
	 */
	function getAbbrev() {
		return $this->getData('abbrev');
	}
	
	/**
	 * Set section title abbreviation.
	 * @param $abbrev string
	 */
	function setAbbrev($abbrev) {
		return $this->setData('abbrev', $abbrev);
	}
	
	/**
	 * Get sequence of section.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}
	
	/**
	 * Set sequence of section.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}
	
	/**
	 * Get open archive setting of section.
	 * @return float
	 */
	function getMetaIndexed() {
		return $this->getData('metaIndexed');
	}
	
	/**
	 * Set open archive setting of section.
	 * @param $openArchive float
	 */
	function setMetaIndexed($metaIndexed) {
		return $this->setData('metaIndexed', $metaIndexed);
	}
	
	/**
	 * Get author index setting of section.
	 * @return float
	 */
	function getAuthorIndexed() {
		return $this->getData('authorIndexed');
	}
	
	/**
	 * Set author index setting of section.
	 * @param $authorIndex float
	 */
	function setAuthorIndexed($authorIndexed) {
		return $this->setData('authorIndexed', $authorIndexed);
	}
	
	/**
	 * Get research tool setting of section.
	 * @return float
	 */
	function getRST() {
		return $this->getData('rst');
	}
	
	/**
	 * Set research tool setting of section.
	 * @param $researchTool float
	 */
	function setRST($rst) {
		return $this->setData('rst', $rst);
	}
	
	/**
	 * Get policy.
	 * @return string
	 */
	function getPolicy() {
		return $this->getData('policy');
	}
	
	/**
	 * Set policy.
	 * @param $policy string
	 */
	function setPolicy($policy) {
		return $this->setData('policy', $policy);
	}
	
}

?>
