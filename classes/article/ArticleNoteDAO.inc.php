<?php

/**
 * ArticleNoteDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article
 *
 * Class for ArticleNote DAO.
 * Operations for retrieving and modifying ArticleNote objects.
 *
 * $Id$
 */
 
 class ArticleNoteDAO extends DAO {
 
 	/**
	 * Constructor.
	 */
	function ArticleNoteDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve Article Note by article id.  Limit provides number of records to retrieve
	 * @param $articleId int
	 * @param $limit int, default NULL
	 * @return ArticleNote objects array
	 */
	function getArticleNotes($articleId, $limit = NULL) {
		$articleNotes = array();
		
		if (isset($limit)) {
			$result = &$this->retrieveLimit(
				'SELECT a.* FROM article_notes a WHERE article_id = ? ORDER BY date_created DESC',	$articleId, $limit
			);
		} else {
			$result = &$this->retrieve(
				'SELECT a.* FROM article_notes a WHERE article_id = ? ORDER BY date_created DESC',	$articleId
			);
		}
				
		while (!$result->EOF) {
			$articleNotes[] = &$this->_returnArticleNoteFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
		
		return $articleNotes;
	}

	/**
	 * Retrieve Article Note by note id
	 * @param $noteId int
	 * @return ArticleNote object
	 */
	function getArticleNoteById($noteId) {
		$result = &$this->retrieve(
			'SELECT a.* FROM article_notes a WHERE note_id = ?', $noteId
		);
		$articleNote = &$this->_returnArticleNoteFromRow($result->GetRowAssoc(false));
		$result->Close();
		return $articleNote;
	}	
	
	/**
	 * creates and returns an article note object from a row
	 * @param $row array
	 * @return ArticleNote object
	 */
	function _returnArticleNoteFromRow($row) {
		$articleNote = &new ArticleNote();
		$articleNote->setNoteId($row['note_id']);
		$articleNote->setArticleId($row['article_id']);
		$articleNote->setUserId($row['user_id']);
		$articleNote->setDateCreated($row['date_created']);
		$articleNote->setDateModified($row['date_modified']);
		$articleNote->setTitle($row['title']);
		$articleNote->setNote($row['note']);
		$articleNote->setFileId($row['file_id']);
		return $articleNote;
	}
	
	/**
	 * inserts a new article note into article_notes table
	 * @param ArticleNote object
	 * @return Article Note Id int
	 */
	function insertArticleNote($articleNote) {
		$this->update(
			'INSERT INTO article_notes
				(article_id, user_id, date_created, date_modified, title, note, file_id)
				VALUES
				(?, ?, ?, ?, ?, ?, ?)',
			array(
				$articleNote->getArticleId(),
				$articleNote->getUserId(),
				str_replace("'",'',$articleNote->getDateCreated()),
				str_replace("'",'',$articleNote->getDateModified()),
				$articleNote->getTitle(),
				$articleNote->getNote(),
				$articleNote->getFileId()
			)
		);

		return $this->getInsertArticleNoteId();		
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
			'UPDATE article_notes
				SET
					user_id = ?,
					date_modified = ?,
					title = ?,
					note = ?,
					file_id = ?
				WHERE note_id = ?',
			array(
				$articleNote->getUserId(),
				str_replace("'",'',$articleNote->getDateModified()),
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
