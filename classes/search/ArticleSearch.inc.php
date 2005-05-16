<?php

/**
 * ArticleSearch.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package search
 *
 * Class for retrieving article search results.
 *
 * $Id$
 */
 
// Search types
define('ARTICLE_SEARCH_AUTHOR',			0x00000001);
define('ARTICLE_SEARCH_TITLE',			0x00000002);
define('ARTICLE_SEARCH_ABSTRACT',		0x00000003);
define('ARTICLE_SEARCH_DISCIPLINE',		0x00000004);
define('ARTICLE_SEARCH_SUBJECT',		0x00000005);
define('ARTICLE_SEARCH_TYPE',			0x00000006);
define('ARTICLE_SEARCH_COVERAGE',		0x00000007);
define('ARTICLE_SEARCH_GALLEY_FILE',		0x00000010);
define('ARTICLE_SEARCH_SUPPLEMENTARY_FILE',	0x00000020);

class ArticleSearch {
	/**
	 * Return an array of valid keyword IDs given a search
	 * string.
	 */
	function &getKeywords($queryString) {
		return String::regexp_split('/\s+/', $queryString);
	}

	/**
	 * See implementation of retrieveResults for a description of this
	 * function.
	 */
	function &_getMergedArray($journal, &$keywords, $publishedFrom, $publishedTo, &$resultCount) {
		$articleSearchDao = &DAORegistry::getDAO('ArticleSearchDAO');

		$resultsPerKeyword = Config::getVar('search', 'results_per_keyword');
		$resultCacheHours = Config::getVar('search', 'result_cache_hours');

		$mergedResults = array();
		foreach ($keywords as $type => $keywordsForType) foreach ($keywordsForType as $keyword) {
			$resultCount = 0;
			$results = &$articleSearchDao->getKeywordResults(
				$journal,
				$keyword,
				$publishedFrom,
				$publishedTo,
				$type,
				is_numeric($resultsPerKeyword)?$resultsPerKeyword:100,
				is_numeric($resultCacheHours)?$resultCacheHours:24
			);
			while (!$results->eof()) {
				$result = &$results->next();
				$articleId = &$result['article_id'];
				if (!isset($mergedResults[$articleId])) {
					$mergedResults[$articleId] = $result['count'];
				} else {
					$mergedResults[$articleId] += $result['count'];
				}
				$resultCount++;
			}
		}
		return $mergedResults;
	}

	/**
	 * See implementation of retrieveResults for a description of this
	 * function.
	 */
	function &_getSparseArray(&$mergedResults, $resultCount) {
		$results = array();
		$i = 0;
		foreach ($mergedResults as $articleId => $count) {
				$frequencyIndicator = ($resultCount * $count) + $i++;
				$results[$frequencyIndicator] = $articleId;
		}
		krsort(&$results);
		return $results;
	}

	/**
	 * See implementation of retrieveResults for a description of this
	 * function.
	 */
	function &_formatResults(&$results) {
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$journalDao = &DAORegistry::getDAO('JournalDAO');

		$publishedArticleCache = array();
		$articleCache = array();
		$issueCache = array();
		$issueAvailabilityCache = array();
		$journalCache = array();

		$returner = array();
		foreach ($results as $articleId) {
			// Get the article, storing in cache if necessary.
			if (!isset($articleCache[$articleId])) {
				$publishedArticleCache[$articleId] = $publishedArticleDao->getPublishedArticleByArticleId($articleId);
				$articleCache[$articleId] = $articleDao->getArticle($articleId);
			}
			$article = $articleCache[$articleId];
			$publishedArticle = $publishedArticleCache[$articleId];

			if ($publishedArticle && $article) {
				// Get the issue, storing in cache if necessary.
				$issueId = $publishedArticle->getIssueId();
				if (!isset($issueCache[$issueId])) {
					$issue = &$issueDao->getIssueById($issueId);
					$issueCache[$issueId] = &$issue;
					import('issue.IssueAction');
					$issueAvailabilityCache[$issueId] = !IssueAction::subscriptionRequired($issue) || !IssueAction::subscribedUser();
				}

				// Get the journal, storing in cache if necessary.
				$journalId = $article->getJournalId();
				if (!isset($journalCache[$journalId])) {
					$journalCache[$journalId] = $journalDao->getJournal($journalId);
				}
	
				// Store the retrieved objects in the result array.
				$returner[] = array('article' => $article, 'publishedArticle' => $publishedArticleCache[$articleId], 'issue' => $issueCache[$issueId], 'journal' => $journalCache[$journalId], 'issueAvailable' => $issueAvailabilityCache[$issueId]);
			}
		}
		return $returner;
	}

	/**
	 * Return an array of search results matching the supplied
	 * keyword IDs in decreasing order of match quality.
	 * Keywords are supplied in an array of the following format:
	 * $keywords[ARTICLE_SEARCH_AUTHOR] = array('John', 'Doe');
	 * $keywords[ARTICLE_SEARCH_...] = array(...);
	 * $keywords[null] = array('Matches', 'All', 'Fields');
	 * @param $journal object The journal to search
	 * @param $keywords array List of keywords
	 * @param $publishedFrom object Search-from date
	 * @param $publishedTo object Search-to date
	 * @param $rangeInfo Information on the range of results to return
	 */
	function &retrieveResults($journal, &$keywords, $publishedFrom = null, $publishedTo = null, $rangeInfo = null) {
		// Fetch all the results from all the keywords into one array
		// (mergedResults), where mergedResults[article_id]
		// = sum of all the occurences for all keywords associated with
		// that article ID.
		// resultCount contains the sum of result counts for all keywords.
		$mergedResults = &ArticleSearch::_getMergedArray($journal, &$keywords, $publishedFrom, $publishedTo, &$resultCount);

		// Convert mergedResults into an array (frequencyIndicator =>
		// $articleId).
		// The frequencyIndicator is a synthetically-generated number,
		// where higher is better, indicating the quality of the match.
		// It is generated here in such a manner that matches with
		// identical frequency do not collide.
		$results = &ArticleSearch::_getSparseArray(&$mergedResults, $resultCount);

		$totalResults = count($results);

		// Use only the results for the specified page, if specified.
		if ($rangeInfo && $rangeInfo->isValid()) {
			$results = &array_slice(
				&$results,
				$rangeInfo->getCount() * ($rangeInfo->getPage()-1),
				$rangeInfo->getCount()
			);
			$page = $rangeInfo->getPage();
			$itemsPerPage = $rangeInfo->getCount();
		} else {
			$page = 1;
			$itemsPerPage = max($totalResults, 1);
		}

		// Take the range of results and retrieve the Article, Journal,
		// and associated objects.
		$results = ArticleSearch::_formatResults(&$results);

		// Return the appropriate iterator.
		return new VirtualArrayIterator(&$results, $totalResults, $page, $itemsPerPage);
	}
}

?>
