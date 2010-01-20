<?php

/**
 * @file SearchHandler.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SearchHandler
 * @ingroup pages_search
 *
 * @brief Handle site index requests. 
 */

// $Id$


import('search.ArticleSearch');

class SearchHandler extends Handler {

	/**
	 * Show the advanced form
	 */
	function index() {
		parent::validate();
		SearchHandler::advanced();
	}

	/**
	 * Show the advanced form
	 */
	function search() {
		parent::validate();
		SearchHandler::advanced();
	}

	/**
	 * Show advanced search form.
	 */
	function advanced() {
		parent::validate();
		SearchHandler::setupTemplate(false);
		$templateMgr = &TemplateManager::getManager();
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');

		if (Request::getJournal() == null) {
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$journals = &$journalDao->getEnabledJournalTitles();  //Enabled added
			$templateMgr->assign('siteSearch', true);
			$templateMgr->assign('journalOptions', array('' => Locale::Translate('search.allJournals')) + $journals);
			$journalPath = Request::getRequestedJournalPath();
			$yearRange = $publishedArticleDao->getArticleYearRange(null);
		} else {
			$journal =& Request::getJournal();
			$yearRange = $publishedArticleDao->getArticleYearRange($journal->getJournalId());
		}	

		SearchHandler::assignAdvancedSearchParameters($templateMgr, $yearRange);

		$templateMgr->display('search/advancedSearch.tpl');
	}

