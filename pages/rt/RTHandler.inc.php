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

		if (!$journalRt || !$journalRt->getAuthorBio()) {
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

		if (!$journalRt || !$journalRt->getViewMetadata()) {
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
		$templateMgr->assign('publishedArticle', $publishedArticle);
		$templateMgr->assign('journalSettings', $journal->getSettings());
		$templateMgr->display('rt/metadata.tpl');
	}
	
	function context($args) {
		// FIXME
	}
	
	function cite($args) {
		// FIXME
	}
	
	function printerFriendly($args) {
		// FIXME
	}
	
	function emailColleague($args) {
		$journal = &Request::getJournal();
		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournalId($journal->getJournalId());

		if (!$journalRt || !$journalRt->getViewMetadata()) {
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
		if (Request::getUser()) {
			$user = &Request::getUser();
			$email->setFrom($user->getEmail(), $user->getFullName());
		}

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

		if (!$journalRt || !$journalRt->getViewMetadata()) {
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
		if (Request::getUser()) {
			$user = &Request::getUser();
			$email->setFrom($user->getEmail(), $user->getFullName());
		}

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
	}
	
	function suppFileMetadata($args) {
	}
}

?>
