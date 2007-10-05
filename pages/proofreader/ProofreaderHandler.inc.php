<?php

/**
 * @file ProofreaderHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.proofreader
 * @class ProofreaderHandler
 *
 * Handle requests for proofreader functions. 
 *
 * $Id$
 */

import('submission.proofreader.ProofreaderAction');

class ProofreaderHandler extends Handler {

	/**
	 * Display proofreader index page.
	 */
	function index($args) {
		ProofreaderHandler::validate();
		ProofreaderHandler::setupTemplate();

		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$proofreaderSubmissionDao = &DAORegistry::getDAO('ProofreaderSubmissionDAO');

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

		$submissions = $proofreaderSubmissionDao->getSubmissions($user->getUserId(), $journal->getJournalId(), $searchField, $searchMatch, $search, $dateSearchField, $fromDate, $toDate, $active, $rangeInfo);

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
		$templateMgr->assign('helpTopicId', 'editorial.proofreadersRole.submissions');
		$templateMgr->display('proofreader/index.tpl');
	}

	/**
	 * Validate that user is a proofreader in the selected journal.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate() {
		parent::validate();
		$journal = &Request::getJournal();
		if (!isset($journal) || !Validation::isProofreader($journal->getJournalId())) {
			Validation::redirectLogin();
		}
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $articleId = 0, $parentPage = null, $showSidebar = true) {
		$templateMgr = &TemplateManager::getManager();
		$pageHierarchy = $subclass ? array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'proofreader'), 'user.role.proofreader'))
				: array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'proofreader'), 'user.role.proofreader'));

		import('submission.sectionEditor.SectionEditorAction');
		$submissionCrumb = SectionEditorAction::submissionBreadcrumb($articleId, $parentPage, 'proofreader');
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
		if (!isset($args[0]) || !ProofreaderAction::instructions($args[0], array('proof'))) {
			Request::redirect(null, Request::getRequestedPage());
		}
	}

	//
	// Submission Proofreading
	//

	function submission($args) {
		import('pages.proofreader.SubmissionProofreadHandler');
		SubmissionProofreadHandler::submission($args);
	}

	function completeProofreader($args) {
		import('pages.proofreader.SubmissionProofreadHandler');
		SubmissionProofreadHandler::completeProofreader($args);
	}

	//
	// Submission Comments
	//

	function viewProofreadComments($args) {
		import('pages.proofreader.SubmissionCommentsHandler');
		SubmissionCommentsHandler::viewProofreadComments($args);
	}

	function postProofreadComment() {
		import('pages.proofreader.SubmissionCommentsHandler');
		SubmissionCommentsHandler::postProofreadComment();
	}

	function viewLayoutComments($args) {
		import('pages.proofreader.SubmissionCommentsHandler');
		SubmissionCommentsHandler::viewLayoutComments($args);
	}

	function postLayoutComment() {
		import('pages.proofreader.SubmissionCommentsHandler');
		SubmissionCommentsHandler::postLayoutComment();
	}

	//
	// Misc.
	//

	function downloadFile($args) {
		import('pages.proofreader.SubmissionProofreadHandler');
		SubmissionProofreadHandler::downloadFile($args);
	}

	function viewFile($args) {
		import('pages.proofreader.SubmissionProofreadHandler');
		SubmissionProofreadHandler::viewFile($args);
	}

	function proofGalley($args) {
		import('pages.proofreader.SubmissionProofreadHandler');
		SubmissionProofreadHandler::proofGalley($args);
	}

	function proofGalleyTop($args) {
		import('pages.proofreader.SubmissionProofreadHandler');
		SubmissionProofreadHandler::proofGalleyTop($args);
	}

	function proofGalleyFile($args) {
		import('pages.proofreader.SubmissionProofreadHandler');
		SubmissionProofreadHandler::proofGalleyFile($args);
	}	

	function viewMetadata($args) {
		import('pages.proofreader.SubmissionProofreadHandler');
		SubmissionProofreadHandler::viewMetadata($args);
	}

	function editComment($args) {
		import('pages.proofreader.SubmissionCommentsHandler');
		SubmissionCommentsHandler::editComment($args);
	}

	function deleteComment($args) {
		import('pages.proofreader.SubmissionCommentsHandler');
		SubmissionCommentsHandler::deleteComment($args);
	}

	function saveComment($args) {
		import('pages.proofreader.SubmissionCommentsHandler');
		SubmissionCommentsHandler::saveComment($args);
	}

}

?>
