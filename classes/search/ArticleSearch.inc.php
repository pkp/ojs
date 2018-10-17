<?php

/**
 * @file classes/search/ArticleSearch.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleSearch
 * @ingroup search
 * @see ArticleSearchDAO
 *
 * @brief Class for retrieving article search results.
 */

import('lib.pkp.classes.search.SubmissionSearch');

class ArticleSearch extends SubmissionSearch {

	/**
	 * See SubmissionSearch::getSparseArray()
	 */
	function getSparseArray($unorderedResults, $orderBy, $orderDir, $exclude) {
		// Calculate a well-ordered (unique) score.
		$resultCount = count($unorderedResults);
		$i = 0;
		foreach ($unorderedResults as $submissionId => &$data) {
			$data['score'] = ($resultCount * $data['count']) + $i++;
			unset($data);
		}

		// If we got a primary sort order then apply it and use score as secondary
		// order only.
		// NB: We apply order after merging and before paging/formatting. Applying
		// order before merging (i.e. in ArticleSearchDAO) would require us to
		// retrieve dependent objects for results being purged later. Doing
		// everything in a closed SQL is not possible (e.g. for authors). Applying
		// sort order after paging and formatting is not possible as we have to
		// order the whole list before slicing it. So this seems to be the most
		// appropriate place, although we may have to retrieve some objects again
		// when formatting results.
		$orderedResults = array();
		$authorDao = DAORegistry::getDAO('AuthorDAO'); /* @var $authorDao AuthorDAO */
		$articleDao = DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$journalTitles = array();
		if ($orderBy == 'popularityAll' || $orderBy == 'popularityMonth') {
			$application = Application::getApplication();
			$metricType = $application->getDefaultMetricType();
			if (is_null($metricType)) {
				// If no default metric has been found then sort by score...
				$orderBy = 'score';
			} else {
				// Retrieve a metrics report for all articles.
				$column = STATISTICS_DIMENSION_ARTICLE_ID;
				$filter = array(
					STATISTICS_DIMENSION_ASSOC_TYPE => array(ASSOC_TYPE_GALLEY, ASSOC_TYPE_ARTICLE),
					STATISTICS_DIMENSION_ARTICLE_ID => array(array_keys($unorderedResults))
				);
				if ($orderBy == 'popularityMonth') {
					$oneMonthAgo = date('Ymd', strtotime('-1 month'));
					$today = date('Ymd');
					$filter[STATISTICS_DIMENSION_DAY] = array('from' => $oneMonthAgo, 'to' => $today);
				}
				$rawReport = $application->getMetrics($metricType, $column, $filter);
				foreach ($rawReport as $row) {
					$unorderedResults[$row['submission_id']]['metric'] = (int)$row['metric'];
				}
			}
		}

		$i=0; // Used to prevent ties from clobbering each other
		foreach ($unorderedResults as $submissionId => $data) {
			// Exclude unwanted IDs.
			if (in_array($submissionId, $exclude)) continue;

			switch ($orderBy) {
				case 'authors':
					$authors = $authorDao->getBySubmissionId($submissionId);
					$authorNames = array();
					foreach ($authors as $author) { /* @var $author Author */
						$authorNames[] = $author->getFullName(false, true);
					}
					$orderKey = implode('; ', $authorNames);
					unset($authors, $authorNames);
					break;

				case 'title':
					$submission = $articleDao->getById($submissionId);
					$orderKey = $submission->getLocalizedTitle(null, false);
					break;

				case 'journalTitle':
					if (!isset($journalTitles[$data['journal_id']])) {
						$journal = $journalDao->getById($data['journal_id']);
						$journalTitles[$data['journal_id']] = $journal->getLocalizedName();
					}
					$orderKey = $journalTitles[$data['journal_id']];
					break;

				case 'issuePublicationDate':
				case 'publicationDate':
					$orderKey = $data[$orderBy];
					break;

				case 'popularityAll':
				case 'popularityMonth':
					$orderKey = (isset($data['metric']) ? $data['metric'] : 0);
					break;

				default: // order by score.
					$orderKey = $data['score'];
			}
			if (!isset($orderedResults[$orderKey])) {
				$orderedResults[$orderKey] = array();
			}
			$orderedResults[$orderKey][$data['score'] + $i++] = $submissionId;
		}

		// Order the results by primary order.
		if (strtolower($orderDir) == 'asc') {
			ksort($orderedResults);
		} else {
			krsort($orderedResults);
		}

		// Order the result by secondary order and flatten it.
		$finalOrder = array();
		foreach($orderedResults as $orderKey => $submissionIds) {
			if (count($submissionIds) == 1) {
				$finalOrder[] = array_pop($submissionIds);
			} else {
				if (strtolower($orderDir) == 'asc') {
					ksort($submissionIds);
				} else {
					krsort($submissionIds);
				}
				$finalOrder = array_merge($finalOrder, array_values($submissionIds));
			}
		}
		return $finalOrder;
	}

