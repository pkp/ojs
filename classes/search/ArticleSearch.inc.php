<?php

/**
 * @file classes/search/ArticleSearch.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleSearch
 * @ingroup search
 * @see ArticleSearchDAO
 *
 * @brief Class for retrieving article search results.
 *
 * FIXME: NEAR; precedence w/o parens?; stemming; weighted counting
 */

// Search types
define('ARTICLE_SEARCH_AUTHOR',			0x00000001);
define('ARTICLE_SEARCH_TITLE',			0x00000002);
define('ARTICLE_SEARCH_ABSTRACT',		0x00000004);
define('ARTICLE_SEARCH_DISCIPLINE',		0x00000008);
define('ARTICLE_SEARCH_SUBJECT',		0x00000010);
define('ARTICLE_SEARCH_TYPE',			0x00000020);
define('ARTICLE_SEARCH_COVERAGE',		0x00000040);
define('ARTICLE_SEARCH_GALLEY_FILE',		0x00000080);
define('ARTICLE_SEARCH_SUPPLEMENTARY_FILE',	0x00000100);
define('ARTICLE_SEARCH_INDEX_TERMS',		0x00000078);

define('ARTICLE_SEARCH_DEFAULT_RESULT_LIMIT', 20);

import('classes.search.ArticleSearchIndex');

class ArticleSearch {

	/**
	 * Parses a search query string.
	 * Supports +/-, AND/OR, parens
	 * @param $query
	 * @return array of the form ('+' => <required>, '' => <optional>, '-' => excluded)
	 */
	function _parseQuery($query) {
		$count = preg_match_all('/(\+|\-|)("[^"]+"|\(|\)|[^\s\)]+)/', $query, $matches);
		$pos = 0;
		$keywords = ArticleSearch::_parseQueryInternal($matches[1], $matches[2], $pos, $count);
		return $keywords;
	}

	/**
	 * Query parsing helper routine.
	 * Returned structure is based on that used by the Search::QueryParser Perl module.
	 */
	function _parseQueryInternal($signTokens, $tokens, &$pos, $total) {
		$return = array('+' => array(), '' => array(), '-' => array());
		$postBool = $preBool = '';

		$notOperator = String::strtolower(__('search.operator.not'));
		$andOperator = String::strtolower(__('search.operator.and'));
		$orOperator = String::strtolower(__('search.operator.or'));
		while ($pos < $total) {
			if (!empty($signTokens[$pos])) $sign = $signTokens[$pos];
			else if (empty($sign)) $sign = '+';
			$token = String::strtolower($tokens[$pos++]);
			switch ($token) {
				case $notOperator:
					$sign = '-';
					break;
				case ')':
					return $return;
				case '(':
					$token = ArticleSearch::_parseQueryInternal($signTokens, $tokens, $pos, $total);
				default:
					$postBool = '';
					if ($pos < $total) {
						$peek = String::strtolower($tokens[$pos]);
						if ($peek == $orOperator) {
							$postBool = 'or';
							$pos++;
						} else if ($peek == $andOperator) {
							$postBool = 'and';
							$pos++;
						}
					}
					$bool = empty($postBool) ? $preBool : $postBool;
					$preBool = $postBool;
					if ($bool == 'or') $sign = '';
					if (is_array($token)) {
						$k = $token;
					} else {
						$articleSearchIndex = new ArticleSearchIndex();
						$k = $articleSearchIndex->filterKeywords($token, true);
					}
					if (!empty($k)) $return[$sign][] = $k;
					$sign = '';
					break;
			}
		}
		return $return;
	}

