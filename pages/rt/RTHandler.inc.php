<?php

/**
 * RTHandler.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.rt
 *
 * Handle Reading Tools requests. 
 *
 * $Id$
 */

import('rt.RT');

import('rt.ojs.RTDAO');
import('rt.ojs.JournalRT');

import('article.ArticleHandler');

class RTHandler extends ArticleHandler {
	function bio($args) {
		$journal = &Request::getJournal();
		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournalId($journal->getJournalId());

		if (!$journalRt || $journalRt->getVersion()==null || !$journalRt->getAuthorBio()) {
			Request::redirect(Request::getPageUrl());
			return;
		}

		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		RTHandler::validate($articleId, $galleyId);

		RTHandler::setupTemplate($articleId);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->display('rt/bio.tpl');
	}
	
	function metadata($args) {
		$journal = &Request::getJournal();
		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournalId($journal->getJournalId());

		if (!$journalRt || $journalRt->getVersion()==null || !$journalRt->getViewMetadata()) {
			Request::redirect(Request::getPageUrl());
			return;
		}

		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		RTHandler::validate($articleId, $galleyId);

		RTHandler::setupTemplate($articleId);

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = &$publishedArticleDao->getPublishedArticleByArticleId($articleId);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('journalRt', $journalRt);
		$templateMgr->assign('publishedArticle', $publishedArticle);
		$templateMgr->assign('journalSettings', $journal->getSettings());
		$templateMgr->display('rt/metadata.tpl');
	}
	
	function context($args) {
		$journal = &Request::getJournal();
		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournalId($journal->getJournalId());

		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$contextId = Isset($args[2]) ? (int) $args[2] : 0;

		$context = &$rtDao->getContext($contextId);
		$version = &$rtDao->getVersion($context->getVersionId(), $journal->getJournalId());

		if (!$journalRt || $journalRt->getVersion()==null || $journalRt->getVersion() !=  $context->getVersionId() || !$version) {
			Request::redirect(Request::getPageUrl());
		}

		RTHandler::validate($articleId, $galleyId);

		RTHandler::setupTemplate($articleId);

		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getArticle($articleId);
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = &$publishedArticleDao->getPublishedArticleByArticleId($articleId);

		// Deal with the post and URL parameters for each search
		// so that the client browser can properly submit the forms
		// with a minimum of client-side processing.
		$searches = array();
		foreach ($context->getSearches() as $search) {
			$postParams = explode('&', $search->getSearchPost());
			$params = array();
			foreach ($postParams as $param) {
				// Split name and value from each parameter
				$nameValue = explode('=', $param);
				if (!isset($nameValue[0])) break;

				$name = trim($nameValue[0]);
				$value = trim(isset($nameValue[1])?$nameValue[1]:'');
				if (!empty($name)) $params[] = array('name' => $name, 'value' => $value);
			}

			if (count($params)!=0) {
				$lastElement = &$params[count($params)-1];
				if ($lastElement['value']=='') $lastElement['needsKeywords'] = true;
			}

			$search->postParams = $params;
			$search->urlNeedsKeywords = substr($search->getSearchUrl(), -1, 1)=='=';
			$searches[] = $search;
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('publishedArticle', $publishedArticle);
		$templateMgr->assign('version', $version);
		$templateMgr->assign('context', $context);
		$templateMgr->assign('searches', &$searches);
		$templateMgr->assign('defineTerm', Request::getUserVar('defineTerm'));
		$templateMgr->assign('keywords', explode(';', $article->getSubject()));
		$templateMgr->assign('journalSettings', $journal->getSettings());
		$templateMgr->display('rt/context.tpl');
	}
	
	function captureCite($args) {
		$journal = &Request::getJournal();
		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournalId($journal->getJournalId());

		if (!$journalRt || $journalRt->getVersion()==null || !$journalRt->getViewMetadata()) {
			Request::redirect(Request::getPageUrl());
			return;
		}

		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$citeType = isset($args[2]) ? $args[2] : null;

		RTHandler::validate($articleId, $galleyId);

		RTHandler::setupTemplate($articleId);

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = &$publishedArticleDao->getPublishedArticleByArticleId($articleId);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('journalRt', $journalRt);
		$templateMgr->assign('journal', $journal);
		$templateMgr->assign('bibFormat', $journal->getSetting('bibFormat'));
		$templateMgr->assign('publishedArticle', $publishedArticle);
		$templateMgr->assign('journalSettings', $journal->getSettings());

		switch ($citeType) {
			case 'endNote':
				$templateMgr->display('rt/citeEndNote.tpl', 'application/x-endnote-refer');
				break;
			case 'referenceManager':
				$templateMgr->display('rt/citeReferenceManager.tpl', 'application/x-Research-Info-Systems');
				break;
			case 'proCite':
				$templateMgr->display('rt/citeProCite.tpl', 'application/x-Research-Info-Systems');
				break;
			default:
				$templateMgr->display('rt/captureCite.tpl');
				break;
		}

	}
	
	function printerFriendly($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $issue, $article) = ArticleHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournalId($journal->getJournalId());

		if (!$journalRt || $journalRt->getVersion()==null || !$journalRt->getPrinterFriendly()) {
			Request::redirect(Request::getPageUrl());
			return;
		}

		ArticleHandler::setupTemplate($articleId);

		$articleGalleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$articleGalleyDao->getGalley($galleyId, $articleId);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('galley', $galley);
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->display('rt/printerFriendly.tpl');	
	}
	
