<?php

/**
 * @file ReviewForm.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package reivewForm
 * @class ReviewForm
 *
 * ReviewForm class.
 * Basic class describing a review form.
 *
 */

class ReviewForm extends DataObject {

	/**
	 * Constructor.
	 */
	function ReviewForm() {
		parent::DataObject();
	}

	/**
	 * Get localized title.
	 * @return string
	 */
	function getReviewFormTitle() {
		return $this->getLocalizedData('title');
	}

	/**
	 * Get localized description.
	 * @return array
	 */
	function getReviewFormDescription() {
		return $this->getLocalizedData('description');
	}

	//
	// Get/set methods
	//

	/**
	 * Get the ID of the review form.
	 * @return int
	 */
	function getReviewFormId() {
		return $this->getData('reviewFormId');
	}

	/**
	 * Set the ID of the review form.
	 * @param $reviewFormId int
	 */
	function setReviewFormId($reviewFormId) {
		return $this->setData('reviewFormId', $reviewFormId);
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
	 * Get sequence of review form.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set sequence of review form.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}

	/**
	 * get published
	 * @return int
	 */
	function getPublished() {
		return $this->getData('published');
	}

	/**
	 * set published
	 * @param $published int
	 */
	function setPublished($published) {
		return $this->setData('published', $published);
	}

	/**
	 * Get active flag
	 * @return int
	 */
	function getActive() {
		return $this->getData('active');
	}

	/**
	 * Set active flag
	 * @param $active int
	 */
	function setActive($active) {
		return $this->setData('active', $active);
	}

	/**
	 * Get title.
	 * @param $locale string
	 * @return string
	 */
	function getTitle($locale) {
		return $this->getData('title', $locale);
	}

	/**
	 * Set title.
	 * @param $title string
	 * @param $locale string
	 */
	function setTitle($title, $locale) {
		return $this->setData('title', $title, $locale);
	}

	/**
	 * Get description.
	 * @param $locale string
	 * @return string
	 */
	function getDescription($locale) {
		return $this->getData('description', $locale);
	}

	/**
	 * Set description.
	 * @param $description string
	 * @param $locale string
	 */
	function setDescription($description, $locale) {
		return $this->setData('description', $description, $locale);
	}
}

?>
