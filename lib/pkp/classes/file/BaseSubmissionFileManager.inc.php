<?php

/**
 * @file classes/file/BaseSubmissionFileManager.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BaseSubmissionFileManager
 * @ingroup file
 *
 * @brief Base helper class for submission file management tasks.
 *
 * Submission directory structure:
 * [submission id]/note
 * [submission id]/public
 * [submission id]/submission
 * [submission id]/submission/original
 * [submission id]/submission/review
 * [submission id]/submission/review/attachment
 * [submission id]/submission/editor
 * [submission id]/submission/copyedit
 * [submission id]/submission/layout
 * [submission id]/attachment
 */

import('lib.pkp.classes.file.ContextFileManager');

class BaseSubmissionFileManager extends ContextFileManager {
	/** @var int */
	var $_submissionId;

	/**
	 * Constructor.
	 * @param $contextId int
	 * @param $submissionId int
	 */
	function __construct($contextId, $submissionId) {
		parent::__construct($contextId);
		$this->_submissionId = (int) $submissionId;
	}


	//
	// Public methods
	//
	/**
	 * Get the base path for file storage.
	 * @return string
	 */
	function getBasePath() {
		$dirNames = Application::getFileDirectories();
		return parent::getBasePath() . $dirNames['submission'] . $this->_submissionId . '/';
	}

	/**
	 * Get the submission ID that this manager operates upon.
	 * @return int
	 */
	function getSubmissionId() {
		return $this->_submissionId;
	}
}

?>
