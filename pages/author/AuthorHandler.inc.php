<?php

/**
 * AuthorHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.author
 *
 * Handle requests for journal author functions. 
 *
 * $Id$
 */

import('pages.author.SubmitHandler');
import('pages.author.TrackSubmissionHandler');
import('pages.author.SubmissionCommentsHandler');

import ('submission.author.AuthorAction');

class AuthorHandler extends Handler {

	/**
	 * Display journal author index page.
	 */
	function index($args) {
		list($journal) = AuthorHandler::validate();
		AuthorHandler::setupTemplate();

		$user = &Request::getUser();
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');

		$page = isset($args[0]) ? $args[0] : '';
		switch($page) {
			case 'completed':
				$active = false;
				break;
			default:
				$page = 'active';
				$active = true;
		}

		$submissions = $authorSubmissionDao->getAuthorSubmissions($user->getUserId(), $journal->getJournalId(), $active);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign('submissions', $submissions);

		import('issue.IssueAction');
		$issueAction = new IssueAction();
		$templateMgr->register_function('print_issue_id', array($issueAction, 'smartyPrintIssueId'));
		$templateMgr->assign('helpTopicId', 'editorial.authorsRole.submissions');
		$templateMgr->display('author/index.tpl');
	}
	
	/**
	 * Validate that user has author permissions in the selected journal.
	 * Redirects to user index page if not properly authenticated.
	 */
	function &validate() {
		parent::validate();
		$journal = &Request::getJournal();
		if (!isset($journal) || !Validation::isAuthor($journal->getJournalId())) {
			Validation::redirectLogin();
		}

		return array($journal);
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $articleId = 0, $parentPage = null, $showSidebar = true) {
		$templateMgr = &TemplateManager::getManager();

		$pageHierarchy = $subclass ? array(array('user', 'navigation.user'), array('author', 'user.role.author'), array('author', 'article.submissions'))
			: array(array('user', 'navigation.user'), array('author', 'user.role.author'));
		$templateMgr->assign('pagePath', '/user/author');

		import('submission.sectionEditor.SectionEditorAction');
		$submissionCrumb = SectionEditorAction::submissionBreadcrumb($articleId, $parentPage, 'author');
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);

		if ($showSidebar) {
			$templateMgr->assign('sidebarTemplate', 'author/navsidebar.tpl');

			$journal = &Request::getJournal();
			$user = &Request::getUser();
			$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
			$submissionsCount = $authorSubmissionDao->getSubmissionsCount($user->getUserId(), $journal->getJournalId());
			$templateMgr->assign('submissionsCount', $submissionsCount);
		}
	}
	
	/**
	 * Display submission management instructions.
	 * @param $args (type)
	 */
	function instructions($args) {
		import('submission.proofreader.ProofreaderAction');
		if (!isset($args[0]) || !ProofreaderAction::instructions($args[0], array('copy', 'proof'))) {
			Request::redirect(Request::getRequestedPage());
		}
	}


	//
	// Article Submission
	//

	function submit($args) {
		SubmitHandler::submit($args);
	}
	
	function saveSubmit($args) {
		SubmitHandler::saveSubmit($args);
	}

	function submitSuppFile($args) {
		SubmitHandler::submitSuppFile($args);
	}
	
	function saveSubmitSuppFile($args) {
		SubmitHandler::saveSubmitSuppFile($args);
	}
	
	function deleteSubmitSuppFile($args) {
		SubmitHandler::deleteSubmitSuppFile($args);
	}
	
	//
	// Submission Tracking
	//
	
	function deleteArticleFile($args) {
		TrackSubmissionHandler::deleteArticleFile($args);
	}
	
	function deleteSubmission($args) {
		TrackSubmissionHandler::deleteSubmission($args);
	}
	
	function submission($args) {
		TrackSubmissionHandler::submission($args);
	}
	
	function editSuppFile($args) {
		TrackSubmissionHandler::editSuppFile($args);
	}
	
	function setSuppFileVisibility($args) {
		TrackSubmissionHandler::setSuppFileVisibility($args);
	}
	
	function saveSuppFile($args) {
		TrackSubmissionHandler::saveSuppFile($args);
	}
	
	function addSuppFile($args) {
		TrackSubmissionHandler::addSuppFile($args);
	}
	
	function submissionReview($args) {
		TrackSubmissionHandler::submissionReview($args);
	}
	
	function submissionEditing($args) {
		TrackSubmissionHandler::submissionEditing($args);
	}
	
	function uploadRevisedVersion() {
		TrackSubmissionHandler::uploadRevisedVersion();
	}
	
	function viewMetadata($args) {
		TrackSubmissionHandler::viewMetadata($args);
	}
	
	function saveMetadata() {
		TrackSubmissionHandler::saveMetadata();
	}
	
	function uploadCopyeditVersion() {
		TrackSubmissionHandler::uploadCopyeditVersion();
	}
	
	function completeAuthorCopyedit($args) {
		TrackSubmissionHandler::completeAuthorCopyedit($args);
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
	
	function download($args) {
		TrackSubmissionHandler::download($args);
	}
	
	//
	// Submission Comments
	//
	
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
	
	function viewProofreadComments($args) {
		SubmissionCommentsHandler::viewProofreadComments($args);
	}

	function viewLayoutComments($args) {
		SubmissionCommentsHandler::viewLayoutComments($args);
	}

	function postLayoutComment() {
		SubmissionCommentsHandler::postLayoutComment();
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
	
}

?>
