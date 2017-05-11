<?php

/**
 * @file classes/query/Query.inc.php
 *
 * Copyright (c) 2016-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Query
 * @ingroup submission
 * @see QueryDAO
 *
 * @brief Class for Query.
 */

import('lib.pkp.classes.note.NoteDAO'); // Constants

class Query extends DataObject {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Get query assoc type
	 * @return int ASSOC_TYPE_...
	 */
	function getAssocType() {
		return $this->getData('assocType');
	}

	/**
	 * Set query assoc type
	 * @param $assocType int ASSOC_TYPE_...
	 */
	function setAssocType($assocType) {
		$this->setData('assocType', $assocType);
	}

	/**
	 * Get query assoc ID
	 * @return int
	 */
	function getAssocId() {
		return $this->getData('assocId');
	}

	/**
	 * Set query assoc ID
	 * @param $assocId int
	 */
	function setAssocId($assocId) {
		$this->setData('assocId', $assocId);
	}

	/**
	 * Get stage ID
	 * @return int
	 */
	function getStageId() {
		return $this->getData('stageId');
	}

	/**
	 * Set stage ID
	 * @param $stageId int
	 */
	function setStageId($stageId) {
		return $this->setData('stageId', $stageId);
	}

	/**
	 * Get sequence of query.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set sequence of query.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		$this->setData('sequence', $sequence);
	}

	/**
	 * Get closed flag
	 * @return boolean
	 */
	function getIsClosed() {
		return $this->getData('closed');
	}

	/**
	 * Set closed flag
	 * @param $isClosed boolean
	 */
	function setIsClosed($isClosed) {
		return $this->setData('closed', $isClosed);
	}

	/**
	 * Get the "head" (first) note for this query.
	 * @return Note
	 */
	function getHeadNote() {
		$notes = $this->getReplies(null, NOTE_ORDER_DATE_CREATED, SORT_DIRECTION_ASC, true);
		$note = $notes->next();
		$notes->close();
		return $note;
	}

	/**
	 * Get all notes on a query.
	 * @param $userId int Optional user ID
	 * @param $sortBy int Optional NOTE_ORDER_...
	 * @param $sortOrder int Optional SORT_DIRECTION_...
	 * @param $isAdmin bool Optional user sees all
	 * @return DAOResultFactory
	 */
	function getReplies($userId = null, $sortBy = NOTE_ORDER_ID, $sortOrder = SORT_DIRECTION_ASC, $isAdmin = false) {
		$noteDao = DAORegistry::getDAO('NoteDAO');
		return $noteDao->getByAssoc(ASSOC_TYPE_QUERY, $this->getId(), null, $sortBy, $sortOrder, $isAdmin);
	}
}

?>
