<?php

/**
 * TrackSubmissionHandler.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.reviewer
 *
 * Handle requests for submission tracking. 
 *
 * $Id$
 */
 /** Submission Management Constants */
define('SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT', 1);
define('SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS', 2); 
define('SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_HERE', 3);
define('SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_ELSEWHERE', 4);
define('SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE', 5);
define('SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS', 6); 

class TrackSubmissionHandler extends ReviewerHandler {
	
	function submission($args) {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate(true);
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$reviewId = $args[0];
		
		TrackSubmissionHandler::validate($reviewId);
		
		$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');
		$submission = $reviewerSubmissionDao->getReviewerSubmission($reviewId);
		
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sections = $sectionDao->getJournalSections($journal->getJournalId());
	
		if ($submission->getDateConfirmed() == null) {
			$confirmedStatus = 0;
		} else {
			$confirmedStatus = 1;
		}
	
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('user', $user);
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('confirmedStatus', $confirmedStatus);
		$templateMgr->assign('declined', $submission->getDeclined());
		$templateMgr->assign('reviewFile', $submission->getReviewFile());
		$templateMgr->assign('reviewerFile', $submission->getReviewerFile());
		$templateMgr->assign('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('journal', Request::getJournal());
		$templateMgr->assign('reviewerRecommendationOptions',
			array(
				'' => 'reviewer.article.decision.chooseOne',
				SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT => 'reviewer.article.decision.accept',
				SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS => 'reviewer.article.decision.pendingRevisions',
				SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_HERE => 'reviewer.article.decision.resubmitHere',
                                SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_ELSEWHERE => 'reviewer.article.decision.resubmitElsewhere',
				SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE => 'reviewer.article.decision.decline',
				SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS => 'reviewer.article.decision.seeComments'
			)
		);
		
		$templateMgr->display('reviewer/submission.tpl');
	}
	
	function confirmReview($args = null) {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate();
		
		$reviewId = Request::getUserVar('reviewId');
		$acceptReview = Request::getUserVar('acceptReview');
		$declineReview = Request::getUserVar('declineReview');
		
		$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');
		$reviewerSubmission = &$reviewerSubmissionDao->getReviewerSubmission($reviewId);

		TrackSubmissionHandler::validate($reviewId);
		
		if (isset($declineReview)) {
			$decline = 1;
		} else {
			$decline = 0;
		}
		
		if (!$reviewerSubmission->getCancelled()) ReviewerAction::confirmReview($reviewId, $decline);
		Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $reviewId));
	}
	
	function recordRecommendation() {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		$recommendation = Request::getUserVar('recommendation');

		$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');
		$reviewerSubmission = &$reviewerSubmissionDao->getReviewerSubmission($reviewId);

		TrackSubmissionHandler::validate($reviewId);
		if (!$reviewerSubmission->getCancelled()) ReviewerAction::recordRecommendation($reviewId, $recommendation);
		
		Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $reviewId));
	}
	
	function viewMetadata($args) {
		parent::validate();
		parent::setupTemplate(true);
	
		$reviewId = $args[0];
		$articleId = $args[1];
		
		TrackSubmissionHandler::validate($reviewId);
		ReviewerAction::viewMetadata($articleId, ROLE_ID_REVIEWER);
	}
	
	/**
	 * Upload the reviewer's annotated version of an article.
	 */
	function uploadReviewerVersion() {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		
		$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');
		$reviewerSubmission = &$reviewerSubmissionDao->getReviewerSubmission($reviewId);

		TrackSubmissionHandler::validate($reviewId);
		if (!$reviewerSubmission->getCancelled()) ReviewerAction::uploadReviewerVersion($reviewId);
		
		Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $reviewId));
	}

	/*
	 * Delete one of the reviewer's annotated versions of an article.
	 */
	function deleteReviewerVersion($args) {		
		ReviewerHandler::validate();
                ReviewerHandler::setupTemplate(true);

                $reviewId = isset($args[0]) ? (int) $args[0] : 0;
		$fileId = isset($args[1]) ? (int) $args[1] : 0;
		$revision = isset($args[2]) ? (int) $args[2] : null;
		
		$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');
		$reviewerSubmission = &$reviewerSubmissionDao->getReviewerSubmission($reviewId);

                TrackSubmissionHandler::validate($reviewId);
                if (!$reviewerSubmission->getCancelled()) ReviewerAction::deleteReviewerVersion($reviewId, $fileId, $revision);

		Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $reviewId));
	}
	
	//
	// Misc
	//
	
	/**
	 * Download a file.
	 * @param $args array ($articleId, $fileId, [$revision])
	 */
	function downloadFile($args) {
		$reviewId = isset($args[0]) ? $args[0] : 0;
		$articleId = isset($args[1]) ? $args[1] : 0;
		$fileId = isset($args[2]) ? $args[2] : 0;
		$revision = isset($args[3]) ? $args[3] : null;

		TrackSubmissionHandler::validate($reviewId);
		if (!ReviewerAction::downloadReviewerFile($reviewId, $articleId, $fileId, $revision)) {
			Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $reviewId));
		}
	}
	
	//
	// Validation
	//
	
	/**
	 * Validate that the user is an assigned reviewer for
	 * the article.
	 * Redirects to reviewer index page if validation fails.
	 */
	function validate($reviewId) {
		$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$isValid = true;
		
		$reviewerSubmission = &$reviewerSubmissionDao->getReviewerSubmission($reviewId);
		
		if ($reviewerSubmission == null) {
			$isValid = false;
		} else if ($reviewerSubmission->getJournalId() != $journal->getJournalId()) {
			$isValid = false;
		} else {
			if ($reviewerSubmission->getReviewerId() != $user->getUserId()) {
				$isValid = false;
			}
		}
		
		if (!$isValid) {
			Request::redirect(Request::getRequestedPage());
		}
	}
}
?>
