<?php

/**
 * @file classes/reviewForm/ReviewFormResponse.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewFormResponse
 * @ingroup reviewForm
 * @see ReviewFormResponseDAO
 *
 * @brief Basic class describing a review form response.
 *
 */

class ReviewFormResponse extends DataObject {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Get/set methods
	//

	/**
	 * Get the review ID.
	 * @return int
	 */
	function getReviewId() {
		return $this->getData('reviewId');
	}

	/**
	 * Set the review ID.
	 * @param $reviewId int
	 */
	function setReviewId($reviewId) {
		$this->setData('reviewId', $reviewId);
	}

	/**
	 * Get ID of review form element.
	 * @return int
	 */
	function getReviewFormElementId() {
		return $this->getData('reviewFormElementId');
	}

	/**
	 * Set ID of review form element.
	 * @param $reviewFormElementId int
	 */
	function setReviewFormElementId($reviewFormElementId) {
		$this->setData('reviewFormElementId', $reviewFormElementId);
	}

	/**
	 * Get response value.
	 * @return int
	 */
	function getValue() {
		return $this->getData('value');
	}

	/**
	 * Set response value.
	 * @param $value int
	 */
	function setValue($value) {
		$this->setData('value', $value);
	}

	/**
	 * Get response type.
	 * @return string
	 */
	function getResponseType() {
		return $this->getData('type');
	}

	/**
	 * Set response type.
	 * @param $type string
	 */
	function setResponseType($type) {
		$this->setData('type', $type);
	}
}

?>
