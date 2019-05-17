<?php

/**
 * @file classes/submission/reviewer/ReviewerSubmission.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerSubmission
 * @ingroup submission
 * @see ReviewerSubmissionDAO
 *
 * @brief ReviewerSubmission class.
 */

import('classes.article.Article');

class ReviewerSubmission extends Article {

	/** @var array SubmissionFile reviewer file revisions of this submission */
	var $reviewerFileRevisions;

	/** @var array SubmissionComments peer review comments of this submission */
	var $peerReviewComments;

	/** @var array the editor decisions of this submission */
	var $editorDecisions;


	/**
	 * Get/Set Methods.
	 */

	/**
	 * Get the competing interests for this submission.
	 * @return string
	 */
	function getCompetingInterests() {
		return $this->getData('competingInterests');
	}

	/**
	 * Set the competing interests statement.
	 * @param $competingInterests string
	 */
	function setCompetingInterests($competingInterests) {
		$this->setData('competingInterests', $competingInterests);
	}

	/**
	 * Get ID of review assignment.
	 * @return int
	 */
	function getReviewId() {
		return $this->getData('reviewId');
	}

	/**
	 * Set ID of review assignment
	 * @param $reviewId int
	 */
	function setReviewId($reviewId) {
		$this->setData('reviewId', $reviewId);
	}

	/**
	 * Get ID of reviewer.
	 * @return int
	 */
	function getReviewerId() {
		return $this->getData('reviewerId');
	}

	/**
	 * Set ID of reviewer.
	 * @param $reviewerId int
	 */
	function setReviewerId($reviewerId) {
		$this->setData('reviewerId', $reviewerId);
	}

	/**
	 * Get full name of reviewer.
	 * @return string
	 */
	function getReviewerFullName() {
		return $this->getData('reviewerFullName');
	}

	/**
	 * Set full name of reviewer.
	 * @param $reviewerFullName string
	 */
	function setReviewerFullName($reviewerFullName) {
		$this->setData('reviewerFullName', $reviewerFullName);
	}

	/**
	 * Get editor decisions.
	 * @return array
	 */
	function getDecisions() {
		return $this->editorDecisions;
	}

	/**
	 * Set editor decisions.
	 * @param $editorDecisions array
	 * @param $round int
	 */
	function setDecisions($editorDecisions) {
		return $this->editorDecisions = $editorDecisions;
	}

	/**
	 * Get reviewer recommendation.
	 * @return string
	 */
	function getRecommendation() {
		return $this->getData('recommendation');
	}

	/**
	 * Set reviewer recommendation.
	 * @param $recommendation string
	 */
	function setRecommendation($recommendation) {
		$this->setData('recommendation', $recommendation);
	}

	/**
	 * Get the reviewer's assigned date.
	 * @return string
	 */
	function getDateAssigned() {
		return $this->getData('dateAssigned');
	}

	/**
	 * Set the reviewer's assigned date.
	 * @param $dateAssigned string
	 */
	function setDateAssigned($dateAssigned) {
		$this->setData('dateAssigned', $dateAssigned);
	}

	/**
	 * Get the reviewer's notified date.
	 * @return string
	 */
	function getDateNotified() {
		return $this->getData('dateNotified');
	}

	/**
	 * Set the reviewer's notified date.
	 * @param $dateNotified string
	 */
	function setDateNotified($dateNotified) {
		$this->setData('dateNotified', $dateNotified);
	}

	/**
	 * Get the reviewer's confirmed date.
	 * @return string
	 */
	function getDateConfirmed() {
		return $this->getData('dateConfirmed');
	}

	/**
	 * Set the reviewer's confirmed date.
	 * @param $dateConfirmed string
	 */
	function setDateConfirmed($dateConfirmed) {
		$this->setData('dateConfirmed', $dateConfirmed);
	}

	/**
	 * Get the reviewer's completed date.
	 * @return string
	 */
	function getDateCompleted() {
		return $this->getData('dateCompleted');
	}

