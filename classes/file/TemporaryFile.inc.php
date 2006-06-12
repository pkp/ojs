<?php

/**
 * TemporaryFile.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package file
 *
 * Temporary file class.
 *
 * $Id$
 */

class TemporaryFile extends DataObject {

	/**
	 * Constructor.
	 */
	function TemporaryFile() {
		parent::DataObject();
	}
	
	/**
	 * Return absolute path to the file on the host filesystem.
	 * @return string
	 */
	function getFilePath() {
		return Config::getVar('files', 'files_dir') . '/temp/' . $this->getFileName();
	}
	
	//
	// Get/set methods
	//
	/**
	 * Get ID of file.
	 * @return int
	 */
	function getFileId() {
		return $this->getData('fileId');
	}
	
	/**
	 * Set ID of file.
	 * @param $fileId int
	 */
	function setFileId($fileId) {
		return $this->setData('fileId', $fileId);
	}
	
	/**
	 * Get ID of associated user.
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}
	
	/**
	 * Set ID of associated user.
	 * @param $userId int
	 */
	function setUserId($userId) {
		return $this->setData('userId', $userId);
	}
	
	/**
	 * Get file name of the file.
	 * @param return string
	 */
	function getFileName() {
		return $this->getData('fileName');	
	}
	
	/**
	 * Set file name of the file.
	 * @param $fileName string
	 */
	function setFileName($fileName) {
		return $this->setData('fileName', $fileName);	
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
		return $this->setData('originalFileName', $originalFileName);	
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
		return $this->setData('filetype', $fileType);	
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
	 * Get nice file size of file.
	 * @return string
	 */
	
	function getNiceFileSize() {
		return round($this->getData('fileSize') / 1000).'k';	
	}
}

?>
