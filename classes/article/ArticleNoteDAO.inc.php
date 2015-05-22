<?php

/**
 * @file classes/article/ArticleNoteDAO.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleNoteDAO
 * @ingroup article
 * @see ArticleNote
 *
 * @brief Operations for retrieving and modifying ArticleNote objects.
 */

import('classes.article.ArticleNote');
import('classes.note.NoteDAO');

class ArticleNoteDAO extends NoteDAO {
	function ArticleNoteDAO() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated class ArticleNoteDAO; use NoteDAO instead.');
		parent::NoteDAO();
	}

	/**
	 * Retrieve Article Notes by article id.
	 * @param $articleId int
	 * @return DAOResultFactory containing ArticleNotes
	 */
	function &getArticleNotes($articleId, $rangeInfo = NULL) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function');
		$returner =& $this->getByAssoc(ASSOC_TYPE_ARTICLE, $articleId);
		return $returner;
	}

	/**
	 * Retrieve Article Notes by user id.
	 * @param $userId int
	 * @return DAOResultFactory containing ArticleNotes
	 */
	function &getArticleNotesByUserId($userId, $rangeInfo = NULL) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function');
		$returner =& $this->getByUserId($userId, $rangeInfo);
		return $returner;
	}

	/**
	 * Retrieve Article Note by note id
	 * @param $noteId int
	 * @return ArticleNote object
	 */
	function getArticleNoteById($noteId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function');
		$returner =& $this->getById($noteId);
		return $returner;
	}

	/**
	 * inserts a new article note into notes table
	 * @param ArticleNote object
	 * @return Article Note Id int
	 */
	function insertArticleNote(&$articleNote) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function');
		$articleNote->setAssocType(ASSOC_TYPE_ARTICLE);
		return $this->insertObject($articleNote);
	}

	/**
	 * Get the ID of the last inserted article note.
	 * @return int
	 */
	function getInsertArticleNoteId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function');
		return $this->getInsertNoteId();
	}

	/**
	 * removes an article note by id
	 * @param noteId int
	 */
	function deleteArticleNoteById($noteId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function');
		return $this->deleteById($noteId);
	}

	/**
	 * updates an article note
	 * @param ArticleNote object
	 */
	function updateArticleNote($articleNote) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function');
		return $this->updateObject($articleNote);
	}

	/**
	 * get all article note file ids
	 * @param fileIds array
	 */
	function getAllArticleNoteFileIds($articleId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function');
		return $this->getAllFileIds(ASSOC_TYPE_ARTICLE, $articleId);
	}

	/**
	 * clear all article notes
	 * @param fileIds array
	 */
	function clearAllArticleNotes($articleId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function');
		return $this->deleteByAssoc(ASSOC_TYPE_ARTICLE, $articleId);
	}
}

?>
