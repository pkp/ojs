<?php

/**
 * @file ArticleLog.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article.log
 * @class ArticleLog
 *
 * ArticleLog class.
 * Static class for adding / accessing article log entries.
 *
 * $Id$
 */

class ArticleLog {

	/**
	 * Add an event log entry to this article.
	 * @param $articleId int
	 * @param $entry ArticleEventLogEntry
	 */
	function logEventEntry($articleId, &$entry) {
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$journalId = $articleDao->getArticleJournalId($articleId);

		if (!$journalId) {
			// Invalid article
			return false;
		}

		$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		if (!$settingsDao->getSetting($journalId, 'articleEventLog')) {
			// Event logging is disabled
			return false;
		}

		// Add the entry
		$entry->setArticleId($articleId);

		if ($entry->getUserId() == null) {
			$user = &Request::getUser();
			$entry->setUserId($user == null ? 0 : $user->getUserId());
		}

		$logDao = &DAORegistry::getDAO('ArticleEventLogDAO');
		return $logDao->insertLogEntry($entry);
	}

	/**
	 * Add a new event log entry with the specified parameters, at the default log level
	 * @param $articleId int
	 * @param $eventType int
	 * @param $assocType int
	 * @param $assocId int
	 * @param $messageKey string
	 * @param $messageParams array
	 */
	function logEvent($articleId, $eventType, $assocType = 0, $assocId = 0, $messageKey = null, $messageParams = array()) {
		return ArticleLog::logEventLevel($articleId, ARTICLE_LOG_LEVEL_NOTICE, $eventType, $assocType, $assocId, $messageKey, $messageParams);
	}

	/**
	 * Add a new event log entry with the specified parameters, including log level.
	 * @param $articleId int
	 * @param $logLevel char
	 * @param $eventType int
	 * @param $assocType int
	 * @param $assocId int
	 * @param $messageKey string
	 * @param $messageParams array
	 */
	function logEventLevel($articleId, $logLevel, $eventType, $assocType = 0, $assocId = 0, $messageKey = null, $messageParams = array()) {
		$entry = &new ArticleEventLogEntry();
		$entry->setLogLevel($logLevel);
		$entry->setEventType($eventType);
		$entry->setAssocType($assocType);
		$entry->setAssocId($assocId);

		if (isset($messageKey)) {
			$entry->setLogMessage($messageKey, $messageParams);
		}

		return ArticleLog::logEventEntry($articleId, $entry);
	}

	/**
	 * Get all event log entries for an article.
	 * @param $articleId int
	 * @return array ArticleEventLogEntry
	 */
	function &getEventLogEntries($articleId, $rangeInfo = null) {
		$logDao = &DAORegistry::getDAO('ArticleEventLogDAO');
		$returner = &$logDao->getArticleLogEntries($articleId, $rangeInfo);
		return $returner;
	}

	/**
	 * Add an email log entry to this article.
	 * @param $articleId int
	 * @param $entry ArticleEmailLogEntry
	 */
	function logEmailEntry($articleId, &$entry) {
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$journalId = $articleDao->getArticleJournalId($articleId);

		if (!$journalId) {
			// Invalid article
			return false;
		}

		$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		if (!$settingsDao->getSetting($journalId, 'articleEmailLog')) {
			// Email logging is disabled
			return false;
		}

		// Add the entry
		$entry->setArticleId($articleId);

		if ($entry->getSenderId() == null) {
			$user = &Request::getUser();
			$entry->setSenderId($user == null ? 0 : $user->getUserId());
		}

		$logDao = &DAORegistry::getDAO('ArticleEmailLogDAO');
		return $logDao->insertLogEntry($entry);
	}

	/**
	 * Get all email log entries for an article.
	 * @param $articleId int
	 * @return array ArticleEmailLogEntry
	 */
	function &getEmailLogEntries($articleId, $rangeInfo = null) {
		$logDao = &DAORegistry::getDAO('ArticleEmailLogDAO');
		$result = &$logDao->getArticleLogEntries($articleId, $rangeInfo);
		return $result;
	}

}

?>
