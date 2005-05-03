<?php

/**
 * SectionEditorHandler.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.sectionEditor
 *
 * Handle requests for section editor functions. 
 *
 * $Id$
 */

import('pages.sectionEditor.SubmissionEditHandler');
import('pages.sectionEditor.SubmissionCommentsHandler');

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

		$submissions = &$sectionEditorSubmissionDao->$functionName($user->getUserId(), $journal->getJournalId(), Request::getUserVar('section'), $sort, Request::getUserVar('order'), $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', $helpTopicId);
		$templateMgr->assign('sectionOptions', array(0 => Locale::Translate('editor.allSections')) + $sections);
		$templateMgr->assign_by_ref('submissions', &$submissions);
		$templateMgr->assign('section', Request::getUserVar('section'));
		$templateMgr->assign('order',$nextOrder);		
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign('sectionEditor', $user->getFullName());

		import('issue.IssueAction');
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
			Validation::redirectLogin();
		}
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $articleId = 0, $parentPage = null, $showSidebar = true) {
		$templateMgr = &TemplateManager::getManager();

		if (Request::getRequestedPage() == 'editor') {
			EditorHandler::setupTemplate(EDITOR_SECTION_SUBMISSIONS, $showSidebar, $articleId, $parentPage);
			$templateMgr->assign('helpTopicId', 'editorial.editorsRole');
			
		} else {
			$templateMgr->assign('helpTopicId', 'editorial.sectionEditorsRole');

			$pageHierarchy = $subclass ? array(array('user', 'navigation.user'), array('sectionEditor', 'user.role.sectionEditor'), array('sectionEditor', 'article.submissions'))
				: array(array('user', 'navigation.user'), array('sectionEditor', 'user.role.sectionEditor'));

			import('submission.sectionEditor.SectionEditorAction');
			$submissionCrumb = SectionEditorAction::submissionBreadcrumb($articleId, $parentPage, 'sectionEditor');
			if (isset($submissionCrumb)) {
				$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
			}
			$templateMgr->assign('pageHierarchy', $pageHierarchy);

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
	}
	
	/**
	 * Display submission management instructions.
	 * @param $args (type)
	 */
	function instructions($args) {
		import('submission.sectionEditor.SectionEditorAction');
		if (!isset($args[0]) || !SectionEditorAction::instructions($args[0])) {
			Request::redirect(Request::getRequestedPage());
		}
	}

	//
	// Submission Tracking
	//

	function enrollSearch($args) {
		SubmissionEditHandler::enrollSearch($args);
	}

	function enroll($args) {
		SubmissionEditHandler::enroll($args);
	}

	function submission($args) {
		SubmissionEditHandler::submission($args);
	}

	function submissionRegrets($args) {
		SubmissionEditHandler::submissionRegrets($args);
	}
	
	function submissionReview($args) {
		SubmissionEditHandler::submissionReview($args);
	}
	
	function submissionEditing($args) {
		SubmissionEditHandler::submissionEditing($args);
	}
	
	function submissionHistory($args) {
		SubmissionEditHandler::submissionHistory($args);
	}
	
	function designateReviewVersion() {
		SubmissionEditHandler::designateReviewVersion();
	}
		
	function changeSection() {
		SubmissionEditHandler::changeSection();
	}
	
	function recordDecision() {
		SubmissionEditHandler::recordDecision();
	}
	
	function selectReviewer($args) {
		SubmissionEditHandler::selectReviewer($args);
	}
	
	function notifyReviewer($args) {
		SubmissionEditHandler::notifyReviewer($args);
	}
	
	function notifyAllReviewers($args) {
		SubmissionEditHandler::notifyAllReviewers($args);
	}
	
	function userProfile($args) {
		SubmissionEditHandler::userProfile($args);
	}
	
	function clearReview($args) {
		SubmissionEditHandler::clearReview($args);
	}
	
	function cancelReview($args) {
		SubmissionEditHandler::cancelReview($args);
	}
	
	function remindReviewer($args) {
		SubmissionEditHandler::remindReviewer($args);
	}

	function thankReviewer($args) {
		SubmissionEditHandler::thankReviewer($args);
	}
	
	function rateReviewer() {
		SubmissionEditHandler::rateReviewer();
	}
	
	function acceptReviewForReviewer($args) {
		SubmissionEditHandler::acceptReviewForReviewer($args);
	}
	
	function enterReviewerRecommendation($args) {
		SubmissionEditHandler::enterReviewerRecommendation($args);
	}
	
	function makeReviewerFileViewable() {
		SubmissionEditHandler::makeReviewerFileViewable();
	}
	
	function setDueDate($args) {
		SubmissionEditHandler::setDueDate($args);
	}
	
	function viewMetadata($args) {
		SubmissionEditHandler::viewMetadata($args);
	}
	
	function saveMetadata() {
		SubmissionEditHandler::saveMetadata();
	}

	function editorReview() {
		SubmissionEditHandler::editorReview();
	}

	function notifyAuthor($args) {
		SubmissionEditHandler::notifyAuthor($args);
	}

	function selectCopyeditor($args) {
		SubmissionEditHandler::selectCopyeditor($args);
	}
	
	function notifyCopyeditor($args) {
		SubmissionEditHandler::notifyCopyeditor($args);
	}
	
	function initiateCopyedit() {
		SubmissionEditHandler::initiateCopyedit();
	}
	
	function thankCopyeditor($args) {
		SubmissionEditHandler::thankCopyeditor($args);
	}

	function notifyAuthorCopyedit($args) {
		SubmissionEditHandler::notifyAuthorCopyedit($args);
	}
	
	function thankAuthorCopyedit($args) {
		SubmissionEditHandler::thankAuthorCopyedit($args);
	}
	
	function notifyFinalCopyedit($args) {
		SubmissionEditHandler::notifyFinalCopyedit($args);
	}
	
	function thankFinalCopyedit($args) {
		SubmissionEditHandler::thankFinalCopyedit($args);
	}
	
	function selectCopyeditRevisions() {
		SubmissionEditHandler::selectCopyeditRevisions();
	}
	
	function uploadReviewVersion() {
		SubmissionEditHandler::uploadReviewVersion();
	}
	
	function uploadCopyeditVersion() {
		SubmissionEditHandler::uploadCopyeditVersion();
	}

	function completeCopyedit($args) {
		SubmissionEditHandler::completeCopyedit($args);
	}
 
	function completeFinalCopyedit($args) {
		SubmissionEditHandler::completeFinalCopyedit($args);
	}

	function addSuppFile($args) {
		SubmissionEditHandler::addSuppFile($args);
	}

	function setSuppFileVisibility($args) {
		SubmissionEditHandler::setSuppFileVisibility($args);
	}

	function editSuppFile($args) {
		SubmissionEditHandler::editSuppFile($args);
	}
	
	function saveSuppFile($args) {
		SubmissionEditHandler::saveSuppFile($args);
	}

	function deleteSuppFile($args) {
		SubmissionEditHandler::deleteSuppFile($args);
	}
	
	function deleteArticleFile($args) {
		SubmissionEditHandler::deleteArticleFile($args);
	}
	
	function archiveSubmission($args) {
		SubmissionEditHandler::archiveSubmission($args);
	}

	function restoreToQueue($args) {
		SubmissionEditHandler::restoreToQueue($args);
	}
	
	function updateSection($args) {
		SubmissionEditHandler::updateSection($args);
	}
	
	
	//
	// Layout Editing
	//
	
	function uploadLayoutFile() {
		SubmissionEditHandler::uploadLayoutFile();
	}
	
	function uploadLayoutVersion() {
		SubmissionEditHandler::uploadLayoutVersion();
	}
	
	function assignLayoutEditor($args) {
		SubmissionEditHandler::assignLayoutEditor($args);
	}
	
	function notifyLayoutEditor($args) {
		SubmissionEditHandler::notifyLayoutEditor($args);
	}
	
	function thankLayoutEditor($args) {
		SubmissionEditHandler::thankLayoutEditor($args);
	}
	
	function uploadGalley() {
		SubmissionEditHandler::uploadGalley();
	}
	
	function editGalley($args) {
		SubmissionEditHandler::editGalley($args);
	}
	
	function saveGalley($args) {
		SubmissionEditHandler::saveGalley($args);
	}
	
	function orderGalley() {
		SubmissionEditHandler::orderGalley();
	}

	function deleteGalley($args) {
		SubmissionEditHandler::deleteGalley($args);
	}
	
	function proofGalley($args) {
		SubmissionEditHandler::proofGalley($args);
	}
	
	function proofGalleyTop($args) {
		SubmissionEditHandler::proofGalleyTop($args);
	}
	
	function proofGalleyFile($args) {
		SubmissionEditHandler::proofGalleyFile($args);
	}	
	
	function uploadSuppFile() {
		SubmissionEditHandler::uploadSuppFile();
	}
	
	function orderSuppFile() {
		SubmissionEditHandler::orderSuppFile();
	}
	
	
	//
	// Submission History
	//

	function submissionEventLog($args) {
		SubmissionEditHandler::submissionEventLog($args);
	}		

	function submissionEventLogType($args) {
		SubmissionEditHandler::submissionEventLogType($args);
	}
	
	function clearSubmissionEventLog($args) {
		SubmissionEditHandler::clearSubmissionEventLog($args);
	}
	
	function submissionEmailLog($args) {
		SubmissionEditHandler::submissionEmailLog($args);
	}
	
	function submissionEmailLogType($args) {
		SubmissionEditHandler::submissionEmailLogType($args);
	}
	
	function clearSubmissionEmailLog($args) {
		SubmissionEditHandler::clearSubmissionEmailLog($args);
	}

	function addSubmissionNote() {
		SubmissionEditHandler::addSubmissionNote();
	}

	function removeSubmissionNote() {
		SubmissionEditHandler::removeSubmissionNote();
	}		

	function updateSubmissionNote() {
		SubmissionEditHandler::updateSubmissionNote();
	}

	function clearAllSubmissionNotes() {
		SubmissionEditHandler::clearAllSubmissionNotes();
	}

	function submissionNotes($args) {
		SubmissionEditHandler::submissionNotes($args);
	}
	
	
	//
	// Misc.
	//

	function downloadFile($args) {
		SubmissionEditHandler::downloadFile($args);
	}
	
	function viewFile($args) {
		SubmissionEditHandler::viewFile($args);
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
	
	function blindCcReviewsToReviewers($args) {
		SubmissionCommentsHandler::blindCcReviewsToReviewers($args);
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
		SubmissionEditHandler::selectProofreader($args);
	}

	function queueForScheduling($args) {
		SubmissionEditHandler::queueForScheduling($args);
	}

	function notifyAuthorProofreader($args) {
		SubmissionEditHandler::notifyAuthorProofreader($args);
	}

	function thankAuthorProofreader($args) {
		SubmissionEditHandler::thankAuthorProofreader($args);	
	}

	function editorInitiateProofreader() {
		SubmissionEditHandler::editorInitiateProofreader();
	}

	function editorCompleteProofreader() {
		SubmissionEditHandler::editorCompleteProofreader();
	}

	function notifyProofreader($args) {
		SubmissionEditHandler::notifyProofreader($args);
	}

	function thankProofreader($args) {
		SubmissionEditHandler::thankProofreader($args);
	}

	function editorInitiateLayoutEditor() {
		SubmissionEditHandler::editorInitiateLayoutEditor();
	}

	function editorCompleteLayoutEditor() {
		SubmissionEditHandler::editorCompleteLayoutEditor();
	}

	function notifyLayoutEditorProofreader($args) {
		SubmissionEditHandler::notifyLayoutEditorProofreader($args);
	}

	function thankLayoutEditorProofreader($args) {
		SubmissionEditHandler::thankLayoutEditorProofreader($args);
	}

}

?>
