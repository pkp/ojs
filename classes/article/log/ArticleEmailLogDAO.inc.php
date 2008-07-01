<?php

/**
 * @file classes/article/log/ArticleEmailLogDAO.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleEmailLogDAO
 * @ingroup article_log
 * @see ArticleEmailLogEntry, ArticleLog
 *
 * @brief Class for inserting/accessing article email log entries.
 */

// $Id$


import ('article.log.ArticleEmailLogEntry');

class ArticleEmailLogDAO extends DAO {
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

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnLogEntryFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all log entries for an article.
	 * @param $articleId int
	 * @return DAOResultFactory containing matching ArticleEmailLogEntry ordered by sequence
	 */
	function &getArticleLogEntries($articleId, $rangeInfo = null) {
		$returner = &$this->getArticleLogEntriesByAssoc($articleId, null, null, $rangeInfo);
		return $returner;
	}

	/**
	 * Retrieve all log entries for an article matching the specified association.
	 * @param $articleId int
	 * @param $assocType int
	 * @param $assocId int
	 * @return DAOResultFactory containing matching ArticleEventLogEntry ordered by sequence
	 */
	function &getArticleLogEntriesByAssoc($articleId, $assocType = null, $assocId = null, $rangeInfo = null) {
		$params = array($articleId);
		if (isset($assocType)) {
			array_push($params, $assocType);
			if (isset($assocId)) {
				array_push($params, $assocId);
			}
		}

		$result = &$this->retrieveRange(
			'SELECT * FROM article_email_log WHERE article_id = ?' . (isset($assocType) ? ' AND assoc_type = ?' . (isset($assocId) ? ' AND assoc_id = ?' : '') : '') . ' ORDER BY log_id DESC',
			$params, $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnLogEntryFromRow');
		return $returner;
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
		$entry->setDateSent($this->datetimeFromDB($row['date_sent']));
		$entry->setIPAddress($row['ip_address']);
		$entry->setEventType($row['event_type']);
		$entry->setAssocType($row['assoc_type']);
		$entry->setAssocId($row['assoc_id']);
		$entry->setFrom($row['from_address']);
		$entry->setRecipients($row['recipients']);
		$entry->setCcs($row['cc_recipients']);
		$entry->setBccs($row['bcc_recipients']);
		$entry->setSubject($row['subject']);
		$entry->setBody($row['body']);

		HookRegistry::call('ArticleEmailLogDAO::_returnLogEntryFromRow', array(&$entry, &$row));

		return $entry;
	}

	/**
	 * Insert a new log entry.
	 * @param $entry ArticleEmailLogEntry
	 */	
	function insertLogEntry(&$entry) {
		if ($entry->getDateSent() == null) {
			$entry->setDateSent(Core::getCurrentDate());
		}
		if ($entry->getIPAddress() == null) {
			$entry->setIPAddress(Request::getRemoteAddr());
		}
		$this->update(
			sprintf('INSERT INTO article_email_log
				(article_id, sender_id, date_sent, ip_address, event_type, assoc_type, assoc_id, from_address, recipients, cc_recipients, bcc_recipients, subject, body)
				VALUES
				(?, ?, %s, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
				$this->datetimeToDB($entry->getDateSent())),
			array(
				$entry->getArticleId(),
				$entry->getSenderId(),
				$entry->getIPAddress(),
				$entry->getEventType(),
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

		$entry->setLogId($this->getInsertLogId());
		return $entry->getLogId();
	}

	/**
	 * Delete a single log entry for an article.
	 * @param $logId int
	 * @param $articleId int optional
	 */
	function deleteLogEntry($logId, $articleId = null) {
		if (isset($articleId)) {
			return $this->update(
				'DELETE FROM article_email_log WHERE log_id = ? AND article_id = ?',
				array($logId, $articleId)
			);

		} else {
			return $this->update(
				'DELETE FROM article_email_log WHERE log_id = ?', $logId
			);
		}
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
	 * Transfer all article log entries to another user.
	 * @param $articleId int
	 */
	function transferArticleLogEntries($oldUserId, $newUserId) {
		return $this->update(
			'UPDATE article_email_log SET sender_id = ? WHERE sender_id = ?',
			array($newUserId, $oldUserId)
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
