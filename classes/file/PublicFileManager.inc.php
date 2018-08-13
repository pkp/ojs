<?php

/**
 * @file classes/file/PublicFileManager.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
	 * Get the path to a journal's public files directory.
	 * @param $journalId int
	 * @return string
	 */
	function getJournalFilesPath($journalId) {
		return $this->getContextFilesPath(ASSOC_TYPE_JOURNAL, $journalId);
	}

	/**
	 * Get the path to a journal's public files directory.
	 * @param $assocType int Assoc type for context
	 * @param $contextId int Press ID
	 * @return string
	 */
	function getContextFilesPath($assocType, $contextId) {
		assert($assocType == ASSOC_TYPE_JOURNAL);
		return Config::getVar('files', 'public_files_dir') . '/journals/' . (int) $contextId;
	}

	/**
	 * Upload a file to a journals's public directory.
	 * @param $journalId int
	 * @param $fileName string the name of the file in the upload form
	 * @param $destFileName string the destination file name
	 * @return boolean
	 */
	function uploadJournalFile($journalId, $fileName, $destFileName) {
		return $this->uploadContextFile(ASSOC_TYPE_JOURNAL, $journalId, $fileName, $destFileName);
	}

	/**
	 * Write a file to a journals's public directory.
	 * @param $journalId int
	 * @param $destFileName string the destination file name
	 * @param $contents string the contents to write to the file
	 * @return boolean
	 */
	function writeJournalFile($journalId, $destFileName, $contents) {
		return $this->writeContextFile(ASSOC_TYPE_JOURNAL, $journalId, $destFileName, $contents);
	}

	/**
	 * Copy a file to a journals's public directory.
	 * @param $journalId int
	 * @param $sourceFile string the source of the file to copy
	 * @param $destFileName string the destination file name
	 * @return boolean
	 */
	function copyJournalFile($journalId, $sourceFile, $destFileName) {
		return $this->copyContextFile(ASSOC_TYPE_JOURNAL, $journalId, $sourceFile, $destFileName);
	}

	/**
	 * Delete a file from a journal's public directory.
	 * @param $journalId int
	 * @param $fileName string the target file name
	 * @return boolean
	 */
	function removeJournalFile($journalId, $fileName) {
		return $this->removeContextFile(ASSOC_TYPE_JOURNAL, $journalId, $fileName);
	}
}

?>
