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

class ReviewerAction extends Action {

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
	function confirmReview($articleId, $decline) {
		$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');
		$user = &Request::getUser();
		
		$reviewerSubmission = &$reviewerSubmissionDao->getReviewerSubmission($articleId, $user->getUserId());
		
		// Only confirm the review for the reviewer if 
		// he has not previously done so.
		if ($reviewerSubmission->getDateConfirmed() == null) {
			$reviewerSubmission->setDeclined($decline);
			$reviewerSubmission->setDateConfirmed(Core::getCurrentDate());
			$reviewerSubmissionDao->updateReviewerSubmission($reviewerSubmission);
		}
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
	
		// Only record the reviewers recommendation if
		// no recommendation has previously been submitted.
		if ($reviewerSubmission->getRecommendation() == null) {
			$reviewerSubmission->setRecommendation($recommendation);
			$reviewerSubmissionDao->updateReviewerSubmission($reviewerSubmission);
		}
	}
	
	/**
	 * Upload the annotated version of an article.
	 * @param $articleId int
	 */
	function uploadReviewerVersion($articleId) {
		import("file.ArticleFileManager");
		$articleFileManager = new ArticleFileManager($articleId);
		$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');
		$user = &Request::getUser();
		
		$reviewerSubmission = $reviewerSubmissionDao->getReviewerSubmission($articleId, $user->getUserId());
		
		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName)) {
			if ($reviewerSubmission->getReviewerFileId() != null) {
				$fileId = $articleFileManager->uploadReviewerFile($fileName, $reviewerSubmission->getReviewerFileId());
			} else {
				$fileId = $articleFileManager->uploadReviewerFile($fileName);
			}
		}
		
		$reviewerSubmission->setReviewerFileId($fileId);

		$reviewerSubmissionDao->updateReviewerSubmission($reviewerSubmission);
	}
}

?>
