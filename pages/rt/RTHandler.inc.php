<?php

/**
 * @file pages/rt/RTHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RTHandler
 * @ingroup pages_rt
 *
 * @brief Handle Reading Tools requests.
 */

import('lib.pkp.classes.rt.RT');

import('classes.rt.ojs.RTDAO');
import('classes.rt.ojs.JournalRT');

import('pages.article.ArticleHandler');

class RTHandler extends ArticleHandler {
	/**
	 * Constructor
	 * @param $request Request
	 */
	function RTHandler(&$request) {
		parent::ArticleHandler($request);
	}

	/**
	 * Display the article metadata
	 * @param $args array
	 * @param $request Request
	 */
	function metadata($args, &$request) {
		$router =& $request->getRouter();
		$this->setupTemplate($request);
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$this->validate($request, $articleId, $galleyId);

		$journal =& $router->getContext($request);
		$issue =& $this->issue;
		$article =& $this->article;

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$journalRt =& $rtDao->getJournalRTByJournal($journal);

		if (!$journalRt || !$journalRt->getViewMetadata()) {
			$request->redirect(null, $router->getRequestedPage($request));
		}

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$section =& $sectionDao->getSection($article->getSectionId(), $journal->getId(), true);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('journalRt', $journalRt);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign_by_ref('section', $section);
		$templateMgr->assign_by_ref('journalSettings', $journal->getSettings());
		// consider public identifiers
		$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true);
		$templateMgr->assign('pubIdPlugins', $pubIdPlugins);
		$templateMgr->assign('ccLicenseBadge', Application::getCCLicenseBadge($article->getLicenseURL()));
		$templateMgr->display('rt/metadata.tpl');
	}

	/**
	 * Display an RT search context
	 * @param $args array
	 * @param $request Request
	 */
	function context($args, &$request) {
		$router =& $request->getRouter();
		$this->setupTemplate($request);
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$contextId = Isset($args[2]) ? (int) $args[2] : 0;

		$this->validate($request, $articleId, $galleyId);
		$journal =& $router->getContext($request);
		$article =& $this->article;

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$journalRt =& $rtDao->getJournalRTByJournal($journal);

		$context =& $rtDao->getContext($contextId);
		if ($context) $version =& $rtDao->getVersion($context->getVersionId(), $journal->getId());

		if (!$context || !$version || !$journalRt || $journalRt->getVersion()==null || $journalRt->getVersion() !=  $context->getVersionId()) {
			$request->redirect(null, 'article', 'view', array($articleId, $galleyId));
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
			$searchParams += $this->_getParameterNames($search->getSearchUrl());
			if ($search->getSearchPost()) {
				$searchParams += $this->_getParameterNames($search->getSearchPost());
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
				$searchValues[$param] = $article->getLocalizedCoverageGeo();
				break;
			case 'title':
				$searchValues[$param] = $article->getLocalizedTitle();
				break;
			default:
				// UNKNOWN parameter! Remove it from the list.
				unset($searchParams[$key]);
				break;
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign_by_ref('version', $version);
		$templateMgr->assign_by_ref('context', $context);
		$templateMgr->assign_by_ref('searches', $searches);
		$templateMgr->assign('searchParams', $searchParams);
		$templateMgr->assign('searchValues', $searchValues);
		$templateMgr->assign('defineTerm', $request->getUserVar('defineTerm'));
		$templateMgr->assign('keywords', explode(';', $article->getLocalizedSubject()));
		$templateMgr->assign('coverageGeo', $article->getLocalizedCoverageGeo());
		$templateMgr->assign_by_ref('journalSettings', $journal->getSettings());
		$templateMgr->display('rt/context.tpl');
	}

	/**
	 * Display citation information
	 * @param $args array
	 * @param $request Request
	 */
	function captureCite($args, &$request) {
		$router =& $request->getRouter();
		$this->setupTemplate($request);
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$citeType = isset($args[2]) ? $args[2] : null;

		$this->validate($request, $articleId, $galleyId);
		$journal =& $router->getContext($request);
		$issue =& $this->issue;
		$article =& $this->article;

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$journalRt =& $rtDao->getJournalRTByJournal($journal);

		if (!$journalRt || !$journalRt->getCaptureCite()) {
			$request->redirect(null, $router->getRequestedPage($request));
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);

		$citationPlugins =& PluginRegistry::loadCategory('citationFormats');
		uasort($citationPlugins, create_function('$a, $b', 'return strcmp($a->getDisplayName(), $b->getDisplayName());'));
		$templateMgr->assign_by_ref('citationPlugins', $citationPlugins);
		if (isset($citationPlugins[$citeType])) {
			// A citation type has been selected; display citation.
			$citationPlugin =& $citationPlugins[$citeType];
		} else {
			// No citation type chosen; choose a default off the top of the list.
			$citationPlugin = $citationPlugins[array_shift(array_keys($citationPlugins))];
		}
		$citationPlugin->displayCitation($article, $issue, $journal);
	}

	/**
	 * Display a printer-friendly version of the article
	 * @param $args array
	 * @param $request Request
	 */
	function printerFriendly($args, &$request) {
		$router =& $request->getRouter();
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;

		$this->validate($request, $articleId, $galleyId);
		$journal =& $router->getContext($request);
		$issue =& $this->issue;
		$article =& $this->article;

		$this->setupTemplate($request);

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$journalRt =& $rtDao->getJournalRTByJournal($journal);

		if (!$journalRt || !$journalRt->getPrinterFriendly()) {
			$request->redirect(null, $router->getRequestedPage($request));
		}

		$articleGalleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		if ($journal->getSetting('enablePublicGalleyId')) {
			$galley =& $articleGalleyDao->getGalleyByBestGalleyId($galleyId, $article->getId());
		} else {
			$galley =& $articleGalleyDao->getGalley($galleyId, $article->getId());
		}

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$section =& $sectionDao->getSection($article->getSectionId(), $journal->getJournalId(), true);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('galley', $galley);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign_by_ref('section', $section);
		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign_by_ref('journal', $journal);
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);

		// Use the article's CSS file, if set.
		if ($galley && $galley->isHTMLGalley() && $styleFile =& $galley->getStyleFile()) {
			$templateMgr->addStyleSheet($router->url($request, null, 'article', 'viewFile', array(
				$article->getId(),
				$galley->getBestGalleyId($journal),
				$styleFile->getFileId()
			)));
		}

		$templateMgr->display('rt/printerFriendly.tpl');
	}

	/**
	 * Display the "Email Colleague" form
	 * @param $args array
	 * @param $request Request
	 */
	function emailColleague($args, &$request) {
		$router =& $request->getRouter();
		$this->setupTemplate($request);
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		$this->validate($request, $articleId, $galleyId);
		$journal =& $router->getContext($request);
		$issue =& $this->issue;
		$article =& $this->article;

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$journalRt =& $rtDao->getJournalRTByJournal($journal);
		$user =& $request->getUser();

		if (!$journalRt || !$journalRt->getEmailOthers() || !$user) {
			$request->redirect(null, $router->getRequestedPage($request));
		}

		import('classes.mail.MailTemplate');
		$email = new MailTemplate('EMAIL_LINK');

		if ($request->getUserVar('send') && !$email->hasErrors()) {
			$email->send();

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->display('rt/sent.tpl');
		} else {
			if (!$request->getUserVar('continued')) {
				$primaryAuthor = $article->getAuthors();
				$primaryAuthor = $primaryAuthor[0];

				$email->setSubject('[' . $journal->getLocalizedSetting('initials') . '] ' . strip_tags($article->getLocalizedTitle()));
				$email->assignParams(array(
					'articleTitle' => strip_tags($article->getLocalizedTitle()),
					'volume' => $issue?$issue->getVolume():null,
					'number' => $issue?$issue->getNumber():null,
					'year' => $issue?$issue->getYear():null,
					'authorName' => $primaryAuthor->getFullName(),
					'articleUrl' => $router->url($request, null, 'article', 'view', array($article->getBestArticleId()))
				));
			}
			$email->displayEditForm($router->url($request, null, null, 'emailColleague', array($articleId, $galleyId)), null, 'rt/email.tpl', array('op' => 'emailColleague'));
		}
	}

	/**
	 * Display the "email author" form
	 * @param $args array
	 * @param $request Request
	 */
	function emailAuthor($args, &$request) {
		$router =& $request->getRouter();
		$this->setupTemplate($request);
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		$this->validate($request, $articleId, $galleyId);
		$journal =& $router->getContext($request);
		$article =& $this->article;

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$journalRt =& $rtDao->getJournalRTByJournal($journal);
		$user =& $request->getUser();

		if (!$journalRt || !$journalRt->getEmailAuthor() || !$user) {
			$request->redirect(null, $router->getRequestedPage($request));
		}

		import('classes.mail.MailTemplate');
		$email = new MailTemplate();
		$email->setAddressFieldsEnabled(false);

		if ($request->getUserVar('send') && !$email->hasErrors()) {
			$authors =& $article->getAuthors();
			$author =& $authors[0];
			$email->addRecipient($author->getEmail(), $author->getFullName());

			$email->send();

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->display('rt/sent.tpl');
		} else {
			if (!$request->getUserVar('continued')) {
				$email->setSubject('[' . $journal->getLocalizedSetting('initials') . '] ' . strip_tags($article->getLocalizedTitle()));
			}
			$email->displayEditForm($router->url($request, null, null, 'emailAuthor', array($articleId, $galleyId)), null, 'rt/email.tpl', array('op' => 'emailAuthor'));
		}
	}

	/**
	 * Display a list of supplementary files
	 * @param $args array
	 * @param $request Request
	 */
	function suppFiles($args, &$request) {
		$router =& $request->getRouter();
		$this->setupTemplate($request);
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		$this->validate($request, $articleId, $galleyId);
		$journal =& $router->getContext($request);
		$article =& $this->article;

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$journalRt =& $rtDao->getJournalRTByJournal($journal);

		if (!$journalRt || !$journalRt->getSupplementaryFiles()) {
			$request->redirect(null, $router->getRequestedPage($request));
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('journalRt', $journalRt);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign_by_ref('journalSettings', $journal->getSettings());
		$templateMgr->display('rt/suppFiles.tpl');
	}

	/**
	 * Display the metadata of a supplementary file
	 * @param $args array
	 * @param $request Request
	 */
	function suppFileMetadata($args, &$request) {
		$router =& $request->getRouter();
		$this->setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_OJS_AUTHOR);
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$suppFileId = isset($args[2]) ? (int) $args[2] : 0;

		$this->validate($request, $articleId, $galleyId);
		$journal =& $router->getContext($request);
		$article =& $this->article;

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$journalRt =& $rtDao->getJournalRTByJournal($journal);

		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$suppFile = $suppFileDao->getSuppFile($suppFileId, $article->getId());

		if (!$journalRt || !$journalRt->getSupplementaryFiles() || !$suppFile) {
			$request->redirect(null, $router->getRequestedPage($request));
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('suppFile', $suppFile);
		$templateMgr->assign_by_ref('journalRt', $journalRt);
		$templateMgr->assign_by_ref('issue', $this->issue);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign_by_ref('journalSettings', $journal->getSettings());
		// consider public identifiers
		$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true);
		$templateMgr->assign('pubIdPlugins', $pubIdPlugins);
		$templateMgr->display('rt/suppFileView.tpl');
	}

	/**
	 * Display the "finding references" search engine list
	 * @param $args array
	 * @param $request Request
	 */
	function findingReferences($args, &$request) {
		$router =& $request->getRouter();
		$this->setupTemplate($request);
		$articleId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		$this->validate($request, $articleId, $galleyId);
		$journal =& $router->getContext($request);
		$article =& $this->article;

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$journalRt =& $rtDao->getJournalRTByJournal($journal);

		if (!$journalRt || !$journalRt->getFindingReferences()) {
			$request->redirect(null, $router->getRequestedPage($request));
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('journalRt', $journalRt);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->display('rt/findingReferences.tpl');
	}

	/**
	 * Get parameter values: Used internally for RT searches
	 */
	function _getParameterNames($value) {
		$matches = null;
		String::regexp_match_all('/\{\$([a-zA-Z0-9]+)\}/', $value, $matches);
		// Remove the entire string from the matches list
		return $matches[1];
	}
}

?>
