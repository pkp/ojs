<?php

/**
 * SearchHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.search
 *
 * Handle site index requests. 
 *
 * $Id$
 */

class SearchHandler extends Handler {

	/**
	 * Show basic search form.
	 */
	function index() {
		parent::validate();
		SearchHandler::setupTemplate();
		$templateMgr = &TemplateManager::getManager();
		
		if (Request::getJournal() == null) {
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$journals = &$journalDao->getEnabledJournalTitles(); //Enabled added
			$templateMgr->assign('siteSearch', true);
			$templateMgr->assign('journalOptions', array('' => Locale::Translate('search.allJournals')) + $journals);
			$journalPath = Request::getRequestedJournalPath();
		}
		
		$templateMgr->display('search/search.tpl');
	}
	
	/**
	 * Show basic search form.
	 */
	function search() {
		parent::validate();
		SearchHandler::index();
	}

	/**
	 * Show advanced search form.
	 */
	function advanced() {
		parent::validate();
		SearchHandler::setupTemplate(true);
		$templateMgr = &TemplateManager::getManager();
		
		if (Request::getJournal() == null) {
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$journals = &$journalDao->getEnabledJournalTitles();  //Enabled added
			$templateMgr->assign('siteSearch', true);
			$templateMgr->assign('journalOptions', array('' => Locale::Translate('search.allJournals')) + $journals);
			$journalPath = Request::getRequestedJournalPath();
		}
		
		$templateMgr->display('search/advancedSearch.tpl');
	}
	
	/**
	 * Show index of published articles by author.
	 */
	function authors() {
		parent::validate();
		SearchHandler::setupTemplate(true);
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->display('search/authorIndex.tpl');
	}
	
	/**
	 * Show basic search results.
	 */
	function results() {
		parent::validate();
		SearchHandler::setupTemplate(true);

		$articleDao = &DAORegistry::getDAO('ArticleDAO');

		switch (Request::getUserVar('searchField')) {
			case ARTICLE_SEARCH_BY_AUTHOR:
				$searchType = ARTICLE_SEARCH_AUTHOR;
				break;
			case ARTICLE_SEARCH_BY_TITLE:
				$searchType = ARTICLE_SEARCH_TITLE;
				break;
			case ARTICLE_SEARCH_BY_ABSTRACT:
				$searchType = ARTICLE_SEARCH_ABSTRACT;
				break;
			case ARTICLE_SEARCH_BY_KEYWORDS:
				$searchType = ARTICLE_SEARCH_GALLEY_FILE;
				break;
			default:
				$searchType = null;
				break;
		}

		$keywordIds = &ArticleSearch::getKeywordIds(Request::getUserVar('query'));
		$results = &ArticleSearch::retrieveResults(&$keywordIds, $searchType);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('results', &$results);
		$templateMgr->display('search/searchResults.tpl');
	}
	
	/**
	 * Show advanced search results.
	 */
	function advancedResults() {
		parent::validate();
		SearchHandler::setupTemplate(true);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->display('search/searchResults.tpl');
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		parent::validate();
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array('search', 'navigation.search'))
				: array()
		);
	}
	
}

?>
