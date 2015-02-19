<?php

/**
 * @file classes/file/PublicFileManager.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublicFileManager
 * @ingroup file
 *
 * @brief Wrapper class for uploading files to a site/journal's public directory.
 */


import('lib.pkp.classes.file.PKPPublicFileManager');

class PublicFileManager extends PKPPublicFileManager {
	/**
	 * Constructor
	 */
	function PublicFileManager() {
		parent::PKPPublicFileManager();
	}

	/**
	 * Get the path to a journal's public files directory.
	 * @param $journalId int
	 * @return string
	 */
	function getJournalFilesPath($journalId) {
		return Config::getVar('files', 'public_files_dir') . '/journals/' . $journalId;
	}

	/**
	 * Upload a file to a journals's public directory.
	 * @param $journalId int
	 * @param $fileName string the name of the file in the upload form
	 * @param $destFileName string the destination file name
	 * @return boolean
	 */
	function uploadJournalFile($journalId, $fileName, $destFileName) {
		return $this->uploadFile($fileName, $this->getJournalFilesPath($journalId) . '/' . $destFileName);
	}

	/**
	 * Write a file to a journals's public directory.
	 * @param $journalId int
	 * @param $destFileName string the destination file name
	 * @param $contents string the contents to write to the file
	 * @return boolean
	 */
	function writeJournalFile($journalId, $destFileName, &$contents) {
		return $this->writeFile($this->getJournalFilesPath($journalId) . '/' . $destFileName, $contents);
	}

	/**
	 * Copy a file to a journals's public directory.
	 * @param $journalId int
	 * @param $sourceFile string the source of the file to copy
	 * @param $destFileName string the destination file name
	 * @return boolean
	 */
	function copyJournalFile($journalId, $sourceFile, $destFileName) {
		return $this->copyFile($sourceFile, $this->getJournalFilesPath($journalId) . '/' . $destFileName);
	}

	/**
	 * Delete a file from a journal's public directory.
	 * @param $journalId int
	 * @param $fileName string the target file name
	 * @return boolean
	 */
	function removeJournalFile($journalId, $fileName) {
		return $this->deleteFile($this->getJournalFilesPath($journalId) . '/' . $fileName);
	}
}

?>
