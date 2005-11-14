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
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		list($journal, $issue, $article) = ArticleHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = $rtDao->getJournalRTByJournalId($journal->getJournalId());

		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$galleyDao->getGalley($galleyId, $article->getArticleId());

		if (!$journalRt || $journalRt->getVersion()==null) {
			if (!$galley || $galley->isHtmlGalley()) return ArticleHandler::viewArticle($args);
			else if ($galley->isPdfGalley()) return ArticleHandler::viewPDFInterstitial($args, $galley);
			else return ArticleHandler::viewDownloadInterstitial($args, $galley);
		}

		if (!$article) {
			Request::redirect(Request::getPageUrl());
			return;
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('galley', $galley);

		$templateMgr->display('article/view.tpl');
	}

	/**
	 * Article interstitial page before PDF is shown
	 */
	function viewPDFInterstitial($args, $galley = null) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $issue, $article) = ArticleHandler::validate($articleId, $galleyId);

		if (!$galley) {
			$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
			$galley = &$galleyDao->getGalley($galleyId, $article->getArticleId());
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('galley', $galley);

		$templateMgr->display('article/pdfInterstitial.tpl');
	}

	/**
	 * Article interstitial page before a non-PDF, non-HTML galley is
	 * downloaded
	 */
	function viewDownloadInterstitial($args, $galley = null) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $issue, $article) = ArticleHandler::validate($articleId, $galleyId);

		if (!$galley) {
			$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
			$galley = &$galleyDao->getGalley($galleyId, $article->getArticleId());
		}
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('galley', $galley);

		$templateMgr->display('article/interstitial.tpl');
	}

	/**
	 * Article view
	 */
	function viewArticle($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		list($journal, $issue, $article) = ArticleHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = $rtDao->getJournalRTByJournalId($journal->getJournalId());

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$section = &$sectionDao->getSection($article->getSectionId());

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

		$commentDao = &DAORegistry::getDAO('CommentDAO');
		$enableComments = $journal->getSetting('enableComments');
		if ($enableComments == COMMENTS_AUTHENTICATED || $enableComments == COMMENTS_UNAUTHENTICATED || $enableComments == COMMENTS_ANONYMOUS) {
			$comments = &$commentDao->getRootCommentsByArticleId($article->getArticleId());
		}

		$articleGalleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$articleGalleyDao->getGalley($galleyId, $article->getArticleId());

		$templateMgr = &TemplateManager::getManager();

		if (!$galley) {
			// Get the subscription status if displaying the abstract;
			// if access is open, we can display links to the full text.
			import('issue.IssueAction');
			$templateMgr->assign('subscriptionRequired', IssueAction::subscriptionRequired($issue));
			$templateMgr->assign('subscribedUser', IssueAction::subscribedUser());
			$templateMgr->assign('subscribedDomain', IssueAction::subscribedDomain());

			// Increment the published article's abstract views count
			$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
			$publishedArticleDao->incrementViewsByArticleId($article->getArticleId());
		} else {
			// Increment the galley's views count
			$articleGalleyDao->incrementViews($galleyId);
		}

		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign_by_ref('galley', $galley);
		$templateMgr->assign_by_ref('section', $section);
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('postingAllowed', (
			$enableComments == COMMENTS_UNAUTHENTICATED ||
			(($enableComments == COMMENTS_AUTHENTICATED ||
			$enableComments == COMMENTS_ANONYMOUS) &&
			Validation::isLoggedIn())
		));
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('defineTermsContextId', isset($defineTermsContextId)?$defineTermsContextId:null);
		$templateMgr->assign('comments', isset($comments)?$comments:null);
		$templateMgr->display('article/article.tpl');	
	}

	/**
	 * Article Reading tools
	 */
	function viewRST($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		list($journal, $issue, $article) = ArticleHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = $rtDao->getJournalRTByJournalId($journal->getJournalId());

		// The RST needs to know whether this galley is HTML or not. Fetch the galley.
		$articleGalleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$articleGalleyDao->getGalley($galleyId, $article->getArticleId());
		
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$section = &$sectionDao->getSection($article->getSectionId());

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('galley', $galley);
		$templateMgr->assign_by_ref('journal', $journal);
		$templateMgr->assign_by_ref('section', $section);

		// Bring in comment constants.
		$commentDao = &DAORegistry::getDAO('CommentDAO');

		$enableComments = $journal->getSetting('enableComments');
		$templateMgr->assign('postingAllowed', (
			$enableComments == COMMENTS_UNAUTHENTICATED ||
			(($enableComments == COMMENTS_AUTHENTICATED ||
			$enableComments == COMMENTS_ANONYMOUS) &&
			Validation::isLoggedIn())
		));
		$templateMgr->assign('postingDisabled', $enableComments == COMMENTS_DISABLED);

		if ($journalRt && $journalRt->getVersion()!=null) {
			$version = $rtDao->getVersion($journalRt->getVersion(), $journalRt->getJournalId());
			if ($version) {
				$templateMgr->assign_by_ref('version', $version);
				$templateMgr->assign_by_ref('journalRt', $journalRt);
			}
		}

		$templateMgr->display('rt/rt.tpl');	
	}

	/**
	 * View a file (inlines file).
	 * @param $args array ($articleId, $galleyId, $fileId [optional])
	 */
	function viewFile($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;
		$fileId = isset($args[2]) ? (int) $args[2] : 0;

		list($journal, $issue, $article) = ArticleHandler::validate($articleId, $galleyId);

		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$galleyDao->getGalley($galleyId, $article->getArticleId());

		if (!$galley) Request::redirect("article/view/$articleId");

		if (!$fileId) {
			$galleyDao->incrementViews($galleyId);
			$fileId = $galley->getFileId();
		} else {
			if (!$galley->isDependentFile($fileId)) {
				Request::redirect("article/view/$articleId");
			}
		}

		// reuse section editor's view file function
		import('submission.sectionEditor.SectionEditorAction');
		SectionEditorAction::viewFile($article->getArticleId(), $fileId);
	}

	/**
	 * Downloads the document
	 */
	function download($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int)$args[1] : 0;
		list($journal, $issue, $article) = ArticleHandler::validate($articleId, $galleyId);

		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$galleyDao->getGalley($galleyId, $article->getArticleId());
		$galleyDao->incrementViews($galleyId);

		if ($article && $galley) {
			import('file.ArticleFileManager');
			$articleFileManager = &new ArticleFileManager($article->getArticleId());
			$articleFileManager->downloadFile($galley->getFileId());
		}
	}

	function downloadSuppFile($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$suppId = isset($args[1]) ? $args[1] : 0;
		list($journal, $issue, $article) = ArticleHandler::validate($articleId);

		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		if ($journal->getSetting('enablePublicSuppFileId')) {
			$suppFile = &$suppFileDao->getSuppFileByBestSuppFileId($article->getArticleId(), $suppId);
		} else {
			$suppFile = &$suppFileDao->getSuppFile((int) $suppId, $article->getArticleId());
		}

		if ($article && $suppFile) {
			import('file.ArticleFileManager');
			$articleFileManager = &new ArticleFileManager($article->getArticleId());
			if ($suppFile->isInlineable()) {
				$articleFileManager->viewFile($suppFile->getFileId());
			} else {
				$articleFileManager->downloadFile($suppFile->getFileId());
			}
		}
	}

	/**
	 * Validation
	 */
	function validate($articleId, $galleyId = null) {

		parent::validate(true);

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');

		if ($journal->getSetting('enablePublicArticleId')) {
			$article = &$publishedArticleDao->getPublishedArticleByBestArticleId($journal->getJournalId(), $articleId);
		} else {
			$article = &$publishedArticleDao->getPublishedArticleByArticleId((int) $articleId);
		}

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		if (isset($article)) $issue = &$issueDao->getIssueByArticleId($article->getArticleId());

		// if issue or article do not exist, are not published, or are
		// not parts of the same journal, redirect to index.
		if (isset($issue) && isset($article) && ($issue->getPublished() || Validation::isEditor($journal->getJournalId())) && $issue->getJournalId() == $journal->getJournalId()) {

			import('issue.IssueAction');
			$subscriptionRequired = IssueAction::subscriptionRequired($issue);
			
			// Check if login is required for viewing.
			if (!Validation::isLoggedIn() && $journalSettingsDao->getSetting($journalId,'restrictArticleAccess')) {
				Validation::redirectLogin();
			}
	
			// bypass all validation if subscription based on domain or ip is valid
			// or if the user is just requesting the abstract
			if ( (!IssueAction::subscribedDomain() && $subscriptionRequired) &&
			     (isset($galleyId) && $galleyId!=0) ) {
				
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