	/**
	 * Set the reviewer's completed date.
	 * @param $dateCompleted string
	 */
	function setDateCompleted($dateCompleted) {
		$this->setData('dateCompleted', $dateCompleted);
	}

	/**
	 * Get the reviewer's acknowledged date.
	 * @return string
	 */
	function getDateAcknowledged() {
		return $this->getData('dateAcknowledged');
	}

	/**
	 * Set the reviewer's acknowledged date.
	 * @param $dateAcknowledged string
	 */
	function setDateAcknowledged($dateAcknowledged) {
		$this->setData('dateAcknowledged', $dateAcknowledged);
	}

	/**
	 * Get the reviewer's due date.
	 * @return string
	 */
	function getDateDue() {
		return $this->getData('dateDue');
	}

	/**
	 * Set the reviewer's due date.
	 * @param $dateDue string
	 */
	function setDateDue($dateDue) {
		$this->setData('dateDue', $dateDue);
	}

	/**
	 * Get the reviewer's response due date.
	 * @return string
	 */
	function getDateResponseDue() {
		return $this->getData('dateResponseDue');
	}

	/**
	 * Set the reviewer's response due date.
	 * @param $dateResponseDue string
	 */
	function setDateResponseDue($dateResponseDue) {
		$this->setData('dateResponseDue', $dateResponseDue);
	}

	/**
	 * Get the declined value.
	 * @return boolean
	 */
	function getDeclined() {
		return $this->getData('declined');
	}

	/**
	 * Set the reviewer's declined value.
	 * @param $declined boolean
	 */
	function setDeclined($declined) {
		$this->setData('declined', $declined);
	}

	/**
	 * Get the cancelled value.
	 * @return boolean
	 */
	function getCancelled() {
		return $this->getData('cancelled');
	}

	/**
	 * Set the reviewer's cancelled value.
	 * @param $cancelled boolean
	 */
	function setCancelled($cancelled) {
		$this->setData('cancelled', $cancelled);
	}

	/**
	/**
	 * Get quality.
	 * @return int|null
	 */
	function getQuality() {
		return $this->getData('quality');
	}

	/**
	 * Set quality.
	 * @param $quality int|null
	 */
	function setQuality($quality) {
		$this->setData('quality', $quality);
	}

	/**
	 * Get stageId.
	 * @return int
	 */
	function getStageId() {
		return $this->getData('stageId');
	}

	/**
	 * Set stageId.
	 * @param $stageId int
	 */
	function setStageId($stageId) {
		$this->setData('stageId', $stageId);
	}

	/**
	 * Get the method of the review (open, blind, or double-blind).
	 * @return int
	 */
	function getReviewMethod() {
		return $this->getData('reviewMethod');
	}

	/**
	 * Set the type of review.
	 * @param $method int
	 */
	function setReviewMethod($method) {
		$this->setData('reviewMethod', $method);
	}

	/**
	 * Get round.
	 * @return int
	 */
	function getRound() {
		return $this->getData('round');
	}

	/**
	 * Set round.
	 * @param $round int
	 */
	function setRound($round) {
		$this->setData('round', $round);
	}

	/**
	 * Get step.
	 * @return int
	 */
	function getStep() {
		return $this->getData('step');
	}

	/**
	 * Set status.
	 * @param $status int
	 */
	function setStep($step) {
		$this->setData('step', $step);
	}

	//
	// Comments
	//

	/**
	 * Get most recent peer review comment.
	 * @return SubmissionComment
	 */
	function getMostRecentPeerReviewComment() {
		return $this->getData('peerReviewComment');
	}

	/**
	 * Set most recent peer review comment.
	 * @param $peerReviewComment SubmissionComment
	 */
	function setMostRecentPeerReviewComment($peerReviewComment) {
		$this->setData('peerReviewComment', $peerReviewComment);
	}
}


