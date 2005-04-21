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

		list($journal, $issue, $article) = ArticleHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = $rtDao->getJournalRTByJournalId($journal->getJournalId());

		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$galleyDao->getGalley($galleyId, $articleId);

		if (!$journalRt || $journalRt->getVersion()==null) {
			if (!$galley || $galley->isHtmlGalley()) return ArticleHandler::viewArticle($args);
			return ArticleHandler::viewInterstitial($args, $galley);
		}

		if (!$article) {
			Request::redirect(Request::getPageUrl());
			return;
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('galley', $galley);

		$templateMgr->display('article/view.tpl');
	}

	/**
	 * Article interstitial page before PDF is shown
	 */
	function viewInterstitial($args, $galley = null) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $issue, $article) = ArticleHandler::validate($articleId, $galleyId);

		if (!$galley) {
			$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
			$galley = &$galleyDao->getGalley($galleyId, $articleId);
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('galley', $galley);

		$templateMgr->display('article/interstitial.tpl');
	}

	/**
	 * Article view
	 */
	function viewArticle($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		list($journal, $issue, $article) = ArticleHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = $rtDao->getJournalRTByJournalId($journal->getJournalId());

		if ($journalRt && $journalRt->getVersion()!=null && $journalRt->getDefineTerms()) {
			// Determine the "Define Terms" context ID.
			$version = $rtDao->getVersion($journalRt->getVersion(), $journalRt->getJournalId());
			if ($version) foreach ($version->getContexts() as $context) {
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

		$articleGalleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$articleGalleyDao->getGalley($galleyId, $articleId);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('issue', $issue);
		$templateMgr->assign('article', $article);
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
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		list($journal, $issue, $article) = ArticleHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = $rtDao->getJournalRTByJournalId($journal->getJournalId());

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('issue', $issue);
		$templateMgr->assign('article', $article);
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('journal', $journal);
		$templateMgr->assign('enableComments', $journal->getSetting('enableComments'));

		if ($journalRt && $journalRt->getVersion()!=null) {
			$version = $rtDao->getVersion($journalRt->getVersion(), $journalRt->getJournalId());
			if ($version) {
				$templateMgr->assign('version', $version);
				$templateMgr->assign('journalRt', $journalRt);
			}
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
		list($journal, $issue, $article) = ArticleHandler::validate($articleId);

		// reuse section editor's view file function
		SectionEditorAction::viewFile($articleId, $fileId);
	}

	/**
	 * Downloads the document
	 */
	function download($args) {
		$articleId = isset($args[0]) ? (int)$args[0] : 0;
		$fileId = isset($args[1]) ? (int)$args[1] : 0;
		list($journal, $issue, $article) = ArticleHandler::validate($articleId);

		if ($articleId && $fileId) {
			import('file.ArticleFileManager');
			$articleFileManager = new ArticleFileManager($articleId);
			$articleFileManager->downloadFile($fileId);
		}
	}

	/**
	 * Validation
	 */
	function &validate($articleId, $galleyId = null) {

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
		return array($journal, $issue, $article);
	}

}

?>
