<?php

/**
 * @defgroup reviewForm
 */

/**
 * @file classes/reviewForm/ReviewForm.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewForm
 * @ingroup reviewForm
 * @see ReviewerFormDAO
 *
 * @brief Basic class describing a review form.
 *
 */

class ReviewForm extends DataObject {

	/**
	 * Constructor.
	 */
	function ReviewForm() {
		parent::DataObject();
	}

	function getReviewFormTitle() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedTitle();
	}

	/**
	 * Get localized title.
	 * @return string
	 */
	function getLocalizedTitle() {
		return $this->getLocalizedData('title');
	}

	/**
	 * Get localized description.
	 * @return array
	 */
	function getLocalizedDescription() {
		return $this->getLocalizedData('description');
	}

	function getReviewFormDescription() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedDescription();
	}

	//
	// Get/set methods
	//

	/**
	 * Get the ID of the review form.
	 * @return int
	 */
	function getReviewFormId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getId();
	}

	/**
	 * Set the ID of the review form.
	 * @param $reviewFormId int
	 */
	function setReviewFormId($reviewFormId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setId($reviewFormId);
	}

	/**
	 * Get the number of completed reviews for this review form.
	 * @return int
	 */
	function getCompleteCount() {
		return $this->getData('completeCount');
	}

	/**
	 * Set the number of complete reviews for this review form.
	 * @param $completeCount int
	 */
	function setCompleteCount($completeCount) {
		return $this->setData('completeCount', $completeCount);
	}

	/**
	 * Get the number of incomplete reviews for this review form.
	 * @return int
	 */
	function getIncompleteCount() {
		return $this->getData('incompleteCount');
	}

	/**
	 * Set the number of incomplete reviews for this review form.
	 * @param $incompleteCount int
	 */
	function setIncompleteCount($incompleteCount) {
		return $this->setData('incompleteCount', $incompleteCount);
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