	/**
	 * Retrieve the search filters from the request.
	 * @param $request Request
	 * @return array All search filters (empty and active)
	 */
	function getSearchFilters($request) {
		$searchFilters = array(
			'query' => $request->getUserVar('query'),
			'searchJournal' => $request->getUserVar('searchJournal'),
			'abstract' => $request->getUserVar('abstract'),
			'authors' => $request->getUserVar('authors'),
			'title' => $request->getUserVar('title'),
			'galleyFullText' => $request->getUserVar('galleyFullText'),
			'discipline' => $request->getUserVar('discipline'),
			'subject' => $request->getUserVar('subject'),
			'type' => $request->getUserVar('type'),
			'coverage' => $request->getUserVar('coverage'),
			'indexTerms' => $request->getUserVar('indexTerms')
		);

		// Is this a simplified query from the navigation
		// block plugin?
		$simpleQuery = $request->getUserVar('simpleQuery');
		if (!empty($simpleQuery)) {
			// In the case of a simplified query we get the
			// filter type from a drop-down.
			$searchType = $request->getUserVar('searchField');
			if (array_key_exists($searchType, $searchFilters)) {
				$searchFilters[$searchType] = $simpleQuery;
			}
		}

		// Publishing dates.
		$fromDate = $request->getUserDateVar('dateFrom', 1, 1);
		$searchFilters['fromDate'] = (is_null($fromDate) ? null : date('Y-m-d H:i:s', $fromDate));
		$toDate = $request->getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		$searchFilters['toDate'] = (is_null($toDate) ? null : date('Y-m-d H:i:s', $toDate));

		// Instantiate the journal.
		$journal = $request->getJournal();
		$siteSearch = !((boolean)$journal);
		if ($siteSearch) {
			$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
			if (!empty($searchFilters['searchJournal'])) {
				$journal = $journalDao->getById($searchFilters['searchJournal']);
			} elseif (array_key_exists('journalTitle', $request->getUserVars())) {
				$journals = $journalDao->getTitles(false);
				while ($journal = $journals->next()) {
					if (in_array(
						$request->getUserVar('journalTitle'),
						(array) $journal->getTitle(null)
					)) break;
				}
			}
		}
		$searchFilters['searchJournal'] = $journal;
		$searchFilters['siteSearch'] = $siteSearch;

		return $searchFilters;
	}

	/**
	 * Load the keywords array from a given search filter.
	 * @param $searchFilters array Search filters as returned from
	 *  ArticleSearch::getSearchFilters()
	 * @return array Keyword array as required by SubmissionSearch::retrieveResults()
	 */
	function getKeywordsFromSearchFilters($searchFilters) {
		$indexFieldMap = $this->getIndexFieldMap();
		$indexFieldMap[SUBMISSION_SEARCH_INDEX_TERMS] = 'indexTerms';
		$keywords = array();
		if (isset($searchFilters['query'])) {
			$keywords[null] = $searchFilters['query'];
		}
		foreach($indexFieldMap as $bitmap => $searchField) {
			if (isset($searchFilters[$searchField]) && !empty($searchFilters[$searchField])) {
				$keywords[$bitmap] = $searchFilters[$searchField];
			}
		}
		return $keywords;
	}

	/**
	 * See SubmissionSearch::formatResults()
	 *
	 * @param $results array
	 * @param $user User optional (if availability information is desired)
	 * @return array An array with the articles, published articles,
	 *  issue, journal, section and the issue availability.
	 */
	function formatResults($results, $user = null) {
		$articleDao = DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$sectionDao = DAORegistry::getDAO('SectionDAO');

		$publishedArticleCache = array();
		$articleCache = array();
		$issueCache = array();
		$issueAvailabilityCache = array();
		$journalCache = array();
		$sectionCache = array();

		$returner = array();
		foreach ($results as $articleId) {
			// Get the article, storing in cache if necessary.
			if (!isset($articleCache[$articleId])) {
				$publishedArticleCache[$articleId] = $publishedArticleDao->getByArticleId($articleId);
				$articleCache[$articleId] = $articleDao->getById($articleId);
			}
			$article = $articleCache[$articleId];
			$publishedArticle = $publishedArticleCache[$articleId];

			if ($publishedArticle && $article) {
				$sectionId = $article->getSectionId();
				if (!isset($sectionCache[$sectionId])) {
					$sectionCache[$sectionId] = $sectionDao->getById($sectionId);
				}

				// Get the journal, storing in cache if necessary.
				$journalId = $article->getJournalId();
				if (!isset($journalCache[$journalId])) {
					$journalCache[$journalId] = $journalDao->getById($journalId);
				}

				// Get the issue, storing in cache if necessary.
				$issueId = $publishedArticle->getIssueId();
				if (!isset($issueCache[$issueId])) {
					$issue = $issueDao->getById($issueId);
					$issueCache[$issueId] = $issue;
					import('classes.issue.IssueAction');
					$issueAction = new IssueAction();
					$issueAvailabilityCache[$issueId] = !$issueAction->subscriptionRequired($issue, $journalCache[$journalId]) || $issueAction->subscribedUser($user, $journalCache[$journalId], $issueId, $articleId) || $issueAction->subscribedDomain(Application::getRequest(), $journalCache[$journalId], $issueId, $articleId);
				}

				// Only display articles from published issues.
				if (!$issueCache[$issueId]->getPublished()) continue;

				// Store the retrieved objects in the result array.
				$returner[] = array(
					'article' => $article,
					'publishedArticle' => $publishedArticleCache[$articleId],
					'issue' => $issueCache[$issueId],
					'journal' => $journalCache[$journalId],
					'issueAvailable' => $issueAvailabilityCache[$issueId],
					'section' => $sectionCache[$sectionId]
				);
			}
		}
		return $returner;
	}

