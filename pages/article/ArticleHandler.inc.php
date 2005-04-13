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

import('rt.ojs.RTDAO');
import('rt.ojs.JournalRT');

class ArticleHandler extends Handler {

	/**
	 * View Article.
	 */
	function view($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		ArticleHandler::validate($articleId, $galleyId);

		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = &$articleDao->getArticle($articleId);

		$journal = &Request::getJournal();
		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = $rtDao->getJournalRTByJournalId($journal->getJournalId());

		if (!$article) {
			Request::redirect(Request::getPageUrl());
			return;
		}

		if (!$journalRt) {
			Request::redirect(Request::getPageUrl() . '/article/viewArticle/' . $articleId . '/' . $galleyId);
			return;
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
		ArticleHandler::validate($articleId, $galleyId);

		$journal = &Request::getJournal();
		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = $rtDao->getJournalRTByJournalId($journal->getJournalId());

		if ($journalRt && $journalRt->getDefineTerms()) {
			// Determine the "Define Terms" context ID.
			$version = $rtDao->getVersion($journalRt->getVersion(), $journalRt->getJournalId());
			foreach ($version->getContexts() as $context) {
				if ($context->getDefineTerms()) {
					$defineTermsContextId = $context->getContextId();
					break;
				}
			}
		}

		$enableComments = $journal->getSetting('enableComments');
		if ($enableComments == 'authenticated' || $enableComments == 'unauthenticated' || $enableComments == 'anonymous') {
			$commentDao = &DAORegistry::getDAO('CommentDAO');
			$comments = &$commentDao->getRootCommentsByArticleId($articleId);
		}

		ArticleHandler::setupTemplate($articleId);

		$articleGalleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$articleGalleyDao->getGalley($galleyId, $articleId);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('galley', $galley);
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('enableComments', $enableComments);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('defineTermsContextId', isset($defineTermsContextId)?$defineTermsContextId:null);
		$templateMgr->assign('comments', isset($comments)?$comments:null);
		$templateMgr->display('article/article.tpl');	
	}

	/**
	 * Article Reading tools
	 */
	function viewRST($args) {
		$journal = &Request::getJournal();

		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		ArticleHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = $rtDao->getJournalRTByJournalId($journal->getJournalId());

		ArticleHandler::setupTemplate($articleId);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('journal', $journal);
		$templateMgr->assign('enableComments', $journal->getSetting('enableComments'));

		if ($journalRt) {
			$version = $rtDao->getVersion($journalRt->getVersion(), $journalRt->getJournalId());
			$templateMgr->assign('version', $version);
			$templateMgr->assign('journalRt', $journalRt);
		}

		$templateMgr->display('article/rst.tpl');	
	}

	/**
	 * View a file (inlines file).
	 * @param $args array ($articleId, $fileId)
	 */
	function viewFile($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$fileId = isset($args[1]) ? $args[1] : 0;
		ArticleHandler::validate($articleId);

		// reuse section editor's view file function
		SectionEditorAction::viewFile($articleId, $fileId);
	}

	/**
	 * Downloads the document
	 */
	function download($args) {
		$articleId = isset($args[0]) ? (int)$args[0] : 0;
		$fileId = isset($args[1]) ? (int)$args[1] : 0;
		ArticleHandler::validate($articleId);

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
	function validate($articleId, $galleyId = null) {

		parent::validate();

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issue = &$issueDao->getIssueByArticleId($articleId);

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$article = &$publishedArticleDao->getPublishedArticleByArticleId($articleId);

		// if issue or article do not exist, redirect to index.
		if (isset($issue) && isset($article)) {

			$subscriptionRequired = IssueAction::subscriptionRequired($issue);
			
			// bypass all validation if subscription based on domain or ip is valid.
			if (!IssueAction::subscribedDomain() && $subscriptionRequired) {
				
				// if no domain subscription, check if login is required for viewing.
				if (!Validation::isLoggedIn() && $journalSettingsDao->getSetting($journalId,'restrictArticleAccess')) {
					Validation::redirectLogin();
				}
	
				// Subscription Access
				$subscribedUser = IssueAction::subscribedUser();
	
				if (!(!$subscriptionRequired || $article->getAccessStatus() || $subscribedUser)) {
					if (!isset($galleyId) || $galleyId) {
						Request::redirect('index');	
					}
				}
			}
		
		} else {
			Request::redirect('index');
		}				
	}

}

?>
