<?php

/**
 * CommentDAO.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article
 *
 * Class for Comment DAO.
 * Operations for retrieving and modifying Comment objects.
 *
 * $Id$
 */

import('comment.Comment');

define ('ARTICLE_COMMENT_RECURSE_ALL', -1);

// Comment system configuration constants
define ('COMMENTS_DISABLED', 0); // All comments disabled
define ('COMMENTS_AUTHENTICATED', 1); // Can be posted by authenticated users
define ('COMMENTS_ANONYMOUS', 2); // Can be posted anonymously by authenticated users
define ('COMMENTS_UNAUTHENTICATED', 3); // Can be posted anonymously by anyone

class CommentDAO extends DAO {
 
 	/**
	 * Constructor.
	 */
	function CommentDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve Comments by article id
	 * @param $articleId int
	 * @return Comment objects array
	 */
	function &getRootCommentsByArticleId($articleId, $childLevels = 0) {
		$comments = array();
		
		$result = &$this->retrieve('SELECT * FROM comments WHERE article_id = ? AND parent_comment_id IS NULL ORDER BY date_posted', $articleId);
		
		while (!$result->EOF) {
			$comments[] = &$this->_returnCommentFromRow($result->GetRowAssoc(false), $childLevels);
			$result->moveNext();
		}

		$result->Close();
		unset($result);
		
		return $comments;
	}
	
	/**
	 * Retrieve Comments by parent comment id
	 * @param $parentId int
	 * @return Comment objects array
	 */
	function &getCommentsByParentId($parentId, $childLevels = 0) {
		$comments = array();
		
		$result = &$this->retrieve('SELECT * FROM comments WHERE parent_comment_id = ? ORDER BY date_posted', $parentId);
		
		while (!$result->EOF) {
			$comments[] = &$this->_returnCommentFromRow($result->GetRowAssoc(false), $childLevels);
			$result->moveNext();
		}

		$result->Close();
		unset($result);
		
		return $comments;
	}
	
	/**
	 * Retrieve comments by user id
	 * @param $userId int
	 * @return Comment objects array
	 */
	function &getCommentsByUserId($userId) {
		$comments = array();
		
		$result = &$this->retrieve('SELECT * FROM comments WHERE user_id = ?', $userId);
		
		while (!$result->EOF) {
			$comments[] = &$this->_returnCommentFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);
		
		return $comments;
	}
	
	/**
	 * Retrieve Comment by comment id
	 * @param $commentId int
	 * @return Comment object
	 */
	function &getComment($commentId, $articleId, $childLevels = 0) {
		$result = &$this->retrieve(
			'SELECT * FROM comments WHERE comment_id = ? and article_id = ?', array($commentId, $articleId)
		);

		$comment = null;
		if ($result->RecordCount() != 0) {
			$comment = &$this->_returnCommentFromRow($result->GetRowAssoc(false), $childLevels);
		}

		$result->Close();
		unset($result);
		
		return $comment;
	}	
	
	/**
	 * Creates and returns an article comment object from a row
	 * @param $row array
	 * @return Comment object
	 */
	function &_returnCommentFromRow($row, $childLevels = 0) {
		$userDao = &DAORegistry::getDAO('UserDAO');

		$comment = &new Comment();
		$comment->setCommentId($row['comment_id']);
		$comment->setArticleId($row['article_id']);
		$comment->setUser($userDao->getUser($row['user_id']), true);
		$comment->setPosterIP($row['poster_ip']);
		$comment->setPosterName($row['poster_name']);
		$comment->setPosterEmail($row['poster_email']);
		$comment->setTitle($row['title']);
		$comment->setBody($row['body']);
		$comment->setDatePosted($this->datetimeFromDB($row['date_posted']));
		$comment->setDateModified($this->datetimeFromDB($row['date_modified']));
		$comment->setParentCommentId($row['parent_comment_id']);
		$comment->setChildCommentCount($row['num_children']);

		if (!HookRegistry::call('CommentDAO::_returnCommentFromRow', array(&$comment, &$row, &$childLevels))) {
			if ($childLevels>0) $comment->setChildren($this->getCommentsByParentId($row['comment_id'], $childLevels-1));
			else if ($childLevels==ARTICLE_COMMENT_RECURSE_ALL) $comment->setChildren($this->getCommentsByParentId($row['comment_id'], ARTICLE_COMMENT_RECURSE_ALL));
		}

		return $comment;
	}
	
