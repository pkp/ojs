<?php

/**
 * @file classes/submission/reviewAssignment/ReviewAssignment.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewAssignment
 * @ingroup submission
 * @see ReviewAssignmentDAO
 *
 * @brief Describes review assignment properties.
 */

define('SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT', 1);
define('SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS', 2);
define('SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_HERE', 3);
define('SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_ELSEWHERE', 4);
define('SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE', 5);
define('SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS', 6);

define('SUBMISSION_REVIEWER_RATING_VERY_GOOD', 5);
define('SUBMISSION_REVIEWER_RATING_GOOD', 4);
define('SUBMISSION_REVIEWER_RATING_AVERAGE', 3);
define('SUBMISSION_REVIEWER_RATING_POOR', 2);
define('SUBMISSION_REVIEWER_RATING_VERY_POOR', 1);

define('SUBMISSION_REVIEW_METHOD_BLIND', 1);
define('SUBMISSION_REVIEW_METHOD_DOUBLEBLIND', 2);
define('SUBMISSION_REVIEW_METHOD_OPEN', 3);

define('REVIEW_ASSIGNMENT_NOT_UNCONSIDERED', 0);
define('REVIEW_ASSIGNMENT_UNCONSIDERED', 1);
define('REVIEW_ASSIGNMENT_UNCONSIDERED_READ', 2);

class ReviewAssignment extends DataObject {

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
	 * Get ID of review assignment's submission.
	 * @return int
	 */
	function getSubmissionId() {
		return $this->getData('submissionId');
	}

	/**
	 * Set ID of review assignment's submission
	 * @param $submissionId int
	 */
	function setSubmissionId($submissionId) {
		$this->setData('submissionId', $submissionId);
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
	 * Get reviewer comments.
	 * @return string
	 */
	function getComments() {
		return $this->getData('comments');
	}

	/**
	 * Set reviewer comments.
	 * @param $comments string
	 */
	function setComments($comments) {
		$this->setData('comments', $comments);
	}

	/**
	 * Get competing interests.
	 * @return string
	 */
	function getCompetingInterests() {
		return $this->getData('competingInterests');
	}

	/**
	 * Set competing interests.
	 * @param $competingInterests string
	 */
	function setCompetingInterests($competingInterests) {
		$this->setData('competingInterests', $competingInterests);
	}

	/**
	 * Get the workflow stage id.
	 * @return int
	 */
	function getStageId() {
		return $this->getData('stageId');
	}

	/**
	 * Set the workflow stage id.
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
	 * Get review round id.
	 * @return int
	 */
	function getReviewRoundId() {
		return $this->getData('reviewRoundId');
	}

	/**
	 * Set review round id.
	 * @param $reviewRoundId int
	 */
	function setReviewRoundId($reviewRoundId) {
		$this->setData('reviewRoundId', $reviewRoundId);
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
	 * Get unconsidered state.
	 * @return int
	 */
	function getUnconsidered() {
		return $this->getData('unconsidered');
	}

	/**
	 * Set unconsidered state.
	 * @param $unconsidered int
	 */
	function setUnconsidered($unconsidered) {
		$this->setData('unconsidered', $unconsidered);
	}

	/**
	 * Get the date the reviewer was rated.
	 * @return string
	 */
	function getDateRated() {
		return $this->getData('dateRated');
	}

	/**
	 * Set the date the reviewer was rated.
	 * @param $dateRated string
	 */
	function setDateRated($dateRated) {
		$this->setData('dateRated', $dateRated);
	}

	/**
	 * Get the date of the last modification.
	 * @return date
	 */
	function getLastModified() {
		return $this->getData('lastModified');
	}

	/**
	 * Set the date of the last modification.
	 * @param $dateModified date
	 */
	function setLastModified($dateModified) {
		$this->setData('lastModified', $dateModified);
	}

	/**
	 * Stamp the date of the last modification to the current time.
	 */
	function stampModified() {
		return $this->setLastModified(Core::getCurrentDate());
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
	 * Get the reviewer's last reminder date.
	 * @return string
	 */
	function getDateReminded() {
		return $this->getData('dateReminded');
	}

	/**
	 * Set the reviewer's last reminder date.
	 * @param $dateReminded string
	 */
	function setDateReminded($dateReminded) {
		$this->setData('dateReminded', $dateReminded);
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
	 * Get the replaced value.
	 * @return boolean
	 */
	function getReplaced() {
		return $this->getData('replaced');
	}

	/**
	 * Set the reviewer's replaced value.
	 * @param $replaced boolean
	 */
	function setReplaced($replaced) {
		$this->setData('replaced', $replaced);
	}

	/**
	 * Get a boolean indicating whether or not the last reminder was automatic.
	 * @return boolean
	 */
	function getReminderWasAutomatic() {
		return $this->getData('reminderWasAutomatic')==1?1:0;
	}

	/**
	 * Set the boolean indicating whether or not the last reminder was automatic.
	 * @param $wasAutomatic boolean
	 */
	function setReminderWasAutomatic($wasAutomatic) {
		$this->setData('reminderWasAutomatic', $wasAutomatic);
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
	 * Get quality.
	 * @return int
	 */
	function getQuality() {
		return $this->getData('quality');
	}

	/**
	 * Set quality.
	 * @param $quality int
	 */
	function setQuality($quality) {
		$this->setData('quality', $quality);
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
	 * Get review form id.
	 * @return int
	 */
	function getReviewFormId() {
		return $this->getData('reviewFormId');
	}

	/**
	 * Set review form id.
	 * @param $reviewFormId int
	 */
	function setReviewFormId($reviewFormId) {
		$this->setData('reviewFormId', $reviewFormId);
	}

	//
	// Files
	//

	/**
	 * Get number of weeks until review is due (or number of weeks overdue).
	 * @return int
	 */
	function getWeeksDue() {
		$dateDue = $this->getDateDue();
		if ($dateDue === null) return null;
		return round((strtotime($dateDue) - time()) / (86400 * 7.0));
	}

	/**
	 * Get an associative array matching reviewer recommendation codes with locale strings.
	 * (Includes default '' => "Choose One" string.)
	 * @return array recommendation => localeString
	 */
	function getReviewerRecommendationOptions() {

		static $reviewerRecommendationOptions = array(
				'' => 'common.chooseOne',
				SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT => 'reviewer.article.decision.accept',
				SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS => 'reviewer.article.decision.pendingRevisions',
				SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_HERE => 'reviewer.article.decision.resubmitHere',
				SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_ELSEWHERE => 'reviewer.article.decision.resubmitElsewhere',
				SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE => 'reviewer.article.decision.decline',
				SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS => 'reviewer.article.decision.seeComments'
		);
		return $reviewerRecommendationOptions;
	}

	/**
	 * Return a localized string representing the reviewer recommendation.
	 */
	function getLocalizedRecommendation() {

		$options = self::getReviewerRecommendationOptions();
		if (array_key_exists($this->getRecommendation(), $options)) {
			return __($options[$this->getRecommendation()]);
		} else {
			return '';
		}
	}
}

?>
