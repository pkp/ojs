<?php

/**
 * AuthorHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.author
 *
 * Handle requests for journal author functions. 
 *
 * $Id$
 */

import('pages.author.SubmitHandler');
import('pages.author.TrackSubmissionHandler');
import('pages.author.SubmissionCommentsHandler');

class AuthorHandler extends Handler {

	/**
	 * Display journal author index page.
	 */
	function index($args) {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate();

		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');

		$page = isset($args[0]) ? $args[0] : '';
		switch($page) {
			case 'completed':
				$active = false;
				break;
			default:
				$page = 'active';
				$active = true;
		}

		$submissions = $authorSubmissionDao->getAuthorSubmissions($user->getUserId(), $journal->getJournalId(), $active);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign('submissions', $submissions);

		$issueAction = new IssueAction();
		$templateMgr->register_function('print_issue_id', array($issueAction, 'smartyPrintIssueId'));

		$templateMgr->display('author/index.tpl');
	}
	
	/**
	 * Validate that user has author permissions in the selected journal.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate() {
		parent::validate();
		$journal = &Request::getJournal();
		if (!isset($journal) || !Validation::isAuthor($journal->getJournalId())) {
			Request::redirect('user');
		}
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $showSidebar = true) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array('user', 'navigation.user'), array('author', 'author.journalAuthor'))
				: array(array('user', 'navigation.user'), array('author', 'author.journalAuthor'))
		);
		$templateMgr->assign('pagePath', '/user/author');

		if ($showSidebar) {
			$templateMgr->assign('sidebarTemplate', 'author/navsidebar.tpl');

			$journal = &Request::getJournal();
			$user = &Request::getUser();
			$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
			$submissionsCount = $authorSubmissionDao->getSubmissionsCount($user->getUserId(), $journal->getJournalId());
			$templateMgr->assign('submissionsCount', $submissionsCount);
		}

	}


	//
	// Article Submission
	//

	function submit($args) {
		SubmitHandler::submit($args);
	}
	
	function saveSubmit($args) {
		SubmitHandler::saveSubmit($args);
	}

	function submitSuppFile($args) {
		SubmitHandler::submitSuppFile($args);
	}
	
	function saveSubmitSuppFile($args) {
		SubmitHandler::saveSubmitSuppFile($args);
	}
	
	function deleteSubmitSuppFile($args) {
		SubmitHandler::deleteSubmitSuppFile($args);
	}
	
	
	//
	// Submission Tracking
	//
	
	function deleteSubmission($args) {
		TrackSubmissionHandler::deleteSubmission($args);
	}
	
	function submission($args) {
		TrackSubmissionHandler::submission($args);
	}
	
	function submissionEditing($args) {
		TrackSubmissionHandler::submissionEditing($args);
	}
	
	function uploadRevisedVersion() {
		TrackSubmissionHandler::uploadRevisedVersion();
	}
	
	function viewMetadata($args) {
		TrackSubmissionHandler::viewMetadata($args);
	}
	
	function saveMetadata() {
		TrackSubmissionHandler::saveMetadata();
	}
	
	function uploadCopyeditVersion() {
		TrackSubmissionHandler::uploadCopyeditVersion();
	}
	
	function completeAuthorCopyedit($args) {
		TrackSubmissionHandler::completeAuthorCopyedit($args);
	}
	
	//
	// Misc.
	//

	function downloadFile($args) {
		TrackSubmissionHandler::downloadFile($args);
	}

	function download($args) {
		TrackSubmissionHandler::download($args);
	}
	
	//
	// Submission Comments
	//
	
	function viewEditorDecisionComments($args) {
		SubmissionCommentsHandler::viewEditorDecisionComments($args);
	}
	
	function postEditorDecisionComment() {
		SubmissionCommentsHandler::postEditorDecisionComment();
	}
	
	function viewCopyeditComments($args) {
		SubmissionCommentsHandler::viewCopyeditComments($args);
	}
	
	function postCopyeditComment() {
		SubmissionCommentsHandler::postCopyeditComment();
	}
	
	function viewProofreadComments($args) {
		SubmissionCommentsHandler::viewProofreadComments($args);
	}
	
	function postProofreadComment() {
		SubmissionCommentsHandler::postProofreadComment();
	}
	
	function editComment($args) {
		SubmissionCommentsHandler::editComment($args);
	}
	
	function saveComment() {
		SubmissionCommentsHandler::saveComment();
	}
	
	function deleteComment($args) {
		SubmissionCommentsHandler::deleteComment($args);
	}

	//
	// Proofreading Actions
	//
	function authorProofreadingComplete($args) {
		TrackSubmissionHandler::authorProofreadingComplete($args);
	}
}

?>
