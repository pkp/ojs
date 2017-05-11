<?php

/**
 * @file classes/search/SubmissionSearch.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionSearch
 * @ingroup search
 * @see SubmissionSearchDAO
 *
 * @brief Class for retrieving search results.
 *
 * FIXME: NEAR; precedence w/o parens?; stemming; weighted counting
 */

// Search types
define('SUBMISSION_SEARCH_AUTHOR',		0x00000001);
define('SUBMISSION_SEARCH_TITLE',		0x00000002);
define('SUBMISSION_SEARCH_ABSTRACT',		0x00000004);
define('SUBMISSION_SEARCH_DISCIPLINE',		0x00000008);
define('SUBMISSION_SEARCH_SUBJECT',		0x00000010);
define('SUBMISSION_SEARCH_TYPE',		0x00000020);
define('SUBMISSION_SEARCH_COVERAGE',		0x00000040);
define('SUBMISSION_SEARCH_GALLEY_FILE',		0x00000080);
define('SUBMISSION_SEARCH_SUPPLEMENTARY_FILE',	0x00000100);
define('SUBMISSION_SEARCH_INDEX_TERMS',		0x00000078);

define('SUBMISSION_SEARCH_DEFAULT_RESULT_LIMIT', 20);

import('lib.pkp.classes.search.SubmissionSearchIndex');

class SubmissionSearch {
	/**
	 * Constructor
	 */
	function __construct() {
	}

	/**
	 * Parses a search query string.
	 * Supports +/-, AND/OR, parens
	 * @param $query
	 * @return array of the form ('+' => <required>, '' => <optional>, '-' => excluded)
	 */
	function _parseQuery($query) {
		$count = preg_match_all('/(\+|\-|)("[^"]+"|\(|\)|[^\s\)]+)/', $query, $matches);
		$pos = 0;
		return $this->_parseQueryInternal($matches[1], $matches[2], $pos, $count);
	}

	/**
	 * Query parsing helper routine.
	 * Returned structure is based on that used by the Search::QueryParser Perl module.
	 */
	function _parseQueryInternal($signTokens, $tokens, &$pos, $total) {
		$return = array('+' => array(), '' => array(), '-' => array());
		$postBool = $preBool = '';

		$submissionSearchIndex = new SubmissionSearchIndex();

		$notOperator = PKPString::strtolower(__('search.operator.not'));
		$andOperator = PKPString::strtolower(__('search.operator.and'));
		$orOperator = PKPString::strtolower(__('search.operator.or'));
		while ($pos < $total) {
			if (!empty($signTokens[$pos])) $sign = $signTokens[$pos];
			else if (empty($sign)) $sign = '+';
			$token = PKPString::strtolower($tokens[$pos++]);
			switch ($token) {
				case $notOperator:
					$sign = '-';
					break;
				case ')':
					return $return;
				case '(':
					$token = $this->_parseQueryInternal($signTokens, $tokens, $pos, $total);
				default:
					$postBool = '';
					if ($pos < $total) {
						$peek = PKPString::strtolower($tokens[$pos]);
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
						$k = $submissionSearchIndex->filterKeywords($token, true);
					}
					if (!empty($k)) $return[$sign][] = $k;
					$sign = '';
					break;
			}
		}
		return $return;
	}

	/**
	 * Takes an unordered list of search result data, flattens it, orders it
	 * and excludes unwanted results.
	 * @param $unorderedResults array An unordered list of search data (article ID
	 *   as key and ranking data as values).
	 * @param $orderBy string One of the values returned by ArticleSearch::getResultSetOrderingOptions();
	 * @param $orderDir string 'asc' or 'desc', see ArticleSearch::getResultSetOrderingDirectionOptions();
	 * @param $exclude array A list of article IDs to exclude from the result.
	 * @return array An ordered and flattened list of article IDs.
	 */
	function _getMergedArray($context, &$keywords, $publishedFrom, $publishedTo) {
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
		return $this->_getMergedKeywordResults($context, $mergedKeywords, null, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours);
	}

	/**
	 * Recursive helper for _getMergedArray.
	 */
	function _getMergedKeywordResults($context, &$keyword, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours) {
		$mergedResults = null;

		if (isset($keyword['type'])) {
			$type = $keyword['type'];
		}

		foreach ($keyword['+'] as $phrase) {
			$results = $this->_getMergedPhraseResults($context, $phrase, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours);
			if ($mergedResults === null) {
				$mergedResults = $results;
			} else {
				foreach ($mergedResults as $submissionId => $data) {
					if (isset($results[$submissionId])) {
						$mergedResults[$submissionId]['count'] += $results[$submissionId]['count'];
					} else {
						unset($mergedResults[$submissionId]);
					}
				}
			}
		}

		if ($mergedResults == null) {
			$mergedResults = array();
		}

		if (!empty($mergedResults) || empty($keyword['+'])) {
			foreach ($keyword[''] as $phrase) {
				$results = $this->_getMergedPhraseResults($context, $phrase, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours);
				foreach ($results as $submissionId => $data) {
					if (isset($mergedResults[$submissionId])) {
						$mergedResults[$submissionId]['count'] += $data['count'];
					} else if (empty($keyword['+'])) {
						$mergedResults[$submissionId] = $data;
					}
				}
			}

			foreach ($keyword['-'] as $phrase) {
				$results = $this->_getMergedPhraseResults($context, $phrase, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours);
				foreach ($results as $submissionId => $count) {
					if (isset($mergedResults[$submissionId])) {
						unset($mergedResults[$submissionId]);
					}
				}
			}
		}

		return $mergedResults;
	}

