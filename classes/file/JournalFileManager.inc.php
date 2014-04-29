<?php

/**
 * @file classes/file/JournalFileManager.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalFileManager
 * @ingroup file
 *
 * @brief Class defining operations for private journal file management.
 */


import('lib.pkp.classes.file.FileManager');

class JournalFileManager extends FileManager {

	/** @var string the path to location of the files */
	var $filesDir;

	/** @var int the ID of the associated journal */
	var $journalId;

	/** @var Journal the associated article */
	var $journal;

	/**
	 * Constructor.
	 * Create a manager for handling journal file uploads.
	 * @param $journalId int
	 */
	function JournalFileManager(&$journal) {
		$this->journalId = $journal->getId();
		$this->journal =& $journal;
		$this->filesDir = Config::getVar('files', 'files_dir') . '/journals/' . $this->journalId . '/';

		parent::FileManager();
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
