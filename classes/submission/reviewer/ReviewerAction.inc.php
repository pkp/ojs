<?php

/**
 * ReviewerAction.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * ReviewerAction class.
 *
 * $Id$
 */

class ReviewerAction {

	/**
	 * Constructor.
	 */
	function ReviewerAction() {

	}
	
	/**
	 * Actions.
	 */
	 
	/**
	 * Records whether or not the reviewer accepts the review assignment.
	 * @param $articleId int
	 * @param $accept boolean
	 */
	function confirmReview($articleId, $accept) {
		$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');
		$user = &Request::getUser();
		
		$reviewerSubmission = &$reviewerSubmissionDao->getReviewerSubmission($articleId, $user->getUserId());
		$reviewAssignment = $reviewerSubmission->getReviewAssignment();
		
		if (!$accept) {
			$reviewAssignment->setDeclined(true);
		}
		
		$reviewAssignment->setDateConfirmed(date('Y-m-d H:i:s'));
		
		$reviewerSubmission->setReviewAssignment($reviewAssignment);
		$reviewerSubmissionDao->updateReviewerSubmission($reviewerSubmission);
	}
	
	/**
	 * Records the reviewer's submission recommendation.
	 * @param $articleId int
	 * @param $recommendation int
	 */
	function recordRecommendation($articleId, $recommendation) {
		$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');
		$user = &Request::getUser();
		
		$reviewerSubmission = &$reviewerSubmissionDao->getReviewerSubmission($articleId, $user->getUserId());
		$reviewAssignment = $reviewerSubmission->getReviewAssignment();
		
		$reviewAssignment->setRecommendation($recommendation);
		
		$reviewerSubmission->setReviewAssignment($reviewAssignment);
		
		$reviewerSubmissionDao->updateReviewerSubmission($reviewerSubmission);
	}
}

?>