	/**
	 * Recursive helper for _getMergedArray.
	 */
	function _getMergedPhraseResults($context, &$phrase, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours) {
		if (isset($phrase['+'])) {
			return $this->_getMergedKeywordResults($context, $phrase, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours);
		}

		$mergedResults = array();
		$searchDao = $this->getSearchDao();

		return $searchDao->getPhraseResults(
			$context,
			$phrase,
			$publishedFrom,
			$publishedTo,
			$type,
			$resultsPerKeyword,
			$resultCacheHours
		);
	}

	/**
	 * Return an array of search results matching the supplied
	 * keyword IDs in decreasing order of match quality.
	 * Keywords are supplied in an array of the following format:
	 * $keywords[SUBMISSION_SEARCH_AUTHOR] = array('John', 'Doe');
	 * $keywords[SUBMISSION_SEARCH_...] = array(...);
	 * $keywords[null] = array('Matches', 'All', 'Fields');
	 * @param $request Request
	 * @param $context object The context to search
	 * @param $keywords array List of keywords
	 * @param $error string a reference to a variable that will
	 *  contain an error message if the search service produces
	 *  an error.
	 * @param $publishedFrom object Search-from date
	 * @param $publishedTo object Search-to date
	 * @param $rangeInfo Information on the range of results to return
	 * @param $exclude array An array of article IDs to exclude from the result.
	 * @return VirtualArrayIterator An iterator with one entry per retrieved
	 *  article containing the article, published article, issue, context, etc.
	 */
	function retrieveResults($request, $context, $keywords, &$error, $publishedFrom = null, $publishedTo = null, $rangeInfo = null, $exclude = array()) {
		// Pagination
		if ($rangeInfo && $rangeInfo->isValid()) {
			$page = $rangeInfo->getPage();
			$itemsPerPage = $rangeInfo->getCount();
		} else {
			$page = 1;
			$itemsPerPage = SUBMISSION_SEARCH_DEFAULT_RESULT_LIMIT;
		}

		// Result set ordering.
		list($orderBy, $orderDir) = $this->getResultSetOrdering($request);

		// Check whether a search plug-in jumps in to provide ranked search results.
		$totalResults = null;
		$results = HookRegistry::call(
			'SubmissionSearch::retrieveResults',
			array(&$context, &$keywords, $publishedFrom, $publishedTo, $orderBy, $orderDir, $exclude, $page, $itemsPerPage, &$totalResults, &$error)
		);

		// If no search plug-in is activated then fall back to the
		// default database search implementation.
		if ($results === false) {
			// Parse the query.
			foreach($keywords as $searchType => $query) {
				$keywords[$searchType] = $this->_parseQuery($query);
			}

			// Fetch all the results from all the keywords into one array
			// (mergedResults), where mergedResults[submission_id]
			// = sum of all the occurences for all keywords associated with
			// that article ID.
			$mergedResults = $this->_getMergedArray($context, $keywords, $publishedFrom, $publishedTo);

			// Convert mergedResults into an array (frequencyIndicator =>
			// $submissionId).
			// The frequencyIndicator is a synthetically-generated number,
			// where higher is better, indicating the quality of the match.
			// It is generated here in such a manner that matches with
			// identical frequency do not collide.
			$results = $this->getSparseArray($mergedResults, $orderBy, $orderDir, $exclude);
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
		$results = $this->formatResults($results);

		// Return the appropriate iterator.
		import('lib.pkp.classes.core.VirtualArrayIterator');
		return new VirtualArrayIterator($results, $totalResults, $page, $itemsPerPage);
	}

	/**
	 * Return the available options for the result
	 * set ordering direction.
	 * @return array
	 */
	function getResultSetOrderingDirectionOptions() {
		return array(
			'asc' => __('search.results.orderDir.asc'),
			'desc' => __('search.results.orderDir.desc')
		);
	}

	/**
	 * Return the currently selected result
	 * set ordering option (default: descending relevance).
	 * @param $request Request
	 * @return array An array with the order field as the
	 * first entry and the order direction as the second
	 * entry.
	 */
	function getResultSetOrdering($request) {
		// Order field.
		$orderBy = $request->getUserVar('orderBy');
		$orderByOptions = $this->getResultSetOrderingOptions($request);
		if (is_null($orderBy) || !in_array($orderBy, array_keys($orderByOptions))) {
			$orderBy = 'score';
		}

		// Ordering direction.
		$orderDir = $request->getUserVar('orderDir');
		$orderDirOptions = $this->getResultSetOrderingDirectionOptions();
		if (is_null($orderDir) || !in_array($orderDir, array_keys($orderDirOptions))) {
			$orderDir = $this->getDefaultOrderDir($orderBy);
		}

		return array($orderBy, $orderDir);
	}

	//
	// Methods to be implemented by subclasses.
	//
	/**
	 * See implementation of retrieveResults for a description of this
	 * function.
	 *
	 * Note that this function is also called externally to fetch
	 * results for the title index, and possibly elsewhere.
	 *
	 * @return array
	 */
	static function formatResults(&$results) {
		assert(false);
	}

	/**
	 * Return the available options for result set ordering.
	 * @param $request Request
	 * @return array
	 */
	function getResultSetOrderingOptions($request) {
		assert(false);
	}

	/**
	 * See implementation of retrieveResults for a description of this
	 * function.
	 */
	protected function &getSparseArray(&$unorderedResults, $orderBy, $orderDir, $exclude) {
		assert(false);
	}

	/**
	 * Return the default order direction.
	 * @param $orderBy string
	 * @return string
	 */
	protected function getDefaultOrderDir($orderBy) {
		assert(false);
	}

	/**
	 * Return the search DAO
	 * @return DAO
	 */
	protected function getSearchDao() {
		assert(false);
	}

}

?>
