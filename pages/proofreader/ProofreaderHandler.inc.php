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
	function setupTemplate($subclass = false, $showSidebar = true) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array('user', 'navigation.user'), array('proofreader', 'proofreader.journalProofreader'))
				: array(array('user', 'navigation.user'))
		);
		$templateMgr->assign('pagePath', '/user/proofreader');

		if ($showSidebar) {
			$templateMgr->assign('sidebarTemplate', 'proofreader/navsidebar.tpl');

			$journal = &Request::getJournal();
			$user = &Request::getUser();
			$proofreaderSubmissionDao = &DAORegistry::getDAO('ProofreaderSubmissionDAO');
			$submissionsCount = $proofreaderSubmissionDao->getSubmissionsCount($user->getUserId(), $journal->getJournalId());
			$templateMgr->assign('submissionsCount', $submissionsCount);
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
}

?>
