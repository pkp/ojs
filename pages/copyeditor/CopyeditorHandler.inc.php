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
	function index($args) {
		CopyeditorHandler::validate();
		CopyeditorHandler::setupTemplate();

		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');

		$page = isset($args[0]) ? $args[0] : '';
		switch($page) {
			case 'completed':
				$active = false;
				break;
			default:
				$page = 'active';
				$active = true;
		}

		$submissions = $copyeditorSubmissionDao->getCopyeditorSubmissionsByCopyeditorId($user->getUserId(), $journal->getJournalId(), $active);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign('submissions', $submissions);

		$issueAction = new IssueAction();
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
			Request::redirect('user');
		}
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $articleId = 0, $parentPage = null, $showSidebar = true) {
		$templateMgr = &TemplateManager::getManager();
		$pageHierarchy = $subclass ? array(array('user', 'navigation.user'), array('copyeditor', 'user.role.copyeditor'))
				: array(array('user', 'navigation.user'), array('copyeditor', 'user.role.copyeditor'));
		$templateMgr->assign('pagePath', '/user/copyeditor');

		$submissionCrumb = SectionEditorAction::submissionBreadcrumb($articleId, $parentPage, 'copyeditor');
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);

		if ($showSidebar) {
			$templateMgr->assign('sidebarTemplate', 'copyeditor/navsidebar.tpl');

			$journal = &Request::getJournal();
			$user = &Request::getUser();
			$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
			$submissionsCount = $copyeditorSubmissionDao->getSubmissionsCount($user->getUserId(), $journal->getJournalId());
			$templateMgr->assign('submissionsCount', $submissionsCount);
		}
	}
	
	//
	// Assignment Tracking
	//
	
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
	

	function viewLayoutComments($args) {
		SubmissionCommentsHandler::viewLayoutComments($args);
	}
	
	function postLayoutComment() {
		SubmissionCommentsHandler::postLayoutComment();
	}
	
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

	//
	// Proofreading Actions
	//
	function authorProofreadingComplete($args) {
		TrackSubmissionHandler::authorProofreadingComplete($args);
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
	
	//
	// Metadata Actions
	//
	function viewMetadata($args) {
		TrackSubmissionHandler::viewMetadata($args);
	}	
	
	function saveMetadata($args) {
		TrackSubmissionHandler::saveMetadata($args);
	}	
	
}

?>
