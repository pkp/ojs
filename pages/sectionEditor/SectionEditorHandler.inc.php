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

import('pages.sectionEditor.TrackSubmissionHandler');

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
	
	//
	// Submission Tracking
	//

	function assignments($args) {
		TrackSubmissionHandler::assignments($args);
	}
	
	function submission($args) {
		TrackSubmissionHandler::submission($args);
	}
	
	function recordRecommendation() {
		TrackSubmissionHandler::recordRecommendation($args);
	}
	
	function selectReviewer($args) {
		TrackSubmissionHandler::selectReviewer($args);
	}
	
	function clearReviewer($args) {
		TrackSubmissionHandler::clearReviewer($args);
	}

	function notifyReviewer() {
		TrackSubmissionHandler::notifyReviewer();
	}

	function remindReviewer($args) {
		TrackSubmissionHandler::remindReviewer($args);
	}

	function replaceReviewer($args) {
		TrackSubmissionHandler::replaceReviewer($args);
	}
	
	function setDueDate($args) {
		TrackSubmissionHandler::setDueDate($args);
	}
}

?>
