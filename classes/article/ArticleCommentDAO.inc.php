<?php

/**
 * ArticleCommentDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article
 *
 * Class for ArticleComment DAO.
 * Operations for retrieving and modifying ArticleComment objects.
 *
 * $Id$
 */
 
class ArticleCommentDAO extends DAO {
 
 	/**
	 * Constructor.
	 */
	function ArticleCommentDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve ArticleComments by article id
	 * @param $articleId int
	 * @param $commentType int
	 * @return ArticleComment objects array
	 */
	function getArticleComments($articleId, $commentType = null, $assocId = null) {
		$articleComments = array();
		
		if ($commentType == null) {
			$result = &$this->retrieve(
				'SELECT a.* FROM article_comments a WHERE article_id = ? ORDER BY date_posted',	$articleId
			);
		} else {
			if ($assocId == null) {
				$result = &$this->retrieve(
					'SELECT a.* FROM article_comments a WHERE article_id = ? AND comment_type = ? ORDER BY date_posted',	array($articleId, $commentType)
				);
			} else {
				$result = &$this->retrieve(
					'SELECT a.* FROM article_comments a WHERE article_id = ? AND comment_type = ? AND assoc_id = ? ORDER BY date_posted',
					array($articleId, $commentType, $assocId)
				);
			}				
		}
		
		while (!$result->EOF) {
			$articleComments[] = &$this->_returnArticleCommentFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
		
		return $articleComments;
	}
	
	/**
	 * Retrieve most recent ArticleComment
	 * @param $articleId int
	 * @param $commentType int
	 * @return ArticleComment
	 */
	function getMostRecentArticleComment($articleId, $commentType = null, $assocId = null) {
		if ($commentType == null) {
			$result = &$this->retrieveLimit(
				'SELECT a.* FROM article_comments a WHERE article_id = ? ORDER BY date_posted DESC',
				$articleId,
				1
			);
		} else {
			if ($assocId == null) {
				$result = &$this->retrieveLimit(
					'SELECT a.* FROM article_comments a WHERE article_id = ? AND comment_type = ? ORDER BY date_posted DESC',
					array($articleId, $commentType),
					1
				);
			} else {
				$result = &$this->retrieveLimit(
					'SELECT a.* FROM article_comments a WHERE article_id = ? AND comment_type = ? AND assoc_id = ? ORDER BY date_posted DESC',
					array($articleId, $commentType, $assocId),
					1
				);
			}				
		}
		
		if (!isset($result) || $result->RecordCount() == 0) {
			return null;
		} else {
			return $this->_returnArticleCommentFromRow($result->GetRowAssoc(false));
		}
	}

	/**
	 * Retrieve Article Comment by comment id
	 * @param $commentId int
	 * @return ArticleComment object
	 */
	function getArticleCommentById($commentId) {
		$result = &$this->retrieve(
			'SELECT a.* FROM article_comments a WHERE comment_id = ?', $commentId
		);
		
		$articleComment = &$this->_returnArticleCommentFromRow($result->GetRowAssoc(false));
		$result->Close();
		
		return $articleComment;
	}	
	
	/**
	 * Creates and returns an article comment object from a row
	 * @param $row array
	 * @return ArticleComment object
	 */
	function _returnArticleCommentFromRow($row) {
		$articleComment = &new ArticleComment();
		$articleComment->setCommentId($row['comment_id']);
		$articleComment->setCommentType($row['comment_type']);
		$articleComment->setRoleId($row['role_id']);
		$articleComment->setArticleId($row['article_id']);
		$articleComment->setAssocId($row['assoc_id']);
		$articleComment->setAuthorId($row['author_id']);
		$articleComment->setCommentTitle($row['comment_title']);
		$articleComment->setComments($row['comments']);
		$articleComment->setDatePosted($row['date_posted']);
		$articleComment->setViewable($row['viewable']);
		
		return $articleComment;
	}
	
	/**
	 * inserts a new article comment into article_comments table
	 * @param ArticleNote object
	 * @return Article Note Id int
	 */
	function insertArticleComment($articleComment) {
		$this->update(
			'INSERT INTO article_comments
				(comment_type, role_id, article_id, assoc_id, author_id, date_posted, comment_title, comments, viewable)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$articleComment->getCommentType(),
				$articleComment->getRoleId(),
				$articleComment->getArticleId(),
				$articleComment->getAssocId(),
				$articleComment->getAuthorId(),
				$articleComment->getDatePosted(),
				$articleComment->getCommentTitle(),
				$articleComment->getComments(),
				$articleComment->getViewable() === null ? 0 : $articleComment->getViewable()
			)
		);

		return $this->getInsertArticleCommentId();		
	}
		
	/**
	 * Get the ID of the last inserted article comment.
	 * @return int
	 */
	function getInsertArticleCommentId() {
		return $this->getInsertId('article_comments', 'comment_id');
	}	

	/**
	 * removes an article comment from article_comments table
	 * @param ArticleComment object
	 */
	function deleteArticleComment($articleComment) {
		$this->deleteArticleCommentById($articleComment->getCommentId());
	}

	/**
	 * removes an article note by id
	 * @param noteId int
	 */
	function deleteArticleCommentById($commentId) {
		$this->update(
			'DELETE FROM article_comments WHERE comment_id = ?', $commentId
		);
	}
	
	/**
	 * updates an article comment
	 * @param ArticleComment object
	 */
	function updateArticleComment($articleComment) {
		$this->update(
			'UPDATE article_comments
				SET
					comment_type = ?,
					role_id = ?,
					article_id = ?,
					assoc_id = ?,
					author_id = ?,
					date_posted = ?,
					comment_title = ?,
					comments = ?,
					viewable = ?
				WHERE comment_id = ?',
			array(
				$articleComment->getCommentType(),
				$articleComment->getRoleId(),
				$articleComment->getArticleId(),
				$articleComment->getAssocId(),
				$articleComment->getAuthorId(),
				$articleComment->getDatePosted(),
				$articleComment->getCommentTitle(),
				$articleComment->getComments(),
				$articleComment->getViewable() === null ? 1 : $articleComment->getViewable(),
				$articleComment->getCommentId()
			)
		);
	}
 }
  
?>
