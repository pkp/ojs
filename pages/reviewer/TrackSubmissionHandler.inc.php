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

class TrackSubmissionHandler extends ReviewerHandler {
	
	
	/**
	 * Display reviewer administration page.
	 */
	function submission($args) {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate();
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');
		$submission = $reviewerSubmissionDao->getReviewerSubmission($args[0], $user->getUserId());
		$reviewAssignment = $submission->getReviewAssignment();
		
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sections = $sectionDao->getJournalSections($journal->getJournalId());
	
		if ($reviewAssignment->getDateConfirmed() == null) {
			$confirmedStatus = 0;
		} else {
			$confirmedStatus = 1;
		}
	
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('user', $user);
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('reviewAssignment', $reviewAssignment);
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('confirmedStatus', $confirmedStatus);
		$templateMgr->display('reviewer/submission.tpl');
	}
	
	function assignments($args) {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate();
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('submissions', $reviewerSubmissionDao->getReviewerSubmissionsByReviewerId($user->getUserId(), $journal->getJournalId()));
		$templateMgr->display('reviewer/submissions.tpl');
	}
	
	function confirmReview() {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate();
		
		$articleId = Request::getUserVar('articleId');
		$acceptReview = Request::getUserVar('acceptReview');
		$declineReview = Request::getUserVar('declineReview');
		
		if (isset($acceptReview)) {
			$accept = 1;
		} else {
			$accept = 0;
		}
		
		ReviewerAction::confirmReview($articleId, $accept);
		
		Request::redirect(sprintf('reviewer/submission/%d', $articleId));
	}
	
	function recordRecommendation() {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$recommendation = Request::getUserVar('recommendation');

		ReviewerAction::recordRecommendation($articleId, $recommendation);
		
		Request::redirect(sprintf('reviewer/submission/%d', $articleId));
	}
}
?>
