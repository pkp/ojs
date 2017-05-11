<?php

/**
 * @file classes/file/PKPFile.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPFile
 * @ingroup file
 *
 * @brief Base PKP file class.
 */

class PKPFile extends DataObject {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Get/set methods
	//
	/**
	 * Get ID of file.
	 * @return int
	 */
	function getFileId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getId();
	}

	/**
	 * Set ID of file.
	 * @param $fileId int
	 */
	function setFileId($fileId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setId($fileId);
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
	 * Get original uploaded file name of the file.
	 * @param return string
	 */
	function getOriginalFileName() {
		return $this->getData('originalFileName');
	}

	/**
	 * Set original uploaded file name of the file.
	 * @param $originalFileName string
	 */
	function setOriginalFileName($originalFileName) {
		$this->setData('originalFileName', $originalFileName);
	}

	/**
	 * Get type of the file.
	 * @ return string
	 */
	function getFileType() {
		return $this->getData('filetype');
	}

	/**
	 * Set type of the file.
	 * @param $type string
	 */
	function setFileType($fileType) {
		$this->setData('filetype', $fileType);
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
	 * Return pretty file size string (in B, KB, MB, or GB units).
	 * @return string
	 */
	function getNiceFileSize() {
		$niceFileSizeUnits = array('B', 'KB', 'MB', 'GB');
		$size = $this->getData('fileSize');
		for($i = 0; $i < 4 && $size > 1024; $i++) {
			$size >>= 10;
		}
		return $size . $niceFileSizeUnits[$i];
	}


	//
	// Abstract template methods to be implemented by subclasses.
	//
	/**
	 * Return absolute path to the file on the host filesystem.
	 * @return string
	 */
	function getFilePath() {
		assert(false);
	}
}

?>
