<?php

/**
 * @file ArticleSearch.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package search
 * @class ArticleSearch
 *
 * Class for retrieving article search results.
 *
 * FIXME: NEAR; precedence w/o parens?; stemming; weighted counting
 *
 * $Id$
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

import('search.ArticleSearchIndex');

class ArticleSearch {

	/**
	 * Parses a search query string.
	 * Supports +/-, AND/OR, parens
	 * @param $query
	 * @return array of the form ('+' => <required>, '' => <optional>, '-' => excluded)
	 */
	function parseQuery($query) {
		$count = preg_match_all('/(\+|\-|)("[^"]+"|\(|\)|[^\s\)]+)/', $query, $matches);
		$pos = 0;
		$keywords = ArticleSearch::_parseQuery($matches[1], $matches[2], $pos, $count);
		return $keywords;
	}

	/**
	 * Query parsing helper routine.
	 * Returned structure is based on that used by the Search::QueryParser Perl module.
	 */
	function _parseQuery($signTokens, $tokens, &$pos, $total) {
		$return = array('+' => array(), '' => array(), '-' => array());
		$postBool = $preBool = '';

		$notOperator = String::strtolower(Locale::translate('search.operator.not'));
		$andOperator = String::strtolower(Locale::translate('search.operator.and'));
		$orOperator = String::strtolower(Locale::translate('search.operator.or'));
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
					$token = ArticleSearch::_parseQuery($signTokens, $tokens, $pos, $total);
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
					if (is_array($token)) $k = $token;
					else $k = ArticleSearchIndex::filterKeywords($token, true);
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
	function &_getMergedArray(&$journal, &$keywords, $publishedFrom, $publishedTo, &$resultCount) {
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
		$mergedResults = &ArticleSearch::_getMergedKeywordResults($journal, $mergedKeywords, null, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours);

		$resultCount = count($mergedResults);
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
			$results = &ArticleSearch::_getMergedPhraseResults($journal, $phrase, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours);
			if ($mergedResults == null) {
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
				$results = &ArticleSearch::_getMergedPhraseResults($journal, $phrase, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours);
				foreach ($results as $articleId => $count) {
					if (isset($mergedResults[$articleId])) {
						$mergedResults[$articleId] += $count;
					} else if (empty($keyword['+'])) {
						$mergedResults[$articleId] = $count;
					}
				}
			}

			foreach ($keyword['-'] as $phrase) {
				$results = &ArticleSearch::_getMergedPhraseResults($journal, $phrase, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours);
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
			$mergedResults = &ArticleSearch::_getMergedKeywordResults($journal, $phrase, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours);
			return $mergedResults;
		}

		$mergedResults = array();
		$articleSearchDao = &DAORegistry::getDAO('ArticleSearchDAO');
		$results = &$articleSearchDao->getPhraseResults(
			$journal,
			$phrase,
			$publishedFrom,
			$publishedTo,
			$type,
			$resultsPerKeyword,
			$resultCacheHours
		);
		while (!$results->eof()) {
			$result = &$results->next();
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
	function &_getSparseArray(&$mergedResults, $resultCount) {
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
	 * See implementation of retrieveResults for a description of this
	 * function.
	 * Note that this function is also called externally to fetch
	 * results for the title index, and possibly elsewhere.
	 */
	function &formatResults(&$results) {
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$sectionDao = &DAORegistry::getDAO('SectionDAO');

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
				$publishedArticleCache[$articleId] = &$publishedArticleDao->getPublishedArticleByArticleId($articleId);
				$articleCache[$articleId] = &$articleDao->getArticle($articleId);
			}
			unset($article);
			$article = &$articleCache[$articleId];
			$publishedArticle = &$publishedArticleCache[$articleId];

			$sectionId = $article->getSectionId();
			if (!isset($sectionCache[$sectionId])) {
				$sectionCache[$sectionId] = &$sectionDao->getSection($sectionId);
			}

			if ($publishedArticle && $article) {
				// Get the journal, storing in cache if necessary.
				$journalId = $article->getJournalId();
				if (!isset($journalCache[$journalId])) {
					$journalCache[$journalId] = $journalDao->getJournal($journalId);
				}

				// Get the issue, storing in cache if necessary.
				$issueId = $publishedArticle->getIssueId();
				if (!isset($issueCache[$issueId])) {
					unset($issue);
					$issue = &$issueDao->getIssueById($issueId);
					$issueCache[$issueId] = &$issue;
					import('issue.IssueAction');
					$issueAvailabilityCache[$issueId] = !IssueAction::subscriptionRequired($issue) || IssueAction::subscribedUser($journalCache[$journalId], $issueId, $articleId) || IssueAction::subscribedDomain($journalCache[$journalId], $issueId, $articleId);
				}

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
	 * @param $publishedFrom object Search-from date
	 * @param $publishedTo object Search-to date
	 * @param $rangeInfo Information on the range of results to return
	 */
	function &retrieveResults(&$journal, &$keywords, $publishedFrom = null, $publishedTo = null, $rangeInfo = null) {
		// Fetch all the results from all the keywords into one array
		// (mergedResults), where mergedResults[article_id]
		// = sum of all the occurences for all keywords associated with
		// that article ID.
		// resultCount contains the sum of result counts for all keywords.
		$mergedResults = &ArticleSearch::_getMergedArray($journal, $keywords, $publishedFrom, $publishedTo, $resultCount);

		// Convert mergedResults into an array (frequencyIndicator =>
		// $articleId).
		// The frequencyIndicator is a synthetically-generated number,
		// where higher is better, indicating the quality of the match.
		// It is generated here in such a manner that matches with
		// identical frequency do not collide.
		$results = &ArticleSearch::_getSparseArray($mergedResults, $resultCount);

		$totalResults = count($results);

		// Use only the results for the specified page, if specified.
		if ($rangeInfo && $rangeInfo->isValid()) {
			$results = array_slice(
				$results,
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
		$results =& ArticleSearch::formatResults($results);

		// Return the appropriate iterator.
		$returner = &new VirtualArrayIterator($results, $totalResults, $page, $itemsPerPage);
		return $returner;
	}
}

?>
