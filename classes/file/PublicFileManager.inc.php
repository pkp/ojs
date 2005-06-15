<?php

/**
 * PublicFileManager.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package file
 *
 * PublicFileManager class.
 * Wrapper class for uploading files to a site/journal's public directory.
 *
 * $Id$
 */

import('file.FileManager');

class PublicFileManager extends FileManager {

	/**
	 * Get the path to the site public files directory.
	 * @return string
	 */
	function getSiteFilesPath() {
		return Config::getVar('files', 'public_files_dir') . '/site';
	}

	/**
	 * Upload a file to the site's public directory.
	 * @param $fileName string the name of the file in the upload form
	 * @param $destFileName string the destination file name
	 * @return boolean
	 */
 	function uploadSiteFile($fileName, $destFileName) {
 		return $this->uploadFile($fileName, $this->getSiteFilesPath() . '/' . $destFileName);
 	}
 	
 	/**
	 * Delete a file from the site's public directory.
 	 * @param $fileName string the target file name
	 * @return boolean
 	 */
 	function removeSiteFile($fileName) {
 		return $this->deleteFile($this->getSiteFilesPath() . '/' . $fileName);
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
 		return $this->writeFile($this->getJournalFilesPath($journalId) . '/' . $destFileName, &$contents);
 	}
 	
	/**
	 * Copy a file to a journals's public directory.
	 * @param $journalId int
	 * @param $sourceFile string the source of the file to copy
	 * @param $destFileName string the destination file name
	 * @return boolean
	 */
 	function copyJournalFile($journalId, $sourceFile, $destFileName) {
 		return copy($sourceFile, $this->getJournalFilesPath($journalId) . '/' . $destFileName);
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
