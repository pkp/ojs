<?php

/**
 * FrontMatterManager.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package file
 *
 * Class defining operations for front matter management.
 *
 * $Id$
 */


class FrontMatterManager extends FileManager {
	
	/** @var string the path to location of the files */
	var $filesDir;
	
	/** @var int the ID of the associated issue */
	var $issueId;
	
	/**
	* Constructor.
	* Create a manager for handling issue file uploads.
	* @param $issueId int
	*/
	function FrontMatterManager($issueId) {
		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();
		$this->filesDir = Config::getVar('files', 'files_dir') . '/journals/' . $journalId . '/issues/';
		if ($issueId) {
			$this->issueId = $issueId;
			$this->filesDir .= "$issueId/";
		} else {
			$this->issueId = 0;
		}
	}

	/**
	 * Upload front matter.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $newFileName string the name of the file to be saved
	 * @return boolean returns true if successful
	 */
	function uploadFile($fileName, $newFileName) {
		return parent::uploadFile($fileName, $this->filesDir.$newFileName);
	}

	/**
	 * Move front matter.
	 * @param $fileName string the name of the file to be moved
	 * @param $dest string the new location for the file
	 * @return boolean returns true if successful
	 */
	function moveFile($fileName, $dest) {
		return rename($this->filesDir.$fileName, $dest);
	}

	/**
	 * get file directory path
	 * @return string directory path for this issue
	 */
	function getIssueDirectory() {
		return $this->filesDir;
	}	

	/**
	 * get file extension
	 * @param string a valid file name
	 * @return string extension
	 */
	function getExtension($fileName) {
		$extension = '';
		$fileParts = explode('.', $fileName);
		if (is_array($fileParts)) {
			$extension = $fileParts[count($fileParts) - 1];
		}
		return $extension;
	}

	/**
	 * download file
	 */
	function download($fileName) {
		parent::downloadFile($this->filesDir . $fileName);
	}


	/**
	* Remove a file.
	* @param $fileName string the name of the file
	* @return boolean
	*/
	function deleteFile($fileName) {
		return parent::deleteFile($this->filesDir . $fileName);
	}

}

?>
