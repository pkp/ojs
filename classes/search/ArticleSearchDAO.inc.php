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
			"$keyword"
		);
		
		if ($result->RecordCount() == 0) {
			return false;
		} else {
			return $result->fields[0];
		}
	}
	
	/**
	 * Retrieve the top results for a keyword with the given
	 * limit (default 100 results).
	 * @param $keywordId int
	 * @return array of results (associative arrays)
	 */
	function &getKeywordResults(&$keywordId, $type = null, $limit = 100) {
		if (isset($type)) {
			$typeValueString = 'AND type=?';
			$typeSelectString = 'assoc_id';
		}
		else {
			$typeValueString = '';
			$typeSelectString = '\'\' as assoc_id';
		}
		$result = &$this->retrieve(
			"SELECT article_id, count, $typeSelectString
			FROM article_search_keyword_index
			WHERE keyword_id = ?
			$typeValueString
			ORDER BY count DESC
			LIMIT ?",
			isset($type)?
				array($keywordId, $type, $limit) :
				array($keywordId, $limit)
		);

		$returner = array();
		while (!$result->EOF) {
			$returner[] = &$result->GetRowAssoc(false);
			$result->MoveNext();
		}
		$result->Close();
		if (!empty($returner)) return $returner;
		return null;
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
			"$keyword"
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
