<?php

/**
 * @file classes/file/TemporaryFile.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TemporaryFile
 * @ingroup file
 * @see TemporaryFileDAO
 *
 * @brief Temporary file class.
 */

import('lib.pkp.classes.file.PKPFile');

class TemporaryFile extends PKPFile {

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Return absolute path to the file on the host filesystem.
	 * @return string
	 */
	function getFilePath() {
		import('lib.pkp.classes.file.TemporaryFileManager');
		$temporaryFileManager = new TemporaryFileManager();
		return $temporaryFileManager->getBasePath() . $this->getServerFileName();
	}

	//
	// Get/set methods
	//

	/**
	 * Get ID of associated user.
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}

	/**
	 * Set ID of associated user.
	 * @param $userId int
	 */
	function setUserId($userId) {
		$this->setData('userId', $userId);
	}
}

?>