	/**
	 * See implementation of retrieveResults for a description of this
	 * function.
	 */
	function &_getMergedArray(&$journal, &$keywords, $publishedFrom, $publishedTo) {
		$resultsPerKeyword = Config::getVar('search', 'results_per_keyword');
		$resultCacheHours = Config::getVar('search', 'result_cache_hours');
		if (!is_numeric($resultsPerKeyword)) $resultsPerKeyword = 100;
		if (!is_numeric($resultCacheHours)) $resultCacheHours = 24;

		$mergedKeywords = array('+' => array(), '' => array(), '-' => array());
		foreach ($keywords as $type => $keyword) {
			if (!empty($keyword['+']))
				$mergedKeywords['+'][] = array('type' => $type, '+' => $keyword['+'], '' => array(), '-' => array());
			if (!empty($keyword['']))
				$mergedKeywords[''][] = array('type' => $type, '+' => array(), '' => $keyword[''], '-' => array());
			if (!empty($keyword['-']))
				$mergedKeywords['-'][] = array('type' => $type, '+' => array(), '' => $keyword['-'], '-' => array());
		}
		$mergedResults =& ArticleSearch::_getMergedKeywordResults($journal, $mergedKeywords, null, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours);

		return $mergedResults;
	}

	/**
	 * Recursive helper for _getMergedArray.
	 */
	function &_getMergedKeywordResults(&$journal, &$keyword, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours) {
		$mergedResults = null;

		if (isset($keyword['type'])) {
			$type = $keyword['type'];
		}

		foreach ($keyword['+'] as $phrase) {
			$results =& ArticleSearch::_getMergedPhraseResults($journal, $phrase, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours);
			if ($mergedResults === null) {
				$mergedResults = $results;
			} else {
				foreach ($mergedResults as $articleId => $count) {
					if (isset($results[$articleId])) {
						$mergedResults[$articleId] += $results[$articleId];
					} else {
						unset($mergedResults[$articleId]);
					}
				}
			}
		}

		if ($mergedResults == null) {
			$mergedResults = array();
		}

		if (!empty($mergedResults) || empty($keyword['+'])) {
			foreach ($keyword[''] as $phrase) {
				$results =& ArticleSearch::_getMergedPhraseResults($journal, $phrase, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours);
				foreach ($results as $articleId => $count) {
					if (isset($mergedResults[$articleId])) {
						$mergedResults[$articleId] += $count;
					} else if (empty($keyword['+'])) {
						$mergedResults[$articleId] = $count;
					}
				}
			}

			foreach ($keyword['-'] as $phrase) {
				$results =& ArticleSearch::_getMergedPhraseResults($journal, $phrase, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours);
				foreach ($results as $articleId => $count) {
					if (isset($mergedResults[$articleId])) {
						unset($mergedResults[$articleId]);
					}
				}
			}
		}

		return $mergedResults;
	}

	/**
	 * Recursive helper for _getMergedArray.
	 */
	function &_getMergedPhraseResults(&$journal, &$phrase, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours) {
		if (isset($phrase['+'])) {
			$mergedResults =& ArticleSearch::_getMergedKeywordResults($journal, $phrase, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours);
			return $mergedResults;
		}

		$mergedResults = array();
		$articleSearchDao =& DAORegistry::getDAO('ArticleSearchDAO'); /* @var $articleSearchDao ArticleSearchDAO */
		$results =& $articleSearchDao->getPhraseResults(
			$journal,
			$phrase,
			$publishedFrom,
			$publishedTo,
			$type,
			$resultsPerKeyword,
			$resultCacheHours
		);
		while (!$results->eof()) {
			$result =& $results->next();
			$articleId = $result['article_id'];
			if (!isset($mergedResults[$articleId])) {
				$mergedResults[$articleId] = $result['count'];
			} else {
				$mergedResults[$articleId] += $result['count'];
			}
		}
		return $mergedResults;
	}

	/**
	 * See implementation of retrieveResults for a description of this
	 * function.
	 */
	function &_getSparseArray(&$mergedResults) {
		$resultCount = count($mergedResults);
		$results = array();
		$i = 0;
		foreach ($mergedResults as $articleId => $count) {
				$frequencyIndicator = ($resultCount * $count) + $i++;
				$results[$frequencyIndicator] = $articleId;
		}
		krsort($results);
		return $results;
	}

