<?php

/**
 * @file CopyeditorHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.copyeditor
 * @class CopyeditorHandler
 *
 * Handle requests for copyeditor functions. 
 *
 * $Id$
 */

import ('submission.copyeditor.CopyeditorAction');

class CopyeditorHandler extends Handler {

	/**
	 * Display copyeditor index page.
	 */
	function index($args) {
		CopyeditorHandler::validate();
		CopyeditorHandler::setupTemplate();

		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');

		// Get the user's search conditions, if any
		$searchField = Request::getUserVar('searchField');
		$dateSearchField = Request::getUserVar('dateSearchField');
		$searchMatch = Request::getUserVar('searchMatch');
		$search = Request::getUserVar('search');

		$fromDate = Request::getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = Request::getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		$rangeInfo = Handler::getRangeInfo('submissions');

		$page = isset($args[0]) ? $args[0] : '';
		switch($page) {
			case 'completed':
				$active = false;
				break;
			default:
				$page = 'active';
				$active = true;
		}

		$submissions = $copyeditorSubmissionDao->getCopyeditorSubmissionsByCopyeditorId($user->getUserId(), $journal->getJournalId(), $searchField, $searchMatch, $search, $dateSearchField, $fromDate, $toDate, $active, $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign_by_ref('submissions', $submissions);

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
		$templateMgr->assign('helpTopicId', 'editorial.copyeditorsRole.submissions');
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
			Validation::redirectLogin();
		}
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $articleId = 0, $parentPage = null) {
		$templateMgr = &TemplateManager::getManager();
		$pageHierarchy = $subclass ? array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'copyeditor'), 'user.role.copyeditor'))
				: array(array('user', 'navigation.user'), array('copyeditor', 'user.role.copyeditor'));

		import('submission.sectionEditor.SectionEditorAction');
		$submissionCrumb = SectionEditorAction::submissionBreadcrumb($articleId, $parentPage, 'copyeditor');
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}

	/**
	 * Display submission management instructions.
	 * @param $args (type)
	 */
	function instructions($args) {
		import('submission.proofreader.ProofreaderAction');
		if (!isset($args[0]) || !ProofreaderAction::instructions($args[0], array('copy'))) {
			Request::redirect(null, Request::getRequestedPage());
		}
	}

	//
	// Assignment Tracking
	//

	function submission($args) {
		import('pages.copyeditor.SubmissionCopyeditHandler');
		SubmissionCopyeditHandler::submission($args);
	}

	function completeCopyedit($args) {
		import('pages.copyeditor.SubmissionCopyeditHandler');
		SubmissionCopyeditHandler::completeCopyedit($args);
	}

	function completeFinalCopyedit($args) {
		import('pages.copyeditor.SubmissionCopyeditHandler');
		SubmissionCopyeditHandler::completeFinalCopyedit($args);
	}

	function uploadCopyeditVersion() {
		import('pages.copyeditor.SubmissionCopyeditHandler');
		SubmissionCopyeditHandler::uploadCopyeditVersion();
	}

	//
	// Misc.
	//

	function downloadFile($args) {
		import('pages.copyeditor.SubmissionCopyeditHandler');
		SubmissionCopyeditHandler::downloadFile($args);
	}

	function viewFile($args) {
		import('pages.copyeditor.SubmissionCopyeditHandler');
		SubmissionCopyeditHandler::viewFile($args);
	}

	//
	// Submission Comments
	//


	function viewLayoutComments($args) {
		import('pages.copyeditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::viewLayoutComments($args);
	}

	function postLayoutComment() {
		import('pages.copyeditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::postLayoutComment();
	}

	function viewCopyeditComments($args) {
		import('pages.copyeditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::viewCopyeditComments($args);
	}

	function postCopyeditComment() {
		import('pages.copyeditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::postCopyeditComment();
	}

	function editComment($args) {
		import('pages.copyeditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::editComment($args);
	}

	function saveComment() {
		import('pages.copyeditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::saveComment();
	}

	function deleteComment($args) {
		import('pages.copyeditor.SubmissionCommentsHandler');
		SubmissionCommentsHandler::deleteComment($args);
	}

	//
	// Proofreading Actions
	//
	function authorProofreadingComplete($args) {
		import('pages.copyeditor.SubmissionCopyeditHandler');
		SubmissionCopyeditHandler::authorProofreadingComplete($args);
	}

	function proofGalley($args) {
		import('pages.copyeditor.SubmissionCopyeditHandler');
		SubmissionCopyeditHandler::proofGalley($args);
	}

	function proofGalleyTop($args) {
		import('pages.copyeditor.SubmissionCopyeditHandler');
		SubmissionCopyeditHandler::proofGalleyTop($args);
	}

	function proofGalleyFile($args) {
		import('pages.copyeditor.SubmissionCopyeditHandler');
		SubmissionCopyeditHandler::proofGalleyFile($args);
	}	

	//
	// Metadata Actions
	//
	function viewMetadata($args) {
		import('pages.copyeditor.SubmissionCopyeditHandler');
		SubmissionCopyeditHandler::viewMetadata($args);
	}

	function saveMetadata($args) {
		import('pages.copyeditor.SubmissionCopyeditHandler');
		SubmissionCopyeditHandler::saveMetadata($args);
	}

	function removeArticleCoverPage($args) {
		import('pages.copyeditor.SubmissionCopyeditHandler');
		SubmissionCopyeditHandler::removeCoverPage($args);
	}

}

?>
