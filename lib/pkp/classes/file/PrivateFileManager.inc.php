<?php

/**
 * @file classes/file/PrivateFileManager.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PrivateFileManager
 * @ingroup file
 *
 * @brief Class defining operations for private file management.
 */

import('lib.pkp.classes.file.FileManager');

class PrivateFileManager extends FileManager {

	/** var $filesDir */
	var $filesDir;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->filesDir = $this->getBasePath();
	}

	/**
	 * Get the base path for file storage.
	 * @return string
	 */
	function getBasePath() {
		return Config::getVar('files', 'files_dir');
	}
}

?>
