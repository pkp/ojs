<?php

/**
 * @file RTHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.rt
 * @class RTHandler
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
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $issue, $article) = RTHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournal($journal);

		if (!$journalRt || !$journalRt->getAuthorBio()) {
			Request::redirect(null, Request::getRequestedPage());
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->display('rt/bio.tpl');
	}

	function metadata($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $issue, $article) = RTHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournal($journal);

		if (!$journalRt || !$journalRt->getViewMetadata()) {
			Request::redirect(null, Request::getRequestedPage());
		}

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$section = &$sectionDao->getSection($article->getSectionId());

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('journalRt', $journalRt);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign_by_ref('section', $section);
		$templateMgr->assign_by_ref('journalSettings', $journal->getSettings());
		$templateMgr->display('rt/metadata.tpl');
	}

	function context($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$contextId = Isset($args[2]) ? (int) $args[2] : 0;

		list($journal, $issue, $article) = RTHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournal($journal);

		$context = &$rtDao->getContext($contextId);
		if ($context) $version = &$rtDao->getVersion($context->getVersionId(), $journal->getJournalId());

		if (!$context || !$version || !$journalRt || $journalRt->getVersion()==null || $journalRt->getVersion() !=  $context->getVersionId()) {
			Request::redirect(null, 'article', 'view', array($articleId, $galleyId));
		}

		// Deal with the post and URL parameters for each search
		// so that the client browser can properly submit the forms
		// with a minimum of client-side processing.
		$searches = array();
		// Some searches use parameters other than the "default" for
		// the search (i.e. keywords, author name, etc). If additional
		// parameters are used, they should be displayed as part of the
		// form for ALL searches in that context.
		$searchParams = array();
		foreach ($context->getSearches() as $search) {
			$params = array();
			$searchParams += RTHandler::getParameterNames($search->getSearchUrl());
			if ($search->getSearchPost()) {
				$searchParams += RTHandler::getParameterNames($search->getSearchPost());
				$postParams = explode('&', $search->getSearchPost());
				foreach ($postParams as $param) {
					// Split name and value from each parameter
					$nameValue = explode('=', $param);
					if (!isset($nameValue[0])) break;

					$name = $nameValue[0];
					$value = trim(isset($nameValue[1])?$nameValue[1]:'');
					if (!empty($name)) $params[] = array('name' => $name, 'value' => $value);
				}
			}

			$search->postParams = $params;
			$searches[] = $search;
		}

		// Remove duplicate extra form elements and get their values
		$searchParams = array_unique($searchParams);
		$searchValues = array();

		foreach ($searchParams as $key => $param) switch ($param) {
			case 'author':
				$searchValues[$param] = $article->getAuthorString();
				break;
			case 'coverageGeo':
				$searchValues[$param] = $article->getArticleCoverageGeo();
				break;
			case 'title':
				$searchValues[$param] = $article->getArticleTitle();
				break;
			default:
				// UNKNOWN parameter! Remove it from the list.
				unset($searchParams[$key]);
				break;
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign_by_ref('version', $version);
		$templateMgr->assign_by_ref('context', $context);
		$templateMgr->assign_by_ref('searches', $searches);
		$templateMgr->assign('searchParams', $searchParams);
		$templateMgr->assign('searchValues', $searchValues);
		$templateMgr->assign('defineTerm', Request::getUserVar('defineTerm'));
		$templateMgr->assign('keywords', explode(';', $article->getArticleSubject()));
		$templateMgr->assign('coverageGeo', $article->getArticleCoverageGeo());
		$templateMgr->assign_by_ref('journalSettings', $journal->getSettings());
		$templateMgr->display('rt/context.tpl');
	}

	function captureCite($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$citeType = isset($args[2]) ? $args[2] : null;

		list($journal, $issue, $article) = RTHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournal($journal);

		if (!$journalRt || !$journalRt->getCaptureCite()) {
			Request::redirect(null, Request::getRequestedPage());
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('journalRt', $journalRt);
		$templateMgr->assign_by_ref('journal', $journal);
		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign_by_ref('article', $article);

		$templateMgr->assign_by_ref('journalSettings', $journal->getSettings());

		$citationPlugins =& PluginRegistry::loadCategory('citationFormats');
		$templateMgr->assign('citationPlugins', $citationPlugins);
		if (isset($citationPlugins[$citeType])) {
			// A citation type has been selected; display citation.
			$citationPlugin =& $citationPlugins[$citeType];
		} else {
			// No citation type has been selected; use a default.
			$citationPlugin = array_shift($citationPlugins);
		}
		$citationPlugin->cite($article, $issue);

/*		switch ($citeType) {
			case 'endNote':
				header('Content-Disposition: attachment; filename="' . $articleId . '-endNote.enw"');
				$templateMgr->display('rt/citeEndNote.tpl', 'application/x-endnote-refer');
				break;
			case 'referenceManager':
				header('Content-Disposition: attachment; filename="' . $articleId . '-refMan.ris"');
				$templateMgr->display('rt/citeReferenceManager.tpl', 'application/x-Research-Info-Systems');
				break;
			case 'proCite':
				header('Content-Disposition: attachment; filename="' . $articleId . '-proCite.ris"');
				$templateMgr->display('rt/citeProCite.tpl', 'application/x-Research-Info-Systems');
				break;
			default:
				$templateMgr->display('rt/captureCite.tpl');
				break;
		} */

	}

	function printerFriendly($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $issue, $article) = RTHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournal($journal);

		if (!$journalRt || !$journalRt->getPrinterFriendly()) {
			Request::redirect(null, Request::getRequestedPage());
		}

		$articleGalleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$articleGalleyDao->getGalley($galleyId, $article->getArticleId());

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$section = &$sectionDao->getSection($article->getSectionId());

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('galley', $galley);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign_by_ref('section', $section);
		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign_by_ref('journal', $journal);
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);

		// Use the article's CSS file, if set.
		if ($galley && $galley->isHTMLGalley() && $styleFile =& $galley->getStyleFile()) {
			$templateMgr->addStyleSheet(Request::url(null, 'article', 'viewFile', array(
				$article->getArticleId(),
				$galley->getGalleyId(),
				$styleFile->getFileId()
			)));
		}

		$templateMgr->display('rt/printerFriendly.tpl');	
	}

	function emailColleague($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		list($journal, $issue, $article) = RTHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournal($journal);
		$user = &Request::getUser();

		if (!$journalRt || !$journalRt->getEmailOthers() || !$user) {
			Request::redirect(null, Request::getRequestedPage());
		}

		import('mail.MailTemplate');
		$email = &new MailTemplate('EMAIL_LINK');

		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$email->send();

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->display('rt/sent.tpl');
		} else {
			if (!Request::getUserVar('continued')) {
				$primaryAuthor = $article->getAuthors();
				$primaryAuthor = $primaryAuthor[0];

				$email->setSubject('[' . $journal->getSetting('journalInitials') . '] ' . strip_tags($article->getArticleTitle()));
				$email->assignParams(array(
					'articleTitle' => strip_tags($article->getArticleTitle()),
					'volume' => $issue?$issue->getVolume():null,
					'number' => $issue?$issue->getNumber():null,
					'year' => $issue?$issue->getYear():null,
					'authorName' => $primaryAuthor->getFullName(),
					'articleUrl' => Request::url(null, 'article', 'view', $article->getBestArticleId())
				));
			}
			$email->displayEditForm(Request::url(null, null, 'emailColleague', array($articleId, $galleyId)), null, 'rt/email.tpl', array('op' => 'emailColleague'));
		}
	}

	function emailAuthor($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		list($journal, $issue, $article) = RTHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournal($journal);
		$user = &Request::getUser();

		if (!$journalRt || !$journalRt->getEmailAuthor() || !$user) {
			Request::redirect(null, Request::getRequestedPage());
		}

		import('mail.MailTemplate');
		$email = &new MailTemplate();
		$email->setAddressFieldsEnabled(false);

		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$authors = &$article->getAuthors();
			$author = &$authors[0];
			$email->addRecipient($author->getEmail(), $author->getFullName());

			$email->send();

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->display('rt/sent.tpl');
		} else {
			if (!Request::getUserVar('continued')) {
				$email->setSubject('[' . $journal->getSetting('journalInitials') . '] ' . strip_tags($article->getArticleTitle()));
			}
			$email->displayEditForm(Request::url(null, null, 'emailAuthor', array($articleId, $galleyId)), null, 'rt/email.tpl', array('op' => 'emailAuthor'));
		}
	}

	function addComment($args) {
	}

	function suppFiles($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $issue, $article) = RTHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournal($journal);

		if (!$journalRt || !$journalRt->getSupplementaryFiles()) {
			Request::redirect(null, Request::getRequestedPage());
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('journalRt', $journalRt);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign_by_ref('journalSettings', $journal->getSettings());
		$templateMgr->display('rt/suppFiles.tpl');
	}

	function suppFileMetadata($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$suppFileId = isset($args[2]) ? (int) $args[2] : 0;
		list($journal, $issue, $article) = RTHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournal($journal);

		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$suppFile = $suppFileDao->getSuppFile($suppFileId, $article->getArticleId());

		if (!$journalRt || !$journalRt->getSupplementaryFiles() || !$suppFile) {
			Request::redirect(null, Request::getRequestedPage());
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('suppFile', $suppFile);
		$templateMgr->assign_by_ref('journalRt', $journalRt);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign_by_ref('journalSettings', $journal->getSettings());
		$templateMgr->display('rt/suppFileView.tpl');
	}

	function getParameterNames($value) {
		$matches = null;
		String::regexp_match_all('/\{\$([a-zA-Z0-9]+)\}/', $value, $matches);
		// Remove the entire string from the matches list
		return $matches[1];
	}
}

?>
