<?php

/**
 * @file classes/issue/IssueFile.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueFile
 * @ingroup issue
 *
 * @brief Issue file class.
 */

import('lib.pkp.classes.file.PKPFile');

/* File content type IDs */
define('ISSUE_FILE_PUBLIC', 0x000001);


class IssueFile extends PKPFile {

	//
	// Get/set methods
	//

	/**
	 * Get ID of issue.
	 * @return int
	 */
	function getIssueId() {
		return $this->getData('issueId');
	}

	/**
	 * set ID of issue.
	 * @param $issueId int
	 */
	function setIssueId($issueId) {
		return $this->setData('issueId', $issueId);
	}

	/**
	 * Get content type of the file.
	 * @ return string
	 */
	function getContentType() {
		return $this->getData('contentType');
	}

	/**
	 * set type of the file.
	 * @param $fileType string
	 */
	function setContentType($contentType) {
		return $this->setData('contentType', $contentType);
	}

	/**
	 * Get modified date of file.
	 * @return date
	 */
	function getDateModified() {
		return $this->getData('dateModified');
	}

	/**
	 * set modified date of file.
	 * @param $dateModified date
	 */
	function setDateModified($dateModified) {
		return $this->setData('dateModified', $dateModified);
	}
}