	/**
	 * Retrieve the search filters from the
	 * request.
	 * @param $request Request
	 * @return array All search filters (empty and active)
	 */
	function getSearchFilters(&$request) {
		$searchFilters = array(
			'query' => $request->getUserVar('query'),
			'searchJournal' => $request->getUserVar('searchJournal'),
			'abstract' => $request->getUserVar('abstract'),
			'authors' => $request->getUserVar('authors'),
			'title' => $request->getUserVar('title'),
			'galleyFullText' => $request->getUserVar('galleyFullText'),
			'suppFiles' => $request->getUserVar('suppFiles'),
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
		$journal =& $request->getJournal();
		$siteSearch = !((boolean)$journal);
		if ($siteSearch) {
			$journalDao =& DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
			if (!empty($searchFilters['searchJournal'])) {
				$journal =& $journalDao->getById($searchFilters['searchJournal']);
			} elseif (array_key_exists('journalTitle', $request->getUserVars())) {
				$journals =& $journalDao->getJournals(
					false, null, JOURNAL_FIELD_TITLE,
					JOURNAL_FIELD_TITLE, 'is', $request->getUserVar('journalTitle')
				);
				if ($journals->getCount() == 1) {
					$journal =& $journals->next();
				}
			}
		}
		$searchFilters['searchJournal'] =& $journal;
		$searchFilters['siteSearch'] = $siteSearch;

		return $searchFilters;
	}

	/**
	 * Load the keywords array from a given search filter.
	 * @param $searchFilters array Search filters as returned from
	 *  ArticleSearch::getSearchFilters()
	 * @return array Keyword array as required by ArticleSearch::retrieveResults()
	 */
	function getKeywordsFromSearchFilters($searchFilters) {
		$indexFieldMap = ArticleSearch::getIndexFieldMap();
		$indexFieldMap[ARTICLE_SEARCH_INDEX_TERMS] = 'indexTerms';
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
	 * See implementation of retrieveResults for a description of this
	 * function.
	 *
	 * Note that this function is also called externally to fetch
	 * results for the title index, and possibly elsewhere.
	 *
	 * @return array An array with the articles, published articles,
	 *  issue, journal, section and the issue availability.
	 */
	function &formatResults(&$results) {
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');

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
				$publishedArticleCache[$articleId] =& $publishedArticleDao->getPublishedArticleByArticleId($articleId);
				$articleCache[$articleId] =& $articleDao->getArticle($articleId);
			}
			unset($article, $publishedArticle);
			$article =& $articleCache[$articleId];
			$publishedArticle =& $publishedArticleCache[$articleId];

			if ($publishedArticle && $article) {
				$sectionId = $article->getSectionId();
				if (!isset($sectionCache[$sectionId])) {
					$sectionCache[$sectionId] =& $sectionDao->getSection($sectionId);
				}

				// Get the journal, storing in cache if necessary.
				$journalId = $article->getJournalId();
				if (!isset($journalCache[$journalId])) {
					$journalCache[$journalId] = $journalDao->getById($journalId);
				}

				// Get the issue, storing in cache if necessary.
				$issueId = $publishedArticle->getIssueId();
				if (!isset($issueCache[$issueId])) {
					unset($issue);
					$issue =& $issueDao->getIssueById($issueId);
					$issueCache[$issueId] =& $issue;
					import('classes.issue.IssueAction');
					$issueAvailabilityCache[$issueId] = !IssueAction::subscriptionRequired($issue) || IssueAction::subscribedUser($journalCache[$journalId], $issueId, $articleId) || IssueAction::subscribedDomain($journalCache[$journalId], $issueId, $articleId);
				}

				// Only display articles from published issues.
				if (!$issueCache[$issueId]->getPublished()) continue;

				// Store the retrieved objects in the result array.
				$returner[] = array(
					'article' => &$article,
					'publishedArticle' => &$publishedArticleCache[$articleId],
					'issue' => &$issueCache[$issueId],
					'journal' => &$journalCache[$journalId],
					'issueAvailable' => $issueAvailabilityCache[$issueId],
					'section' => &$sectionCache[$sectionId]
				);
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
	 * @param $error string a reference to a variable that will
	 *  contain an error message if the search service produces
	 *  an error.
	 * @param $publishedFrom object Search-from date
	 * @param $publishedTo object Search-to date
	 * @param $rangeInfo Information on the range of results to return
	 * @return VirtualArrayIterator An iterator with one entry per retrieved
	 *  article containing the article, published article, issue, journal, etc.
	 */
	function &retrieveResults(&$journal, &$keywords, &$error, $publishedFrom = null, $publishedTo = null, $rangeInfo = null) {
		// Pagination
		if ($rangeInfo && $rangeInfo->isValid()) {
			$page = $rangeInfo->getPage();
			$itemsPerPage = $rangeInfo->getCount();
		} else {
			$page = 1;
			$itemsPerPage = ARTICLE_SEARCH_DEFAULT_RESULT_LIMIT;
		}

		// Check whether a search plug-in jumps in to provide ranked search results.
		$totalResults = null;
		$results =& HookRegistry::call(
			'ArticleSearch::retrieveResults',
			array(&$journal, &$keywords, $publishedFrom, $publishedTo, $page, $itemsPerPage, &$totalResults, &$error)
		);

		// If no search plug-in is activated then fall back to the
		// default database search implementation.
		if ($results === false) {
			// Parse the query.
			foreach($keywords as $searchType => $query) {
				$keywords[$searchType] = ArticleSearch::_parseQuery($query);
			}

			// Fetch all the results from all the keywords into one array
			// (mergedResults), where mergedResults[article_id]
			// = sum of all the occurences for all keywords associated with
			// that article ID.
			$mergedResults =& ArticleSearch::_getMergedArray($journal, $keywords, $publishedFrom, $publishedTo);

			// Convert mergedResults into an array (frequencyIndicator =>
			// $articleId).
			// The frequencyIndicator is a synthetically-generated number,
			// where higher is better, indicating the quality of the match.
			// It is generated here in such a manner that matches with
			// identical frequency do not collide.
			$results =& ArticleSearch::_getSparseArray($mergedResults);
			$totalResults = count($results);

			// Use only the results for the specified page.
			$offset = $itemsPerPage * ($page-1);
			$length = max($totalResults - $offset, 0);
			$length = min($itemsPerPage, $length);
			if ($length == 0) {
				$results = array();
			} else {
				$results = array_slice(
					$results,
					$offset,
					$length
				);
			}
		}

		// Take the range of results and retrieve the Article, Journal,
		// and associated objects.
		$results =& ArticleSearch::formatResults($results);

		// Return the appropriate iterator.
		import('lib.pkp.classes.core.VirtualArrayIterator');
		$returner = new VirtualArrayIterator($results, $totalResults, $page, $itemsPerPage);
		return $returner;
	}

	function getIndexFieldMap() {
		return array(
			ARTICLE_SEARCH_AUTHOR => 'authors',
			ARTICLE_SEARCH_TITLE => 'title',
			ARTICLE_SEARCH_ABSTRACT => 'abstract',
			ARTICLE_SEARCH_GALLEY_FILE => 'galleyFullText',
			ARTICLE_SEARCH_SUPPLEMENTARY_FILE => 'suppFiles',
			ARTICLE_SEARCH_DISCIPLINE => 'discipline',
			ARTICLE_SEARCH_SUBJECT => 'subject',
			ARTICLE_SEARCH_TYPE => 'type',
			ARTICLE_SEARCH_COVERAGE => 'coverage'
		);
	}
}

?>
