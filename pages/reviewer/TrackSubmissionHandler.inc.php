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
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('submissions', $reviewerSubmissionDao->getReviewerSubmissionsByReviewerId($user->getUserId(), $journal->getJournalId()));
		$templateMgr->display('reviewer/submissions.tpl');
	}

	function submission($args) {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate(true);
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$articleId = $args[0];
		
		TrackSubmissionHandler::validate($articleId);
		
		$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');
		$submission = $reviewerSubmissionDao->getReviewerSubmission($articleId, $user->getUserId());
		
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
		
		$articleId = Request::getUserVar('articleId');
		$acceptReview = Request::getUserVar('acceptReview');
		$declineReview = Request::getUserVar('declineReview');
		
		TrackSubmissionHandler::validate($articleId);
		
		if (isset($declineReview)) {
			$decline = 1;
		} else {
			$decline = 0;
		}
		
		ReviewerAction::confirmReview($articleId, $decline);
		
		Request::redirect(sprintf('reviewer/submission/%d', $articleId));
	}
	
	function recordRecommendation() {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$recommendation = Request::getUserVar('recommendation');

		TrackSubmissionHandler::validate($articleId);
		ReviewerAction::recordRecommendation($articleId, $recommendation);
		
		Request::redirect(sprintf('reviewer/submission/%d', $articleId));
	}
	
	function viewMetadata($args) {
		parent::validate();
		parent::setupTemplate(true);
	
		$articleId = $args[0];
		
		TrackSubmissionHandler::validate($articleId);
		ReviewerAction::viewMetadata($articleId, ROLE_ID_REVIEWER);
	}
	
	function saveMetadata() {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		ReviewerAction::saveMetadata($articleId);
	}
	
	/**
	 * Upload the reviewer's annotated version of an article.
	 */
	function uploadReviewerVersion() {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		ReviewerAction::uploadReviewerVersion($articleId);
		
		Request::redirect(sprintf('reviewer/submission/%d', $articleId));	
	}
	
	//
	// Validation
	//
	
	/**
	 * Validate that the user is an assigned reviewer for
	 * the articler.
	 * Redirects to reviewer index page if validation fails.
	 */
	function validate($articleId) {
		$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$reviewerSubmission = &$reviewerSubmissionDao->getReviewerSubmission($articleId, $user->getUserId());
		
		if ($reviewerSubmission == null || $reviewerSubmission->getReviewerId() != $user->getUserId()) {
			Request::redirect('reviewer');
		}
	}
}
?>
