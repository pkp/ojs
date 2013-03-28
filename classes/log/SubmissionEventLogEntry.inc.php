<?php

/**
 * @file classes/log/SubmissionEventLogEntry.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionEventLogEntry
 * @ingroup log
 * @see SubmissionEventLogDAO
 *
 * @brief Describes an entry in the submission history log.
 */

import('lib.pkp.classes.log.PKPSubmissionEventLogEntry');


class SubmissionEventLogEntry extends PKPSubmissionEventLogEntry {
	/**
	 * Constructor.
	 */
	function SubmissionEventLogEntry() {
		parent::PKPSubmissionEventLogEntry();
	}

	//
	// Getters/setters
	//
	/**
	 * Set the article ID
	 * @param $articleId int
	 */
	function setArticleId($articleId) {
		return $this->setAssocId($articleId);
	}


	/**
	 * Get the article ID
	 * @return int
	 */
	function getArticleId() {
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
