<?php

/**
 * TrackSubmissionHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.author
 *
 * Handle requests for submission tracking. 
 *
 * $Id$
 */

class TrackSubmissionHandler extends AuthorHandler {
	
	/**
	 * Display list of an author's submissions.
	 */
	function track() {
		parent::validate();
		parent::setupTemplate(true);
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('submissions', $articleDao->getArticlesByUserId($user->getUserId(), $journal->getJournalId()));
		$templateMgr->display('author/submissions.tpl');
	}
	
	/**
	 * Delete a submission.
	 */
	function deleteSubmission($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		if (isset($args) && !empty($args)) {
			$journal = &Request::getJournal();
			
			$articleDao = &DAORegistry::getDAO('ArticleDAO');
			$articleDao->deleteArticleById($args[0]);
		}
		
		Request::redirect('author/track');
	}
	
	/**
	 * Display the status and other details of an author's submission.
	 */
	function submissionStatus($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		if (isset($args) && !empty($args)) {
		
			$journal = &Request::getJournal();
			$user = &Request::getUser();
			
			$articleDao = &DAORegistry::getDAO('ArticleDAO');
			$article = $articleDao->getArticle($args[0]);
			
			$roleDao = &DAORegistry::getDAO('RoleDAO');
			$editors = &$roleDao->getUsersByRoleId(ROLE_ID_EDITOR, $journal->getJournalId());
			
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('article', $article);
			$templateMgr->assign('editors', $editors);
			$templateMgr->display('author/submissionStatus.tpl');
		}
	}
	
}
?>
