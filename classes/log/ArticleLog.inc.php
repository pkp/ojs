<?php

/**
 * @file classes/log/MonographLog.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MonographLog
 * @ingroup log
 *
 * @brief Static class for adding / accessing monograph log entries.
 */

import('lib.pkp.classes.log.PkpLog');

class ArticleLog extends PkpLog {
	/**
	 * Add a new event log entry with the specified parameters
	 * @param $request object
	 * @param $submission object
	 * @param $eventType int
	 * @param $messageKey string
	 * @param $params array optional
	 * @return object ArticleLogEntry iff the event was logged
	 */
	function logEvent(&$request, &$submission, $eventType, $messageKey, $params = array()) {
		// Create a new entry object
		$articleEventLogDao = DAORegistry::getDAO('SubmissionEventLogDAO');
		$entry = $articleEventLogDao->newDataObject();

		// Set implicit parts of the log entry
		$entry->setDateLogged(Core::getCurrentDate());
		$entry->setIPAddress($request->getRemoteAddr());

		$user =& $request->getUser();
		if ($user) $entry->setUserId($user->getId());

		$entry->setAssocType(ASSOC_TYPE_SUBMISSION);
		$entry->setAssocId($submission->getId());

		// Set explicit parts of the log entry
		$entry->setEventType($eventType);
		$entry->setMessage($messageKey);
		$entry->setParams($params);
		$entry->setIsTranslated(0); // Legacy for other apps. All messages use locale keys.

		// Insert the resulting object
		$articleEventLogDao->insertObject($entry);
		return $entry;
	}
}

?>
