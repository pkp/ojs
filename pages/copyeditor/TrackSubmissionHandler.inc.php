<?php

/**
 * TrackSubmissionHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.copyeditor
 *
 * Handle requests for submission tracking. 
 *
 * $Id$
 */

class TrackSubmissionHandler extends CopyeditorHandler {
	
	function assignments($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('submissions', $copyeditorSubmissionDao->getCopyeditorSubmissionsByCopyeditorId($user->getUserId(), $journal->getJournalId()));
		$templateMgr->display('copyeditor/submissions.tpl');
	}
	
	function submission($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = $args[0];
		
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$submission = $copyeditorSubmissionDao->getCopyeditorSubmission($articleId);

		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('submission', $submission);
		
		$templateMgr->display('copyeditor/submission.tpl');
	}
	
	function completeCopyedit() {
		parent::validate();
		parent::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		
		CopyeditorAction::completeCopyedit($articleId);
		
		Request::redirect(sprintf('copyeditor/submission/%d', $articleId));
	}
	
	function completeFinalCopyedit() {
		parent::validate();
		parent::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		
		CopyeditorAction::completeFinalCopyedit($articleId);
		
		Request::redirect(sprintf('copyeditor/submission/%d', $articleId));
	}
	
	//
	// Validation
	//
	
	/**
	 * Validate that the user is the assigned copyeditor for
	 * the article.
	 * Redirects to copyeditor index page if validation fails.
	 */
	function validate($articleId) {
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$copyeditorSubmission = &$copyeditorSubmissionDao->getCopyeditorSubmission($articleId, $user->getUserId());
		
		if ($copyeditorSubmission == null || $copyeditorSubmission->getCopyeditorId() != $user->getUserId()) {
			Request::redirect('copyeditor');
		}
	}
}
?>
