<?php

/**
 * @file classes/context/LibraryFile.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LibraryFile
 * @ingroup context
 * @see LibraryFileDAO
 *
 * @brief Library file class.
 */

define('LIBRARY_FILE_TYPE_CONTRACT',	0x00001);
define('LIBRARY_FILE_TYPE_MARKETING',	0x00002);
define('LIBRARY_FILE_TYPE_PERMISSION',	0x00003);
define('LIBRARY_FILE_TYPE_REPORT',	0x00004);
define('LIBRARY_FILE_TYPE_OTHER',	0x00005);

import('lib.pkp.classes.file.FileManager');

class LibraryFile extends DataObject {
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
		$contextId = $this->getContextId();

		return Config::getVar('files', 'public_files_dir') . '/contexts/' . $contextId . '/library/' . $this->getServerFileName();
	}

	//
	// Get/set methods
	//
	/**
	 * Get ID of context.
	 * @return int
	 */
	function getContextId() {
		return $this->getData('contextId');
	}

	/**
	 * Set ID of context.
	 * @param $contextId int
	 */
	function setContextId($contextId) {
		$this->setData('contextId', $contextId);
	}

	/**
	 * Get ID of submission.
	 * @return int
	 */
	function getSubmissionId() {
		return $this->getData('submissionId');
	}

	/**
	 * Set ID of submission.
	 * @param $submission int
	 */
	function setSubmissionId($submissionId) {
		$this->setData('submissionId', $submissionId);
	}

	/**
	 * Get server-side file name of the file.
	 * @param return string
	 */
	function getServerFileName() {
		return $this->getData('fileName');
	}

	/**
	 * Set server-side file name of the file.
	 * @param $fileName string
	 */
	function setServerFileName($fileName) {
		$this->setData('fileName', $fileName);
	}

	/**
	 * Get original file name of the file.
	 * @param return string
	 */
	function getOriginalFileName() {
		return $this->getData('originalFileName');
	}

	/**
	 * Set original file name of the file.
	 * @param $originalFileName string
	 */
	function setOriginalFileName($originalFileName) {
		$this->setData('originalFileName', $originalFileName);
	}

	/**
	 * Set the name of the file
	 * @param $name string
	 * @param $locale string
	 */
	function setName($name, $locale) {
		$this->setData('name', $name, $locale);
	}

	/**
	 * Get the name of the file
	 * @param $locale string
	 * @return string
	 */
	function getName($locale) {
		return $this->getData('name', $locale);
	}

	/**
	 * Get the localized name of the file
	 * @return string
	 */
	function getLocalizedName() {
		return $this->getLocalizedData('name');
	}

	/**
	 * Get file type of the file.
	 * @ return string
	 */
	function getFileType() {
		return $this->getData('fileType');
	}

	/**
	 * Set file type of the file.
	 * @param $fileType string
	 */
	function setFileType($fileType) {
		$this->setData('fileType', $fileType);
	}

	/**
	 * Get type of the file.
	 * @ return string
	 */
	function getType() {
		return $this->getData('type');
	}

	/**
	 * Set type of the file.
	 * @param $type string
	 */
	function setType($type) {
		$this->setData('type', $type);
	}

	/**
	 * Get uploaded date of file.
	 * @return date
	 */
	function getDateUploaded() {
		return $this->getData('dateUploaded');
	}

	/**
	 * Set uploaded date of file.
	 * @param $dateUploaded date
	 */
	function setDateUploaded($dateUploaded) {
		return $this->SetData('dateUploaded', $dateUploaded);
	}

	/**
	 * Get modified date of file.
	 * @return date
	 */
	function getDateModified() {
		return $this->getData('dateModified');
	}

	/**
	 * Set modified date of file.
	 * @param $dateModified date
	 */
	function setDateModified($dateModified) {
		return $this->SetData('dateModified', $dateModified);
	}

	/**
	 * Get file size of file.
	 * @return int
	 */
	function getFileSize() {
		return $this->getData('fileSize');
	}


	/**
	 * Set file size of file.
	 * @param $fileSize int
	 */
	function setFileSize($fileSize) {
		return $this->SetData('fileSize', $fileSize);
	}

	/**
	 * Get nice file size of file.
	 * @return string
	 */
	function getNiceFileSize() {
		$fileManager = new FileManager();
		return $fileManager->getNiceFileSize($this->getData('fileSize'));
	}

	/**
	 * Get the file's document type (enumerated types)
	 * @return string
	 */
	function getDocumentType() {
		$fileManager = new FileManager();
		return $fileManager->getDocumentType($this->getFileType());
	}
}

?>
