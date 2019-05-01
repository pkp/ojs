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
	 * Show the search form
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, &$request) {
		$this->validate();
		$this->search($args, $request);
	}

	/**
	 * Private function to transmit current filter values
	 * to the template.
	 * @param $request PKPRequest
	 * @param $templateMgr TemplateManager
	 * @param $searchFilters array
	 */
	function _assignSearchFilters(&$request, &$templateMgr, $searchFilters) {
		// Get the journal id (if any).
		$journal =& $searchFilters['searchJournal'];
		$journalId = ($journal ? $journal->getId() : null);
		$searchFilters['searchJournal'] = $journalId;

		// Assign all filters except for dates which need special treatment.
		$templateSearchFilters = array();
		foreach($searchFilters as $filterName => $filterValue) {
			if (in_array($filterName, array('fromDate', 'toDate'))) continue;
			$templateSearchFilters[$filterName] = $filterValue;
		}

		// Find out whether we have active/empty filters.
		$hasActiveFilters = false;
		$hasEmptyFilters = false;
		foreach($templateSearchFilters as $filterName => $filterValue) {
			// The main query and journal selector will always be displayed
			// apart from other filters.
			if (in_array($filterName, array('query', 'searchJournal', 'siteSearch'))) continue;
			if (empty($filterValue)) {
				$hasEmptyFilters = true;
			} else {
				$hasActiveFilters = true;
			}
		}

		// Assign the filters to the template.
		$templateMgr->assign($templateSearchFilters);

		// Special case: publication date filters.
		foreach(array('From', 'To') as $fromTo) {
			$month = $request->getUserVar("date${fromTo}Month");
			$day = $request->getUserVar("date${fromTo}Day");
			$year = $request->getUserVar("date${fromTo}Year");
			if (empty($year)) {
				$date = NULL;
				$hasEmptyFilters = true;
			} else {
				$defaultMonth = ($fromTo == 'From' ? 1 : 12);
				$defaultDay = ($fromTo == 'From' ? 1 : 31);
				$date = date(
					'Y-m-d H:i:s',
					mktime(
						0, 0, 0, empty($month) ? $defaultMonth : $month,
						empty($day) ? $defaultDay : $day, $year
					)
				);
				$hasActiveFilters = true;
			}
			$templateMgr->assign(array(
				"date${fromTo}Month" => $month,
				"date${fromTo}Day" => $day,
				"date${fromTo}Year" => $year,
				"date${fromTo}" => $date
			));
		}

		// Assign filter flags to the template.
		$templateMgr->assign(compact('hasEmptyFilters', 'hasActiveFilters'));

		// Assign the year range.
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$yearRange = $publishedArticleDao->getArticleYearRange($journalId);
		$startYear = '-' . (date('Y') - substr($yearRange[1], 0, 4));
		if (substr($yearRange[0], 0, 4) >= date('Y')) {
			$endYear = '+' . (substr($yearRange[0], 0, 4) - date('Y'));
		} else {
			$endYear = (substr($yearRange[0], 0, 4) - date('Y'));
		}
		$templateMgr->assign(compact('startYear', 'endYear'));

		// Assign journal options.
		if ($searchFilters['siteSearch']) {
			$journalDao =& DAORegistry::getDAO('JournalDAO');
			$journals =& $journalDao->getJournalTitles(true);
			$templateMgr->assign('journalOptions', array('' => AppLocale::Translate('search.allJournals')) + $journals);
		}
	}

	/**
	 * Show the search form
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function search($args, &$request) {
		$this->validate();

		// Get and transform active filters.
		$searchFilters = ArticleSearch::getSearchFilters($request);
		$keywords = ArticleSearch::getKeywordsFromSearchFilters($searchFilters);

		// Get the range info.
		$rangeInfo = $this->getRangeInfo('search');

		// Retrieve results.
		$error = '';
		$results =& ArticleSearch::retrieveResults(
			$searchFilters['searchJournal'], $keywords, $error,
			$searchFilters['fromDate'], $searchFilters['toDate'],
			$rangeInfo
		);

		// Prepare and display the search template.
		$this->setupTemplate($request, true);
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->setCacheability(CACHEABILITY_NO_STORE);
		$templateMgr->assign('jsLocaleKeys', array('search.noKeywordError'));
		$this->_assignSearchFilters($request, $templateMgr, $searchFilters);
		$templateMgr->assign_by_ref('results', $results);
		$templateMgr->assign('error', $error);
		$templateMgr->display('search/search.tpl');
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
}

?>
