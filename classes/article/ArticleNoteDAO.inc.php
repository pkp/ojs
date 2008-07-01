<?php

/**
 * @file classes/article/ArticleNoteDAO.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleNoteDAO
 * @ingroup article
 * @see ArticleNote
 *
 * @brief Operations for retrieving and modifying ArticleNote objects.
 */

// $Id$


import('article.ArticleNote');

class ArticleNoteDAO extends DAO {
	/**
	 * Retrieve Article Notes by article id.
	 * @param $articleId int
	 * @return DAOResultFactory containing ArticleNotes
	 */
	function &getArticleNotes($articleId, $rangeInfo = NULL) {
		$sql = 'SELECT n.*, a.file_name, a.original_file_name FROM article_notes n LEFT JOIN article_files a ON (n.file_id = a.file_id) WHERE a.article_id = ? OR (n.file_id = 0 AND n.article_id = ?) ORDER BY n.date_created DESC';

		$result = &$this->retrieveRange($sql, array($articleId, $articleId), $rangeInfo);

		$returner = &new DAOResultFactory($result, $this, '_returnArticleNoteFromRow');
		return $returner;
	}

	/**
	 * Retrieve Article Notes by user id.
	 * @param $userId int
	 * @return DAOResultFactory containing ArticleNotes
	 */
	function &getArticleNotesByUserId($userId, $rangeInfo = NULL) {
		$sql = 'SELECT n.*, a.file_name, a.original_file_name FROM article_notes n LEFT JOIN article_files a ON (n.file_id = a.file_id) WHERE n.user_id = ? ORDER BY n.date_created DESC';

		$result = &$this->retrieveRange($sql, $userId, $rangeInfo);

		$returner = &new DAOResultFactory($result, $this, '_returnArticleNoteFromRow');
		return $returner;
	}

	/**
	 * Retrieve Article Note by note id
	 * @param $noteId int
	 * @return ArticleNote object
	 */
	function getArticleNoteById($noteId) {
		$result = &$this->retrieve(
			'SELECT n.*, a.file_name, a.original_file_name FROM article_notes n LEFT JOIN article_files a ON (n.file_id = a.file_id) WHERE n.note_id = ?', $noteId
		);
		$articleNote = &$this->_returnArticleNoteFromRow($result->GetRowAssoc(false));

		$result->Close();
		unset($result);

		return $articleNote;
	}	

	/**
	 * creates and returns an article note object from a row
	 * @param $row array
	 * @return ArticleNote object
	 */
	function &_returnArticleNoteFromRow($row) {
		$articleNote = &new ArticleNote();
		$articleNote->setNoteId($row['note_id']);
		$articleNote->setArticleId($row['article_id']);
		$articleNote->setUserId($row['user_id']);
		$articleNote->setDateCreated($this->datetimeFromDB($row['date_created']));
		$articleNote->setDateModified($this->datetimeFromDB($row['date_modified']));
		$articleNote->setTitle($row['title']);
		$articleNote->setNote($row['note']);
		$articleNote->setFileId($row['file_id']);

		$articleNote->setFileName($row['file_name']);
		$articleNote->setOriginalFileName($row['original_file_name']);

		HookRegistry::call('ArticleNoteDAO::_returnArticleNoteFromRow', array(&$articleNote, &$row));

		return $articleNote;
	}

	/**
	 * inserts a new article note into article_notes table
	 * @param ArticleNote object
	 * @return Article Note Id int
	 */
	function insertArticleNote(&$articleNote) {
		$this->update(
			sprintf('INSERT INTO article_notes
				(article_id, user_id, date_created, date_modified, title, note, file_id)
				VALUES
				(?, ?, %s, %s, ?, ?, ?)',
				$this->datetimeToDB($articleNote->getDateCreated()), $this->datetimeToDB($articleNote->getDateModified())),
			array(
				$articleNote->getArticleId(),
				$articleNote->getUserId(),
				$articleNote->getTitle(),
				$articleNote->getNote(),
				$articleNote->getFileId()
			)
		);

		$articleNote->setNoteId($this->getInsertArticleNoteId());
		return $articleNote->getNoteId();
	}

	/**
	 * Get the ID of the last inserted article note.
	 * @return int
	 */
	function getInsertArticleNoteId() {
		return $this->getInsertId('article_notes', 'note_id');
	}	

	/**
	 * removes an article note by id
	 * @param noteId int
	 */
	function deleteArticleNoteById($noteId) {
		$this->update(
			'DELETE FROM article_notes WHERE note_id = ?', $noteId
		);
	}

	/**
	 * updates an article note
	 * @param ArticleNote object
	 */
	function updateArticleNote($articleNote) {
		$this->update(
			sprintf('UPDATE article_notes
				SET
					user_id = ?,
					date_modified = %s,
					title = ?,
					note = ?,
					file_id = ?
				WHERE note_id = ?',
				$this->datetimeToDB($articleNote->getDateModified())),
			array(
				$articleNote->getUserId(),
				$articleNote->getTitle(),
				$articleNote->getNote(),
				$articleNote->getFileId(),
				$articleNote->getNoteId()
			)
		);
	}

	/**
	 * get all article note file ids
	 * @param fileIds array
	 */
	function getAllArticleNoteFileIds($articleId) {
		$fileIds = array();

		$result = &$this->retrieve(
			'SELECT a.file_id FROM article_notes a WHERE article_id = ? AND file_id > ?', array($articleId, 0)
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$fileIds[] = $row['file_id'];
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $fileIds;
	}	

	/**
	 * clear all article notes
	 * @param fileIds array
	 */
	function clearAllArticleNotes($articleId) {
		$result = &$this->retrieve(
			'DELETE FROM article_notes WHERE article_id = ?', $articleId
		);

		$result->Close();
	}
}

?>
