<?php

/**
 * @file classes/log/SubmissionEmailLogDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionEmailLogDAO
 * @ingroup log
 * @see EmailLogDAO
 *
 * @brief Extension to EmailLogDAO for submission-specific log entries.
 */

import('lib.pkp.classes.log.EmailLogDAO');
import('classes.log.SubmissionEmailLogEntry');

class SubmissionEmailLogDAO extends EmailLogDAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Instantiate and return a SubmissionEmailLogEntry
	 * @return SubmissionEmailLogEntry
	 */
	function newDataObject() {
		$returner = new SubmissionEmailLogEntry();
		$returner->setAssocType(ASSOC_TYPE_SUBMISSION);
		return $returner;
	}

	/**
	 * Get submission email log entries by submission ID and event type
	 * @param $submissionId int
	 * @param $eventType SUBMISSION_EMAIL_...
	 * @param $userId int optional Return only emails sent to this user.
	 * @return DAOResultFactory
	 */
	function getByEventType($submissionId, $eventType, $userId = null) {
		return parent::getByEventType(ASSOC_TYPE_SUBMISSION, $submissionId, $eventType, $userId);
	}

	/**
	 * Get submission email log entries by submission ID
	 * @param $submissionId int
	 * @return DAOResultFactory
	 */
	function getBySubmissionId($submissionId) {
		return $this->getByAssoc(ASSOC_TYPE_SUBMISSION, $submissionId);
	}
}

?>
