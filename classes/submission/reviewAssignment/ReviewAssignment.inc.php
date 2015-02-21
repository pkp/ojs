<?php

/**
 * @file classes/submission/reviewAssignment/ReviewAssignment.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewAssignment
 * @ingroup submission
 * @see ReviewAssignmentDAO
 *
 * @brief Describes review assignment properties.
 */

import('lib.pkp.classes.submission.reviewAssignment.PKPReviewAssignment');

class ReviewAssignment extends PKPReviewAssignment {
	/**
	 * Constructor.
	 */
	function ReviewAssignment() {
		parent::PKPReviewAssignment();
	}

	//
	// Get/set methods
	//

	/**
	 * Get ID of article.
	 * @return int
	 */
	function getArticleId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getSubmissionId();
	}

	/**
	 * Set ID of article.
	 * @param $articleId int
	 */
	function setArticleId($articleId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setSubmissionId($articleId);
	}

	/**
	 * Get an associative array matching reviewer recommendation codes with locale strings.
	 * (Includes default '' => "Choose One" string.)
	 * @return array recommendation => localeString
	 */
	function &getReviewerRecommendationOptions() {
		// Bring in reviewer constants
		import('classes.submission.reviewer.ReviewerSubmission');

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
	 * Get an associative array matching reviewer rating codes with locale strings.
	 * @return array recommendation => localeString
	 */
	function &getReviewerRatingOptions() {
		static $reviewerRatingOptions = array(
			SUBMISSION_REVIEWER_RATING_VERY_GOOD => 'editor.article.reviewerRating.veryGood',
			SUBMISSION_REVIEWER_RATING_GOOD => 'editor.article.reviewerRating.good',
			SUBMISSION_REVIEWER_RATING_AVERAGE => 'editor.article.reviewerRating.average',
			SUBMISSION_REVIEWER_RATING_POOR => 'editor.article.reviewerRating.poor',
			SUBMISSION_REVIEWER_RATING_VERY_POOR => 'editor.article.reviewerRating.veryPoor'
		);
		return $reviewerRatingOptions;
	}
}

?>
