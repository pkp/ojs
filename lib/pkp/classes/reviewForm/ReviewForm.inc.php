<?php

/**
 * @defgroup reviewForm Review Form
 * Implements review forms, which are forms that can be created and customized
 * by the manager and presented to the reviewer in order to assess submissions.
 */

/**
 * @file classes/reviewForm/ReviewForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
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
	function __construct() {
		parent::__construct();
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
	 * @return string
	 */
	function getLocalizedDescription() {
		return $this->getLocalizedData('description');
	}

	//
	// Get/set methods
	//

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
		$this->setData('completeCount', $completeCount);
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
		$this->setData('incompleteCount', $incompleteCount);
	}

	/**
	 * Get the associated type.
	 * @return int
	 */
	function getAssocType() {
		return $this->getData('assocType');
	}

	/**
	 * Set the associated type.
	 * @param $assocType int
	 */
	function setAssocType($assocType) {
		$this->setData('assocType', $assocType);
	}

	/**
	 * Get the Id of the associated type.
	 * @return int
	 */
	function getAssocId() {
		return $this->getData('assocId');
	}

	/**
	 * Set the Id of the associated type.
	 * @param $assocId int
	 */
	function setAssocId($assocId) {
		$this->setData('assocId', $assocId);
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
		$this->setData('sequence', $sequence);
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
		$this->setData('active', $active);
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
		$this->setData('title', $title, $locale);
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
		$this->setData('description', $description, $locale);
	}
}

?>
