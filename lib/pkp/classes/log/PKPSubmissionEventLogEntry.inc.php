<?php

/**
 * @file classes/log/PKPSubmissionEventLogEntry.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPSubmissionEventLogEntry
 * @ingroup log
 * @see SubmissionEventLogDAO
 *
 * @brief Describes an entry in the submission history log.
 */

import('lib.pkp.classes.log.EventLogEntry');

/**
 * Log entry event types. All types must be defined here.
 */
define('SUBMISSION_LOG_TYPE_DEFAULT', 			0);
define('SUBMISSION_LOG_TYPE_AUTHOR', 			0x01);
define('SUBMISSION_LOG_TYPE_EDITOR', 			0x02);
define('SUBMISSION_LOG_TYPE_REVIEW', 			0x03);
define('SUBMISSION_LOG_TYPE_COPYEDIT', 			0x04);
define('SUBMISSION_LOG_TYPE_LAYOUT', 			0x05);
define('SUBMISSION_LOG_TYPE_PROOFREAD', 			0x06);

// General events					0x10000000
define('SUBMISSION_LOG_SUBMISSION_SUBMIT',		0x10000001);
define('SUBMISSION_LOG_METADATA_UPDATE',			0x10000002);
define('SUBMISSION_LOG_ADD_PARTICIPANT',			0x10000003);
define('SUBMISSION_LOG_REMOVE_PARTICIPANT',		0x10000004);

define('SUBMISSION_LOG_METADATA_PUBLISH',		0x10000006);
define('SUBMISSION_LOG_METADATA_UNPUBLISH',		0x10000007);

// Editor events

define('SUBMISSION_LOG_EDITOR_DECISION',			0x30000003);

// Reviewer events					0x40000000
define('SUBMISSION_LOG_REVIEW_ASSIGN',			0x40000001);

define('SUBMISSION_LOG_REVIEW_ACCEPT',			0x40000006);
define('SUBMISSION_LOG_REVIEW_DECLINE',			0x40000007);
define('SUBMISSION_LOG_REVIEW_UNCONSIDERED',		0x40000009);

define('SUBMISSION_LOG_REVIEW_SET_DUE_DATE',		0x40000011);

define('SUBMISSION_LOG_REVIEW_CLEAR',			0x40000014);

// Deletion of the last revision of a file
define('SUBMISSION_LOG_LAST_REVISION_DELETED', 	0x50000003);

// Production events
define('SUBMISSION_LOG_PROOFS_APPROVED',		0x50000008);

class PKPSubmissionEventLogEntry extends EventLogEntry {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Getters/setters
	//
	/**
	 * Set the submission ID
	 * @param $submission int
	 */
	function setSubmissionId($submissionId) {
		return $this->setAssocId($submissionId);
	}


	/**
	 * Get the submission ID
	 * @return int
	 */
	function getSubmissionId() {
		return $this->getAssocId();
	}


	/**
	 * Get the assoc ID
	 * @return int
	 */
	function getAssocType() {
		return ASSOC_TYPE_SUBMISSION;
	}
}

?>
