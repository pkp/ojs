<?php

/**
 * EditAssignmentsDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * Class for DAO relating editors to articles.
 *
 * $Id$
 */

class EditAssignmentDAO extends DAO {

	var $userDao;

	/**
	 * Constructor.
	 */
	function ReviewAssignmentDAO() {
		parent::DAO();
		$this->userDao = DAORegistry::getDAO('UserDAO');
	}
	
	/**
	 * Retrieve an edit assignment by id.
	 * @param $editId int
	 * @return ReviewAssignment
	 */
	function &getEditAssignment($editId) {
		$result = &$this->retrieve(
			'SELECT e.*, u.first_name, u.last_name, u.email FROM edit_assignments e LEFT JOIN users u ON (e.editor_id = u.user_id) WHERE e.edit_id = ?',
			$editId
			);
		
		if ($result->RecordCount() == 0) {
			return null;
		} else {
			return $this->_returnEditAssignmentFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Retrieve an edit assignment by article id.
	 * @param $articleId int
	 * @return ReviewAssignment
	 */
	function &getEditAssignmentByArticleId($articleId) {
		$result = &$this->retrieve(
			'SELECT e.*, u.first_name, u.last_name, u.email FROM edit_assignments e LEFT JOIN users u ON (e.editor_id = u.user_id) WHERE e.article_id = ? AND replaced = 0',
			$articleId
			);
		
		if ($result->RecordCount() == 0) {
			return null;
		} else {
			return $this->_returnEditAssignmentFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Get all edit assignments for an article.
	 * @param $articleId int
	 * @param $replaced boolean
	 * @return array ReviewAssignments
	 */
	function &getReplacedEditAssignmentsByArticleId($articleId) {
		$editAssignments = array();
		
		$result = &$this->retrieve(
			'SELECT e.*, u.first_name, u.last_name, u.email FROM edit_assignments e LEFT JOIN users u ON (e.editor_id = u.user_id) WHERE e.article_id = ? AND replaced = 1',
			$articleId
		);
		
		while (!$result->EOF) {
			$editAssignments[] = $this->_returnEditAssignmentFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		
		return $editAssignments;
	}

	/**
	 * Internal function to return an edit assignment object from a row.
	 * @param $row array
	 * @return EditAssignment
	 */
	function &_returnEditAssignmentFromRow(&$row) {
		$editAssignment = &new EditAssignment();
		$editAssignment->setEditId($row['edit_id']);
		$editAssignment->setArticleId($row['article_id']);
		$editAssignment->setEditorId($row['editor_id']);
		$editAssignment->setEditorFullName($row['first_name'].' '.$row['last_name']);
		$editAssignment->setEditorFirstName($row['first_name']);
		$editAssignment->setEditorLastName($row['last_name']);
		$editAssignment->setEditorEmail($row['email']);
		$editAssignment->setComments($row['comments']);
		$editAssignment->setDateNotified($row['date_notified']);
		$editAssignment->setDateCompleted($row['date_completed']);
		$editAssignment->setReplaced($row['replaced']);

		return $editAssignment;
	}
	
	/**
	 * Insert a new EditAssignment.
	 * @param $editAssignment EditAssignment
	 */	
	function insertEditAssignment(&$editAssignment) {
		$this->update(
			'INSERT INTO edit_assignments
				(article_id, editor_id, comments, replaced, date_notified, date_completed, date_acknowledged)
				VALUES
				(?, ?, ?, ?, ?, ?, ?)',
			array(
				$editAssignment->getArticleId(),
				$editAssignment->getEditorId(),
				$editAssignment->getComments(),
				$editAssignment->getReplaced() === null ? 0 : $editAssignment->getReplaced(),
				$editAssignment->getDateNotified(),
				$editAssignment->getDateCompleted(),
				$editAssignment->getDateAcknowledged()
			)
		);
		
		$editAssignment->setEditId($this->getInsertEditId());
	}
	
	/**
	 * Update an existing edit assignment.
	 * @param $editAssignment EditAssignment
	 */
	function updateEditAssignment(&$editAssignment) {
		return $this->update(
			'UPDATE edit_assignments
				SET	article_id = ?,
					editor_id = ?,
					comments = ?,
					replaced = ?,
					date_notified = ?,
					date_completed = ?,
					date_acknowledged = ?
				WHERE edit_id = ?',
			array(
				$editAssignment->getArticleId(),
				$editAssignment->getEditorId(),
				$editAssignment->getComments(),
				$editAssignment->getReplaced(),
				$editAssignment->getDateNotified(),
				$editAssignment->getDateCompleted(),
				$editAssignment->getDateAcknowledged(),
				$editAssignment->getEditId()
			)
		);
	}
	
	/**
	 * Delete edit assignment.
	 * @param $reviewId int
	 */
	function deleteEditAssignmentById($editId) {
		return $this->update(
			'DELETE FROM edit_assignments WHERE edit_id = ?',
			$editId
		);
	}
	
	/**
	 * Get the ID of the last inserted edit assignment.
	 * @return int
	 */
	function getInsertEditId() {
		return $this->getInsertId('edit_assignments', 'edit_id');
	}
	
}

?>
