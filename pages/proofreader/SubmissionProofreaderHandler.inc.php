<?php

/**
 * SubmissionProofreaderHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.proofreader
 *
 * Handle requests for proofreader submission functions. 
 *
 * $Id$
 */

class SubmissionProofreaderHandler extends ProofreaderHandler {

	/**
	 * Assignments
	 */
	function assignments($args) {
		parent::validate();
		parent::setupTemplate(true);

		$journal = &Request::getJournal();
		$user = &Request::getUser();

		$active = true;
		if (isset($args[0])) {
			$active = ($args[0] != 'completed') ? true : false;
		}

		$proofreaderSubmissionDao = &DAORegistry::getDAO('ProofreaderSubmissionDAO');
		$submissions = $proofreaderSubmissionDao->getSubmissions($user->getUserId(), $journal->getJournalId(), $active);
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('submissions', $submissions);
		$templateMgr->assign('active', $active);
		$templateMgr->display('proofreader/submissions.tpl');		
	}

	/**
	 * Submission - Proofreading view
	 */
	function submission($args) {
		$articleId = isset($args[0]) ? (int)$args[0] : 0;

		SubmissionProofreaderHandler::validate($articleId);
		parent::setupTemplate(true);

		$journal = &Request::getJournal();
		$useProofreaders = $journal->getSetting('useProofreaders');

		$authorDao = &DAORegistry::getDAO('AuthorDAO');
		$authors = $authorDao->getAuthorsByArticle($articleId);

		$proofreaderSubmissionDao = &DAORegistry::getDAO('ProofreaderSubmissionDAO');
		$submission = $proofreaderSubmissionDao->getSubmission($articleId);

		ProofreaderAction::proofreaderProofreadingUnderway($articleId);

		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('useProofreaders', $useProofreaders);
		$templateMgr->assign('authors', $authors);
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('proofAssignment', $submission->getProofAssignment());
		
		$templateMgr->display('proofreader/submission.tpl');
	}

	/**
	 * Sets proofreader completion date
	 */
	function completeProofreader($args) {
		$articleId = Request::getUserVar('articleId');

		SubmissionProofreaderHandler::validate($articleId);
		parent::setupTemplate(true);

		$send = false;
		if (isset($args[0])) {
			$send = ($args[0] == 'send') ? true : false;
		}

		if ($send) {
			ProofreaderAction::proofreadEmail($articleId,'PROOFREAD_COMP');
			Request::redirect(sprintf('proofreader/submission/%d', $articleId));	
		} else {
			ProofreaderAction::proofreadEmail($articleId,'PROOFREAD_COMP','/proofreader/completeProofreader/send');
		}		
	}

	/**
	 * Validate that the user is the assigned proofreader for the submission.
	 * Redirects to proofreader index page if validation fails.
	 */
	function validate($articleId) {
		parent::validate();
		
		$isValid = false;
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$proofreaderDao = &DAORegistry::getDAO('ProofreaderSubmissionDAO');
		$submission = &$proofreaderDao->getSubmission($articleId, $journal->getJournalId());

		if (isset($submission)) {
			$proofAssignment = &$submission->getProofAssignment();
			if ($proofAssignment->getProofreaderId() == $user->getUserId()) {
				$isValid = true;
			}			
		}
		
		if (!$isValid) {
			Request::redirect(Request::getRequestedPage());
		}
	}
	
}

?>
