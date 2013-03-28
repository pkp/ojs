<?php

/**
 * @file classes/file/JournalFileManager.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalFileManager
 * @ingroup file
 *
 * @brief Class defining operations for private journal file management.
 */

import('classes.file.BaseJournalFileManager');

class JournalFileManager extends BaseJournalFileManager {

	/** @var string the path to location of the files */
	var $filesDir;

	/** @var int the ID of the associated journal */
	var $journalId;

	/** @var Journal the associated article */
	var $journal;

	/**
	 * Constructor.
	 * @param $pressId int
	 * @param $monographId int
	 */
	function JournalFileManager($journalId, $articleId) {
		parent::BaseJournalFileManager($journalId, $articleId);
	}

	/**
	 * Get the base path for file storage
	 * @return string
	 */
	function getBasePath() {
		return parent::getBasePath() . '/journals/' . $this->journalId . '/';
	}

	function uploadFile($fileName, $destFileName) {
		return parent::uploadFile($fileName, $this->filesDir . $destFileName);
	}

	function downloadFile($filePath, $fileType, $inline = false) {
		return parent::downloadFile($this->filesDir . $filePath, $fileType, $inline);
	}

	function deleteFile($fileName) {
		return parent::deleteFile($this->filesDir . $fileName);
	}
}

?>
