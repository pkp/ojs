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
		$templateMgr->assign('pagePath', '/user/sectionEditor');
	}
	
	//
	// Submission Tracking
	//

	function assignments($args) {
		TrackSubmissionHandler::assignments($args);
	}
	
	function summary($args) {
		TrackSubmissionHandler::summary($args);
	}
	
	function submission($args) {
		TrackSubmissionHandler::submission($args);
	}

	function submissionReview($args) {
		TrackSubmissionHandler::submissionReview($args);
	}
	
	function submissionEditing($args) {
		TrackSubmissionHandler::submissionEditing($args);
	}
	
	function submissionHistory($args) {
		TrackSubmissionHandler::submissionHistory($args);
	}
	
	function designateReviewVersion() {
		TrackSubmissionHandler::designateReviewVersion();
	}
		
	function changeSection() {
		TrackSubmissionHandler::changeSection();
	}
	
	function recordDecision() {
		TrackSubmissionHandler::recordDecision();
	}
	
	function selectReviewer($args) {
		TrackSubmissionHandler::selectReviewer($args);
	}
	
	function notifyReviewer($args) {
		TrackSubmissionHandler::notifyReviewer($args);
	}
	
	function initiateReview() {
		TrackSubmissionHandler::initiateReview();
	}
	
	function reinitiateReview() {
		TrackSubmissionHandler::reinitiateReview();
	}
	
	function initiateAllReviews() {
		TrackSubmissionHandler::initiateAllReviews();
	}
	
	function cancelReview() {
		TrackSubmissionHandler::cancelReview();
	}
	
	function removeReview() {
		TrackSubmissionHandler::removeReview();
	}

	function remindReviewer($args) {
		TrackSubmissionHandler::remindReviewer($args);
	}

	function replaceReviewer($args) {
		TrackSubmissionHandler::replaceReviewer($args);
	}
	
	function rateReviewer() {
		TrackSubmissionHandler::rateReviewer();
	}
	
	function makeReviewerFileViewable() {
		TrackSubmissionHandler::makeReviewerFileViewable();
	}
	
	function setDueDate($args) {
		TrackSubmissionHandler::setDueDate($args);
	}
	
	function enterReviewerRecommendation($args) {
		TrackSubmissionHandler::enterReviewerRecommendation($args);
	}
	
	function viewMetadata($args) {
		TrackSubmissionHandler::viewMetadata($args);
	}
	
	function saveMetadata() {
		TrackSubmissionHandler::saveMetadata();
	}

	function editorReview() {
		TrackSubmissionHandler::editorReview();
	}

	function selectCopyeditor($args) {
		TrackSubmissionHandler::selectCopyeditor($args);
	}
	
	function notifyCopyeditor() {
		TrackSubmissionHandler::notifyCopyeditor();
	}
	
	function thankCopyeditor() {
		TrackSubmissionHandler::thankCopyeditor();
	}

	function notifyAuthorCopyedit() {
		TrackSubmissionHandler::notifyAuthorCopyedit();
	}
	
	function thankAuthorCopyedit() {
		TrackSubmissionHandler::thankAuthorCopyedit();
	}
	
	function initiateFinalCopyedit() {
		TrackSubmissionHandler::initiateFinalCopyedit();
	}
	
	function thankFinalCopyedit() {
		TrackSubmissionHandler::thankFinalCopyedit();
	}
	
	function uploadReviewVersion() {
		TrackSubmissionHandler::uploadReviewVersion();
	}

	function uploadPostReviewArticle() {
		TrackSubmissionHandler::uploadPostReviewArticle();
	}
	
	function addSuppFile($args) {
		TrackSubmissionHandler::addSuppFile($args);
	}
	
	function saveSuppFile($args) {
		TrackSubmissionHandler::saveSuppFile($args);
	}
	
}

?>
