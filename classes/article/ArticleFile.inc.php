<?php

/**
 * ArticleFile.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article
 *
 * Article file class.
 *
 * $Id$
 */

class ArticleFile extends DataObject {

	/**
	 * Constructor.
	 */
	function ArticleFile() {
		parent::DataObject();
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
	 * Get ID of article.
	 * @return int
	 */
	function getArticleId() {
		return $this->getData('articleId');
	}
	
	/**
	 * Set ID of article.
	 * @param $articleId int
	 */
	function setArticleId($articleId) {
		return $this->setData('articleId', $articleId);
	}
	
	/**
	 * Get file name of the file.
	 * @ return string
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
		return $this->setData('fileType', $fileType);	
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
		return $this->setData('type', $type);	
	}
	
	/**
	 * Get status of the file.
	 * @return string
	 */
	function getStatus() {
		return $this->getData('status');	
	}
	
	/**
	 * Set status of the file.
	 * @param $status string
	 */
	function setStatus($type) {
		return $this->setData('status', $status);	
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
	
	
}

?>
