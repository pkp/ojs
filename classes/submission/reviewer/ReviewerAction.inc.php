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
	function confirmReview($reviewId, $decline) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		
		// Only confirm the review for the reviewer if 
		// he has not previously done so.
		if ($reviewAssignment->getDateConfirmed() == null) {
			$reviewAssignment->setDeclined($decline);
			$reviewAssignment->setDateConfirmed(Core::getCurrentDate());
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

			// Add log
			$entry = new ArticleEventLogEntry();
			$entry->setArticleId($reviewAssignment->getArticleId());
			$entry->setUserId($user->getUserId());
			$entry->setDateLogged(Core::getCurrentDate());
			if ($decline) {
				$entry->setEventType(ARTICLE_LOG_REVIEW_DECLINE);
				$entry->setLogMessage('log.review.reviewDeclined', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $reviewAssignment->getArticleId(), 'round' => $reviewAssignment->getRound()));
			} else {
				$entry->setEventType(ARTICLE_LOG_REVIEW_ACCEPT);
				$entry->setLogMessage('log.review.reviewAccepted', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $reviewAssignment->getArticleId(), 'round' => $reviewAssignment->getRound()));
			}
			$entry->setAssocType(ARTICLE_LOG_TYPE_REVIEW);
			$entry->setAssocId($reviewAssignment->getReviewId());
				
			ArticleLog::logEventEntry($reviewAssignment->getArticleId(), $entry);
		}
	}
	
	/**
	 * Records the reviewer's submission recommendation.
	 * @param $articleId int
	 * @param $recommendation int
	 */
	function recordRecommendation($reviewId, $recommendation) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
	
		// Only record the reviewers recommendation if
		// no recommendation has previously been submitted.
		if ($reviewAssignment->getRecommendation() == null) {
			$reviewAssignment->setRecommendation($recommendation);
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
		
			// Add log
			ArticleLog::logEvent($reviewAssignment->getArticleId(), ARTICLE_LOG_REVIEW_RECOMMENDATION, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewRecommendationSet', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $reviewAssignment->getArticleId(), 'round' => $reviewAssignment->getRound()));
		}
	}
	
	/**
	 * Upload the annotated version of an article.
	 * @param $articleId int
	 */
	function uploadReviewerVersion($reviewId) {
		import("file.ArticleFileManager");
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		
		$articleFileManager = new ArticleFileManager($reviewAssignment->getArticleId());
		$user = &Request::getUser();
		
		// Only upload the file if the reviewer has yet to submit a recommendation
		if ($reviewAssignment->getRecommendation() == null) {
			$fileName = 'upload';
			if ($articleFileManager->uploadedFileExists($fileName)) {
				if ($reviewAssignment->getReviewerFileId() != null) {
					$fileId = $articleFileManager->uploadReviewerFile($fileName, $reviewAssignment->getReviewerFileId());
				} else {
					$fileId = $articleFileManager->uploadReviewerFile($fileName);
				}
			}
		}
		
		if (isset($fileId) && $fileId != 0) {
			$reviewAssignment->setReviewerFileId($fileId);
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
	
			// Add log
			ArticleLog::logEvent($reviewAssignment->getArticleId(), ARTICLE_LOG_REVIEW_FILE, ARTICLE_LOG_TYPE_REVIEW, $reviewAssignment->getReviewId());
		}
	}
	
	/**
	 * View reviewer comments.
	 * @param $articleId int
	 * @param $reviewId int
	 */
	function viewPeerReviewComments($articleId, $reviewId) {
		import("submission.form.comment.PeerReviewCommentForm");
		
		$commentForm = new PeerReviewCommentForm($articleId, $reviewId, ROLE_ID_REVIEWER);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post reviewer comments.
	 * @param $articleId int
	 * @param $reviewId int
	 */
	function postPeerReviewComment($articleId, $reviewId) {
		import("submission.form.comment.PeerReviewCommentForm");
		
		$commentForm = new PeerReviewCommentForm($articleId, $reviewId, ROLE_ID_REVIEWER);
		$commentForm->readInputData();
		
		if ($commentForm->validate()) {
			$commentForm->execute();
			
		} else {
			parent::setupTemplate(true);
			$commentForm->display();
		}
	}
	
	//
	// Misc
	//
	
	/**
	 * Download a file a reviewer has access to.
	 * @param $reviewId int
	 * @param $articleId int
	 * @param $fileId int
	 * @param $revision int
	 */
	function downloadReviewerFile($reviewId, $articleId, $fileId, $revision = null) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);

		$canDownload = false;
		
		// Reviewers have access to:
		// 1) The latest revision of the file to be reviewed.
		// 2) Any file that he uploads.
		// 3) Any supplementary file that is visible to reviewers.
		if ($reviewAssignment->getReviewFileId() == $fileId) {
			if ($revision != null) {
				$canDownload = $reviewAssignment->getReviewRevision() == $revision ? true : false;
			} else {
				$canDownload = true;
			}
		} else if ($reviewAssignment->getReviewerFileId() == $fileId) {
			$canDownload = true;
		} else {
			foreach ($reviewAssignment->getSuppFiles() as $suppFile) {
				if ($suppFile->getFileId() == $fileId && $suppFile->getShowReviewers()) {
					$canDownload = true;
				}
			}
		}
		
		if ($canDownload) {
			return Action::downloadFile($articleId, $fileId, $revision);
		} else {
			return false;
		}
	}
}

?>
