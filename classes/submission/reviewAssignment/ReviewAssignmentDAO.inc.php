<?php

/**
 * ReviewAssignmentsDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * Class for DAO relating reviewers to articles.
 *
 * $Id$
 */

class ReviewAssignmentDAO extends DAO {

	var $userDao;

	/**
	 * Constructor.
	 */
	function ReviewAssignmentDAO() {
		parent::DAO();
		$this->userDao = DAORegistry::getDAO('UserDAO');
	}
	
	/**
	 * Retrieve a review assignment by reviewer and article.
	 * @param $articleId int
	 * @param $reviewerId int
	 * @return ReviewAssignment
	 */
	function &getReviewAssignment($articleId, $reviewerId) {
		$result = &$this->retrieve(
			'SELECT r.*, u.first_name, u.last_name FROM review_assignments r LEFT JOIN users u ON (r.reviewer_id = u.user_id) WHERE r.article_id = ? AND r.reviewer_id = ?',
			array($articleId, $reviewerId)
			);
		
		if ($result->RecordCount() == 0) {
			return null;
		} else {
			return $this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
		}
	}
	

	/**
	 * Get all review assignments for an article.
	 * @param $articleId int
	 * @return array ReviewAssignments
	 */
	function &getReviewAssignmentsByArticleId($articleId) {
		$reviewAssignments = array();
		
		$result = &$this->retrieve(
			'SELECT r.*, u.first_name, u.last_name FROM review_assignments r LEFT JOIN users u ON (r.reviewer_id = u.user_id) WHERE r.article_id = ?',
			$articleId
		);
		
		while (!$result->EOF) {
			$reviewAssignments[] = $this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		
		return $reviewAssignments;
	}

	/**
	 * Internal function to return a review assignment object from a row.
	 * @param $row array
	 * @return ReviewAssignment
	 */
	function &_returnReviewAssignmentFromRow(&$row) {
		$reviewAssignment = &new ReviewAssignment();
		$reviewAssignment->setReviewId($row['review_id']);
		$reviewAssignment->setArticleId($row['article_id']);
		$reviewAssignment->setReviewerId($row['reviewer_id']);
		$reviewAssignment->setReviewerFullName($row['first_name'].' '.$row['last_name']);
		$reviewAssignment->setComments($row['comments']);
		$reviewAssignment->setRecommendation($row['recommendation']);
		$reviewAssignment->setDateAssigned($row['date_assigned']);
		$reviewAssignment->setDateNotified($row['date_notified']);
		$reviewAssignment->setDateConfirmed($row['date_confirmed']);
		$reviewAssignment->setDateCompleted($row['date_completed']);
		$reviewAssignment->setDateAcknowledged($row['date_acknowledged']);
		$reviewAssignment->setDateDue($row['date_due']);
		$reviewAssignment->setDeclined($row['declined']);
		$reviewAssignment->setReplaced($row['replaced']);
	
		return $reviewAssignment;
	}
	
	/**
	 * Insert a new Review Assignment.
	 * @param $reviewAssignment ReviewAssignment
	 */	
	function insertReviewAssignment(&$reviewAssignment) {
		$this->update(
			'INSERT INTO review_assignments
				(article_id, reviewer_id, comments, recommendation, declined, replaced, date_assigned, date_notified, date_confirmed, date_completed, date_acknowledged, date_due)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$reviewAssignment->getArticleId(),
				$reviewAssignment->getReviewerId(),
				$reviewAssignment->getComments(),
				$reviewAssignment->getRecommendation(),
				$reviewAssignment->getDeclined(),
				$reviewAssignment->getReplaced(),
				$reviewAssignment->getDateAssigned(),
				$reviewAssignment->getDateNotified(),
				$reviewAssignment->getDateConfirmed(),
				$reviewAssignment->getDateCompleted(),
				$reviewAssignment->getDateAcknowledged(),
				$reviewAssignment->getDateDue()
			)
		);
		
		$reviewAssignment->setReviewId($this->getInsertReviewId());
	}
	
