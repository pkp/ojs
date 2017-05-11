<?php

/**
 * @file classes/log/SubmissionFileLog.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFileLog
 * @ingroup log
 *
 * @brief Static class for adding / accessing submission file log entries.
 */

import('lib.pkp.classes.log.SubmissionLog');

class SubmissionFileLog extends SubmissionLog {
	/**
	 * Add a new file event log entry with the specified parameters
	 * @param $request object
	 * @param $submissionFile object
	 * @param $eventType int
	 * @param $messageKey string
	 * @param $params array optional
	 * @return object SubmissionLogEntry iff the event was logged
	 */
	static function logEvent($request, &$submissionFile, $eventType, $messageKey, $params = array()) {
		// Create a new entry object
		$submissionFileEventLogDao = DAORegistry::getDAO('SubmissionFileEventLogDAO');
		$entry = $submissionFileEventLogDao->newDataObject();

		// Set implicit parts of the log entry
		$entry->setDateLogged(Core::getCurrentDate());
		$entry->setIPAddress($request->getRemoteAddr());

		$user = $request->getUser();
		if ($user) $entry->setUserId($user->getId());

		$entry->setAssocType(ASSOC_TYPE_SUBMISSION_FILE);
		$entry->setAssocId($submissionFile->getFileId());

		// Set explicit parts of the log entry
		$entry->setEventType($eventType);
		$entry->setMessage($messageKey);
		$entry->setParams($params);
		$entry->setIsTranslated(0); // Legacy for other apps. All messages use locale keys.

		// Insert the resulting object
		$submissionFileEventLogDao->insertObject($entry);
		return $entry;
	}
}

?>
