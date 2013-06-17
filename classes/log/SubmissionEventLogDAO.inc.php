<?php

/**
 * @file classes/log/SubmissionEventLogDAO.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionEventLogDAO
 * @ingroup log
 * @see PKPSubmissionEventLogDAO
 *
 */

import('lib.pkp.classes.log.PKPSubmissionEventLogDAO');
import('classes.log.SubmissionEventLogEntry');

class SubmissionEventLogDAO extends PKPSubmissionEventLogDAO {
	/**
	 * Constructor
	 */
	function SubmissionEventLogDAO() {
		parent::PKPSubmissionEventLogDAO();
	}

	/**
	 * Generate a new DataObject
	 * @return SubmissionEventLogEntry
	 */
	function newDataObject() {
		$returner = new SubmissionEventLogEntry();
		$returner->setAssocType(ASSOC_TYPE_SUBMISSION_FILE);
		return $returner;
	}

	/**
	 * Get article event log entries by article ID
	 * @param $articleId int
	 * @return DAOResultFactory
	 */
	function &getByArticleId($articleId) {
		return $this->getByAssoc(ASSOC_TYPE_SUBMISSION, $articleId);
	}
}

?>
