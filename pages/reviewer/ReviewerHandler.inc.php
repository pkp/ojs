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

import('pages.reviewer.TrackSubmissionHandler');

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
			$subclass ? array(array('user', 'navigation.user'), array('reviewer', 'reviewer.journalReviewer'))
				: array(array('user', 'navigation.user'))
		);
		$templateMgr->assign('pagePath', '/user/reviewer');
	}
	
	//
	// Submission Tracking
	//
	
	function submission($args) {
		TrackSubmissionHandler::submission($args);
	}
	
	function assignments($args) {
		TrackSubmissionHandler::assignments($args);
	}

	function confirmReview() {
		TrackSubmissionHandler::confirmReview();
	}
	
	function recordRecommendation() {
		TrackSubmissionHandler::recordRecommendation();
	}
	
	function viewMetadata($args) {
		TrackSubmissionHandler::viewMetadata($args);
	}
	
	function saveMetadata() {
		TrackSubmissionHandler::saveMetadata();
	}
	
	function uploadReviewerVersion() {
		TrackSubmissionHandler::uploadReviewerVersion();
	}
}

?>
