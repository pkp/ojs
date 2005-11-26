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
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $issue, $article) = RTHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournalId($journal->getJournalId());

		if (!$journalRt || $journalRt->getVersion()==null || !$journalRt->getAuthorBio()) {
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
		$journalRt = &$rtDao->getJournalRTByJournalId($journal->getJournalId());

		if (!$journalRt || $journalRt->getVersion()==null || !$journalRt->getViewMetadata()) {
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
		$journalRt = &$rtDao->getJournalRTByJournalId($journal->getJournalId());

		$context = &$rtDao->getContext($contextId);
		if ($context) $version = &$rtDao->getVersion($context->getVersionId(), $journal->getJournalId());

		if (!$context || !$version || !$journalRt || $journalRt->getVersion()==null || $journalRt->getVersion() !=  $context->getVersionId()) {
			Request::redirect(null, 'article', 'view', array($articleId, $galleyId));
		}

		// Deal with the post and URL parameters for each search
		// so that the client browser can properly submit the forms
		// with a minimum of client-side processing.
		$searches = array();
		foreach ($context->getSearches() as $search) {
			$params = array();
			if ($search->getSearchPost()) {
				$postParams = explode('&', $search->getSearchPost());
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
			}
			
			$search->postParams = $params;
			$searches[] = $search;
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign_by_ref('version', $version);
		$templateMgr->assign_by_ref('context', $context);
		$templateMgr->assign_by_ref('searches', $searches);
		$templateMgr->assign('defineTerm', Request::getUserVar('defineTerm'));
		$templateMgr->assign('keywords', explode(';', $article->getSubject()));
		$templateMgr->assign_by_ref('journalSettings', $journal->getSettings());
		$templateMgr->display('rt/context.tpl');
	}
	
	function captureCite($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$citeType = isset($args[2]) ? $args[2] : null;

		list($journal, $issue, $article) = RTHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournalId($journal->getJournalId());

		if (!$journalRt || $journalRt->getVersion()==null || !$journalRt->getCaptureCite()) {
			Request::redirect(null, Request::getRequestedPage());
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('journalRt', $journalRt);
		$templateMgr->assign_by_ref('journal', $journal);
		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign('bibFormat', $journalRt->getBibFormat());
		$templateMgr->assign_by_ref('journalSettings', $journal->getSettings());

		switch ($citeType) {
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
		}

	}
	
	function printerFriendly($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $issue, $article) = RTHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournalId($journal->getJournalId());

		if (!$journalRt || $journalRt->getVersion()==null || !$journalRt->getPrinterFriendly()) {
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
		$templateMgr->display('rt/printerFriendly.tpl');	
	}
	
	function emailColleague($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		list($journal, $issue, $article) = RTHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournalId($journal->getJournalId());
		$user = &Request::getUser();

		if (!$journalRt || $journalRt->getVersion()==null || !$journalRt->getEmailOthers() || !$user) {
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
					'volume' => $issue->getVolume(),
					'number' => $issue->getNumber(),
					'year' => $issue->getYear(),
					'authorName' => $primaryAuthor->getFullName(),
					'articleUrl' => Request::url(null, 'article', 'view', $article->getBestArticleId())
				));
			}
			$email->displayEditForm(Request::url(null, null, 'emailColleague', array($articleId, $galleyId)), null, 'rt/email.tpl');
		}
	}

	function emailAuthor($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		list($journal, $issue, $article) = RTHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournalId($journal->getJournalId());
		$user = &Request::getUser();

		if (!$journalRt || $journalRt->getVersion()==null || !$journalRt->getEmailAuthor() || !$user) {
			Request::redirect(null, Request::getRequestedPage());
		}

		import('mail.MailTemplate');
		$email = &new MailTemplate();

		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$email->send();

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->display('rt/sent.tpl');
		} else {
			if (!Request::getUserVar('continued')) {
				$email->setSubject('[' . $journal->getSetting('journalInitials') . '] ' . strip_tags($article->getArticleTitle()));
				$authors = &$article->getAuthors();
				$author = &$authors[0];
				$email->addRecipient($author->getEmail(), $author->getFullName());
			}
			$email->displayEditForm(Request::url(null, null, 'emailAuthor', array($articleId, $galleyId)), null, 'rt/email.tpl');
		}
	}

	function addComment($args) {
	}
	
	function suppFiles($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $issue, $article) = RTHandler::validate($articleId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = &$rtDao->getJournalRTByJournalId($journal->getJournalId());

		if (!$journalRt || $journalRt->getVersion()==null || !$journalRt->getSupplementaryFiles()) {
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
		$journalRt = &$rtDao->getJournalRTByJournalId($journal->getJournalId());

		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$suppFile = $suppFileDao->getSuppFile($suppFileId, $article->getArticleId());

		if (!$journalRt || $journalRt->getVersion()==null || !$journalRt->getSupplementaryFiles() || !$suppFile) {
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
}

?>
