<?php

/**
 * ArticleNote.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article
 *
 * Class for ArticleNote.
 *
 * $Id$
 */
 
class ArticleNote extends DataObject {
 
	/**
	 * Constructor.
	 */
	function ArticleNote() {
		parent::DataObject();
	}
	
	/**
	 * get article note id
	 * @return int
	 */
	function getNoteId() {
		return $this->getData('noteId');
	}
	 
	/**
	 * set article note id
	 * @param $noteId int
	 */
	function setNoteId($noteId) {
		return $this->setData('noteId',$noteId);
	}
 
	/**
	 * get article id
	 * @return int
	 */
	function getArticleId() {
		return $this->getData('articleId');
	}
	 
	/**
	 * set article id
	 * @param $articleId int
	 */
	function setArticleId($articleId) {
		return $this->setData('articleId',$articleId);
	}
	
	/**
	 * get user id
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}
	 
	/**
	 * set user id
	 * @param $userId int
	 */
	function setUserId($userId) {
		return $this->setData('userId',$userId);
	}
 
 	/**
	 * get date created
	 * @return date
	 */
	function getDateCreated() {
		return $this->getData('dateCreated');
	}
	 
	/**
	 * set date created
	 * @param $dateCreated date
	 */
	function setDateCreated($dateCreated) {
		return $this->setData('dateCreated',$dateCreated);
	}

 	/**
	 * get date modified
	 * @return date
	 */
	function getDateModified() {
		return $this->getData('dateModified');
	}
	 
	/**
	 * set date modified
	 * @param $dateModified date
	 */
	function setDateModified($dateModified) {
		return $this->setData('dateModified',$dateModified);
	}

	/**
	 * get title
	 * @return string
	 */
	function getTitle() {
		return $this->getData('title');
	}
	 
	/**
	 * set title
	 * @param $title string
	 */
	function setTitle($title) {
		return $this->setData('title',$title);
	}

	/**
	 * get note
	 * @return string
	 */
	function getNote() {
		return $this->getData('note');
	}
	 
	/**
	 * set note
	 * @param $note string
	 */
	function setNote($note) {
		return $this->setData('note',$note);
	}

	/**
	 * get file id
	 * @return int
	 */
	function getFileId() {
		return $this->getData('fileId');
	}
	 
	/**
	 * set file id
	 * @param $fileId int
	 */
	function setFileId($fileId) {
		return $this->setData('fileId',$fileId);
	}

 }
 
?>
