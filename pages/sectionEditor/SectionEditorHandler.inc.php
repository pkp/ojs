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
import('pages.sectionEditor.SubmissionCommentsHandler');

class SectionEditorHandler extends Handler {

	/**
	 * Display section editor index page.
	 */
	function index($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate();

		$journal = &Request::getJournal();
		$user = &Request::getUser();

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');

		// sorting list to user specified column
		switch(Request::getUserVar('sort')) {
			case 'submitted':
				$sort = 'date_submitted';
				break;
			default:
				$sort = 'article_id';
		}

		$page = isset($args[0]) ? $args[0] : '';
		$nextOrder = (Request::getUserVar('order') == 'desc') ? 'asc' : 'desc';
		$sections = &$sectionDao->getSectionTitles($journal->getJournalId());

		switch($page) {
			case 'submissionsInEditing':
				$functionName = 'getSectionEditorSubmissionsInEditing';
				break;
			case 'submissionsArchives':
				$functionName = 'getSectionEditorSubmissionsArchives';
				break;
			default:
				$page = 'submissionsInReview';
				$functionName = 'getSectionEditorSubmissionsInReview';
		}

		$submissions = &$sectionEditorSubmissionDao->$functionName($user->getUserId(), $journal->getJournalId(), Request::getUserVar('section'), $sort, Request::getUserVar('order'));

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('sectionOptions', array(0 => Locale::Translate('editor.allSections')) + $sections);
		$templateMgr->assign('submissions', $submissions);
		$templateMgr->assign('section', Request::getUserVar('section'));
		$templateMgr->assign('order',$nextOrder);		
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign('sectionEditor', $user->getFullName());

		$issueAction = new IssueAction();
		$templateMgr->register_function('print_issue_id', array($issueAction, 'smartyPrintIssueId'));

		$templateMgr->display('sectionEditor/index.tpl');
	}

