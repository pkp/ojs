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
	function authors($args) {
		parent::validate();
		SearchHandler::setupTemplate(true);

		$journal = Request::getJournal();
		if (!isset($journal)) {
			Request::redirect(Request::getPageUrl());
			return;
		}

		$authorDao = &DAORegistry::getDAO('AuthorDAO');

		if (isset($args[0]) && $args[0] == 'view') {
			$firstName = Request::getUserVar('firstName');
			$middleName = Request::getUserVar('middleName');
			$lastName = Request::getUserVar('lastName');
			$affiliation = Request::getUserVar('affiliation');

			$publishedArticles = $authorDao->getPublishedArticlesForAuthor($firstName, $middleName, $lastName, $affiliation);

			// Load information associated with each article.
			$issues = array();
			$sections = array();
			$issuesUnavailable = array();

			$issueDao = &DAORegistry::getDAO('IssueDAO');
			$sectionDao = &DAORegistry::getDAO('SectionDAO');

			foreach ($publishedArticles as $article) {
				$issueId = $article->getIssueId();
				$sectionId = $article->getSectionId();

				if (!isset($issues[$issueId])) {
					$issue = &$issueDao->getIssueById($issueId);
					$issues[$issueId] = &$issue;
					$issuesUnavailable[$issueId] = IssueAction::subscriptionRequired($issue) && !IssueAction::subscribedUser();
				}
				if (!isset($sections[$sectionId])) $sections[$sectionId] = &$sectionDao->getSection($sectionId);
			}

			if (empty($publishedArticles)) {
				Request::redirect(Request::getPageUrl());
				return;
			}

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('publishedArticles', $publishedArticles);
			$templateMgr->assign('issues', &$issues);
			$templateMgr->assign('issuesUnavailable', &$issuesSubscriptionRequired);
			$templateMgr->assign('sections', &$sections);
			$templateMgr->assign('firstName', $firstName);
			$templateMgr->assign('middleName', $middleName);
			$templateMgr->assign('lastName', $lastName);
			$templateMgr->assign('affiliation', $affiliation);
			$templateMgr->display('search/authorDetails.tpl');
		} else {
			$authors = &$authorDao->getAuthorsAlphabetizedByJournal($journal->getJournalId());

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('authors', &$authors);
			$templateMgr->display('search/authorIndex.tpl');
		}
	}
	
	/**
	 * Show basic search results.
	 */
	function results() {
		parent::validate();
		SearchHandler::setupTemplate(true);

		$journalDao = &DAORegistry::getDAO('JournalDAO');

		$journal = Request::getJournal();
		$searchJournal = Request::getUserVar('searchJournal');
		if (!empty($searchJournal)) {
			$journal = &$journalDao->getJournal($searchJournal);
		}

		switch (Request::getUserVar('searchField')) {
			case ARTICLE_SEARCH_AUTHOR:
				$searchType = ARTICLE_SEARCH_AUTHOR;
				$assocName = 'article.author';
				break;
			case ARTICLE_SEARCH_TITLE:
				$searchType = ARTICLE_SEARCH_TITLE;
				$assocName = null;
				break;
			case ARTICLE_SEARCH_ABSTRACT:
				$searchType = ARTICLE_SEARCH_ABSTRACT;
				$asocName = null;
				break;
			case ARTICLE_SEARCH_GALLEY_FILE:
				$searchType = ARTICLE_SEARCH_GALLEY_FILE;
				$assocName = null;
				break;
			default:
				// Match any field.
				$searchType = null;
				$assocName = null;
				break;
		}

		// Load the keywords array with submitted values
		$keywords = array($searchType => ArticleSearch::getKeywords(Request::getUserVar('query')));

		$results = &ArticleSearch::retrieveResults($journal, &$keywords);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('results', &$results);
		$templateMgr->assign('assocName', $assocName);
		$templateMgr->display('search/searchResults.tpl');
	}
	
	/**
	 * Show advanced search results.
	 */
	function advancedResults() {
		parent::validate();
		SearchHandler::setupTemplate(true);

		$journalDao = &DAORegistry::getDAO('JournalDAO');

		$journal = Request::getJournal();
		$searchJournal = Request::getUserVar('searchJournal');
		if (!empty($searchJournal)) {
			$journal = &$journalDao->getJournal($searchJournal);
		}

		// Load the keywords array with submitted values
		$keywords = array(null => ArticleSearch::getKeywords(Request::getUserVar('query')));
		$keywords[ARTICLE_SEARCH_AUTHOR] = ArticleSearch::getKeywords(Request::getUserVar('author'));
		$keywords[ARTICLE_SEARCH_TITLE] = ArticleSearch::getKeywords(Request::getUserVar('title'));
		$keywords[ARTICLE_SEARCH_DISCIPLINE] = ArticleSearch::getKeywords(Request::getUserVar('discipline'));
		$keywords[ARTICLE_SEARCH_SUBJECT] = ArticleSearch::getKeywords(Request::getUserVar('subject'));
		$keywords[ARTICLE_SEARCH_TYPE] = ArticleSearch::getKeywords(Request::getUserVar('type'));
		$keywords[ARTICLE_SEARCH_COVERAGE] = ArticleSearch::getKeywords(Request::getUserVar('coverage'));

		$fromMonth = Request::getUserVar('dateFromMonth');
                $fromDay = Request::getUserVar('dateFromDay');
                $fromYear = Request::getUserVar('dateFromYear');
		if (!empty($fromYear)) $fromDate = date('Y-m-d H:i:s',mktime(0,0,0,$fromMonth==null?12:$fromMonth,$fromDay==null?31:$fromDay,$fromYear));
		else $fromDate = null;

		$toMonth = Request::getUserVar('dateFromMonth');
                $toDay = Request::getUserVar('dateFromDay');
                $toYear = Request::getUserVar('dateFromYear');
		if (!empty($toYear)) $toDate = date('Y-m-d H:i:s',mktime(23,59,0,$toMonth==null?12:$toMonth,$toDay==null?31:$toDay,$toYear));
		else $toDate = null;

		$results = &ArticleSearch::retrieveResults($journal, &$keywords, $fromDate, $toDate);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('results', &$results);
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
