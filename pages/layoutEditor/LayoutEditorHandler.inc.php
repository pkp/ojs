<?php

/**
 * LayoutEditorHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.layoutEditor
 *
 * Handle requests for layout editor functions. 
 *
 * $Id$
 */

import('pages.layoutEditor.SubmissionLayoutHandler');
import('pages.layoutEditor.SubmissionCommentsHandler');

import('submission.layoutEditor.LayoutEditorAction');

class LayoutEditorHandler extends Handler {

	/**
	 * Display editor index page.
	 */
	function index($args) {
		LayoutEditorHandler::validate();
		LayoutEditorHandler::setupTemplate();

		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$layoutEditorSubmissionDao = &DAORegistry::getDAO('LayoutEditorSubmissionDAO');

		$page = isset($args[0]) ? $args[0] : '';
		switch($page) {
			case 'completed':
				$active = false;
				break;
			default:
				$page = 'active';
				$active = true;
		}
		$rangeInfo = Handler::getRangeInfo('submissions');
		$submissions = $layoutEditorSubmissionDao->getSubmissions($user->getUserId(), $journal->getJournalId(), $active, $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign_by_ref('submissions', &$submissions);

		import('issue.IssueAction');
		$issueAction = new IssueAction();
		$templateMgr->register_function('print_issue_id', array($issueAction, 'smartyPrintIssueId'));
		$templateMgr->assign('helpTopicId', 'editorial.layoutEditorsRole.submissions');
		$templateMgr->display('layoutEditor/index.tpl');
	}
	
	/**
	 * Validate that user is a layout editor in the selected journal.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate() {
		parent::validate();
		$journal = &Request::getJournal();
		if (!isset($journal) || !Validation::isLayoutEditor($journal->getJournalId())) {
			Validation::redirectLogin();
		}
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $articleId = 0, $parentPage = null, $showSidebar = true) {
		$templateMgr = &TemplateManager::getManager();
		$pageHierarchy = $subclass ? array(array('user', 'navigation.user'), array('layoutEditor', 'user.role.layoutEditor'))
				: array(array('user', 'navigation.user'), array('layoutEditor', 'user.role.layoutEditor'));
		$templateMgr->assign('pagePath', '/user/layoutEditor');

		import('submission.sectionEditor.SectionEditorAction');
		$submissionCrumb = SectionEditorAction::submissionBreadcrumb($articleId, $parentPage, 'layoutEditor');
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);

		if ($showSidebar) {
			$templateMgr->assign('sidebarTemplate', 'layoutEditor/navsidebar.tpl');

			$journal = &Request::getJournal();
			$user = &Request::getUser();
			$layoutEditorSubmissionDao = &DAORegistry::getDAO('LayoutEditorSubmissionDAO');
			$submissionsCount = $layoutEditorSubmissionDao->getSubmissionsCount($user->getUserId(), $journal->getJournalId());
			$templateMgr->assign('submissionsCount', $submissionsCount);
		}

	}
	
	/**
	 * Display submission management instructions.
	 * @param $args (type)
	 */
	function instructions($args) {
		import('submission.proofreader.ProofreaderAction');
		if (!isset($args[0]) || !ProofreaderAction::instructions($args[0], array('layout', 'proof'))) {
			Request::redirect(Request::getRequestedPage());
		}
	}
	
	
	//
	// Submission Layout Editing
	//
	
	function submission($args) {
		SubmissionLayoutHandler::submission($args);
	}
	
	function submissionEditing($args) {
		SubmissionLayoutHandler::submission($args);
	}
	
	function completeAssignment($args) {
		SubmissionLayoutHandler::completeAssignment($args);
	}
	
	function uploadGalley() {
		SubmissionLayoutHandler::uploadGalley();
	}
	
	function editGalley($args) {
		SubmissionLayoutHandler::editGalley($args);
	}
	
	function saveGalley($args) {
		SubmissionLayoutHandler::saveGalley($args);
	}

	function deleteGalley($args) {
		SubmissionLayoutHandler::deleteGalley($args);
	}
	
	function orderGalley() {
		SubmissionLayoutHandler::orderGalley();
	}
	
	function proofGalley($args) {
		SubmissionLayoutHandler::proofGalley($args);
	}
	
	function proofGalleyTop($args) {
		SubmissionLayoutHandler::proofGalleyTop($args);
	}
	
	function proofGalleyFile($args) {
		SubmissionLayoutHandler::proofGalleyFile($args);
	}
	
	function uploadSuppFile() {
		SubmissionLayoutHandler::uploadSuppFile();
	}

	function editSuppFile($args) {
		SubmissionLayoutHandler::editSuppFile($args);
	}
	
	function saveSuppFile($args) {
		SubmissionLayoutHandler::saveSuppFile($args);
	}

	function deleteSuppFile($args) {
		SubmissionLayoutHandler::deleteSuppFile($args);
	}
	
	function orderSuppFile() {
		SubmissionLayoutHandler::orderSuppFile();
	}
	
	function downloadFile($args) {
		SubmissionLayoutHandler::downloadFile($args);
	}
	
	function viewFile($args) {
		SubmissionLayoutHandler::viewFile($args);
	}

	//
	// Proofreading Actions
	//

	function layoutEditorProofreadingComplete($args) {
		SubmissionLayoutHandler::layoutEditorProofreadingComplete($args);
	}
	
	
	//
	// Submission Comments
	//
	
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
	
}

?>
