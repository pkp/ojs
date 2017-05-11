<?php

/**
 * @file classes/note/Note.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Note
 * @ingroup note
 * @see NoteDAO
 * @brief Class for Note.
 */


class Note extends DataObject {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * get user id of the note's author
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}

	/**
	 * set user id of the note's author
	 * @param $userId int
	 */
	function setUserId($userId) {
		$this->setData('userId', $userId);
	}

	/**
	 * Return the user of the note's author.
	 * @return User
	 */
	function getUser() {
		$userDao = DAORegistry::getDAO('UserDAO');
		return $userDao->getById($this->getUserId(), true);
	}

	/**
	 * get date note was created
	 * @return date (YYYY-MM-DD HH:MM:SS)
	 */
	function getDateCreated() {
		return $this->getData('dateCreated');
	}

	/**
	 * set date note was created
	 * @param $dateCreated date (YYYY-MM-DD HH:MM:SS)
	 */
	function setDateCreated($dateCreated) {
		$this->setData('dateCreated', $dateCreated);
	}

	/**
	 * get date note was modified
	 * @return date (YYYY-MM-DD HH:MM:SS)
	 */
	function getDateModified() {
		return $this->getData('dateModified');
	}

	/**
	 * set date note was modified
	 * @param $dateModified date (YYYY-MM-DD HH:MM:SS)
	 */
	function setDateModified($dateModified) {
		$this->setData('dateModified', $dateModified);
	}

	/**
	 * get note contents
	 * @return string
	 */
	function getContents() {
		return $this->getData('contents');
	}

	/**
	 * set note contents
	 * @param $contents string
	 */
	function setContents($contents) {
		$this->setData('contents', $contents);
	}

	/**
	 * get note title
	 * @return string
	 */
	function getTitle() {
		return $this->getData('title');
	}

	/**
	 * set note title
	 * @param $title string
	 */
	function setTitle($title) {
		$this->setData('title', $title);
	}

	/**
	 * get note type
	 * @return int
	 */
	function getAssocType() {
		return $this->getData('assocType');
	}

	/**
	 * set note type
	 * @param $assocType int
	 */
	function setAssocType($assocType) {
		$this->setData('assocType', $assocType);
	}

	/**
	 * get note assoc id
	 * @return int
	 */
	function getAssocId() {
		return $this->getData('assocId');
	}

	/**
	 * set note assoc id
	 * @param $assocId int
	 */
	function setAssocId($assocId) {
		$this->setData('assocId', $assocId);
	}

	/**
	 * Mark a note viewed.
	 * @param $userId int
	 * @return int RECORD_VIEW_RESULT_...
	 */
	function markViewed($userId) {
		$viewsDao = DAORegistry::getDAO('ViewsDAO');
		return $viewsDao->recordView(ASSOC_TYPE_NOTE, $this->getId(), $userId);
	}
}

?>
