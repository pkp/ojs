<?php

/**
 * ArticleHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.article
 *
 * Handle requests for article functions. 
 *
 * $Id$
 */

class ArticleHandler extends Handler {

	/**
	 * View Article.
	 */
	function view($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		ArticleHandler::validate();

		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = &$articleDao->getArticle($articleId);

		if (!$article) {
			Request::redirect(Request::getPageUrl());		
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);

		$templateMgr->display('article/view.tpl');
	}

	/**
	 * Article view
	 */
	function viewArticle($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		ArticleHandler::validate();

		ArticleHandler::setupTemplate($articleId);

		$articleGalleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$articleGalleyDao->getGalley($galleyId, $articleId);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('galley', $galley);
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->display('article/article.tpl');	
	}

	/**
	 * Article Reading tools
	 */
	function viewRST($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		ArticleHandler::validate();

		ArticleHandler::setupTemplate($articleId);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->display('article/rst.tpl');	
	}

	/**
	 * View a file (inlines file).
	 * @param $args array ($articleId, $fileId)
	 */
	function viewFile($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$fileId = isset($args[1]) ? $args[1] : 0;
		ArticleHandler::validate();

		// reuse section editor's view file function
		SectionEditorAction::viewFile($articleId, $fileId);
	}

	/**
	 * Downloads the document
	 */
	function download($args) {
		$articleId = isset($args[0]) ? (int)$args[0] : 0;
		$fileId = isset($args[1]) ? (int)$args[1] : 0;
		ArticleHandler::validate();

		if ($articleId && $fileId) {
			import('file.ArticleFileManager');
			$articleFileManager = new ArticleFileManager($articleId);
			$articleFileManager->downloadFile($fileId);
		}
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($articleId) {

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issue = &$issueDao->getIssueByArticleId($articleId);

		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = &$articleDao->getArticle($articleId);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('issue', $issue);
		$templateMgr->assign('article', $article);
	}

	/**
	 * Validation
	 */
	function validate() {

		parent::validate();

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		
		if (!Validation::isLoggedIn() && $journalSettingsDao->getSetting($journalId,'restrictArticleAccess')) {
			Request::redirect('login');
		}
		
	}

}

?>