	/**
	 * Validate that user is a section editor in the selected journal.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate() {
		parent::validate();
		$journal = &Request::getJournal();
		// FIXME This is kind of evil
		$page = Request::getRequestedPage();
		if (!isset($journal) || ($page == 'sectionEditor' && !Validation::isSectionEditor($journal->getJournalId())) || ($page == 'editor' && !Validation::isEditor($journal->getJournalId()))) {
			Request::redirect('user');
		}
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $articleId = 0, $parentPage = null, $showSidebar = true) {
		$templateMgr = &TemplateManager::getManager();

		if (Request::getRequestedPage() == 'editor') {
			EditorHandler::setupTemplate($subclass);
			
		} else {
			$templateMgr->assign('pageHierarchy',
				$subclass ? array(array('user', 'navigation.user'), array('sectionEditor', 'sectionEditor.journalSectionEditor'), array('sectionEditor', 'article.submissions'))
					: array(array('user', 'navigation.user'), array('sectionEditor', 'sectionEditor.journalSectionEditor'))
			);
			$templateMgr->assign('pagePath', '/user/sectionEditor');

			if ($showSidebar) {
				$templateMgr->assign('sidebarTemplate', 'sectionEditor/navsidebar.tpl');
				$journal = &Request::getJournal();
				$user = &Request::getUser();

				$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
				$submissionsCount = &$sectionEditorSubmissionDao->getSectionEditorSubmissionsCount($user->getUserId(), $journal->getJournalId());
				$templateMgr->assign('submissionsCount', $submissionsCount);
			}
		}

		if ($articleId) {
			$templateMgr->assign('pageArticleId', $articleId);
			$templateMgr->assign('submissionPageHierarchy', true);
		}

		if ($parentPage) {
			switch($parentPage) {
				case 'summary':
					$parent = array('submission', 'submission.summary');
					break;
				case 'review':
					$parent = array('submissionReview', 'submission.review');
					break;
				case 'editing':
					$parent = array('submissionEditing', 'submission.editing');
					break;
				case 'history':
					$parent = array('submissionHistory', 'submission.history');
					break;
			}
			$templateMgr->assign('parentPage', $parent);
		}

	}
	
	//
	// Submission Tracking
	//

	function enrollSearch($args) {
		TrackSubmissionHandler::enrollSearch($args);
	}

	function enroll($args) {
		TrackSubmissionHandler::enroll($args);
	}

	function submission($args) {
		TrackSubmissionHandler::submission($args);
	}

	function submissionRegrets($args) {
		TrackSubmissionHandler::submissionRegrets($args);
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
	
	function reinitiateReview($args) {
		TrackSubmissionHandler::reinitiateReview($args);
	}
	
	function notifyReviewer($args) {
		TrackSubmissionHandler::notifyReviewer($args);
	}
	
	function userProfile($args) {
		TrackSubmissionHandler::userProfile($args);
	}
	
	function clearReview($args) {
		TrackSubmissionHandler::clearReview($args);
	}
	
	function cancelReview($args) {
		TrackSubmissionHandler::cancelReview($args);
	}
	
	function remindReviewer($args) {
		TrackSubmissionHandler::remindReviewer($args);
	}

	function thankReviewer($args) {
		TrackSubmissionHandler::thankReviewer($args);
	}
	
	function rateReviewer() {
		TrackSubmissionHandler::rateReviewer();
	}
	
	function acceptReviewForReviewer($args) {
		TrackSubmissionHandler::acceptReviewForReviewer($args);
	}
	
	function enterReviewerRecommendation($args) {
		TrackSubmissionHandler::enterReviewerRecommendation($args);
	}
	
	function makeReviewerFileViewable() {
		TrackSubmissionHandler::makeReviewerFileViewable();
	}
	
	function setDueDate($args) {
		TrackSubmissionHandler::setDueDate($args);
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

	function notifyAuthor($args) {
		TrackSubmissionHandler::notifyAuthor($args);
	}

	function selectCopyeditor($args) {
		TrackSubmissionHandler::selectCopyeditor($args);
	}
	
	function notifyCopyeditor($args) {
		TrackSubmissionHandler::notifyCopyeditor($args);
	}
	
	function initiateCopyedit() {
		TrackSubmissionHandler::initiateCopyedit();
	}
	
	function thankCopyeditor($args) {
		TrackSubmissionHandler::thankCopyeditor($args);
	}

	function notifyAuthorCopyedit($args) {
		TrackSubmissionHandler::notifyAuthorCopyedit($args);
	}
	
	function thankAuthorCopyedit($args) {
		TrackSubmissionHandler::thankAuthorCopyedit($args);
	}
	
	function notifyFinalCopyedit($args) {
		TrackSubmissionHandler::notifyFinalCopyedit($args);
	}
	
	function thankFinalCopyedit($args) {
		TrackSubmissionHandler::thankFinalCopyedit($args);
	}
	
	function selectCopyeditRevisions() {
		TrackSubmissionHandler::selectCopyeditRevisions();
	}
	
	function uploadReviewVersion() {
		TrackSubmissionHandler::uploadReviewVersion();
	}
	
	function uploadCopyeditVersion() {
		TrackSubmissionHandler::uploadCopyeditVersion();
	}

	function addSuppFile($args) {
		TrackSubmissionHandler::addSuppFile($args);
	}

	function editSuppFile($args) {
		TrackSubmissionHandler::editSuppFile($args);
	}
	
	function saveSuppFile($args) {
		TrackSubmissionHandler::saveSuppFile($args);
	}

	function deleteSuppFile($args) {
		TrackSubmissionHandler::deleteSuppFile($args);
	}
	
	function deleteArticleFile($args) {
		TrackSubmissionHandler::deleteArticleFile($args);
	}
	
	function archiveSubmission($args) {
		TrackSubmissionHandler::archiveSubmission($args);
	}

	function restoreToQueue($args) {
		TrackSubmissionHandler::restoreToQueue($args);
	}
	
	function updateSection($args) {
		TrackSubmissionHandler::updateSection($args);
	}
	
	
	//
	// Layout Editing
	//
	
	function uploadLayoutFile() {
		TrackSubmissionHandler::uploadLayoutFile();
	}
	
	function uploadLayoutVersion() {
		TrackSubmissionHandler::uploadLayoutVersion();
	}
	
	function assignLayoutEditor($args) {
		TrackSubmissionHandler::assignLayoutEditor($args);
	}
	
	function notifyLayoutEditor($args) {
		TrackSubmissionHandler::notifyLayoutEditor($args);
	}
	
	function thankLayoutEditor($args) {
		TrackSubmissionHandler::thankLayoutEditor($args);
	}
	
	function uploadGalley() {
		TrackSubmissionHandler::uploadGalley();
	}
	
	function editGalley($args) {
		TrackSubmissionHandler::editGalley($args);
	}
	
	function saveGalley($args) {
		TrackSubmissionHandler::saveGalley($args);
	}
	
	function orderGalley() {
		TrackSubmissionHandler::orderGalley();
	}

	function deleteGalley($args) {
		TrackSubmissionHandler::deleteGalley($args);
	}
	
	function proofGalley($args) {
		TrackSubmissionHandler::proofGalley($args);
	}
	
	function proofGalleyTop($args) {
		TrackSubmissionHandler::proofGalleyTop($args);
	}
	
	function proofGalleyFile($args) {
		TrackSubmissionHandler::proofGalleyFile($args);
	}	
	
	function uploadSuppFile() {
		TrackSubmissionHandler::uploadSuppFile();
	}
	
	function orderSuppFile() {
		TrackSubmissionHandler::orderSuppFile();
	}
	
	
	//
	// Submission History
	//

	function submissionEventLog($args) {
		TrackSubmissionHandler::submissionEventLog($args);
	}		

	function submissionEventLogType($args) {
		TrackSubmissionHandler::submissionEventLogType($args);
	}
	
	function clearSubmissionEventLog($args) {
		TrackSubmissionHandler::clearSubmissionEventLog($args);
	}
	
	function submissionEmailLog($args) {
		TrackSubmissionHandler::submissionEmailLog($args);
	}
	
	function submissionEmailLogType($args) {
		TrackSubmissionHandler::submissionEmailLogType($args);
	}
	
	function clearSubmissionEmailLog($args) {
		TrackSubmissionHandler::clearSubmissionEmailLog($args);
	}

	function addSubmissionNote() {
		TrackSubmissionHandler::addSubmissionNote();
	}

	function removeSubmissionNote() {
		TrackSubmissionHandler::removeSubmissionNote();
	}		

	function updateSubmissionNote() {
		TrackSubmissionHandler::updateSubmissionNote();
	}

	function clearAllSubmissionNotes() {
		TrackSubmissionHandler::clearAllSubmissionNotes();
	}

	function submissionNotes($args) {
		TrackSubmissionHandler::submissionNotes($args);
	}
	
	
	//
	// Misc.
	//

	function downloadFile($args) {
		TrackSubmissionHandler::downloadFile($args);
	}
	
	function viewFile($args) {
		TrackSubmissionHandler::viewFile($args);
	}
	
	//
	// Submission Comments
	//
	
	function viewPeerReviewComments($args) {
		SubmissionCommentsHandler::viewPeerReviewComments($args);
	}
	
	function postPeerReviewComment() {
		SubmissionCommentsHandler::postPeerReviewComment();
	}
	
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
	
	function viewLayoutComments($args) {
		SubmissionCommentsHandler::viewLayoutComments($args);
	}
	
	function postLayoutComment() {
		SubmissionCommentsHandler::postLayoutComment();
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
	
	function importPeerReviews() {
		SubmissionCommentsHandler::importPeerReviews();
	}

	/** Proof Assignment Functions */
	function selectProofreader($args) {
		TrackSubmissionHandler::selectProofreader($args);
	}