	/**
	 * Identify similarity terms for a given submission.
	 * @param $submissionId integer
	 * @return null|array An array of string keywords or null
	 * if some kind of error occurred.
	 */
	function getSimilarityTerms($submissionId) {
		// Check whether a search plugin provides terms for a similarity search.
		$searchTerms = array();
		$result = HookRegistry::call('ArticleSearch::getSimilarityTerms', array($submissionId, &$searchTerms));

		// If no plugin implements the hook then use the subject keywords
		// of the submission for a similarity search.
		if ($result === false) {
			// Retrieve the article.
			$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
			$article = $publishedArticleDao->getByArticleId($submissionId);
			if (is_a($article, 'PublishedArticle')) {
				// Retrieve keywords (if any).
				$searchTerms = $article->getLocalizedSubject();
				// Tokenize keywords.
				$searchTerms = trim(preg_replace('/\s+/', ' ', strtr($searchTerms, ',;', ' ')));
				if (!empty($searchTerms)) $searchTerms = explode(' ', $searchTerms);
			}
		}

		return $searchTerms;
	}

	function getIndexFieldMap() {
		return array(
			SUBMISSION_SEARCH_AUTHOR => 'authors',
			SUBMISSION_SEARCH_TITLE => 'title',
			SUBMISSION_SEARCH_ABSTRACT => 'abstract',
			SUBMISSION_SEARCH_GALLEY_FILE => 'galleyFullText',
			SUBMISSION_SEARCH_DISCIPLINE => 'discipline',
			SUBMISSION_SEARCH_SUBJECT => 'subject',
			SUBMISSION_SEARCH_TYPE => 'type',
			SUBMISSION_SEARCH_COVERAGE => 'coverage'
		);
	}

	/**
	 * See SubmissionSearch::getResultSetOrderingOptions()
	 */
	function getResultSetOrderingOptions($request) {
		$resultSetOrderingOptions = array(
			'score' => __('search.results.orderBy.relevance'),
			'authors' => __('search.results.orderBy.author'),
			'issuePublicationDate' => __('search.results.orderBy.issue'),
			'publicationDate' => __('search.results.orderBy.date'),
			'title' => __('search.results.orderBy.article')
		);

		// Only show the "popularity" options if we have a default metric.
		$application = Application::getApplication();
		$metricType = $application->getDefaultMetricType();
		if (!is_null($metricType)) {
			$resultSetOrderingOptions['popularityAll'] = __('search.results.orderBy.popularityAll');
			$resultSetOrderingOptions['popularityMonth'] = __('search.results.orderBy.popularityMonth');
		}

		// Only show the "journal title" option if we have several journals.
		$journal = $request->getContext();
		if (!is_a($journal, 'Journal')) {
			$resultSetOrderingOptions['journalTitle'] = __('search.results.orderBy.journal');
		}

		// Let plugins mangle the search ordering options.
		HookRegistry::call(
			'SubmissionSearch::getResultSetOrderingOptions',
			array($journal, &$resultSetOrderingOptions)
		);

		return $resultSetOrderingOptions;
	}

	/**
	 * See SubmissionSearch::getDefaultOrderDir()
	 */
	function getDefaultOrderDir($orderBy) {
		$orderDir = 'asc';
		if (in_array($orderBy, array('score', 'publicationDate', 'issuePublicationDate', 'popularityAll', 'popularityMonth'))) {
			$orderDir = 'desc';
		}
		return $orderDir;
	}

	/**
	 * See SubmissionSearch::getSearchDao()
	 */
	protected function getSearchDao() {
		return DAORegistry::getDAO('ArticleSearchDAO');
	}
}


