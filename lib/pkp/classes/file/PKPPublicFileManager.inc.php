<?php

/**
 * @file classes/file/PKPPublicFileManager.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPPublicFileManager
 * @ingroup file
 *
 * @brief Wrapper class for uploading files to a site/journal's public directory.
 */


import('lib.pkp.classes.file.FileManager');

class PKPPublicFileManager extends FileManager {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Get the path to the site public files directory.
	 * @return string
	 */
	function getSiteFilesPath() {
		return Config::getVar('files', 'public_files_dir') . '/site';
	}

	/**
	 * Get the path to a context's public files directory.
	 * @param $assocType int Assoc type for context
	 * @param $contextId int Context ID
	 * @return string
	 */
	function getContextFilesPath($assocType, $contextId) {
		assert(false); // Must be implemented by subclasses
	}

	/**
	 * Upload a file to a context's public directory.
	 * @param $assocType int The assoc type of the context
	 * @param $contextId int The context ID
	 * @param $fileName string the name of the file in the upload form
	 * @param $destFileName string the destination file name
	 * @return boolean
	 */
	function uploadContextFile($assocType, $contextId, $fileName, $destFileName) {
		return $this->uploadFile($fileName, $this->getContextFilesPath($assocType, $contextId) . '/' . $destFileName);
	}

	/**
	 * Write a file to a context's public directory.
	 * @param $assocType int Assoc type for context
	 * @param $contextId int Context ID
	 * @param $destFileName string the destination file name
	 * @param $contents string the contents to write to the file
	 * @return boolean
	 */
	function writeContextFile($assocType, $contextId, $destFileName, $contents) {
		return $this->writeFile($this->getContextFilesPath($assocType, $contextId) . '/' . $destFileName, $contents);
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
	 * Copy a file to a context's public directory.
	 * @param $assocType Assoc type for context
	 * @param $contextId int Context ID
	 * @param $sourceFile string the source of the file to copy
	 * @param $destFileName string the destination file name
	 * @return boolean
	 */
	function copyContextFile($assocType, $contextId, $sourceFile, $destFileName) {
		return $this->copyFile($sourceFile, $this->getContextFilesPath($assocType, $contextId) . '/' . $destFileName);
	}

	/**
	 * Delete a file from a context's public directory.
	 * @param $assocType Assoc type for context
	 * @param $contextId int Context ID
	 * @param $fileName string the target file name
	 * @return boolean
	 */
	function removeContextFile($assocType, $contextId, $fileName) {
		return $this->deleteFile($this->getContextFilesPath($assocType, $contextId) . '/' . $fileName);
	}

	/**
	 * Delete a file from the site's public directory.
	 * @param $fileName string the target file name
	 * @return boolean
	 */
	function removeSiteFile($fileName) {
		return $this->deleteFile($this->getSiteFilesPath() . '/' . $fileName);
	}
}

?>
