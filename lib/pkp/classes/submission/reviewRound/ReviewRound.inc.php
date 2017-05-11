<?php
/**
 * @defgroup submission_reviewRound Review Round
 */

/**
 * @file classes/submission/reviewRound/ReviewRound.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewRound
 * @ingroup submission_reviewRound
 * @see ReviewRoundDAO
 *
 * @brief Basic class describing a review round.
 */

define('REVIEW_ROUND_STATUS_REVISIONS_REQUESTED', 1);
define('REVIEW_ROUND_STATUS_RESUBMITTED', 2);
define('REVIEW_ROUND_STATUS_SENT_TO_EXTERNAL', 3);
define('REVIEW_ROUND_STATUS_ACCEPTED', 4);
define('REVIEW_ROUND_STATUS_DECLINED', 5);
define('REVIEW_ROUND_STATUS_PENDING_REVIEWERS', 6);
define('REVIEW_ROUND_STATUS_PENDING_REVIEWS', 7);
define('REVIEW_ROUND_STATUS_REVIEWS_READY', 8);
define('REVIEW_ROUND_STATUS_REVIEWS_COMPLETED', 9);

class ReviewRound extends DataObject {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Get/set methods
	//

	/**
	 * get submission id
	 * @return int
	 */
	function getSubmissionId() {
		return $this->getData('submissionId');
	}

	/**
	 * set submission id
	 * @param $submissionId int
	 */
	function setSubmissionId($submissionId) {
		$this->setData('submissionId', $submissionId);
	}

	/**
	 * Get review stage id (internal or external review).
	 * @return int
	 */
	function getStageId() {
		return $this->getData('stageId');
	}

	/**
	 * Set review stage id
	 * @param $stageId int
	 */
	function setStageId($stageId) {
		$this->setData('stageId', $stageId);
	}

	/**
	 * Get review round
	 * @return int
	 */
	function getRound() {
		return $this->getData('round');
	}

	/**
	 * Set review round
	 * @param $assocType int
	 */
	function setRound($round) {
		$this->setData('round', $round);
	}

	/**
	 * Get current round status
	 * @return int
	 */
	function getStatus() {
		return $this->getData('status');
	}

	/**
	 * Set current round status
	 * @param $status int
	 */
	function setStatus($status) {
		$this->setData('status', $status);
	}

	/**
	 * Get locale key associated with current status
	 * @param $isAuthor boolean True iff the status is to be shown to the author (slightly tweaked phrasing)
	 * @return int
	 */
	function getStatusKey($isAuthor = false) {
		switch ($this->getStatus()) {
			case REVIEW_ROUND_STATUS_REVISIONS_REQUESTED:
				return 'editor.submission.roundStatus.revisionsRequested';
			case REVIEW_ROUND_STATUS_RESUBMITTED:
				return 'editor.submission.roundStatus.resubmitted';
			case REVIEW_ROUND_STATUS_SENT_TO_EXTERNAL:
				return 'editor.submission.roundStatus.sentToExternal';
			case REVIEW_ROUND_STATUS_ACCEPTED:
				return 'editor.submission.roundStatus.accepted';
			case REVIEW_ROUND_STATUS_DECLINED:
				return 'editor.submission.roundStatus.declined';
			case REVIEW_ROUND_STATUS_PENDING_REVIEWERS:
				return 'editor.submission.roundStatus.pendingReviewers';
			case REVIEW_ROUND_STATUS_PENDING_REVIEWS:
				return 'editor.submission.roundStatus.pendingReviews';
			case REVIEW_ROUND_STATUS_REVIEWS_READY:
				return $isAuthor?'author.submission.roundStatus.reviewsReady':'editor.submission.roundStatus.reviewsReady';
			case REVIEW_ROUND_STATUS_REVIEWS_COMPLETED:
				return 'editor.submission.roundStatus.reviewsCompleted';
			default: return null;
		}
	}
}

?>
