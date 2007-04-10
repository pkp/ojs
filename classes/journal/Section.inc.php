<?php

/**
 * Section.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
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

	/**
	 * Get localized title of journal section.
	 */
	function getSectionTitle() {
		$alternateLocaleNum = Locale::isAlternateJournalLocale($this->getJournalId());
		$title = null;
		switch ($alternateLocaleNum) {
			case 1: $title = $this->getTitleAlt1(); break;
			case 2: $title = $this->getTitleAlt2(); break;
		}
		// Fall back on the primary locale title.
		if (empty($title)) $title = $this->getTitle();

		return $title;
	}

	/**
	 * Get localized abbreviation of journal section.
	 */
	function getSectionAbbrev() {
		$alternateLocaleNum = Locale::isAlternateJournalLocale($this->getJournalId());
		$abbrev = null;
		switch ($alternateLocaleNum) {
			case 1: $abbrev = $this->getAbbrevAlt1(); break;
			case 2: $abbrev = $this->getAbbrevAlt2(); break;
		}
		// Fall back on the primary locale title.
		if (empty($abbrev)) $abbrev = $this->getAbbrev();

		return $abbrev;
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
	 * Get title of section (alternate locale 1).
	 * @return string
	 */
	function getTitleAlt1() {
		return $this->getData('titleAlt1');
	}
	
	/**
	 * Set title of section (alternate locale 1).
	 * @param $titleAlt1 string
	 */
	function setTitleAlt1($titleAlt1) {
		return $this->setData('titleAlt1', $titleAlt1);
	}
	
	/**
	 * Get title of section (alternate locale 2).
	 * @return string
	 */
	function getTitleAlt2() {
		return $this->getData('titleAlt2');
	}
	
	/**
	 * Set title of section (alternate locale 2).
	 * @param $titleAlt2 string
	 */
	function setTitleAlt2($titleAlt2) {
		return $this->setData('titleAlt2', $titleAlt2);
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
	 * Get section title abbreviation (alternate locale 1).
	 * @return string
	 */
	function getAbbrevAlt1() {
		return $this->getData('abbrevAlt1');
	}
	
	/**
	 * Set section title abbreviation (alternate locale 1).
	 * @param $abbrevAlt1 string
	 */
	function setAbbrevAlt1($abbrevAlt1) {
		return $this->setData('abbrevAlt1', $abbrevAlt1);
	}
	
	/**
	 * Get section title abbreviation (alternate locale 2).
	 * @return string
	 */
	function getAbbrevAlt2() {
		return $this->getData('abbrevAlt2');
	}
	
	/**
	 * Set section title abbreviation (alternate locale 2).
	 * @param $abbrevAlt2 string
	 */
	function setAbbrevAlt2($abbrevAlt2) {
		return $this->setData('abbrevAlt2', $abbrevAlt2);
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
	 * @return boolean
	 */
	function getMetaIndexed() {
		return $this->getData('metaIndexed');
	}
	
	/**
	 * Set open archive setting of section.
	 * @param $metaIndexed boolean
	 */
	function setMetaIndexed($metaIndexed) {
		return $this->setData('metaIndexed', $metaIndexed);
	}
	
	/**
	 * Get peer-reviewed setting of section.
	 * @return boolean
	 */
	function getMetaReviewed() {
		return $this->getData('metaReviewed');
	}
	
	/**
	 * Set peer-reviewed setting of section.
	 * @param $metaReviewed boolean
	 */
	function setMetaReviewed($metaReviewed) {
		return $this->setData('metaReviewed', $metaReviewed);
	}
	
	/**
	 * Get boolean indicating whether abstracts are disabled
	 * @return boolean
	 */
	function getAbstractsDisabled() {
		return $this->getData('abstractsDisabled');
	}
	
	/**
	 * Set boolean indicating whether abstracts are disabled
	 * @param $abstractsDisabled boolean
	 */
	function setAbstractsDisabled($abstractsDisabled) {
		return $this->setData('abstractsDisabled', $abstractsDisabled);
	}
	
	/**
	 * Get string identifying type of items in this section.
	 * @return string
	 */
	function getIdentifyType() {
		return $this->getData('identifyType');
	}
	
	/**
	 * Set string identifying type of items in this section.
	 * @param $identifyType string
	 */
	function setIdentifyType($identifyType) {
		return $this->setData('identifyType', $identifyType);
	}
	
	/**
	 * Return boolean indicating whether or not submissions are restricted to [section]Editors.
	 * @return boolean
	 */
	function getEditorRestricted() {
		return $this->getData('editorRestricted');
	}
	
	/**
	 * Set whether or not submissions are restricted to [section]Editors.
	 * @param $editorRestricted boolean
	 */
	function setEditorRestricted($editorRestricted) {
		return $this->setData('editorRestricted', $editorRestricted);
	}
	
	/**
	 * Return boolean indicating if title should be hidden in issue ToC.
	 * @return boolean
	 */
	function getHideTitle() {
		return $this->getData('hideTitle');
	}
	
	/**
	 * Set if title should be hidden in issue ToC.
	 * @param $hideTitle boolean
	 */
	function setHideTitle($hideTitle) {
		return $this->setData('hideTitle', $hideTitle);
	}
	
	/**
	 * Return boolean indicating if title should be hidden in About.
	 * @return boolean
	 */
	function getHideAbout() {
		return $this->getData('hideAbout');
	}
	
	/**
	 * Set if title should be hidden in About.
	 * @param $hideAbout boolean
	 */
	function setHideAbout($hideAbout) {
		return $this->setData('hideAbout', $hideAbout);
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
