<?php

/**
 * @file classes/journal/Section.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Section
 * @ingroup journal
 * @see SectionDAO
 *
 * @brief Describes basic section properties.
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
	 * @return string
	 */
	function getLocalizedTitle() {
		return $this->getLocalizedData('title');
	}

	function getSectionTitle() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedTitle();
	}

	/**
	 * Get localized abbreviation of journal section.
	 * @return string
	 */
	function getLocalizedAbbrev() {
		return $this->getLocalizedData('abbrev');
	}

	function getTrackAbbrev() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedAbbrev();
	}

	//
	// Get/set methods
	//

	/**
	 * Get ID of section.
	 * @return int
	 */
	function getSectionId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getId();
	}

	/**
	 * Set ID of section.
	 * @param $sectionId int
	 */
	function setSectionId($sectionId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setId($sectionId);
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
	 * Get ID of primary review form.
	 * @return int
	 */
	function getReviewFormId() {
		return $this->getData('reviewFormId');
	}

	/**
	 * Set ID of primary review form.
	 * @param $reviewFormId int
	 */
	function setReviewFormId($reviewFormId) {
		return $this->setData('reviewFormId', $reviewFormId);
	}

	/**
	 * Get title of section.
	 * @param $locale string
	 * @return string
	 */
	function getTitle($locale) {
		return $this->getData('title', $locale);
	}

	/**
	 * Set title of section.
	 * @param $title string
	 * @param $locale string
	 */
	function setTitle($title, $locale) {
		return $this->setData('title', $title, $locale);
	}

	/**
	 * Get section title abbreviation.
	 * @param $locale string
	 * @return string
	 */
	function getAbbrev($locale) {
		return $this->getData('abbrev', $locale);
	}

	/**
	 * Set section title abbreviation.
	 * @param $abbrev string
	 * @param $locale string
	 */
	function setAbbrev($abbrev, $locale) {
		return $this->setData('abbrev', $abbrev, $locale);
	}

	/**
	 * Get abstract word count limit.
	 * @return int
	 */
	function getAbstractWordCount() {
		return $this->getData('wordCount');
	}

	/**
	 * Set abstract word count limit.
	 * @param $wordCount int
	 */
	function setAbstractWordCount($wordCount) {
		return $this->setData('wordCount', $wordCount);
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
	 * Get boolean indicating whether abstracts are required
	 * @return boolean
	 */
	function getAbstractsNotRequired() {
		return $this->getData('abstractsNotRequired');
	}

	/**
	 * Set boolean indicating whether abstracts are required
	 * @param $abstractsNotRequired boolean
	 */
	function setAbstractsNotRequired($abstractsNotRequired) {
		return $this->setData('abstractsNotRequired', $abstractsNotRequired);
	}

	/**
	 * Get localized string identifying type of items in this section.
	 * @return string
	 */
	function getLocalizedIdentifyType() {
		return $this->getLocalizedData('identifyType');
	}

	function getSectionIdentifyType() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedIdentifyType();
	}

	/**
	 * Get string identifying type of items in this section.
	 * @param $locale string
	 * @return string
	 */
	function getIdentifyType($locale) {
		return $this->getData('identifyType', $locale);
	}

	/**
	 * Set string identifying type of items in this section.
	 * @param $identifyType string
	 * @param $locale string
	 */
	function setIdentifyType($identifyType, $locale) {
		return $this->setData('identifyType', $identifyType, $locale);
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
	 * Return boolean indicating if author should be hidden in issue ToC.
	 * @return boolean
	 */
	function getHideAuthor() {
		return $this->getData('hideAuthor');
	}

	/**
	 * Set if author should be hidden in issue ToC.
	 * @param $hideAuthor boolean
	 */
	function setHideAuthor($hideAuthor) {
		return $this->setData('hideAuthor', $hideAuthor);
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
	 * Return boolean indicating if RT comments should be disabled.
	 * @return boolean
	 */
	function getDisableComments() {
		return $this->getData('disableComments');
	}

	/**
	 * Set if RT comments should be disabled.
	 * @param $disableComments boolean
	 */
	function setDisableComments($disableComments) {
		return $this->setData('disableComments', $disableComments);
	}

	/**
	 * Get localized section policy.
	 * @return string
	 */
	function getLocalizedPolicy() {
		return $this->getLocalizedData('policy');
	}

	function getSectionPolicy() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedPolicy();
	}

	/**
	 * Get policy.
	 * @param $locale string
	 * @return string
	 */
	function getPolicy($locale) {
		return $this->getData('policy', $locale);
	}

	/**
	 * Set policy.
	 * @param $policy string
	 * @param $locale string
	 */
	function setPolicy($policy, $locale) {
		return $this->setData('policy', $policy, $locale);
	}
}

?>
