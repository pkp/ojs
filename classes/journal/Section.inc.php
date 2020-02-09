<?php

/**
 * @file classes/journal/Section.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Section
 * @ingroup journal
 * @see SectionDAO
 *
 * @brief Describes basic section properties.
 */

import('lib.pkp.classes.context.PKPSection');

class Section extends PKPSection {

	/**
	 * Get localized abbreviation of journal section.
	 * @return string
	 */
	function getLocalizedAbbrev() {
		return $this->getLocalizedData('abbrev');
	}


	//
	// Get/set methods
	//

	/**
	 * Get ID of journal.
	 * @return int
	 */
	function getJournalId() {
		return $this->getContextId();
	}

	/**
	 * Set ID of journal.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setContextId($journalId);
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
	 * Get "will/will not be indexed" setting of section.
	 * @return boolean
	 */
	function getMetaIndexed() {
		return $this->getData('metaIndexed');
	}

	/**
	 * Set "will/will not be indexed" setting of section.
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
}