	function emailColleague($args) {
		$journal = &Request::getJournal();
		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournalId($journal->getJournalId());
		$user = &Request::getUser();

		if (!$journalRt || $journalRt->getVersion()==null || !$journalRt->getEmailOthers() || !$user) {
			Request::redirect(Request::getPageUrl());
			return;
		}

		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getArticle($articleId);

		RTHandler::validate($articleId, $galleyId);

		RTHandler::setupTemplate($articleId);

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = &$publishedArticleDao->getPublishedArticleByArticleId($articleId);

		$email = &new MailTemplate();
		$email->setFrom($user->getEmail(), $user->getFullName());

		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$email->send();

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->display('rt/sent.tpl');
		} else {
			if (!Request::getUserVar('continued')) {
				$email->setSubject('[' . $journal->getSetting('journalInitials') . '] ' . $article->getArticleTitle());
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/emailColleague/' . $articleId . '/' . $galleyId, null, 'rt/email.tpl');
		}
	}

	function emailAuthor($args) {
		$journal = &Request::getJournal();
		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournalId($journal->getJournalId());
		$user = &Request::getUser();

		if (!$journalRt || $journalRt->getVersion()==null || !$journalRt->getEmailAuthor() || !$user) {
			Request::redirect(Request::getPageUrl());
			return;
		}

		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getArticle($articleId);

		RTHandler::validate($articleId, $galleyId);

		RTHandler::setupTemplate($articleId);

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = &$publishedArticleDao->getPublishedArticleByArticleId($articleId);

		$email = &new MailTemplate();
		$email->setFrom($user->getEmail(), $user->getFullName());

		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$email->send();

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->display('rt/sent.tpl');
		} else {
			if (!Request::getUserVar('continued')) {
				$email->setSubject('[' . $journal->getSetting('journalInitials') . '] ' . $article->getArticleTitle());
				$authors = &$article->getAuthors();
				$author = &$authors[0];
				$email->addRecipient($author->getEmail(), $author->getFullName());
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/emailAuthor/' . $articleId . '/' . $galleyId, null, 'rt/email.tpl');
		}
	}

	function addComment($args) {
	}
	
	function suppFiles($args) {
		$journal = &Request::getJournal();
		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournalId($journal->getJournalId());

		if (!$journalRt || $journalRt->getVersion()==null || !$journalRt->getSupplementaryFiles()) {
			Request::redirect(Request::getPageUrl());
			return;
		}

		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		RTHandler::validate($articleId, $galleyId);

		RTHandler::setupTemplate($articleId);

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = &$publishedArticleDao->getPublishedArticleByArticleId($articleId);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('journalRt', $journalRt);
		$templateMgr->assign('publishedArticle', $publishedArticle);
		$templateMgr->assign('journalSettings', $journal->getSettings());
		$templateMgr->display('rt/suppFiles.tpl');
	}
	
	function suppFileMetadata($args) {
		$journal = &Request::getJournal();
		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournalId($journal->getJournalId());

		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$suppFileId = isset($args[2]) ? (int) $args[2] : 0;

		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$suppFile = $suppFileDao->getSuppFile($suppFileId, $articleId);

		if (!$journalRt || $journalRt->getVersion()==null || !$journalRt->getSupplementaryFiles() || !$suppFile) {
			Request::redirect(Request::getPageUrl());
			return;
		}

		RTHandler::validate($articleId, $galleyId);

		RTHandler::setupTemplate($articleId);

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = &$publishedArticleDao->getPublishedArticleByArticleId($articleId);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('suppFile', $suppFile);
		$templateMgr->assign('journalRt', $journalRt);
		$templateMgr->assign('publishedArticle', $publishedArticle);
		$templateMgr->assign('journalSettings', $journal->getSettings());
		$templateMgr->display('rt/suppFileView.tpl');
	}
}

?>
