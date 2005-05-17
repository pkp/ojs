<?php

/**
 * ArticleSearchDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package search
 *
 * DAO class for article search index.
 *
 * $Id$
 */

import('search.ArticleSearch');

define('KEYWORD_MAXIMUM_LENGTH', 60);

class ArticleSearchDAO extends DAO {

	/**
	 * Constructor.
	 */
	function ArticleSearchDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve keyword ID from keyword text.
	 * @param $keyword string
	 * @return int
	 */
	function &getKeywordId($keyword) {
		$result = &$this->retrieve(
			'SELECT keyword_id
			FROM article_search_keyword_list
			WHERE keyword_text = ?',
			substr($keyword, 0, KEYWORD_MAXIMUM_LENGTH)
		);
		
		if ($result->RecordCount() == 0) {
			return false;
		} else {
			return $result->fields[0];
		}
	}
	
	/**
	 * Retrieve the top results for a keyword with the given
	 * limit (default 500 results).
	 * @param $keywordId int
	 * @return array of results (associative arrays)
	 */
	function &getKeywordResults($journal, $keyword, $publishedFrom = null, $publishedTo = null, $type = null, $limit = 500, $cacheHours = 24) {
		$params = array(substr($keyword, 0, KEYWORD_MAXIMUM_LENGTH));

		if (!empty($type)) {
			$typeValueString = 'AND (aski.type & ?) != 0 ';
			$params[] = $type;
		} else {
			$typeValueString = '';
		}

		if (!empty($publishedFrom)) {
			$publishedFromString = 'AND pa.date_published>=?';
			$params[] = $publishedFrom;
		} else {
			$publishedFromString = '';
		}

		if (!empty($publishedTo)) {
			$publishedToString = 'AND pa.date_published>=?';
			$params[] = $publishedTo;
		} else {
			$publishedToString = '';
		}

		if (!empty($journal)) {
			$journalWhereString = 'AND a.journal_id = ?';
			$params[] = $journal->getJournalId();
		} else {
			$journalWhereString = '';
		}

		$result = &$this->retrieveCached(
			"SELECT
				aski.article_id as article_id,
				sum(aski.count) as count
			FROM
				article_search_keyword_index aski,
				article_search_keyword_list askl,
				articles a,
				published_articles pa,
				issues i
			WHERE
				aski.keyword_id = askl.keyword_id AND
				askl.keyword_text = LOWER(?) AND
				aski.article_id = a.article_id AND
				pa.article_id = a.article_id AND
				i.issue_id = pa.issue_id AND
				i.published = 1
				$typeValueString
				$publishedFromString
				$publishedToString
				$journalWhereString
			GROUP BY aski.article_id
			ORDER BY count DESC
			LIMIT $limit",
			$params,
			3600 * $cacheHours // Cache for 24 hours
		);

		return new DBRowIterator(&$result);
	}
	
	/**
	 * Add keyword text to the keyword list.
	 * @param $keyword string
	 * @return int the inserted keyword ID
	 */
	function insertKeyword($keyword) {
		$this->update(
			'INSERT INTO article_search_keyword_list
			(keyword_text)
			VALUES
			(?)',
			substr($keyword, 0, KEYWORD_MAXIMUM_LENGTH)
		);
		
		return $this->getInsertId('article_search_keyword_list', 'keyword_id');
	}
	
	/**
	 * Insert a new keyword for an article.
	 * @param $articleId int
	 * @param $keyword string
	 * @param $count int
	 * @param $type int
	 * @param $assocId int optional
	 */	
	function insertArticleKeyword($articleId, $keyword, $count, $type, $assocId = null) {
		$keywordId = $this->getKeywordId($keyword);
		if (!$keywordId) {
			$keywordId = $this->insertKeyword($keyword);
		}
		
		return $this->update(
			'INSERT INTO article_search_keyword_index
				(article_id, keyword_id, count, type, assoc_id)
				VALUES
				(?, ?, ?, ?, ?)',
			array(
				$articleId,
				$keywordId,
				$count,
				$type,
				$assocId == null ? 0 : $assocId
			)
		);
	}
	
	/**
	 * Insert a set of keywords for an article.
	 * @param $articleId int
	 * @param $keywords array set of $keyword => $count elements
	 * @param $type int
	 * @param $assocId int optional
	 */
	function insertArticleKeywords($articleId, $keywords, $type, $assocId = null) {
		foreach ($keywords as $keyword => $count) {
			$this->insertArticleKeyword($articleId, $keyword, $count, $type, $assocId);
		}
	}
	
	/**
	 * Delete article keywords.
	 * @param $articleId int
	 * @param $type int optional
	 * @param $assocId int optional
	 */
	function deleteArticleKeywords($articleId, $type = null, $assocId = null) {
		$sql = 'DELETE FROM article_search_keyword_index
				WHERE article_id = ?';
		$params = array($articleId);
		
		if (isset($type)) {
			$sql .= ' AND type = ?';
			$params[] = $type;
		}
		
		if (isset($assocId)) {
			$sql .= ' AND assoc_id = ?';
			$params[] = $assocId;
		}
		
		return $this->update($sql, $params);
	}
	
}

?>
