<?php

/**
 * @file plugins/generic/objectsForReview/classes/ObjectForReviewAssignment.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ObjectForReviewAssignment
 * @ingroup plugins_generic_objectsForReview
 * @see ObjectForReviewAssignmentDAO
 *
 * @brief Basic class describing an object for review assignment.
 */

define('OFR_STATUS_AVAILABLE',	0x01);
define('OFR_STATUS_REQUESTED',	0x02);
define('OFR_STATUS_ASSIGNED',	0x03);
define('OFR_STATUS_MAILED',		0x04);
define('OFR_STATUS_SUBMITTED',	0x05);


class ObjectForReviewAssignment extends DataObject {
	/**
	 * Constructor
	 */
	function ObjectForReviewAssignment() {
		parent::DataObject();
	}

	//
	// Get/set methods
	//
	/**
	 * get object id
	 * @return int
	 */
	function getObjectId() {
		return $this->getData('objectId');
	}

	/**
	 * set object id
	 * @param $objectId int
	 */
	function setObjectId($objectId) {
		return $this->setData('objectId', $objectId);
	}

	/**
	 * Get the associated object for review.
	 * @return ObjectForReview
	 */
	function &getObjectForReview() {
		$ofrDao =& DAORegistry::getDAO('ObjectForReviewDAO');
		return $ofrDao->getById($this->getData('objectId'));
	}

	/**
	 * Get user ID for this assignment.
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}

	/**
	 * Set user ID for this assignment.
	 * @param $userId int
	 */
	function setUserId($userId) {
		return $this->setData('userId', $userId);
	}

	/**
	 * Get the user assigned to the object for review.
	 * @return User
	 */
	function &getUser() {
		$userDao =& DAORegistry::getDAO('UserDAO');
		return $userDao->getById($this->getData('userId'));
	}

	/**
	 * Get submission ID for this assignment.
	 * @return int
	 */
	function getSubmissionId() {
		return $this->getData('submissionId');
	}

	/**
	 * Set submission ID for this assignment.
	 * @param $submissionId int
	 */
	function setSubmissionId($submissionId) {
		return $this->setData('submissionId', $submissionId);
	}

	/**
	 * Get the article.
	 * @return Article
	 */
	function &getArticle() {
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		return $articleDao->getArticle($this->getSubmissionId());
	}

	/**
	 * Get date requested.
	 * @return string
	 */
	function getDateRequested() {
		return $this->getData('dateRequested');
	}

	/**
	 * Set date requested.
	 * @param $dateRequested string
	 */
	function setDateRequested($dateRequested) {
		return $this->setData('dateRequested', $dateRequested);
	}

	/**
	 * Get date assigned.
	 * @return string
	 */
	function getDateAssigned() {
		return $this->getData('dateAssigned');
	}

	/**
	 * Set date assigned.
	 * @param $dateAssigned string
	 */
	function setDateAssigned($dateAssigned) {
		return $this->setData('dateAssigned', $dateAssigned);
	}


	/**
	 * Get date mailed.
	 * @return string
	 */
	function getDateMailed() {
		return $this->getData('dateMailed');
	}

	/**
	 * Set date mailed.
	 * @param $dateMailed string
	 */
	function setDateMailed($dateMailed) {
		return $this->setData('dateMailed', $dateMailed);
	}

	/**
	 * Get date due.
	 * @return string
	 */
	function getDateDue() {
		return $this->getData('dateDue');
	}

	/**
	 * Set date due.
	 * @param $dateDue string
	 */
	function setDateDue($dateDue) {
		return $this->setData('dateDue', $dateDue);
	}

	/**
	 * Check whether the review has past due date
	 * @return boolean
	 */
	function isLate() {
		$dateDue = $this->getData('dateDue');
		if (!empty($dateDue)) {
			if (strtotime($dateDue) > time()) {
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}

	/**
	 * Get date reminded, before the due date.
	 * @return string
	 */
	function getDateRemindedBefore() {
		return $this->getData('dateRemindedBefore');
	}

	/**
	 * Set date reminded, before the due date.
	 * @param $dateRemindedBefore string
	 */
	function setDateRemindedBefore($dateRemindedBefore) {
		return $this->setData('dateRemindedBefore', $dateRemindedBefore);
	}

	/**
	 * Get date reminded, after the due date.
	 * @return string
	 */
	function getDateRemindedAfter() {
		return $this->getData('dateRemindedAfter');
	}

	/**
	 * Set date reminded, after the due date.
	 * @param $dateRemindedAfter string
	 */
	function setDateRemindedAfter($dateRemindedAfter) {
		return $this->setData('dateRemindedAfter', $dateRemindedAfter);
	}

	/**
	 * Get status of the object for review assignment.
	 * @return int OFR_STATUS_...
	 */
	function getStatus() {
		return $this->getData('status');
	}

	/**
	 * Set status of the object for review assignment.
	 * @param $status int OFR_STATUS_...
	 */
	function setStatus($status) {
		return $this->setData('status', $status);
	}

	/**
	 * Get object for review assignment status locale key.
	 * @return string
	 */
	function getStatusString() {
		switch ($this->getData('status')) {
			case OFR_STATUS_AVAILABLE:
				return 'plugins.generic.objectsForReview.objectForReviewAssignment.status.available';
			case OFR_STATUS_REQUESTED:
				return 'plugins.generic.objectsForReview.objectForReviewAssignment.status.requested';
			case OFR_STATUS_ASSIGNED:
				return 'plugins.generic.objectsForReview.objectForReviewAssignment.status.assigned';
			case OFR_STATUS_MAILED:
				return 'plugins.generic.objectsForReview.objectForReviewAssignment.status.mailed';
			case OFR_STATUS_SUBMITTED:
				return 'plugins.generic.objectsForReview.objectForReviewAssignment.status.submitted';
			default:
				return 'plugins.generic.objectsForReview.objectForReviewAssignment.status';
		}
	}

	/**
	 * Get notes for the assignment.
	 * @return string
	 */
	function getNotes() {
		return $this->getData('notes');
	}

	/**
	 * Set notes for the assignment.
	 * @param $notes string
	 */
	function setNotes($notes) {
		return $this->setData('notes', $notes);
	}

}

?>