	/**
	 * Update an existing email template.
	 * @param $emailTemplate EmailTemplate
	 */
	function updateReviewAssignment(&$reviewAssignment) {
		return $this->update(
			'UPDATE review_assignments
				SET	article_id = ?,
					reviewer_id = ?,
					comments = ?,
					recommendation = ?,
					declined = ?,
					replaced = ?,
					date_assigned = ?,
					date_notified = ?,
					date_confirmed = ?,
					date_completed = ?,
					date_acknowledged = ?,
					date_due = ?
				WHERE review_id = ?',
			array(
				$reviewAssignment->getArticleId(),
				$reviewAssignment->getReviewerId(),
				$reviewAssignment->getComments(),
				$reviewAssignment->getRecommendation(),
				$reviewAssignment->getDeclined(),
				$reviewAssignment->getReplaced(),
				$reviewAssignment->getDateAssigned(),
				$reviewAssignment->getDateNotified(),
				$reviewAssignment->getDateConfirmed(),
				$reviewAssignment->getDateCompleted(),
				$reviewAssignment->getDateAcknowledged(),
				$reviewAssignment->getDateDue(),
				$reviewAssignment->getReviewId()
			)
		);
	}
	
	/**
	 * Delete review assignment.
	 * @param $reviewId int
	 */
	function deleteReviewAssignmentById($reviewId) {
		return $this->update(
			'DELETE FROM review_assignments WHERE review_id = ?',
			$reviewId
		);
	}
	
	/**
	 * Retrieve a list of articles assigned to the specified reviewer.
	 * @param $reviewerId int
	 * @return array matching Articles
	 */
	function &getArticlesByReviewerId($journalId, $reviewerId) {
		$articles = array();
		
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
				
		$result = &$this->retrieve(
			'SELECT a.* FROM articles AS a, review_assignments AS r WHERE a.article_id = r.article_id AND a.journal_id = ? AND r.reviewer_id = ?',
			array($journalId, $reviewerId)
		);
		
		while (!$result->EOF) {
			$articles[] = &$articleDao->_returnArticleFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $articles;
	}
	
	/**
	 * Retrieve a list of all reviewers assigned to the specified article.
	 * @param $articleId int
	 * @return array matching Users
	 */
	function &getReviewersByArticleId($journalId, $articleId) {
		$users = array();
		
		$userDao = &DAORegistry::getDAO('UserDAO');
				
		$result = &$this->retrieve(
			'SELECT u.* FROM users AS u, review_assignments AS r WHERE u.user_id = r.reviewer_id AND r.article_id = ? ORDER BY last_name, first_name',
			array($reviewerId)
		);
		
		while (!$result->EOF) {
			$users[] = &$userDao->_returnUserFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $users;
	}
	
	/**
	 * Retrieve a list of all reviewers not assigned to the specified article.
	 * @param $journalId int
	 * @param $articleId int
	 * @return array matching Users
	 */
	function &getReviewersNotAssignedToArticle($journalId, $articleId) {
		$users = array();
		
		$userDao = &DAORegistry::getDAO('UserDAO');
				
		$result = &$this->retrieve(
			'SELECT u.* FROM users AS u, roles r LEFT JOIN review_assignments AS a on a.reviewer_id = u.user_id AND a.journal_id = r.journal_id AND a.article_id = ? WHERE u.user_id = r.user_id AND r.journal_id = ? AND r.role_id = ? AND a.article_id IS NULL ORDER BY last_name, first_name',
			array($articleId, $journalId, RoleDAO::getRoleIdFromPath('reviewer'))
		);
		
		while (!$result->EOF) {
			$users[] = &$userDao->_returnUserFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $users;
	}
	
	/**
	 * Delete all review assignments for a specified article in a journal.
	 * @param $articleId int
	 */
	function deleteReviewAssignmentsByArticleId($articleId) {
		return $this->update(
			'DELETE FROM review_assignments WHERE article_id = ?',
			$articleId
		);
	}
	
	/**
	 * Delete all review assignments for the specified reviewer.
	 * @param $reviewerId int
	 */
	function deleteAssignmentsByReviewerId($reviewerId) {
		return $this->update(
			'DELETE FROM review_assignments WHERE reviewer_id = ?',
			$reviewerId
		);
	}
	
	/**
	 * Check if a reviewer is assigned to a specified article.
	 * @param $articleId int
	 * @param $reviewerId int
	 * @return boolean
	 */
	function reviewerExists($articleId, $reviewerId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM review_assignments WHERE article_id = ? AND reviewer_id = ?', array($articleId, $reviewerId)
		);
		return isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;
	}
	
	/**
	 * Get the ID of the last inserted review assignment.
	 * @return int
	 */
	function getInsertReviewId() {
		return $this->getInsertId('review_assignments', 'review_id');
	}
	
}

?>
