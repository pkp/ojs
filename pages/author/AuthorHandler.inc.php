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
	function index() {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate();
		
		$templateMgr = &TemplateManager::getManager();
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
	function setupTemplate($subclass = false) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array('user', 'navigation.user'), array('author', 'author.journalAuthor'))
				: array(array('user', 'navigation.user'))
		);
		$templateMgr->assign('pagePath', '/user/author');
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

	function track() {
		TrackSubmissionHandler::track();
	}
	
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
	
	function downloadFile($args) {
		TrackSubmissionHandler::downloadFile($args);
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
