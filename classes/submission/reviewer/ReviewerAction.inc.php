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

class ReviewerAction extends Action{

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
		
		$reviewAssignment->setDateConfirmed(Core::getCurrentDate());
		
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
	
		$reviewerSubmission->setRecommendation($recommendation);
		
		$reviewerSubmissionDao->updateReviewerSubmission($reviewerSubmission);
	}
	
	/**
	 * Upload the annotated version of an article.
	 * @param $articleId int
	 */
	function uploadAnnotatedArticle($articleId) {
		import("file.ArticleFileManager");
		$articleFileManager = new ArticleFileManager($articleId);
		$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');
		$user = &Request::getUser();
		
		$reviewerSubmission = $reviewerSubmissionDao->getReviewerSubmission($articleId, $user->getUserId());
		
		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName)) {
			if ($reviewerSubmission->getReviewFileId() != null) {
				$fileId = $articleFileManager->uploadReviewerFile($fileName, $reviewerSubmission->getReviewFileId());
			} else {
				$fileId = $articleFileManager->uploadReviewerFile($fileName);
			}
		}
		
		$reviewerSubmission->setReviewFileId($fileId);

		$reviewerSubmissionDao->updateReviewerSubmission($reviewerSubmission);
	}
}

?>
