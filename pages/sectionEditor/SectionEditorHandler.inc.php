<?php

/**
 * @file SectionEditorHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.sectionEditor
 * @class SectionEditorHandler
 *
 * Handle requests for section editor functions. 
 *
 * $Id$
 */

import('submission.sectionEditor.SectionEditorAction');

class SectionEditorHandler extends Handler {

	/**
	 * Display section editor index page.
	 */
	function index($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate();

		$journal = &Request::getJournal();
		$user = &Request::getUser();

		$rangeInfo = Handler::getRangeInfo('submissions');

		// Get the user's search conditions, if any
		$searchField = Request::getUserVar('searchField');
		$dateSearchField = Request::getUserVar('dateSearchField');
		$searchMatch = Request::getUserVar('searchMatch');
		$search = Request::getUserVar('search');

		$fromDate = Request::getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = Request::getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');

		$page = isset($args[0]) ? $args[0] : '';
		$sections = &$sectionDao->getSectionTitles($journal->getJournalId());

		switch($page) {
			case 'submissionsInEditing':
				$functionName = 'getSectionEditorSubmissionsInEditing';
				$helpTopicId = 'editorial.sectionEditorsRole.submissions.inEditing';
				break;
			case 'submissionsArchives':
				$functionName = 'getSectionEditorSubmissionsArchives';
				$helpTopicId = 'editorial.sectionEditorsRole.submissions.archives';
				break;
			default:
				$page = 'submissionsInReview';
				$functionName = 'getSectionEditorSubmissionsInReview';
				$helpTopicId = 'editorial.sectionEditorsRole.submissions.inReview';
		}

		$submissions = &$sectionEditorSubmissionDao->$functionName(
			$user->getUserId(),
			$journal->getJournalId(),
			Request::getUserVar('section'),
			$searchField,
			$searchMatch,
			$search,
			$dateSearchField,
			$fromDate,
			$toDate,
			$rangeInfo
		);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', $helpTopicId);
		$templateMgr->assign('sectionOptions', array(0 => Locale::Translate('editor.allSections')) + $sections);
		$templateMgr->assign_by_ref('submissions', $submissions);
		$templateMgr->assign('section', Request::getUserVar('section'));
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign('sectionEditor', $user->getFullName());

		// Set search parameters
		$duplicateParameters = array(
			'searchField', 'searchMatch', 'search',
			'dateFromMonth', 'dateFromDay', 'dateFromYear',
			'dateToMonth', 'dateToDay', 'dateToYear',
			'dateSearchField'
		);
		foreach ($duplicateParameters as $param)
			$templateMgr->assign($param, Request::getUserVar($param));

		$templateMgr->assign('dateFrom', $fromDate);
		$templateMgr->assign('dateTo', $toDate);
		$templateMgr->assign('fieldOptions', Array(
			SUBMISSION_FIELD_TITLE => 'article.title',
			SUBMISSION_FIELD_AUTHOR => 'user.role.author',
			SUBMISSION_FIELD_EDITOR => 'user.role.editor'
		));
		$templateMgr->assign('dateFieldOptions', Array(
			SUBMISSION_FIELD_DATE_SUBMITTED => 'submissions.submitted',
			SUBMISSION_FIELD_DATE_COPYEDIT_COMPLETE => 'submissions.copyeditComplete',
			SUBMISSION_FIELD_DATE_LAYOUT_COMPLETE => 'submissions.layoutComplete',
			SUBMISSION_FIELD_DATE_PROOFREADING_COMPLETE => 'submissions.proofreadingComplete'
		));

		import('issue.IssueAction');
		$issueAction = &new IssueAction();
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
			Validation::redirectLogin();
		}
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $articleId = 0, $parentPage = null, $showSidebar = true) {
		$templateMgr = &TemplateManager::getManager();
		$isEditor = Validation::isEditor();

		if (Request::getRequestedPage() == 'editor') {
			EditorHandler::setupTemplate(EDITOR_SECTION_SUBMISSIONS, $articleId, $parentPage);
			$templateMgr->assign('helpTopicId', 'editorial.editorsRole');

		} else {
			$templateMgr->assign('helpTopicId', 'editorial.sectionEditorsRole');

			$pageHierarchy = $subclass ? array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, $isEditor?'editor':'sectionEditor'), $isEditor?'user.role.editor':'user.role.sectionEditor'), array(Request::url(null, $isEditor?'editor':'sectionEditor'), 'article.submissions'))
				: array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, $isEditor?'editor':'sectionEditor'), $isEditor?'user.role.editor':'user.role.sectionEditor'));

			import('submission.sectionEditor.SectionEditorAction');
			$submissionCrumb = SectionEditorAction::submissionBreadcrumb($articleId, $parentPage, 'sectionEditor');
			if (isset($submissionCrumb)) {
				$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
			}
			$templateMgr->assign('pageHierarchy', $pageHierarchy);
		}
	}

	/**
	 * Display submission management instructions.
	 * @param $args (type)
	 */
	function instructions($args) {
		import('submission.sectionEditor.SectionEditorAction');
		if (!isset($args[0]) || !SectionEditorAction::instructions($args[0])) {
			Request::redirect(null, Request::getRequestedPage());
		}
	}

	//
	// Submission Tracking
	//

	function enrollSearch($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::enrollSearch($args);
	}

	function createReviewer($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::createReviewer($args);
	}

	function suggestUsername() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::suggestUsername();
	}

	function enroll($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::enroll($args);
	}

	function submission($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::submission($args);
	}

	function submissionRegrets($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::submissionRegrets($args);
	}

	function submissionReview($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::submissionReview($args);
	}

	function submissionEditing($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::submissionEditing($args);
	}

	function submissionHistory($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::submissionHistory($args);
	}

	function changeSection() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::changeSection();
	}

	function recordDecision() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::recordDecision();
	}

	function selectReviewer($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::selectReviewer($args);
	}

	function notifyReviewer($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::notifyReviewer($args);
	}

	function notifyAllReviewers($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::notifyAllReviewers($args);
	}

	function userProfile($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::userProfile($args);
	}

	function clearReview($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::clearReview($args);
	}

	function cancelReview($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::cancelReview($args);
	}

	function remindReviewer($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::remindReviewer($args);
	}

	function thankReviewer($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::thankReviewer($args);
	}

	function rateReviewer() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::rateReviewer();
	}

	function confirmReviewForReviewer($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::confirmReviewForReviewer($args);
	}

	function uploadReviewForReviewer($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::uploadReviewForReviewer($args);
	}

	function enterReviewerRecommendation($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::enterReviewerRecommendation($args);
	}

	function makeReviewerFileViewable() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::makeReviewerFileViewable();
	}

	function setDueDate($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::setDueDate($args);
	}

	function viewMetadata($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::viewMetadata($args);
	}

	function saveMetadata() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::saveMetadata();
	}

	function editorReview() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::editorReview();
	}

	function selectCopyeditor($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::selectCopyeditor($args);
	}

	function notifyCopyeditor($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::notifyCopyeditor($args);
	}

	function initiateCopyedit() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::initiateCopyedit();
	}

	function thankCopyeditor($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::thankCopyeditor($args);
	}

	function notifyAuthorCopyedit($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::notifyAuthorCopyedit($args);
	}

	function thankAuthorCopyedit($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::thankAuthorCopyedit($args);
	}

	function notifyFinalCopyedit($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::notifyFinalCopyedit($args);
	}

	function thankFinalCopyedit($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::thankFinalCopyedit($args);
	}

	function selectCopyeditRevisions() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::selectCopyeditRevisions();
	}

	function uploadReviewVersion() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::uploadReviewVersion();
	}

	function uploadCopyeditVersion() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::uploadCopyeditVersion();
	}

	function completeCopyedit($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::completeCopyedit($args);
	}

	function completeFinalCopyedit($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::completeFinalCopyedit($args);
	}

	function addSuppFile($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::addSuppFile($args);
	}

	function setSuppFileVisibility($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::setSuppFileVisibility($args);
	}

	function editSuppFile($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::editSuppFile($args);
	}

	function saveSuppFile($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::saveSuppFile($args);
	}

	function deleteSuppFile($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::deleteSuppFile($args);
	}

	function deleteArticleFile($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::deleteArticleFile($args);
	}

	function archiveSubmission($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::archiveSubmission($args);
	}

	function unsuitableSubmission($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::unsuitableSubmission($args);
	}

	function restoreToQueue($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::restoreToQueue($args);
	}

	function updateSection($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::updateSection($args);
	}


	//
	// Layout Editing
	//

	function deleteArticleImage($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::deleteArticleImage($args);
	}

	function uploadLayoutFile() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::uploadLayoutFile();
	}

	function uploadLayoutVersion() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::uploadLayoutVersion();
	}

	function assignLayoutEditor($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::assignLayoutEditor($args);
	}

	function notifyLayoutEditor($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::notifyLayoutEditor($args);
	}

	function thankLayoutEditor($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::thankLayoutEditor($args);
	}

	function uploadGalley() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::uploadGalley();
	}

	function editGalley($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::editGalley($args);
	}

	function saveGalley($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::saveGalley($args);
	}

	function orderGalley() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::orderGalley();
	}

	function deleteGalley($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::deleteGalley($args);
	}

	function proofGalley($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::proofGalley($args);
	}

	function proofGalleyTop($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::proofGalleyTop($args);
	}

	function proofGalleyFile($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::proofGalleyFile($args);
	}	

	function uploadSuppFile() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::uploadSuppFile();
	}

	function orderSuppFile() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::orderSuppFile();
	}


	//
	// Submission History
	//

	function submissionEventLog($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::submissionEventLog($args);
	}		

	function submissionEventLogType($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::submissionEventLogType($args);
	}

	function clearSubmissionEventLog($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::clearSubmissionEventLog($args);
	}

	function submissionEmailLog($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::submissionEmailLog($args);
	}

	function submissionEmailLogType($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::submissionEmailLogType($args);
	}

	function clearSubmissionEmailLog($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::clearSubmissionEmailLog($args);
	}

	function addSubmissionNote() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::addSubmissionNote();
	}

	function removeSubmissionNote() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::removeSubmissionNote();
	}		

	function updateSubmissionNote() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::updateSubmissionNote();
	}

	function clearAllSubmissionNotes() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::clearAllSubmissionNotes();
	}

	function submissionNotes($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::submissionNotes($args);
	}


	//
	// Misc.
	//

	function downloadFile($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::downloadFile($args);
	}

	function viewFile($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::viewFile($args);
	}

	//
	// Submission Comments
	//

	function viewPeerReviewComments($args) {
		import('pages.sectionEditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::viewPeerReviewComments($args);
	}

	function postPeerReviewComment() {
		import('pages.sectionEditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::postPeerReviewComment();
	}

	function viewEditorDecisionComments($args) {
		import('pages.sectionEditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::viewEditorDecisionComments($args);
	}

	function blindCcReviewsToReviewers($args) {
		import('pages.sectionEditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::blindCcReviewsToReviewers($args);
	}

	function postEditorDecisionComment() {
		import('pages.sectionEditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::postEditorDecisionComment();
	}

	function viewCopyeditComments($args) {
		import('pages.sectionEditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::viewCopyeditComments($args);
	}

	function postCopyeditComment() {
		import('pages.sectionEditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::postCopyeditComment();
	}

	function emailEditorDecisionComment() {
		import('pages.sectionEditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::emailEditorDecisionComment();
	}

	function viewLayoutComments($args) {
		import('pages.sectionEditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::viewLayoutComments($args);
	}

	function postLayoutComment() {
		import('pages.sectionEditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::postLayoutComment();
	}

	function viewProofreadComments($args) {
		import('pages.sectionEditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::viewProofreadComments($args);
	}

	function postProofreadComment() {
		import('pages.sectionEditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::postProofreadComment();
	}

	function editComment($args) {
		import('pages.sectionEditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::editComment($args);
	}

	function saveComment() {
		import('pages.sectionEditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::saveComment();
	}

	function deleteComment($args) {
		import('pages.sectionEditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::deleteComment($args);
	}

	/** Proof Assignment Functions */
	function selectProofreader($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::selectProofreader($args);
	}

	function notifyAuthorProofreader($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::notifyAuthorProofreader($args);
	}

	function thankAuthorProofreader($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::thankAuthorProofreader($args);	
	}

	function editorInitiateProofreader() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::editorInitiateProofreader();
	}

	function editorCompleteProofreader() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::editorCompleteProofreader();
	}

	function notifyProofreader($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::notifyProofreader($args);
	}

	function thankProofreader($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::thankProofreader($args);
	}

	function editorInitiateLayoutEditor() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::editorInitiateLayoutEditor();
	}

	function editorCompleteLayoutEditor() {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::editorCompleteLayoutEditor();
	}

	function notifyLayoutEditorProofreader($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::notifyLayoutEditorProofreader($args);
	}

	function thankLayoutEditorProofreader($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::thankLayoutEditorProofreader($args);
	}

	/**
	 * Scheduling functions
	 */

	function scheduleForPublication($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::scheduleForPublication($args);
	}
	
	/**
	 * Payments
	 */
	 function payPublicationFee($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::payPublicationFee($args);
	 }

	 function waivePublicationFee($args) {
		import('pages.sectionEditor.SubmissionEditHandler');
		SubmissionEditHandler::waivePublicationFee($args);
	 }
}

?>
