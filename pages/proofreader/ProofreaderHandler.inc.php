<?php

/**
 * ProofreaderHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.proofreader
 *
 * Handle requests for proofreader functions. 
 *
 * $Id$
 */

import('pages.proofreader.SubmissionProofreaderHandler');
import('pages.proofreader.SubmissionCommentsHandler');

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

		$page = isset($args[0]) ? $args[0] : '';
		switch($page) {
			case 'completed':
				$active = false;
				break;
			default:
				$page = 'active';
				$active = true;
		}

		$submissions = $proofreaderSubmissionDao->getSubmissions($user->getUserId(), $journal->getJournalId(), $active);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign('submissions', $submissions);

		$issueAction = new IssueAction();
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
			Request::redirect('user');
		}
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $articleId = 0, $parentPage = null, $showSidebar = true) {
		$templateMgr = &TemplateManager::getManager();
		$pageHierarchy = $subclass ? array(array('user', 'navigation.user'), array('proofreader', 'user.role.proofreader'))
				: array(array('user', 'navigation.user'), array('proofreader', 'user.role.proofreader'));
		$templateMgr->assign('pagePath', '/user/proofreader');

		$submissionCrumb = SectionEditorAction::submissionBreadcrumb($articleId, $parentPage, 'proofreader');
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);

		if ($showSidebar) {
			$templateMgr->assign('sidebarTemplate', 'proofreader/navsidebar.tpl');

			$journal = &Request::getJournal();
			$user = &Request::getUser();
			$proofreaderSubmissionDao = &DAORegistry::getDAO('ProofreaderSubmissionDAO');
			$submissionsCount = $proofreaderSubmissionDao->getSubmissionsCount($user->getUserId(), $journal->getJournalId());
			$templateMgr->assign('submissionsCount', $submissionsCount);
		}

	}
	
	/**
	 * Display submission management instructions.
	 * @param $args (type)
	 */
	function instructions($args) {
		if (!isset($args[0]) || !ProofreaderAction::instructions($args[0], array('proof'))) {
			Request::redirect(Request::getRequestedPage());
		}
	}

	//
	// Submission Proofreading
	//

	function submission($args) {
		SubmissionProofreaderHandler::submission($args);
	}

	function completeProofreader($args) {
		SubmissionProofreaderHandler::completeProofreader($args);
	}
	
	//
	// Submission Comments
	//
	
	function viewProofreadComments($args) {
		SubmissionCommentsHandler::viewProofreadComments($args);
	}
	
	function postProofreadComment() {
		SubmissionCommentsHandler::postProofreadComment();
	}

	function viewLayoutComments($args) {
		SubmissionCommentsHandler::viewLayoutComments($args);
	}

	function postLayoutComment() {
		SubmissionCommentsHandler::postLayoutComment();
	}

	//
	// Misc.
	//

	function downloadFile($args) {
		SubmissionProofreaderHandler::downloadFile($args);
	}

	function viewFile($args) {
		SubmissionProofreaderHandler::viewFile($args);
	}
	
	function proofGalley($args) {
		SubmissionProofreaderHandler::proofGalley($args);
	}
	
	function proofGalleyTop($args) {
		SubmissionProofreaderHandler::proofGalleyTop($args);
	}
	
	function proofGalleyFile($args) {
		SubmissionProofreaderHandler::proofGalleyFile($args);
	}	
	
}

?>
