<?php

/**
 * ArticleFile.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
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
	
	/**
	 * Return absolute path to the file on the host filesystem.
	 * @return string
	 */
	function getFilePath() {
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = &$articleDao->getArticle($this->getArticleId());
		$journalId = $article->getJournalId();
		
		return Config::getVar('files', 'files_dir') . '/journals/' . $journalId .
		'/articles/' . $this->getArticleId() . '/' . $this->getType() . '/' . $this->getFileName();
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
	 * Get associated ID of file. (Used, e.g., for email log attachments.)
	 * @return int
	 */
	function getAssocId() {
		return $this->getData('assocId');
	}
	
	/**
	 * Set associated ID of file. (Used, e.g., for email log attachments.)
	 * @param $assocId int
	 */
	function setAssocId($assocId) {
		return $this->setData('assocId', $assocId);
	}
	
	/**
	 * Get revision number.
	 * @return int
	 */
	function getRevision() {
		return $this->getData('revision');
	}
	
	/**
	 * Set revision number.
	 * @param $revision int
	 */
	function setRevision($revision) {
		return $this->setData('revision', $revision);
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
	function setStatus($status) {
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
		return FileManager::getNiceFileSize($this->getData('fileSize'));
	}

	/**
	 * Get round.
	 * @return int
	 */
	
	function getRound() {
		return $this->getData('round');	
	}
	

	/**
	 * Set round.
	 * @param $round int
	 */
	 
	function setRound($round) {
		return $this->SetData('round', $round);
	}
	
	/**
	 * Get viewable.
	 * @return boolean
	 */
	
	function getViewable() {
		return $this->getData('viewable');	
	}
	

	/**
	 * Set viewable.
	 * @param $viewable boolean
	 */
	 
	function setViewable($viewable) {
		return $this->SetData('viewable', $viewable);
	}

	/**
	 * Check if the file may be displayed inline.
	 * @return boolean
	 */
	function isInlineable() {
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		return $articleFileDao->isInlineable($this);
	}
}

?>