	/**
	 * Show index of published articles by author.
	 */
	function authors($args) {
		parent::validate();
		SearchHandler::setupTemplate(true);

		$journal =& Request::getJournal();

		$authorDao = &DAORegistry::getDAO('AuthorDAO');

		if (isset($args[0]) && $args[0] == 'view') {
			// View a specific author
			$firstName = Request::getUserVar('firstName');
			$middleName = Request::getUserVar('middleName');
			$lastName = Request::getUserVar('lastName');
			$affiliation = Request::getUserVar('affiliation');
			$country = Request::getUserVar('country');

			$publishedArticles = $authorDao->getPublishedArticlesForAuthor($journal?$journal->getJournalId():null, $firstName, $middleName, $lastName, $affiliation, $country);

			// Load information associated with each article.
			$journals = array();
			$issues = array();
			$sections = array();
			$issuesUnavailable = array();

			$issueDao =& DAORegistry::getDAO('IssueDAO');
			$sectionDao =& DAORegistry::getDAO('SectionDAO');
			$journalDao =& DAORegistry::getDAO('JournalDAO');

			foreach ($publishedArticles as $article) {
				$articleId = $article->getArticleId();
				$issueId = $article->getIssueId();
				$sectionId = $article->getSectionId();
				$journalId = $article->getJournalId();

				if (!isset($issues[$issueId])) {
					import('issue.IssueAction');
					$issue = &$issueDao->getIssueById($issueId);
					$issues[$issueId] = &$issue;
					$issuesUnavailable[$issueId] = IssueAction::subscriptionRequired($issue) && (!IssueAction::subscribedUser($journal, $issueId, $articleId) && !IssueAction::subscribedDomain($journal, $issueId, $articleId));
				}
				if (!isset($journals[$journalId])) {
					$journals[$journalId] =& $journalDao->getJournal($journalId);
				}
				if (!isset($sections[$sectionId])) {
					$sections[$sectionId] =& $sectionDao->getSection($sectionId);
				}
			}

			if (empty($publishedArticles)) {
				Request::redirect(null, Request::getRequestedPage());
			}

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign_by_ref('publishedArticles', $publishedArticles);
			$templateMgr->assign_by_ref('issues', $issues);
			$templateMgr->assign('issuesUnavailable', $issuesUnavailable);
			$templateMgr->assign_by_ref('sections', $sections);
			$templateMgr->assign_by_ref('journals', $journals);
			$templateMgr->assign('firstName', $firstName);
			$templateMgr->assign('middleName', $middleName);
			$templateMgr->assign('lastName', $lastName);
			$templateMgr->assign('affiliation', $affiliation);

			$countryDao =& DAORegistry::getDAO('CountryDAO');
			$country = $countryDao->getCountry($country);
			$templateMgr->assign('country', $country);

			$templateMgr->display('search/authorDetails.tpl');
		} else {
			// Show the author index
			$searchInitial = Request::getUserVar('searchInitial');
			$rangeInfo = Handler::getRangeInfo('authors');

			$authors = &$authorDao->getAuthorsAlphabetizedByJournal(
				isset($journal)?$journal->getJournalId():null,
				$searchInitial,
				$rangeInfo
			);

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));
			$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
			$templateMgr->assign_by_ref('authors', $authors);
			$templateMgr->display('search/authorIndex.tpl');
		}
	}

	/**
	 * Show index of published articles by title.
	 */
	function titles($args) {
		parent::validate();
		SearchHandler::setupTemplate(true);

		$journal =& Request::getJournal();

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');

		$rangeInfo = Handler::getRangeInfo('search');

		$articleIds = &$publishedArticleDao->getPublishedArticleIdsAlphabetizedByJournal(isset($journal)?$journal->getJournalId():null);
		$totalResults = count($articleIds);
		$articleIds = array_slice($articleIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
		$results = &new VirtualArrayIterator(ArticleSearch::formatResults($articleIds), $totalResults, $rangeInfo->getPage(), $rangeInfo->getCount());

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('results', $results);
		$templateMgr->display('search/titleIndex.tpl');
	}

	/**
	 * Show basic search results.
	 */
	function results() {
		parent::validate();
		SearchHandler::setupTemplate(true);

		$rangeInfo = Handler::getRangeInfo('search');

		$searchJournalId = Request::getUserVar('searchJournal');
		if (!empty($searchJournalId)) {
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$journal = &$journalDao->getJournal($searchJournalId);
		} else {
			$journal =& Request::getJournal();
		}

		$searchType = Request::getUserVar('searchField');
		if (!is_numeric($searchType)) $searchType = null;

		// Load the keywords array with submitted values
		$keywords = array($searchType => ArticleSearch::parseQuery(Request::getUserVar('query')));

		$results = &ArticleSearch::retrieveResults($journal, $keywords, null, null, $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->setCacheability(CACHEABILITY_NO_STORE);
		$templateMgr->assign_by_ref('results', $results);
		$templateMgr->assign('basicQuery', Request::getUserVar('query'));
		$templateMgr->assign('searchField', Request::getUserVar('searchField'));
		$templateMgr->display('search/searchResults.tpl');
	}

	/**
	 * Show advanced search results.
	 */
	function advancedResults() {
		parent::validate();
		SearchHandler::setupTemplate(true);

		$rangeInfo = Handler::getRangeInfo('search');

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$searchJournalId = Request::getUserVar('searchJournal');
		if (!empty($searchJournalId)) {
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$journal = &$journalDao->getJournal($searchJournalId);
			$yearRange = $publishedArticleDao->getArticleYearRange($journal->getJournalId());
		} else {
			$journal =& Request::getJournal();
			$yearRange = $publishedArticleDao->getArticleYearRange(null);
		}

		// Load the keywords array with submitted values
		$keywords = array(null => ArticleSearch::parseQuery(Request::getUserVar('query')));
		$keywords[ARTICLE_SEARCH_AUTHOR] = ArticleSearch::parseQuery(Request::getUserVar('author'));
		$keywords[ARTICLE_SEARCH_TITLE] = ArticleSearch::parseQuery(Request::getUserVar('title'));
		$keywords[ARTICLE_SEARCH_DISCIPLINE] = ArticleSearch::parseQuery(Request::getUserVar('discipline'));
		$keywords[ARTICLE_SEARCH_SUBJECT] = ArticleSearch::parseQuery(Request::getUserVar('subject'));
		$keywords[ARTICLE_SEARCH_TYPE] = ArticleSearch::parseQuery(Request::getUserVar('type'));
		$keywords[ARTICLE_SEARCH_COVERAGE] = ArticleSearch::parseQuery(Request::getUserVar('coverage'));
		$keywords[ARTICLE_SEARCH_GALLEY_FILE] = ArticleSearch::parseQuery(Request::getUserVar('fullText'));
		$keywords[ARTICLE_SEARCH_SUPPLEMENTARY_FILE] = ArticleSearch::parseQuery(Request::getUserVar('supplementaryFiles'));

		$fromDate = Request::getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = Request::getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		$results = &ArticleSearch::retrieveResults($journal, $keywords, $fromDate, $toDate, $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('results', $results);
		SearchHandler::assignAdvancedSearchParameters($templateMgr, $yearRange);

		$templateMgr->display('search/searchResults.tpl');
	}	

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		parent::validate();
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'user.searchAndBrowse');
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array(Request::url(null, 'search'), 'navigation.search'))
				: array()
		);

		$journal =& Request::getJournal();
		if (!$journal || !$journal->getSetting('restrictSiteAccess')) {
			$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
		}
	}

	function assignAdvancedSearchParameters(&$templateMgr, $yearRange) {
		$templateMgr->assign('query', Request::getUserVar('query'));
		$templateMgr->assign('searchJournal', Request::getUserVar('searchJournal'));
		$templateMgr->assign('author', Request::getUserVar('author'));
		$templateMgr->assign('title', Request::getUserVar('title'));
		$templateMgr->assign('fullText', Request::getUserVar('fullText'));
		$templateMgr->assign('supplementaryFiles', Request::getUserVar('supplementaryFiles'));
		$templateMgr->assign('discipline', Request::getUserVar('discipline'));
		$templateMgr->assign('subject', Request::getUserVar('subject'));
		$templateMgr->assign('type', Request::getUserVar('type'));
		$templateMgr->assign('coverage', Request::getUserVar('coverage'));
		$fromMonth = Request::getUserVar('dateFromMonth');
                $fromDay = Request::getUserVar('dateFromDay');
                $fromYear = Request::getUserVar('dateFromYear');
		$templateMgr->assign('dateFromMonth', $fromMonth);
		$templateMgr->assign('dateFromDay', $fromDay);
		$templateMgr->assign('dateFromYear', $fromYear);
		if (!empty($fromYear)) $templateMgr->assign('dateFrom', date('Y-m-d H:i:s',mktime(0,0,0,$fromMonth==null?12:$fromMonth,$fromDay==null?31:$fromDay,$fromYear)));

		$toMonth = Request::getUserVar('dateToMonth');
                $toDay = Request::getUserVar('dateToDay');
                $toYear = Request::getUserVar('dateToYear');
		$templateMgr->assign('dateToMonth', $toMonth);
		$templateMgr->assign('dateToDay', $toDay);
		$templateMgr->assign('dateToYear', $toYear);
		
		$startYear = '-' . (date('Y') - substr($yearRange[1], 0, 4));
		if (substr($yearRange[0], 0, 4) >= date('Y')) {
			$endYear = '+' . (substr($yearRange[0], 0, 4) - date('Y'));
		} else {
			$endYear = (substr($yearRange[0], 0, 4) - date('Y'));
		}
		$templateMgr->assign('endYear', $endYear);
		$templateMgr->assign('startYear', $startYear);
		if (!empty($toYear)) $templateMgr->assign('dateTo', date('Y-m-d H:i:s',mktime(0,0,0,$toMonth==null?12:$toMonth,$toDay==null?31:$toDay,$toYear)));
	}
}

?>
