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

	/**
	 * If no journal is selected, display list of journals.
	 * Otherwise, display the index page for the selected journal.
	 */
	function index() {
		RTHandler::validate();
	}
	
	function about() {
		RTHandler::validate();
	}
	
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
	
	function cite() {
		RTHandler::validate();
	}
	
	function printerFriendly() {
		RTHandler::validate();
	}
	
	function defineWord() {
		RTHandler::validate();
	}
	
	function emailColleague() {
		RTHandler::validate();
	}
	
	function suppFiles() {
		RTHandler::validate();
	}
	
	function suppFileMetadata() {
		RTHandler::validate();
	}
}

?>
