<?php

/**
 * ArticleEmailLogDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article.log
 *
 * Class for inserting/accessing article email log entries.
 *
 * $Id$
 */

class ArticleEmailLogDAO extends DAO {

	/**
	 * Constructor.
	 */
	function ArticleEmailLogDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve a log entry by ID.
	 * @param $logId int
	 * @param $articleId int optional
	 * @return ArticleEmailLogEntry
	 */
	function &getLogEntry($logId, $articleId = null) {
		if (isset($articleId)) {
			$result = &$this->retrieve(
				'SELECT * FROM article_email_log WHERE log_id = ? AND article_id = ?',
				array($logId, $articleId)
			);
		} else {
			$result = &$this->retrieve(
				'SELECT * FROM article_email_log WHERE log_id = ?', $logId
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
	 * @param $recentFirst boolean order with most recent entries first (default true)
	 * @return array ArticleEmailLogEntry ordered by sequence
	 */
	function &getArticleLogEntries($articleId, $recentFirst = true) {
		$entries = array();
		
		$result = &$this->retrieve(
			'SELECT * FROM article_email_log WHERE article_id = ? ORDER BY date_sent ' . ($recentFirst ? 'DESC' : 'ASC'),
			$articleId
		);
		
		while (!$result->EOF) {
			$entries[] = &$this->_returnLogEntryFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $entries;
	}
	
	/**
	 * Internal function to return an ArticleEmailLogEntry object from a row.
	 * @param $row array
	 * @return ArticleEmailLogEntry
	 */
	function &_returnLogEntryFromRow(&$row) {
		$entry = &new ArticleEmailLogEntry();
		$entry->setLogId($row['log_id']);
		$entry->setArticleId($row['article_id']);
		$entry->setSenderId($row['sender_id']);
		$entry->setDateSent($row['date_sent']);
		$entry->setAssocType($row['assoc_type']);
		$entry->setAssocId($row['assoc_id']);
		$entry->setFrom($row['from_address']);
		$entry->setRecipients($row['recipients']);
		$entry->setCcs($row['cc_recipients']);
		$entry->setBccs($row['bcc_recipients']);
		$entry->setSubject($row['subject']);
		$entry->setBody($row['body']);
		
		return $entry;
	}

	/**
	 * Insert a new log entry.
	 * @param $entry ArticleEmailLogEntry
	 */	
	function insertLogEntry(&$entry) {
		$ret = $this->update(
			'INSERT INTO article_email_log
				(article_id, sender_id, date_sent, assoc_type, assoc_id, from_address, recipients, cc_recipients, bcc_recipients, subject, body)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$entry->getArticleId(),
				$entry->getSenderId(),
				$entry->getDateSent() == null ? Core::getCurrentDate() : $entry->getDateSent(),
				$entry->getAssocType(),
				$entry->getAssocId(),
				$entry->getFrom(),
				$entry->getRecipients(),
				$entry->getCcs(),
				$entry->getBccs(),
				$entry->getSubject(),
				$entry->getBody()
			)
		);
		
		if ($ret) {
			$entry->setLogId($this->getInsertLogId());
		}
			
		return $ret;
	}
	
	/**
	 * Delete all log entries for an article.
	 * @param $articleId int
	 */
	function deleteArticleLogEntries($articleId) {
		return $this->update(
			'DELETE FROM article_email_log WHERE article_id = ?', $articleId
		);
	}
	
	/**
	 * Get the ID of the last inserted log entry.
	 * @return int
	 */
	function getInsertLogId() {
		return $this->getInsertId('article_email_log', 'log_id');
	}
	
}

?>
