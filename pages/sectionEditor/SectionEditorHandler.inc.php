<?php

/**
 * SectionEditorHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.sectionEditor
 *
 * Handle requests for section editor functions. 
 *
 * $Id$
 */

class SectionEditorHandler extends Handler {

	/**
	 * Display section editor index page.
	 */
	function index() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate();
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->display('sectionEditor/index.tpl');
	}
	
	/**
	 * Show assignments list.
	 */
	function assignments($args = array()) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$templateMgr = &TemplateManager::getManager();
			
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$assignedArticles = &$sectionEditorSubmissionDao->getSectionEditorSubmissions($user->getUserId(), $journal->getJournalId());
		$templateMgr->assign('assignedArticles', $assignedArticles);
		
		if(isset($args[0]) && $args[0] == 'completed') {
			$templateMgr->assign('showCompleted', true);
		}
		$templateMgr->display('sectionEditor/assignments.tpl');
	}
	
	function submission($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		$journal = &Request::getJournal();
		
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$submission = $sectionEditorSubmissionDao->getSectionEditorSubmission($args[0]);

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sections = $sectionDao->getJournalSections($journal->getJournalId());

		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$numReviewers = $journalSettingsDao->getSetting($journal->getJournalId(), 'numReviewersPerSubmission');
		
		if (count($submission->getReviewAssignments()) < $numReviewers) {
			$numSelectReviewers = $numReviewers - count($submission->getReviewAssignments());
		} else {
			$numSelectReviewers = 1;
		}
		
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('reviewAssignments', $submission->getReviewAssignments());
		$templateMgr->assign('numSelectReviewers', $numSelectReviewers);
		$templateMgr->assign('sections', $sections);

		$templateMgr->display('sectionEditor/submission.tpl');
	}
	
	function recordRecommendation() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$recommendation = Request::getUserVar('recommendation');

		SectionEditorAction::recordRecommendation($articleId, $recommendation);
		
		Request::redirect(sprintf('sectionEditor/submission/%d', $articleId));
	}
	
	function selectReviewer($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		$journal = &Request::getJournal();
		
		if (isset($args[1]) && $args[1] != null) {
			SectionEditorAction::AddReviewer($args[0], $args[1]);
		
			Request::redirect('sectionEditor/submission/'.$args[0]);
			
		} else {
			$roleDao = &DAORegistry::getDAO('RoleDAO');
			// Actually, we probably want to get reviewers that
			// have not already been assigned this particular
			// article. We'll work on that later ...
			$reviewers = $roleDao->getUsersByRoleId(ROLE_ID_REVIEWER, $journal->getJournalId());
		
			$templateMgr = &TemplateManager::getManager();
		
			$templateMgr->assign('reviewers', $reviewers);
			$templateMgr->assign('articleId', $args[0]);
	
			$templateMgr->display('sectionEditor/selectReviewer.tpl');
		}
	}
	
	function clearReviewer($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		$reviewId = $args[1];
		
		SectionEditorAction::clearReviewer($articleId, $reviewId);
		
		Request::redirect(sprintf('sectionEditor/submission/%d', $articleId));

	}

	
	/**
	 * Validate that user is a section editor in the selected journal.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate() {
		parent::validate();
		$journal = &Request::getJournal();
		if (!isset($journal) || !Validation::isSectionEditor($journal->getJournalId())) {
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
			$subclass ? array(array('user', 'navigation.user'), array('sectionEditor', 'sectionEditor.journalSectionEditor'))
				: array(array('user', 'navigation.user'))
		);
	}
	
}

?>
