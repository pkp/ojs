<?php

/**
 * @file pages/search/SearchHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SearchHandler
 * @ingroup pages_search
 *
 * @brief Handle site index requests.
 */

import('classes.search.ArticleSearch');
import('classes.handler.Handler');

class SearchHandler extends Handler {
	/**
	 * Constructor
	 **/
	function SearchHandler() {
		parent::Handler();
		$this->addCheck(new HandlerValidatorCustom($this, false, null, null, create_function('$journal', 'return !$journal || $journal->getSetting(\'publishingMode\') != PUBLISHING_MODE_NONE;'), array(Request::getJournal())));
	}

	/**
	 * Show the advanced form
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, &$request) {
		$this->validate();
		$this->advanced($args, $request);
	}

	/**
	 * Show the advanced form
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function search($args, &$request) {
		$this->validate();
		$this->advanced($args, $request);
	}

	/**
	 * Show advanced search form.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function advanced($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, false);
		$templateMgr =& TemplateManager::getManager();
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');

		$journal =& $request->getJournal();
		if (!$journal) {
			$journalDao =& DAORegistry::getDAO('JournalDAO');
			$journals =& $journalDao->getJournalTitles(true);
			$templateMgr->assign('siteSearch', true);
			$templateMgr->assign('journalOptions', array('' => AppLocale::Translate('search.allJournals')) + $journals);
			$journalPath = $request->getRequestedJournalPath();
			$yearRange = $publishedArticleDao->getArticleYearRange(null);
		} else {
			$yearRange = $publishedArticleDao->getArticleYearRange($journal->getId());
		}

		$this->_assignAdvancedSearchParameters($request, $templateMgr, $yearRange);

		$templateMgr->display('search/advancedSearch.tpl');
	}

	/**
	 * Show index of published articles by author.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function authors($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$journal =& $request->getJournal();

		$authorDao =& DAORegistry::getDAO('AuthorDAO');

		if (isset($args[0]) && $args[0] == 'view') {
			// View a specific author
			$firstName = $request->getUserVar('firstName');
			$middleName = $request->getUserVar('middleName');
			$lastName = $request->getUserVar('lastName');
			$affiliation = $request->getUserVar('affiliation');
			$country = $request->getUserVar('country');

			$publishedArticles = $authorDao->getPublishedArticlesForAuthor($journal?$journal->getId():null, $firstName, $middleName, $lastName, $affiliation, $country);

			// Load information associated with each article.
			$journals = array();
			$issues = array();
			$sections = array();
			$issuesUnavailable = array();

			$issueDao =& DAORegistry::getDAO('IssueDAO');
			$sectionDao =& DAORegistry::getDAO('SectionDAO');
			$journalDao =& DAORegistry::getDAO('JournalDAO');

			foreach ($publishedArticles as $article) {
				$articleId = $article->getId();
				$issueId = $article->getIssueId();
				$sectionId = $article->getSectionId();
				$journalId = $article->getJournalId();

				if (!isset($issues[$issueId])) {
					import('classes.issue.IssueAction');
					$issue =& $issueDao->getIssueById($issueId);
					$issues[$issueId] =& $issue;
					$issuesUnavailable[$issueId] = IssueAction::subscriptionRequired($issue) && (!IssueAction::subscribedUser($journal, $issueId, $articleId) && !IssueAction::subscribedDomain($journal, $issueId, $articleId));
				}
				if (!isset($journals[$journalId])) {
					$journals[$journalId] =& $journalDao->getById($journalId);
				}
				if (!isset($sections[$sectionId])) {
					$sections[$sectionId] =& $sectionDao->getSection($sectionId, $journalId, true);
				}
			}

			if (empty($publishedArticles)) {
				$request->redirect(null, $request->getRequestedPage());
			}

			$templateMgr =& TemplateManager::getManager();
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
			$searchInitial = $request->getUserVar('searchInitial');
			$rangeInfo = $this->getRangeInfo('authors');

			$authors =& $authorDao->getAuthorsAlphabetizedByJournal(
				isset($journal)?$journal->getId():null,
				$searchInitial,
				$rangeInfo
			);

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('searchInitial', $request->getUserVar('searchInitial'));
			$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
			$templateMgr->assign_by_ref('authors', $authors);
			$templateMgr->display('search/authorIndex.tpl');
		}
	}

	/**
	 * Show index of published articles by title.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function titles($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$journal =& $request->getJournal();

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');

		$rangeInfo = $this->getRangeInfo('search');

		$articleIds =& $publishedArticleDao->getPublishedArticleIdsAlphabetizedByJournal(isset($journal)?$journal->getId():null);
		$totalResults = count($articleIds);
		$articleIds = array_slice($articleIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
		import('lib.pkp.classes.core.VirtualArrayIterator');
		$results = new VirtualArrayIterator(ArticleSearch::formatResults($articleIds), $totalResults, $rangeInfo->getPage(), $rangeInfo->getCount());

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('results', $results);
		$templateMgr->display('search/titleIndex.tpl');
	}

	/**
	 * Display categories.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function categories($args, &$request) {
		$this->validate();
		$this->setupTemplate($request);

		$site =& $request->getSite();
		$journal =& $request->getJournal();

		$categoryDao =& DAORegistry::getDAO('CategoryDAO');
		$cache =& $categoryDao->getCache();

		if ($journal || !$site->getSetting('categoriesEnabled') || !$cache) {
			$request->redirect('index');
		}

		// Sort by category name
		uasort($cache, create_function('$a, $b', '$catA = $a[\'category\']; $catB = $b[\'category\']; return strcasecmp($catA->getLocalizedName(), $catB->getLocalizedName());'));

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('categories', $cache);
		$templateMgr->display('search/categories.tpl');
	}

	/**
	 * Display category contents.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function category($args, &$request) {
		$categoryId = (int) array_shift($args);

		$this->validate();
		$this->setupTemplate($request, true, 'categories');

		$site =& $request->getSite();
		$journal =& $request->getJournal();

		$categoryDao =& DAORegistry::getDAO('CategoryDAO');
		$cache =& $categoryDao->getCache();

		if ($journal || !$site->getSetting('categoriesEnabled') || !$cache || !isset($cache[$categoryId])) {
			$request->redirect('index');
		}

		$journals =& $cache[$categoryId]['journals'];
		$category =& $cache[$categoryId]['category'];

		// Sort by journal name
		uasort($journals, create_function('$a, $b', 'return strcasecmp($a->getLocalizedTitle(), $b->getLocalizedTitle());'));

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('journals', $journals);
		$templateMgr->assign_by_ref('category', $category);
		$templateMgr->assign('journalFilesPath', $request->getBaseUrl() . '/' . Config::getVar('files', 'public_files_dir') . '/journals/');
		$templateMgr->display('search/category.tpl');
	}

	/**
	 * Show basic search results.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function results($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$rangeInfo = $this->getRangeInfo('search');

		$searchJournalId = $request->getUserVar('searchJournal');
		if (!empty($searchJournalId)) {
			$journalDao =& DAORegistry::getDAO('JournalDAO');
			$journal =& $journalDao->getById($searchJournalId);
		} else {
			$journal =& $request->getJournal();
		}

		$searchType = $request->getUserVar('searchField');
		if (!is_numeric($searchType)) $searchType = null;

		// Load the keywords array with submitted values
		$keywords = array($searchType => $request->getUserVar('query'));

		$error = '';
		$results =& ArticleSearch::retrieveResults($journal, $keywords, $error, null, null, $rangeInfo);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->setCacheability(CACHEABILITY_NO_STORE);
		$templateMgr->assign_by_ref('results', $results);
		$templateMgr->assign('error', $error);
		$templateMgr->assign('basicQuery', $request->getUserVar('query'));
		$templateMgr->assign('searchField', $request->getUserVar('searchField'));
		$templateMgr->display('search/searchResults.tpl');
	}

	/**
	 * Show advanced search results.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function advancedResults($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$rangeInfo = $this->getRangeInfo('search');

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$searchJournalId = $request->getUserVar('searchJournal');
		if (!empty($searchJournalId)) {
			$journalDao =& DAORegistry::getDAO('JournalDAO');
			$journal =& $journalDao->getById($searchJournalId);
			$yearRange = $publishedArticleDao->getArticleYearRange($journal->getId());
		} else {
			$journal =& $request->getJournal();
			$yearRange = $publishedArticleDao->getArticleYearRange(null);
		}

		// Load the keywords array with submitted values
		$keywords = array(null => $request->getUserVar('query'));
		$keywords[ARTICLE_SEARCH_AUTHOR] = $request->getUserVar('author');
		$keywords[ARTICLE_SEARCH_TITLE] = $request->getUserVar('title');
		$keywords[ARTICLE_SEARCH_DISCIPLINE] = $request->getUserVar('discipline');
		$keywords[ARTICLE_SEARCH_SUBJECT] = $request->getUserVar('subject');
		$keywords[ARTICLE_SEARCH_TYPE] = $request->getUserVar('type');
		$keywords[ARTICLE_SEARCH_COVERAGE] = $request->getUserVar('coverage');
		$keywords[ARTICLE_SEARCH_GALLEY_FILE] = $request->getUserVar('fullText');
		$keywords[ARTICLE_SEARCH_SUPPLEMENTARY_FILE] = $request->getUserVar('supplementaryFiles');

		$fromDate = $request->getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = $request->getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		$error = '';
		$results =& ArticleSearch::retrieveResults($journal, $keywords, $error, $fromDate, $toDate, $rangeInfo);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('results', $results);
		$templateMgr->assign('error', $error);
		$this->_assignAdvancedSearchParameters($request, $templateMgr, $yearRange);

		$templateMgr->display('search/searchResults.tpl');
	}

	/**
	 * Setup common template variables.
	 * @param $request PKPRequest
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 * @param $op string Current operation (for breadcrumb construction)
	 */
	function setupTemplate($request, $subclass = false, $op = 'index') {
		parent::setupTemplate();
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'user.searchAndBrowse');