	/**
	 * inserts a new article comment into article_comments table
	 * @param Comment object
	 * @return int ID of new comment
	 */
	function insertComment(&$comment) {
		$comment->setDatePosted(Core::getCurrentDate());
		$comment->setDateModified($comment->getDatePosted());
		$user = $comment->getUser();
		$this->update(
			sprintf('INSERT INTO comments
				(article_id, num_children, parent_comment_id, user_id, poster_ip, date_posted, date_modified, title, body, poster_name, poster_email)
				VALUES
				(?, ?, ?, ?, ?, %s, %s, ?, ?, ?, ?)',
				$this->datetimeToDB($comment->getDatePosted()), $this->datetimeToDB($comment->getDateModified())),
			array(
				$comment->getArticleId(),
				$comment->getChildCommentCount(),
				$comment->getParentCommentId(),
				(isset($user)?$user->getUserId():null),
				$comment->getPosterIP(),
				$comment->getTitle(),
				$comment->getBody(),
				$comment->getPosterName(),
				$comment->getPosterEmail()
			)
		);

		$comment->setCommentId($this->getInsertCommentId());

		if ($comment->getParentCommentId()) $this->incrementChildCount($comment->getParentCommentId());

		return $comment->getCommentId();
	}
		
	/**
	 * Get the ID of the last inserted article comment.
	 * @return int
	 */
	function getInsertCommentId() {
		return $this->getInsertId('comments', 'comment_id');
	}	

	/**
	 * Increase the current count of child comments for the specified comment.
	 * @param commentId int
	 */
	function incrementChildCount($commentId) {
		$this->update('UPDATE comments SET num_children=num_children+1 WHERE comment_id = ?', $commentId);
	}

	/**
	 * Decrease the current count of child comments for the specified comment.
	 * @param commentId int
	 */
	function decrementChildCount($commentId) {
		$this->update('UPDATE comments SET num_children=num_children-1 WHERE comment_id = ?', $commentId);
	}

	/**
	 * removes an article comment from article_comments table
	 * @param Comment object
	 */
	function deleteComment(&$comment, $isRecursing = false) {
		$result = $this->update('DELETE FROM comments WHERE comment_id = ?', $comment->getCommentId());
		if (!$isRecursing) $this->decrementChildCount($comment->getParentCommentId());
		foreach ($comment->getChildren() as $child) {
			$this->deleteComment($child, true);
		}
	}

	/**
	 * removes article comments by article ID
	 * @param Comment object
	 */
	function deleteCommentsByArticle($articleId) {
		return $this->update('DELETE FROM comments WHERE article_id = ?', $articleId);
	}

	/**
	 * updates a comment
	 * @param Comment object
	 */
	function updateComment(&$comment) {
		$comment->setDateModified(Core::getCurrentDate());
		$user = $comment->getUser();
		$this->update(
			sprintf('UPDATE article_comments
				SET
					article_id = ?,
					num_children = ?,
					parent_comment_id = ?,
					user_id = ?,
					poster_ip = ?,
					date_posted = %s,
					date_modified = %s,
					title = ?,
					body = ?,
					poster_name = ?,
					poster_email = ?
				WHERE comment_id = ?',
				$this->datetimeToDB($comment->getDatePosted()), $this->datetimeToDB($comment->getDateModified())),
			array(
				$comment->getArticleId(),
				$comment->getChildCommentCount(),
				$comment->getParentCommentId(),
				(isset($user)?$user->getUserId():null),
				$comment->getPosterIP(),
				$comment->getTitle(),
				$comment->getBody(),
				$comment->getPosterName(),
				$comment->getPosterEmail(),
				$comment->getCommentId()
			)
		);
	}
 }
  
?>
