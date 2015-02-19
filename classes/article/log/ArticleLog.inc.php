<?php

/**
 * @defgroup article_log
 */

/**
 * @file classes/article/log/ArticleLog.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleLog
 * @ingroup article_log
 *
 * @brief Static class for adding / accessing article log entries.
 */

import('classes.article.log.ArticleEventLogEntry');
import('classes.article.log.ArticleEmailLogEntry');

class ArticleLog {
	/**
	 * Add a new event log entry with the specified parameters
	 * @param $request object
	 * @param $article object
	 * @param $eventType int
	 * @param $messageKey string
	 * @param $params array optional
	 * @return object ArticleLogEntry iff the event was logged
	 */
	function logEvent(&$request, &$article, $eventType, $messageKey, $params = array()) {
		$journal =& $request->getJournal();
		$user =& $request->getUser();
		$userId = (isset($user)) ? $user->getId() : 0;
		return ArticleLog::logEventHeadless($journal, $userId, $article, $eventType, $messageKey, $params);
	}

	/**
	 * Add a new event log entry with the specified parameters
	 * @param $request object
	 * @param $article object
	 * @param $eventType int
	 * @param $messageKey string
	 * @param $params array optional
	 * @return object ArticleLogEntry iff the event was logged
	 */
	function logEventHeadless(&$journal, $userId, &$article, $eventType, $messageKey, $params = array()) {

		// Create a new entry object
		$articleEventLogDao =& DAORegistry::getDAO('ArticleEventLogDAO');
		$entry = $articleEventLogDao->newDataObject();

		// Set implicit parts of the log entry
		$entry->setDateLogged(Core::getCurrentDate());
		$entry->setIPAddress(Request::getRemoteAddr());
		$entry->setUserId($userId);
		$entry->setAssocType(ASSOC_TYPE_ARTICLE);
		$entry->setAssocId($article->getId());

		// Set explicit parts of the log entry
		$entry->setEventType($eventType);
		$entry->setMessage($messageKey);
		$entry->setParams($params);
		$entry->setIsTranslated(0);
		$entry->setParams($params);

		// Insert the resulting object
		$articleEventLogDao->insertObject($entry);
		return $entry;
	}

	/**
	 * Add an email log entry to this article.
	 * @param $articleId int
	 * @param $entry ArticleEmailLogEntry
	 * @param $request object
	 */
	function logEmail($articleId, &$entry, $request = null) {
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$journalId = $articleDao->getArticleJournalId($articleId);

		if (!$journalId) {
			// Invalid article
			return false;
		}

		// Add the entry
		$entry->setAssocType(ASSOC_TYPE_ARTICLE);
		$entry->setAssocId($articleId);

		if ($request) {
			$user =& $request->getUser();
			$entry->setSenderId($user == null ? 0 : $user->getId());
			$entry->setIPAddress($request->getRemoteAddr());
		} else $entry->setSenderId(0);

		$entry->setDateSent(Core::getCurrentDate());

		$logDao =& DAORegistry::getDAO('ArticleEmailLogDAO');
		return $logDao->insertObject($entry);
	}
}

?>
