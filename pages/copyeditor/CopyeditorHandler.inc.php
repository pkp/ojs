<?php

/**
 * CopyeditorHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.copyeditor
 *
 * Handle requests for copyeditor functions. 
 *
 * $Id$
 */

import('pages.copyeditor.TrackSubmissionHandler');
import('pages.copyeditor.SubmissionCommentsHandler');
class CopyeditorHandler extends Handler {

	/**
	 * Display copyeditor index page.
	 */
	function index() {
		CopyeditorHandler::validate();
		CopyeditorHandler::setupTemplate();
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->display('copyeditor/index.tpl');
	}
	
	/**
	 * Validate that user is a copyeditor in the selected journal.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate() {
		parent::validate();
		$journal = &Request::getJournal();
		if (!isset($journal) || !Validation::isCopyeditor($journal->getJournalId())) {
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
			$subclass ? array(array('user', 'navigation.user'), array('copyeditor', 'copyeditor.journalCopyeditor'))
				: array(array('user', 'navigation.user'))
		);
		$templateMgr->assign('pagePath', '/user/copyeditor');
	}
	
	//
	// Assignment Tracking
	//

	function assignments($args) {
		TrackSubmissionHandler::assignments($args);
	}
	
	function submission($args) {
		TrackSubmissionHandler::submission($args);
	}
	
	function completeCopyedit($args) {
		TrackSubmissionHandler::completeCopyedit($args);
	}
	
	function completeFinalCopyedit($args) {
		TrackSubmissionHandler::completeFinalCopyedit($args);
	}
	
	function uploadCopyeditVersion() {
		TrackSubmissionHandler::uploadCopyeditVersion();
	}
	
	//
	// Submission Comments
	//
	

	function viewCopyeditComments($args) {
		SubmissionCommentsHandler::viewCopyeditComments($args);
	}
	
	function postCopyeditComment() {
		SubmissionCommentsHandler::postCopyeditComment();
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
}

?>
