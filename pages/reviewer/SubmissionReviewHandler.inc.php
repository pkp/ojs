<?php

/**
 * SubmissionReviewHandler.inc.php
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

class SubmissionReviewHandler extends ReviewerHandler {
	
	function submission($args) {
		ReviewerHandler::validate();
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$reviewId = $args[0];

		list($journal, $submission) = SubmissionReviewHandler::validate($reviewId);
		
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment = $reviewAssignmentDao->getReviewAssignmentById($reviewId);
	
		if ($submission->getDateConfirmed() == null) {
			$confirmedStatus = 0;
		} else {
			$confirmedStatus = 1;
		}

		ReviewerHandler::setupTemplate(true, $submission->getArticleId());
	
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('user', $user);
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('reviewAssignment', $reviewAssignment);
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('confirmedStatus', $confirmedStatus);
		$templateMgr->assign('declined', $submission->getDeclined());
		$templateMgr->assign('reviewFile', $reviewAssignment->getReviewFile());
		$templateMgr->assign('reviewerFile', $submission->getReviewerFile());
		$templateMgr->assign('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('journal', $journal);
		$templateMgr->assign('reviewGuidelines', $journal->getSetting('reviewGuidelines'));
		$templateMgr->assign('reviewerRecommendationOptions',
			array(
				'' => 'common.chooseOne',
				SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT => 'reviewer.article.decision.accept',
				SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS => 'reviewer.article.decision.pendingRevisions',
				SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_HERE => 'reviewer.article.decision.resubmitHere',
                                SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_ELSEWHERE => 'reviewer.article.decision.resubmitElsewhere',
				SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE => 'reviewer.article.decision.decline',
				SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS => 'reviewer.article.decision.seeComments'
			)
		);
		$templateMgr->assign('helpTopicId', 'editorial.reviewersRole.review');		
		$templateMgr->display('reviewer/submission.tpl');
	}
	
	function confirmReview($args = null) {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate();
		
		$reviewId = Request::getUserVar('reviewId');
		$declineReview = Request::getUserVar('declineReview');
		
		$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');

		list($journal, $reviewerSubmission) = SubmissionReviewHandler::validate($reviewId);
		
		if (isset($declineReview)) {
			$decline = 1;
		} else {
			$decline = 0;
		}
		
		if (!$reviewerSubmission->getCancelled()) {
			if (ReviewerAction::confirmReview(&$reviewerSubmission, $decline, Request::getUserVar('send'))) {
				Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $reviewId));
			}
		} else {
			Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $reviewId));
		}
	}
	
	function recordRecommendation() {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		$recommendation = Request::getUserVar('recommendation');

		list($journal, $reviewerSubmission) = SubmissionReviewHandler::validate($reviewId);
		if (!$reviewerSubmission->getCancelled()) ReviewerAction::recordRecommendation($reviewId, $recommendation);
		
		Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $reviewId));
	}
	
	function viewMetadata($args) {
		parent::validate();
	
		$reviewId = $args[0];
		$articleId = $args[1];

		parent::setupTemplate(true, $articleId, 'review');
		
		list($journal, $reviewerSubmission) = SubmissionReviewHandler::validate($reviewId);
		ReviewerAction::viewMetadata($reviewerSubmission, ROLE_ID_REVIEWER);
	}
	
	/**
	 * Upload the reviewer's annotated version of an article.
	 */
	function uploadReviewerVersion() {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		
		list($journal, $reviewerSubmission) = SubmissionReviewHandler::validate($reviewId);
		ReviewerAction::uploadReviewerVersion($reviewId);
		
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
		
                list($journal, $reviewerSubmission) = SubmissionReviewHandler::validate($reviewId);
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

		list($journal, $reviewerSubmission) = SubmissionReviewHandler::validate($reviewId);
		if (!ReviewerAction::downloadReviewerFile($reviewId, $reviewerSubmission, $fileId, $revision)) {
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
	function &validate($reviewId) {
		parent::validate();
		
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

		return array($journal, $reviewerSubmission);
	}
}
?>
