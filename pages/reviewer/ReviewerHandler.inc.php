<?php

/**
 * ReviewerHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.reviewer
 *
 * Handle requests for reviewer functions. 
 *
 * $Id$
 */

class ReviewerHandler extends Handler {

	/**
	 * Display reviewer index page.
	 */
	function index() {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate();
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->display('reviewer/index.tpl');
	}
	
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
	
	/**
	 * Validate that user is a reviewer in the selected journal.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate() {
		parent::validate();
		$journal = &Request::getJournal();
		if (!isset($journal) || !Validation::isReviewer($journal->getJournalId())) {
			Request::redirect('user');
		}
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array('user', 'navigation.user'), array('manager', 'manager.journalManagement'))
				: array(array('user', 'navigation.user'))
		);
	}
	
}

?>
