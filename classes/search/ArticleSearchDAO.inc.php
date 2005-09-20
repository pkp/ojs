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

class ArticleSearchDAO extends DAO {

	/**
	 * Constructor.
	 */
	function ArticleSearchDAO() {
		parent::DAO();
	}
	
	/**
	 * Add a word to the keyword list (if it doesn't already exist).
     * @param $keyword string
     * @return int the keyword ID
     */
	function insertKeyword($keyword) {
		$result = &$this->retrieve(
			'SELECT keyword_id FROM article_search_keyword_list WHERE keyword_text = ?',
			$keyword
		);
		if($result->RecordCount() == 0) {
			$this->update(
				'INSERT INTO article_search_keyword_list (keyword_text) VALUES (?)',
				$keyword
			);
			$keywordId = $this->getInsertId('article_search_keyword_list', 'keyword_id');
			
		} else {
			$keywordId = $result->fields[0];
		}

		$result->Close();
		unset($result);
		
		return $keywordId;
	}
	
	/**
	 * Retrieve the top results for a phrases with the given
	 * limit (default 500 results).
	 * @param $keywordId int
	 * @return array of results (associative arrays)
	 */
	function &getPhraseResults($journal, $phrase, $publishedFrom = null, $publishedTo = null, $type = null, $limit = 500, $cacheHours = 24) {
		if (empty($phrase)) {
			$results = false;
			$returner = &new DBRowIterator($results);
			return $returner;
		}
		
		$sqlFrom = '';
		$sqlWhere = '';
		
		for ($i = 0, $count = count($phrase); $i < $count; $i++) {
			if (!empty($sqlFrom)) {
				$sqlFrom .= ', ';
				$sqlWhere .= ' AND ';
			}
			$sqlFrom .= 'article_search_object_keywords o'.$i.' NATURAL JOIN article_search_keyword_list k'.$i;
			if (strstr($phrase[$i], '%') === false) $sqlWhere .= 'k'.$i.'.keyword_text = ?';
			else $sqlWhere .= 'k'.$i.'.keyword_text LIKE ?';
			if ($i > 0) $sqlWhere .= ' AND o0.object_id = o'.$i.'.object_id AND o0.pos+'.$i.' = o'.$i.'.pos';
			
			$params[] = $phrase[$i];
		}

		if (!empty($type)) {
			$sqlWhere .= ' AND (o.type & ?) != 0';
			$params[] = $type;
		}

		if (!empty($publishedFrom)) {
			$sqlWhere .= ' AND pa.date_published >= ' . $this->datetimeToDB($publishedFrom);
		}

		if (!empty($publishedTo)) {
			$sqlWhere .= ' AND pa.date_published <= ' . $this->datetimeToDB($publishedTo);
		}

		if (!empty($journal)) {
			$sqlWhere .= ' AND i.journal_id = ?';
			$params[] = $journal->getJournalId();
		}

		$result = &$this->retrieveCached(
			'SELECT
				o.article_id,
				COUNT(*) AS count
			FROM
				published_articles pa,
				issues i,
				article_search_objects o NATURAL JOIN ' . $sqlFrom . '
			WHERE
				pa.article_id = o.article_id AND
				i.issue_id = pa.issue_id AND
				i.published = 1 AND ' . $sqlWhere . '
			GROUP BY o.article_id
			ORDER BY count DESC
			LIMIT ' . $limit,
			$params,
			3600 * $cacheHours // Cache for 24 hours
		);

		$returner = &new DBRowIterator($result);
		return $returner;
	}
	
	/**
	 * Delete all keywords for an article object.
	 * @param $articleId int
	 * @param $type int optional
	 * @param $assocId int optional
	 */
	function deleteArticleKeywords($articleId, $type = null, $assocId = null) {
		$sql = 'SELECT object_id FROM article_search_objects WHERE article_id = ?';
		$params = array($articleId);
		
		if (isset($type)) {
			$sql .= ' AND type = ?';
			$params[] = $type;
		}
		
		if (isset($assocId)) {
			$sql .= ' AND assoc_id = ?';
			$params[] = $assocId;
		}
		
		$result = &$this->retrieve($sql, $params);
		while (!$result->EOF) {
			$objectId = $result->fields[0];
			$this->update('DELETE FROM article_search_object_keywords WHERE object_id = ?', $objectId);
			$this->update('DELETE FROM article_search_objects WHERE object_id = ?', $objectId);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);
	}
	
	/**
	 * Add an article object to the index (if already exists, indexed keywords are cleared).
	 * @param $articleId int
	 * @param $type int
	 * @param $assocId int
	 * @return int the object ID
	 */
	function insertObject($articleId, $type, $assocId) {
		$result = &$this->retrieve(
			'SELECT object_id FROM article_search_objects WHERE article_id = ? AND type = ? AND assoc_id = ?',
			array($articleId, $type, $assocId)
		);
		if ($result->RecordCount() == 0) {
			$this->update(
				'INSERT INTO article_search_objects (article_id, type, assoc_id) VALUES (?, ?, ?)',
				array($articleId, $type, $assocId)
			);
			$objectId = $this->getInsertId('article_search_objects', 'object_id');
			
		} else {
			$objectId = $result->fields[0];
			$this->update(
				'DELETE FROM article_search_object_keywords WHERE object_id = ?',
				$objectId
			);
		}
		$result->Close();
		unset($result);
		
		return $objectId;
	}
	
	/**
	 * Index an occurrence of a keyword in an object.s
	 * @param $objectId int
	 * @param $keyword string
	 * @param $position int
	 * @return $keyword
	 */
	function insertObjectKeyword($objectId, $keyword, $position) {
		// FIXME Cache recently retrieved keywords?
		$keywordId = $this->insertKeyword($keyword);
		$this->update(
			'INSERT INTO article_search_object_keywords (object_id, keyword_id, pos) VALUES (?, ?, ?)',
			array($objectId, $keywordId, $position)
		);
	}
	
}

?>
