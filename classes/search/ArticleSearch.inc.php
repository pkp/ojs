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

define('ARTICLE_SEARCH_BY_ALL', 		0x00000101);
define('ARTICLE_SEARCH_BY_AUTHOR',		0x00000102);
define('ARTICLE_SEARCH_BY_TITLE',		0x00000103);
define('ARTICLE_SEARCH_BY_ABSTRACT',		0x00000104);
define('ARTICLE_SEARCH_BY_KEYWORDS',		0x00000105);

class ArticleSearch {
	/**
	 * Return an array of valid keyword IDs given a search
	 * string.
	 */
	function &getKeywordIds($queryString) {
		$articleSearchDao = &DAORegistry::getDAO('ArticleSearchDAO');
		$queryKeywords = String::regexp_split('/\s+/', $queryString);

		$keywordIds = array();
		foreach ($queryKeywords as $keyword) {
			$keywordId = $articleSearchDao->getKeywordId($keyword);
			if ($keywordId) $keywordIds[] = $keywordId;
		}
		if (!empty($keywordIds)) return $keywordIds;
		return null;
	}

	/**
	 * Return an array of search results matching the supplied
	 * keyword IDs in decreasing order of match quality.
	 * $limit indicates the number of results to return, and
	 * $offest indicates the number of results to skip from the top.
	 */
	function &retrieveResults(&$keywordIds, $type = null, $limit = 25, $offset = 0) {
		$articleSearchDao = &DAORegistry::getDAO('ArticleSearchDAO');

		// Fetch all the results from all the keywords into one array
		// (mergedResults), where mergedResults[article_id][assoc_id]
		// = sum of all the occurences for all keywords associated with
		// that article ID and assoc ID. (If $type is not specified,
		// the value of assoc_id is constant and irrelevant.)
		$mergedResults = array();
		foreach ($keywordIds as $keywordId) {
			$resultCount = 0;
			$results = &$articleSearchDao->getKeywordResults($keywordId, $type);
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

		// Convert mergedResults into an array (frequencyIndicator =>
		// array('articleId' => $articleId, 'assocId' => $assocId)).
		// The frequencyIndicator is a synthetically-generated number,
		// where higher is better, indicating the quality of the match.
		// It is generated here in such a manner that matches with
		// identical frequency do not collide.
		$results = array();
		$i = 0;
		foreach ($mergedResults as $articleId => $assocArray) {
			foreach ($assocArray as $assocId => $count) {
				$frequencyIndicator = ($resultCount * $count) + $i++;
				$results[$frequencyIndicator] = array('articleId' => $articleId, 'assocId' => $assocId);
			}
		}
		krsort(&$mergedResults);

		// Take the range of results from $offset to $offset + $limit,
		// and retrieve the Article object and associated object.
		$resultsSlice = &array_slice(&$results, $offset, $limit);
		$articleCache = array();
		$articleDao = &DAORegistry::getDAO('ArticleDAO');

		$returner = array();
		foreach ($resultsSlice as $result) {
			$articleId = $result['articleId'];
			if (!isset($articleCache[$articleId])) {
				$articleCache[$articleId] = $articleDao->getArticle($articleId);
			}
			$returner[] = array('article' => $articleCache[$articleId], $result['assocId']);
		}
		return $returner;
	}
}

?>
