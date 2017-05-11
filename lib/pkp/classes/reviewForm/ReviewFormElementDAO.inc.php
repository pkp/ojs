<?php

/**
 * @file classes/reviewForm/ReviewFormElementDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewFormElementDAO
 * @ingroup reviewForm
 * @see ReviewFormElement
 *
 * @brief Operations for retrieving and modifying ReviewFormElement objects.
 *
 */

import ('lib.pkp.classes.reviewForm.ReviewFormElement');

class ReviewFormElementDAO extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Retrieve a review form element by ID.
	 * @param $reviewFormElementId int Review form element ID
	 * @param $reviewFormId int optional
	 * @return ReviewFormElement
	 */
	function getById($reviewFormElementId, $reviewFormId = null) {
		$params = array((int) $reviewFormElementId);
		if ($reviewFormId) {
			$params[] = (int) $reviewFormId;
		}
		$result = $this->retrieve(
			'SELECT	*
			FROM	review_form_elements
			WHERE	review_form_element_id = ?
			' . ($reviewFormId?' AND review_form_id = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return ReviewFormElement
	 */
	function newDataObject() {
		return new ReviewFormElement();
	}

	/**
	 * Internal function to return a ReviewFormElement object from a row.
	 * @param $row array
	 * @return ReviewFormElement
	 */
	function _fromRow($row) {
		$reviewFormElement = $this->newDataObject();
		$reviewFormElement->setId($row['review_form_element_id']);
		$reviewFormElement->setReviewFormId($row['review_form_id']);
		$reviewFormElement->setSequence($row['seq']);
		$reviewFormElement->setElementType($row['element_type']);
		$reviewFormElement->setRequired($row['required']);
		$reviewFormElement->setIncluded($row['included']);

		$this->getDataObjectSettings('review_form_element_settings', 'review_form_element_id', $row['review_form_element_id'], $reviewFormElement);

		HookRegistry::call('ReviewFormElementDAO::_fromRow', array(&$reviewFormElement, &$row));

		return $reviewFormElement;
	}

	/**
	 * Get the list of fields for which data can be localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('question', 'possibleResponses');
	}

	/**
	 * Update the localized fields for this table
	 * @param $reviewFormElement object
	 */
	function updateLocaleFields($reviewFormElement) {
		$this->updateDataObjectSettings('review_form_element_settings', $reviewFormElement, array(
			'review_form_element_id' => (int) $reviewFormElement->getId()
		));
	}

	/**
	 * Insert a new review form element.
	 * @param $reviewFormElement ReviewFormElement
	 * @return int Review form element ID
	 */
	function insertObject($reviewFormElement) {
		$this->update(
			'INSERT INTO review_form_elements
				(review_form_id, seq, element_type, required, included)
			VALUES
				(?, ?, ?, ?, ?)',
			array(
				(int) $reviewFormElement->getReviewFormId(),
				$reviewFormElement->getSequence() == null ? 0 : (float) $reviewFormElement->getSequence(),
				(int) $reviewFormElement->getElementType(),
				(int) $reviewFormElement->getRequired(),
				(int) $reviewFormElement->getIncluded(),
			)
		);

		$reviewFormElement->setId($this->getInsertId());
		$this->updateLocaleFields($reviewFormElement);
		return $reviewFormElement->getId();
	}

	/**
	 * Update an existing review form element.
	 * @param $reviewFormElement ReviewFormElement
	 */
	function updateObject($reviewFormElement) {
		$returner = $this->update(
			'UPDATE review_form_elements
				SET	review_form_id = ?,
					seq = ?,
					element_type = ?,
					required = ?,
					included = ?
				WHERE	review_form_element_id = ?',
			array(
				(int) $reviewFormElement->getReviewFormId(),
				(float) $reviewFormElement->getSequence(),
				(int) $reviewFormElement->getElementType(),
				(int) $reviewFormElement->getRequired(),
				(int) $reviewFormElement->getIncluded(),
				(int) $reviewFormElement->getId()
			)
		);
		$this->updateLocaleFields($reviewFormElement);
		return $returner;
	}

	/**
	 * Delete a review form element.
	 * @param $reviewFormElement reviewFormElement
	 */
	function deleteObject($reviewFormElement) {
		return $this->deleteById($reviewFormElement->getId());
	}

	/**
	 * Delete a review form element by ID.
	 * @param $reviewFormElementId int
	 */
	function deleteById($reviewFormElementId) {
		$reviewFormResponseDao = DAORegistry::getDAO('ReviewFormResponseDAO');
		$reviewFormResponseDao->deleteByReviewFormElementId($reviewFormElementId);

		$this->update('DELETE FROM review_form_element_settings WHERE review_form_element_id = ?', (int) $reviewFormElementId);
		return $this->update('DELETE FROM review_form_elements WHERE review_form_element_id = ?', (int) $reviewFormElementId);
	}

	/**
	 * Delete review form elements by review form ID
	 * to be called only when deleting a review form.
	 * @param $reviewFormId int
	 */
	function deleteByReviewFormId($reviewFormId) {
		$reviewFormElements = $this->getByReviewFormId($reviewFormId);
		while ($reviewFormElement = $reviewFormElements->next()) {
			$this->deleteById($reviewFormElement->getId());
		}
	}

	/**
	 * Delete a review form element setting
	 * @param $reviewFormElementId int
	 * @param $settingName string
	 * @param $locale string
	 */
	function deleteSetting($reviewFormElementId, $name, $locale = null) {
		$params = array((int) $reviewFormElementId, $name);
		if ($locale) $params[] = $locale;

		$this->update(
			'DELETE FROM review_form_element_settings
			WHERE review_form_element_id = ? AND setting_name = ?
			' . ($locale?' AND locale = ?':''),
			$params
		);
	}

	/**
	 * Retrieve all elements for a review form.
	 * @param $reviewFormId int
	 * @param $rangeInfo object RangeInfo object (optional)
	 * @return DAOResultFactory containing ReviewFormElements ordered by sequence
	 */
	function getByReviewFormId($reviewFormId, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT * FROM review_form_elements WHERE review_form_id = ? ORDER BY seq',
			(int) $reviewFormId, $rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Retrieve ids of all required elements for a review form.
	 * @param $reviewFormId int
	 * return array
	 */
	function getRequiredReviewFormElementIds($reviewFormId) {
		$result = $this->retrieve(
			'SELECT review_form_element_id FROM review_form_elements WHERE review_form_id = ? AND required = 1 ORDER BY seq',
			$reviewFormId
		);

		$requiredReviewFormElementIds = array();
		while (!$result->EOF) {
			$requiredReviewFormElementIds[] = $result->fields[0];
			$result->MoveNext();
		}

		$result->Close();
		return $requiredReviewFormElementIds;
	}

	/**
	 * Check if a review form element exists with the specified ID.
	 * @param $reviewFormElementId int
	 * @param $reviewFormId int optional
	 * @return boolean
	 */
	function reviewFormElementExists($reviewFormElementId, $reviewFormId = null) {
		$params = array((int) $reviewFormElementId);
		if ($reviewFormId) $params[] = (int) $reviewFormId;

		$result = $this->retrieve(
			'SELECT	COUNT(*)
			FROM	review_form_elements
			WHERE	review_form_element_id = ?
				' . ($reviewFormId?' AND review_form_id = ?':''),
			$params
		);

		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		return $returner;
	}

	/**
	 * Sequentially renumber a review form elements in their sequence order.
	 * @param $reviewFormId int
	 */
	function resequenceReviewFormElements($reviewFormId) {
		$result = $this->retrieve(
			'SELECT review_form_element_id FROM review_form_elements WHERE review_form_id = ? ORDER BY seq',
			(int) $reviewFormId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($reviewFormElementId) = $result->fields;
			$this->update(
				'UPDATE review_form_elements SET seq = ? WHERE review_form_element_id = ?',
				array(
					$i,
					$reviewFormElementId
				)
			);

			$result->MoveNext();
		}
		$result->Close();
	}

	/**
	 * Get the ID of the last inserted review form element.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('review_form_elements', 'review_form_element_id');
	}
}

?>