		$opMap = array(
			'index' => 'navigation.search',
			'categories' => 'navigation.categories'
		);

		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array($request->url(null, 'search', $op), $opMap[$op]))
				: array()
		);

		$journal =& $request->getJournal();
		if (!$journal || !$journal->getSetting('restrictSiteAccess')) {
			$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
		}
	}

	/**
	 * Private function to retain assigned advanced search params in the
	 * template manager.
	 * @param $request PKPRequest
	 * @param $templateMgr TemplateManager
	 * @param $yearRange array
	 */
	function _assignAdvancedSearchParameters(&$request, &$templateMgr, $yearRange) {
		$templateMgr->assign('query', $request->getUserVar('query'));
		$templateMgr->assign('searchJournal', $request->getUserVar('searchJournal'));
		$templateMgr->assign('author', $request->getUserVar('author'));
		$templateMgr->assign('title', $request->getUserVar('title'));
		$templateMgr->assign('fullText', $request->getUserVar('fullText'));
		$templateMgr->assign('supplementaryFiles', $request->getUserVar('supplementaryFiles'));
		$templateMgr->assign('discipline', $request->getUserVar('discipline'));
		$templateMgr->assign('subject', $request->getUserVar('subject'));
		$templateMgr->assign('type', $request->getUserVar('type'));
		$templateMgr->assign('coverage', $request->getUserVar('coverage'));
		$fromMonth = $request->getUserVar('dateFromMonth');
		$fromDay = $request->getUserVar('dateFromDay');
		$fromYear = $request->getUserVar('dateFromYear');
		$templateMgr->assign('dateFromMonth', $fromMonth);
		$templateMgr->assign('dateFromDay', $fromDay);
		$templateMgr->assign('dateFromYear', $fromYear);
		if (!empty($fromYear)) $templateMgr->assign('dateFrom', date('Y-m-d H:i:s',mktime(0,0,0,$fromMonth==null?12:$fromMonth,$fromDay==null?31:$fromDay,$fromYear)));

		$toMonth = $request->getUserVar('dateToMonth');
		$toDay = $request->getUserVar('dateToDay');
		$toYear = $request->getUserVar('dateToYear');
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
