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

class CopyeditorHandler extends Handler {

	/**
	 * Display copyeditor index page.
	 */
	function index() {
		CopyeditorHandler::validate();
		CopyeditorHandler::setupTemplate();
		
		$templateMgr = &TemplateManager::getManager();
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
	function setupTemplate($subclass = false) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array('user', 'navigation.user'), array('copyeditor', 'copyeditor.journalCopyeditor'))
				: array(array('user', 'navigation.user'))
		);
	}
	
	function assignments($args) {
		CopyeditorHandler::validate();
		CopyeditorHandler::setupTemplate(true);
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('submissions', $copyeditorSubmissionDao->getCopyeditorSubmissionsByCopyeditorId($user->getUserId(), $journal->getJournalId()));
		$templateMgr->display('copyeditor/submissions.tpl');
	}
	
	function submission($args) {
		CopyeditorHandler::validate();
		CopyeditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$submission = $copyeditorSubmissionDao->getCopyeditorSubmission($articleId);

		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('submission', $submission);
		
		$templateMgr->display('copyeditor/submission.tpl');
	}
	
	function completeCopyedit() {
		CopyeditorHandler::validate();
		CopyeditorHandler::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		
		CopyeditorAction::completeCopyedit($articleId);
		
		Request::redirect(sprintf('copyeditor/submission/%d', $articleId));
	}
	
	function completeFinalCopyedit() {
		CopyeditorHandler::validate();
		CopyeditorHandler::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		
		CopyeditorAction::completeFinalCopyedit($articleId);
		
		Request::redirect(sprintf('copyeditor/submission/%d', $articleId));
	}
	
}

?>
