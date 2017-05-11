<?php

/**
 * @file classes/file/ContextFileManager.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContextFileManager
 * @ingroup file
 *
 * @brief Class defining operations for private context file management.
 */


import('lib.pkp.classes.file.PrivateFileManager');

class ContextFileManager extends PrivateFileManager {
	/** @var int the ID of the associated context */
	var $contextId;

	/**
	 * Constructor.
	 * Create a manager for handling context file uploads.
	 * @param $context Context
	 */
	function __construct($contextId) {
		parent::__construct();
		$this->contextId = (int) $contextId;
	}

	/**
	 * Get the base path for file storage
	 * @return string
	 */
	function getBasePath() {
		$dirNames = Application::getFileDirectories();
		return parent::getBasePath() . $dirNames['context'] . $this->contextId . '/';
	}
}

?>
