<?php

/**
 * @file classes/issue/IssueFile.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueFile
 * @ingroup issue
 *
 * @brief Issue file class.
 */

/* File content type IDs */
define('ISSUE_FILE_PUBLIC', 0x000001);

class IssueFile extends DataObject {

	/**
	 * Constructor.
	 */
	function IssueFile() {
		parent::DataObject();
	}

	//
	// Get/set methods
	//

	/**
	 * Get ID of issue.
	 * @return int
	 */
	function getIssueId() {
		return $this->getData('issueId');
	}

	/**
	 * set ID of issue.
	 * @param $issueId int
	 */
	function setIssueId($issueId) {
		return $this->setData('issueId', $issueId);
	}

	/**
	 * Get file name of the file.
	 * @param return string
	 */
	function getFileName() {
		return $this->getData('fileName');
	}

	/**
	 * set file name of the file.
	 * @param $fileName string
	 */
	function setFileName($fileName) {
		return $this->setData('fileName', $fileName);
	}

	/**
	 * Get content file type of the file.
	 * @ return string
	 */
	function getFileType() {
		return $this->getData('fileType');
	}

	/**
	 * set file type of the file.
	 * @param $fileType string
	 */
	function setFileType($fileType) {
		return $this->setData('fileType', $fileType);
	}

	/**
	 * Get file size of file.
	 * @return int
	 */
	function getFileSize() {
		return $this->getData('fileSize');
	}

	/**
	 * set file size of file.
	 * @param $fileSize int
	 */
	function setFileSize($fileSize) {
		return $this->setData('fileSize', $fileSize);
	}

	/**
	 * Get nice file size of file.
	 * @return string
	 */
	function getNiceFileSize() {
		return FileManager::getNiceFileSize($this->getData('fileSize'));
	}

	/**
	 * Get content type of the file.
	 * @ return string
	 */
	function getContentType() {
		return $this->getData('contentType');
	}

	/**
	 * set type of the file.
	 * @param $fileType string
	 */
	function setContentType($contentType) {
		return $this->setData('contentType', $contentType);
	}

	/**
	 * Get original uploaded file name of the file.
	 * @param return string
	 */
	function getOriginalFileName() {
		return $this->getData('originalFileName');
	}

	/**
	 * set original uploaded file name of the file.
	 * @param $originalFileName string
	 */
	function setOriginalFileName($originalFileName) {
		return $this->setData('originalFileName', $originalFileName);
	}

	/**
	 * Get uploaded date of file.
	 * @return date
	 */
	function getDateUploaded() {
		return $this->getData('dateUploaded');
	}

	/**
	 * set uploaded date of file.
	 * @param $dateUploaded date
	 */
	function setDateUploaded($dateUploaded) {
		return $this->setData('dateUploaded', $dateUploaded);
	}

	/**
	 * Get modified date of file.
	 * @return date
	 */
	function getDateModified() {
		return $this->getData('dateModified');
	}

	/**
	 * set modified date of file.
	 * @param $dateModified date
	 */
	function setDateModified($dateModified) {
		return $this->setData('dateModified', $dateModified);
	}
}

?>
