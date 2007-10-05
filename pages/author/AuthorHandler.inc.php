<?php

/**
 * @file AuthorHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.author
 * @class AuthorHandler
 *
 * Handle requests for journal author functions. 
 *
 * $Id$
 */

import ('submission.author.AuthorAction');

class AuthorHandler extends Handler {

	/**
	 * Display journal author index page.
	 */
	function index($args) {
		list($journal) = AuthorHandler::validate();
		AuthorHandler::setupTemplate();

		$user = &Request::getUser();
		$rangeInfo = &Handler::getRangeInfo('submissions');
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

		$submissions = $authorSubmissionDao->getAuthorSubmissions($user->getUserId(), $journal->getJournalId(), $active, $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageToDisplay', $page);
		if (!$active) {
			// Make view counts available if enabled.
			$templateMgr->assign('statViews', $journal->getSetting('statViews'));
		}
		$templateMgr->assign_by_ref('submissions', $submissions);

		// assign payment 
		import('payment.ojs.OJSPaymentManager');
		$paymentManager =& OJSPaymentManager::getManager();

		if ( $paymentManager->isConfigured() ) {		
			$templateMgr->assign('fastTrackEnabled', $paymentManager->fastTrackEnabled());
			$templateMgr->assign('publicationEnabled', $paymentManager->publicationEnabled());
			
			$completedPaymentDAO =& DAORegistry::getDAO('OJSCompletedPaymentDAO');
			$templateMgr->assign_by_ref('completedPaymentDAO', $completedPaymentDAO);
		} 				

		import('issue.IssueAction');
		$issueAction = &new IssueAction();
		$templateMgr->register_function('print_issue_id', array($issueAction, 'smartyPrintIssueId'));
		$templateMgr->assign('helpTopicId', 'editorial.authorsRole.submissions');
		$templateMgr->display('author/index.tpl');
	}

	/**
	 * Validate that user has author permissions in the selected journal.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate($reason = null) {
		parent::validate();
		$journal = &Request::getJournal();
		if (!isset($journal) || !Validation::isAuthor($journal->getJournalId())) {
			Validation::redirectLogin($reason);
		}

		return array(&$journal);
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $articleId = 0, $parentPage = null) {
		$templateMgr = &TemplateManager::getManager();

		$pageHierarchy = $subclass ? array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'author'), 'user.role.author'), array(Request::url(null, 'author'), 'article.submissions'))
			: array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'author'), 'user.role.author'));

		import('submission.sectionEditor.SectionEditorAction');
		$submissionCrumb = SectionEditorAction::submissionBreadcrumb($articleId, $parentPage, 'author');
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
		if (!isset($args[0]) || !ProofreaderAction::instructions($args[0], array('copy', 'proof'))) {
			Request::redirect(null, null, 'index');
		}
	}


	//
	// Article Submission
	//

	function submit($args) {
		import('pages.author.SubmitHandler');
		SubmitHandler::submit($args);
	}

	function saveSubmit($args) {
		import('pages.author.SubmitHandler');
		SubmitHandler::saveSubmit($args);
	}

	function submitSuppFile($args) {
		import('pages.author.SubmitHandler');
		SubmitHandler::submitSuppFile($args);
	}

	function saveSubmitSuppFile($args) {
		import('pages.author.SubmitHandler');
		SubmitHandler::saveSubmitSuppFile($args);
	}

	function deleteSubmitSuppFile($args) {
		import('pages.author.SubmitHandler');
		SubmitHandler::deleteSubmitSuppFile($args);
	}

	function expediteSubmission($args) {
		import('pages.author.SubmitHandler');
		SubmitHandler::expediteSubmission($args);
	}

	//
	// Submission Tracking
	//

	function deleteArticleFile($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::deleteArticleFile($args);
	}

	function deleteSubmission($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::deleteSubmission($args);
	}

	function submission($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::submission($args);
	}

	function editSuppFile($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::editSuppFile($args);
	}

	function setSuppFileVisibility($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::setSuppFileVisibility($args);
	}

	function saveSuppFile($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::saveSuppFile($args);
	}

	function addSuppFile($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::addSuppFile($args);
	}

	function submissionReview($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::submissionReview($args);
	}

	function submissionEditing($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::submissionEditing($args);
	}

	function uploadRevisedVersion() {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::uploadRevisedVersion();
	}

	function viewMetadata($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::viewMetadata($args);
	}

	function saveMetadata() {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::saveMetadata();
	}

	function uploadCopyeditVersion() {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::uploadCopyeditVersion();
	}

	function completeAuthorCopyedit($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::completeAuthorCopyedit($args);
	}

	//
	// Misc.
	//

	function downloadFile($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::downloadFile($args);
	}

	function viewFile($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::viewFile($args);
	}

	function download($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::download($args);
	}

	//
	// Submission Comments
	//

	function viewEditorDecisionComments($args) {
		import('pages.author.SubmissionCommentsHandler');
		SubmissionCommentsHandler::viewEditorDecisionComments($args);
	}

	function viewCopyeditComments($args) {
		import('pages.author.SubmissionCommentsHandler');
		SubmissionCommentsHandler::viewCopyeditComments($args);
	}

	function postCopyeditComment() {
		import('pages.author.SubmissionCommentsHandler');
		SubmissionCommentsHandler::postCopyeditComment();
	}

	function emailEditorDecisionComment() {
		import('pages.author.SubmissionCommentsHandler');
		SubmissionCommentsHandler::emailEditorDecisionComment();
	}

	function viewProofreadComments($args) {
		import('pages.author.SubmissionCommentsHandler');
		SubmissionCommentsHandler::viewProofreadComments($args);
	}

	function viewLayoutComments($args) {
		import('pages.author.SubmissionCommentsHandler');
		SubmissionCommentsHandler::viewLayoutComments($args);
	}

	function postLayoutComment() {
		import('pages.author.SubmissionCommentsHandler');
		SubmissionCommentsHandler::postLayoutComment();
	}

	function postProofreadComment() {
		import('pages.author.SubmissionCommentsHandler');
		SubmissionCommentsHandler::postProofreadComment();
	}

	function editComment($args) {
		import('pages.author.SubmissionCommentsHandler');
		SubmissionCommentsHandler::editComment($args);
	}

	function saveComment() {
		import('pages.author.SubmissionCommentsHandler');
		SubmissionCommentsHandler::saveComment();
	}

	function deleteComment($args) {
		import('pages.author.SubmissionCommentsHandler');
		SubmissionCommentsHandler::deleteComment($args);
	}

	//
	// Proofreading Actions
	//
	function authorProofreadingComplete($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::authorProofreadingComplete($args);
	}

	function proofGalley($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::proofGalley($args);
	}

	function proofGalleyTop($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::proofGalleyTop($args);
	}

	function proofGalleyFile($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::proofGalleyFile($args);
	}	
	
	// 
	// Payment Actions
	//
	function payFastTrackFee($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::payToFastTrack($args);			
	}

	function payPublicationFee($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::payPublicationFee($args);			
	}	

}

?>
