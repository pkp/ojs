<?php

/**
 * TrackSubmissionHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
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
define('SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT', 3);
define('SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE', 4);
define('SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS', 5); 

class TrackSubmissionHandler extends ReviewerHandler {
	
	/**
	 * Display reviewer administration page.
	 */	
	function assignments($args) {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate(true);
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');
		
		$completed = isset($args[0]) && $args[0] == 'completed' ? true : false;
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('submissions', $reviewerSubmissionDao->getReviewerSubmissionsByReviewerId($user->getUserId(), $journal->getJournalId(), $completed));
		$templateMgr->display('reviewer/submissions.tpl');
	}

	function assignment($args) {
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
		$templateMgr->assign('reviewFile', $submission->getReviewFile());
		$templateMgr->assign('reviewerFile', $submission->getReviewerFile());
		$templateMgr->assign('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('reviewerRecommendationOptions',
			array(
				'' => 'reviewer.article.decision.chooseOne',
				SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT => 'reviewer.article.decision.accept',
				SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS => 'reviewer.article.decision.pendingRevisions',
				SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT => 'reviewer.article.decision.resubmit',
				SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE => 'reviewer.article.decision.decline',
				SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS => 'reviewer.article.decision.seeComments'
			)
		);
		
		$templateMgr->display('reviewer/submission.tpl');
	}
	
	function confirmReview() {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate();
		
		$reviewId = Request::getUserVar('reviewId');
		$acceptReview = Request::getUserVar('acceptReview');
		$declineReview = Request::getUserVar('declineReview');
		
		TrackSubmissionHandler::validate($reviewId);
		
		if (isset($declineReview)) {
			$decline = 1;
		} else {
			$decline = 0;
		}
		
		ReviewerAction::confirmReview($reviewId, $decline);
		
		Request::redirect(sprintf('reviewer/assignment/%d', $reviewId));
	}
	
	function recordRecommendation() {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		$recommendation = Request::getUserVar('recommendation');

		TrackSubmissionHandler::validate($reviewId);
		ReviewerAction::recordRecommendation($reviewId, $recommendation);
		
		Request::redirect(sprintf('reviewer/assignment/%d', $reviewId));
	}
	
	function viewMetadata($args) {
		parent::validate();
		parent::setupTemplate(true);
	
		$reviewId = $args[0];
		$articleId = $args[1];
		
		TrackSubmissionHandler::validate($reviewId);
		ReviewerAction::viewMetadata($articleId, ROLE_ID_REVIEWER);
	}
	
	function saveMetadata() {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate(true);
		
		$reviewId = Request::getUserVar('articleId');
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($reviewId);
		ReviewerAction::saveMetadata($articleId);
	}
	
	/**
	 * Upload the reviewer's annotated version of an article.
	 */
	function uploadReviewerVersion() {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		
		TrackSubmissionHandler::validate($reviewId);
		ReviewerAction::uploadReviewerVersion($reviewId);
		
		Request::redirect(sprintf('reviewer/assignment/%d', $reviewId));	
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