	function queueForScheduling($args) {
		TrackSubmissionHandler::queueForScheduling($args);
	}

	function notifyAuthorProofreader($args) {
		TrackSubmissionHandler::notifyAuthorProofreader($args);
	}

	function thankAuthorProofreader($args) {
		TrackSubmissionHandler::thankAuthorProofreader($args);	
	}

	function editorInitiateProofreader() {
		TrackSubmissionHandler::editorInitiateProofreader();
	}

	function editorCompleteProofreader() {
		TrackSubmissionHandler::editorCompleteProofreader();
	}

	function notifyProofreader($args) {
		TrackSubmissionHandler::notifyProofreader($args);
	}

	function thankProofreader($args) {
		TrackSubmissionHandler::thankProofreader($args);
	}

	function editorInitiateLayoutEditor() {
		TrackSubmissionHandler::editorInitiateLayoutEditor();
	}

	function editorCompleteLayoutEditor() {
		TrackSubmissionHandler::editorCompleteLayoutEditor();
	}

	function notifyLayoutEditorProofreader($args) {
		TrackSubmissionHandler::notifyLayoutEditorProofreader($args);
	}

	function thankLayoutEditorProofreader($args) {
		TrackSubmissionHandler::thankLayoutEditorProofreader($args);
	}

}

?>
