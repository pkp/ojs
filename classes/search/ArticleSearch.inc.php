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
	function &_getMergedArray($journal, &$keywords, &$resultCount) {
		$articleSearchDao = &DAORegistry::getDAO('ArticleSearchDAO');

		$mergedResults = array();
		foreach ($keywords as $type => $keywordsForType) foreach ($keywordsForType as $keyword) {
			$resultCount = 0;
			$results = &$articleSearchDao->getKeywordResults($journal, $keyword, $type);
			foreach ($results as $result) {
				$articleId = &$result['article_id'];
				$assocId = &$result['assoc_id'];
				if (!isset($mergedResults[$articleId])) {
					$mergedResults[$articleId] = array($assocId => $result['count']);
				}
				else $mergedResults[$articleId][$assocId] += $result['count'];
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
		foreach ($mergedResults as $articleId => $assocArray) {
			foreach ($assocArray as $assocId => $count) {
				$frequencyIndicator = ($resultCount * $count) + $i++;
				$results[$frequencyIndicator] = array('articleId' => $articleId, 'assocId' => $assocId);
			}
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
		$journalCache = array();

		$returner = array();
		foreach ($results as $result) {
			// Get the article, storing in cache if necessary.
			$articleId = $result['articleId'];
			if (!isset($articleCache[$articleId])) {
				$publishedArticleCache[$articleId] = $publishedArticleDao->getPublishedArticleByArticleId($articleId);
				$articleCache[$articleId] = $articleDao->getArticle($articleId);
			}
			$article = $articleCache[$articleId];
			$publishedArticle = $publishedArticleCache[$articleId];

			if ($publishedArticle && $article) {
				// Get the issue, storing in cache if necessary.
				$issueId = $publishedArticle->getIssueId();
				if (!isset($issueCache[$issueId]))
					$issueCache[$issueId] = $issueDao->getIssueById($issueId);

				// Get the journal, storing in cache if necessary.
				$journalId = $article->getJournalId();
				if (!isset($journalCache[$journalId])) {
					$journalCache[$journalId] = $journalDao->getJournal($journalId);
				}
	
				// Store the retrieved objects in the result array.
				$returner[] = array('article' => $article, 'publishedArticle' => $publishedArticleCache[$articleId], 'issue' => $issueCache[$issueId], 'journal' => $journalCache[$journalId]);
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
	 * $limit indicates the number of results to return, and
	 * $offest indicates the number of results to skip from the top.
	 */
	function &retrieveResults($journal, &$keywords, $limit = 25, $offset = 0) {
		// Fetch all the results from all the keywords into one array
		// (mergedResults), where mergedResults[article_id][assoc_id]
		// = sum of all the occurences for all keywords associated with
		// that article ID and assoc ID. (If $type is not specified,
		// the value of assoc_id is constant and irrelevant.)
		// resultCount contains the sum of result counts for all keywords.
		$mergedResults = &ArticleSearch::_getMergedArray($journal, &$keywords, &$resultCount);

		// Convert mergedResults into an array (frequencyIndicator =>
		// array('articleId' => $articleId, 'assocId' => $assocId)).
		// The frequencyIndicator is a synthetically-generated number,
		// where higher is better, indicating the quality of the match.
		// It is generated here in such a manner that matches with
		// identical frequency do not collide.
		$results = &ArticleSearch::_getSparseArray(&$mergedResults, $resultCount);

		// Take the range of results from $offset to $offset + $limit,
		// and retrieve the Article, Journal, and associated objects.
		$resultsSlice = &array_slice(&$results, $offset, $limit);
		return ArticleSearch::_formatResults(&$resultsSlice);
	}
}

?>
