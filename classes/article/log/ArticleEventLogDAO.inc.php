<?php

/**
 * ArticleEventLogDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article.log
 *
 * Class for inserting/accessing article history log entries.
 *
 * $Id$
 */

import ('article.log.ArticleEventLogEntry');

class ArticleEventLogDAO extends DAO {

	/**
	 * Constructor.
	 */
	function ArticleEventLogDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve a log entry by ID.
	 * @param $logId int
	 * @param $articleId int optional
	 * @return ArticleEventLogEntry
	 */
	function &getLogEntry($logId, $articleId = null) {
		if (isset($articleId)) {
			$result = &$this->retrieve(
				'SELECT * FROM article_event_log WHERE log_id = ? AND article_id = ?',
				array($logId, $articleId)
			);
		} else {
			$result = &$this->retrieve(
				'SELECT * FROM article_event_log WHERE log_id = ?', $logId
			);
		}
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnLogEntryFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Retrieve all log entries for an article.
	 * @param $articleId int
	 * @param $limit int limit the number of entries retrieved (default false)
	 * @param $recentFirst boolean order with most recent entries first (default true)
	 * @return array ArticleEventLogEntry ordered by sequence
	 */
	function &getArticleLogEntries($articleId, $limit = false, $recentFirst = true) {
		return $this->getArticleLogEntriesByAssoc($articleId, null, null, $limit, $recentFirst);
	}
	
	/**
	 * Retrieve all log entries for an article matching the specified association.
	 * @param $articleId int
	 * @param $assocType int
	 * @param $assocId int
	 * @param $limit int limit the number of entries retrieved (default false)
	 * @param $recentFirst boolean order with most recent entries first (default true)
	 * @return array ArticleEventLogEntry ordered by sequence
	 */
	function &getArticleLogEntriesByAssoc($articleId, $assocType = null, $assocId = null, $limit = false, $recentFirst = true) {
		$entries = array();
		
		$params = array($articleId);
		if (isset($assocType)) {
			array_push($params, $assocType);
			if (isset($assocId)) {
				array_push($params, $assocId);
			}
		}
		
		$result = &$this->retrieveLimit(
			'SELECT * FROM article_event_log WHERE article_id = ?' . (isset($assocType) ? ' AND assoc_type = ?' . (isset($assocId) ? ' AND assoc_id = ?' : '') : '') . ' ORDER BY date_logged ' . ($recentFirst ? 'DESC' : 'ASC'),
			$params,
			$limit
		);
		
		while (!$result->EOF) {
			$entries[] = &$this->_returnLogEntryFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $entries;
	}
	
	/**
	 * Internal function to return an ArticleEventLogEntry object from a row.
	 * @param $row array
	 * @return ArticleEventLogEntry
	 */
	function &_returnLogEntryFromRow(&$row) {
		$entry = &new ArticleEventLogEntry();
		$entry->setLogId($row['log_id']);
		$entry->setArticleId($row['article_id']);
		$entry->setUserId($row['user_id']);
		$entry->setDateLogged($row['date_logged']);
		$entry->setIPAddress($row['ip_address']);
		$entry->setLogLevel($row['log_level']);
		$entry->setEventType($row['event_type']);
		$entry->setAssocType($row['assoc_type']);
		$entry->setAssocId($row['assoc_id']);
		$entry->setMessage($row['message']);
		
		return $entry;
	}

	/**
	 * Insert a new log entry.
	 * @param $entry ArticleEventLogEntry
	 */	
	function insertLogEntry(&$entry) {
		$ret = $this->update(
			'INSERT INTO article_event_log
				(article_id, user_id, date_logged, ip_address, log_level, event_type, assoc_type, assoc_id, message)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$entry->getArticleId(),
				$entry->getUserId(),
				$entry->getDateLogged() == null ? Core::getCurrentDate() : $entry->getDateLogged(),
				$entry->getIPAddress() == null ? Request::getRemoteAddr() : $entry->getIPAddress(),
				$entry->getLogLevel(),
				$entry->getEventType(),
				$entry->getAssocType(),
				$entry->getAssocId(),
				$entry->getMessage()
			)
		);
		
		if ($ret) {
			$entry->setLogId($this->getInsertLogId());
		}
			
		return $ret;
	}
	
	/**
	 * Delete a single log entry for an article.
	 * @param $logId int
	 * @param $articleId int optional
	 */
	function deleteLogEntry($logId, $articleId = null) {
		if (isset($articleId)) {
			return $this->update(
				'DELETE FROM article_event_log WHERE log_id = ? AND article_id = ?',
				array($logId, $articleId)
			);
			
		} else {
			return $this->update(
				'DELETE FROM article_event_log WHERE log_id = ?', $logId
			);
		}
	}
	
	/**
	 * Delete all log entries for an article.
	 * @param $articleId int
	 */
	function deleteArticleLogEntries($articleId) {
		return $this->update(
			'DELETE FROM article_event_log WHERE article_id = ?', $articleId
		);
	}
	
	/**
	 * Get the ID of the last inserted log entry.
	 * @return int
	 */
	function getInsertLogId() {
		return $this->getInsertId('article_event_log', 'log_id');
	}
	
}

?>
