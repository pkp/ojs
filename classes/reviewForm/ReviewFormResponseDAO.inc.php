<?php

/**
 * @file classes/reviewForm/ReviewFormResponseDAO.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewFormResponseDAO
 * @ingroup reviewForm
 * @see ReviewFormResponse
 *
 * @brief Operations for retrieving and modifying ReviewFormResponse objects.
 *
 */

import ('reviewForm.ReviewFormResponse');

class ReviewFormResponseDAO extends DAO {

	/**
	 * Constructor.
	 */
	function ReviewFormResponseDAO() {
		parent::DAO();
	}

	/**
	 * Retrieve a review form response.
	 * @param $reviewId int
	 * @param $reviewFormElementId int
	 * @return ReviewFormResponse
	 */
	function &getReviewFormResponse($reviewId, $reviewFormElementId) {
		$sql = 'SELECT * FROM review_form_responses WHERE review_id = ? AND review_form_element_id = ?';
		$params = array($reviewId, $reviewFormElementId);
		$result =& $this->retrieve($sql, $params);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnReviewFormResponseFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a ReviewFormResponse object from a row.
	 * @param $row array
	 * @return ReviewFormResponse
	 */
	function &_returnReviewFormResponseFromRow(&$row) {
		$responseValue = $this->convertFromDB($row['response_value'], $row['response_type']);
		$reviewFormResponse =& new ReviewFormResponse();
		$reviewFormResponse->setReviewId($row['review_id']);
		$reviewFormResponse->setReviewFormElementId($row['review_form_element_id']);
		$reviewFormResponse->setValue($responseValue);
		$reviewFormResponse->setResponseType($row['response_type']);

		HookRegistry::call('ReviewFormResponseDAO::_returnReviewFormResponseFromRow', array(&$reviewFormResponse, &$row));

		return $reviewFormResponse;
	}

	/**
	 * Insert a new review form response.
	 * @param $reviewFormResponse ReviewFormResponse
	 */
	function insertReviewFormResponse(&$reviewFormResponse) {
		$responseValue = $this->convertToDB($reviewFormResponse->getValue(), $reviewFormResponse->getResponseType());
		$this->update(
			'INSERT INTO review_form_responses
				(review_form_element_id, review_id, response_type, response_value)
				VALUES
				(?, ?, ?, ?)',
			array(
				$reviewFormResponse->getReviewFormElementId(),
				$reviewFormResponse->getReviewId(),
				$reviewFormResponse->getResponseType(),
				$responseValue
			)
		);
	}

	/**
	 * Update an existing review form response.
	 * @param $reviewFormResponse ReviewFormResponse
	 */
	function updateReviewFormResponse(&$reviewFormResponse) {
		$responseValue = $this->convertToDB($reviewFormResponse->getValue(), $reviewFormResponse->getResponseType());
		$returner = $this->update(
			'UPDATE review_form_responses
				SET
					response_type = ?,
					response_value = ?
				WHERE review_form_element_id = ? AND review_id = ?',
			array(
				$reviewFormResponse->getResponseType(),
				$responseValue,
				$reviewFormResponse->getReviewFormElementId(),
				$reviewFormResponse->getReviewId()
			)
		);

		return $returner;
	}

	/**
	 * Delete a review form response.
	 * @param $reviewFormResponse ReviewFormResponse
	 */
	function deleteReviewFormResponse(&$reviewFormResponse) {
		return $this->deleteReviewFormResponseById($reviewFormResponse->getReviewId(), $reviewFormResponse->getReviewFormElementId());
	}

	/**
	 * Delete a review form response by ID.
	 * @param $reviewId int
	 * @param $reviewFormElementId int
	 */
	function deleteReviewFormResponseById($reviewId, $reviewFormElementId) {
		return $this->update(
			'DELETE FROM review_form_responses WHERE review_id = ? AND review_form_element_id = ?',
			array($reviewId, $reviewFormElementId)
		);
	}

	/**
	 * Delete review form response by review ID
	 * @param $reviewId int
	 */
	function deleteReviewFormResponseByReviewId($reviewId) {
		return $this->update(
			'DELETE FROM review_form_responses WHERE review_id = ?',
			$reviewId
		);
	}

	/**
	 * Delete group membership by user ID
	 * @param $reviewFormElementId int
	 */
	function deleteReviewFormResponseByReviewFormElementId($reviewFormElementId) {
		return $this->update(
			'DELETE FROM review_form_responses WHERE review_form_element_id = ?',
			$reviewFormElementId
		);
	}

	/**
	 * Retrieve all review form responses for a review in an associative array.
	 * @param $reviewId int
	 * @return array review_form_element_id => array(review form response for this element)
	 */
	function &getReviewReviewFormResponseValues($reviewId) {
		$returner = array();

		$result =& $this->retrieveRange(
			'SELECT * FROM review_form_responses WHERE review_id = ?',
			$reviewId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$reviewFormResponse =& $this->_returnReviewFormResponseFromRow($row);
			$returner[$reviewFormResponse->getReviewFormElementId()] = $reviewFormResponse->getValue();
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if a review form response for the review.
	 * @param $reviewId int
	 * @param $reviewFormElementId int optional
	 * @return boolean
	 */
	function reviewFormResponseExists($reviewId, $reviewFormElementId = null) {
		$sql = 'SELECT COUNT(*) FROM review_form_responses WHERE review_id = ?';
		$params = array($reviewId);
		if ($reviewFormElementId !== null) {
			$sql .= ' AND review_form_element_id = ?';
			$params[] = $reviewFormElementId;
		}
		$result =& $this->retrieve($sql, $params);

		$returner = isset($result->fields[0]) && $result->fields[0] > 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}
}

?>
