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
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('submissions', $authorSubmissionDao->getAuthorSubmissions($user->getUserId(), $journal->getJournalId()));
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
	function submission($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		if (isset($args) && !empty($args)) {
		
			$journal = &Request::getJournal();
			$user = &Request::getUser();
			
			$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
			$article = $authorSubmissionDao->getAuthorSubmission($args[0]);
			
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('article', $article);
			$templateMgr->assign('editor', $article->getEditor());
			$templateMgr->assign('submissionFile', $article->getSubmissionFile());
			$templateMgr->assign('suppFiles', $article->getSuppFiles());
		
			$templateMgr->display('author/submission.tpl');
		}
	}
	
	/**
	 * Upload the author's revised version of an article.
	 */
	function uploadRevisedArticle() {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		AuthorAction::uploadRevisedArticle($articleId);
		
		Request::redirect(sprintf('author/submission/%d', $articleId));	
	}
}
?>
