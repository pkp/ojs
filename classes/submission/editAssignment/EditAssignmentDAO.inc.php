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

import('submission.editAssignment.EditAssignment');

class EditAssignmentDAO extends DAO {
	/**
	 * Constructor.
	 */
	function EditAssignmentDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve an edit assignment by id.
	 * @param $editId int
	 * @return EditAssignment
	 */
	function &getEditAssignment($editId) {
		$result = &$this->retrieve(
			'SELECT e.*, u.first_name, u.last_name, u.email, u.initials FROM edit_assignments e LEFT JOIN users u ON (e.editor_id = u.user_id) WHERE e.edit_id = ?',
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
	 * @return EditAssignment
	 */
	function &getEditAssignmentByArticleId($articleId) {
		$result = &$this->retrieve(
			'SELECT e.*, u.first_name, u.last_name, u.email, u.initials FROM edit_assignments e LEFT JOIN users u ON (e.editor_id = u.user_id) WHERE e.article_id = ?',
			$articleId
			);
		
		if ($result->RecordCount() == 0) {
			return null;
		} else {
			return $this->_returnEditAssignmentFromRow($result->GetRowAssoc(false));
		}
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
		$editAssignment->setEditorInitials($row['initials']);
		$editAssignment->setEditorEmail($row['email']);

		return $editAssignment;
	}
	
	/**
	 * Insert a new EditAssignment.
	 * @param $editAssignment EditAssignment
	 */	
	function insertEditAssignment(&$editAssignment) {
		$this->update(
			'INSERT INTO edit_assignments
				(article_id, editor_id)
				VALUES
				(?, ?)',
			array(
				$editAssignment->getArticleId(),
				$editAssignment->getEditorId()
			)
		);
		
		$editAssignment->setEditId($this->getInsertEditId());
		return $editAssignment->getEditId();
	}
	
	/**
	 * Update an existing edit assignment.
	 * @param $editAssignment EditAssignment
	 */
	function updateEditAssignment(&$editAssignment) {
		return $this->update(
			'UPDATE edit_assignments
				SET	article_id = ?,
					editor_id = ?
				WHERE edit_id = ?',
			array(
				$editAssignment->getArticleId(),
				$editAssignment->getEditorId(),
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
	 * Delete edit assignments by article.
	 * @param $articleId int
	 */
	function deleteEditAssignmentsByArticle($articleId) {
		return $this->update(
			'DELETE FROM edit_assignments WHERE article_id = ?',
			$articleId
		);
	}

	/**
	 * Get the ID of the last inserted edit assignment.
	 * @return int
	 */
	function getInsertEditId() {
		return $this->getInsertId('edit_assignments', 'edit_id');
	}
	
	/**
	 * Get the assignment counts and last assigned date for all editors in the given journal.
	 * @return array
	 */
	function getEditorStatistics($journalId) {
		$statistics = Array();

		// Get counts of completed submissions
		$result = &$this->retrieve('SELECT ea.editor_id AS editor_id, COUNT(ea.article_id) AS complete FROM edit_assignments ea, articles a, published_articles pa WHERE ea.article_id=a.article_id AND pa.article_id = a.article_id AND a.journal_id=? GROUP BY ea.editor_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['complete'] = $row['complete'];
			$result->MoveNext();
		}
		$result->Close();

		// Get counts of incomplete submissions
		$result = &$this->retrieve('SELECT ea.editor_id AS editor_id, COUNT(ea.article_id) AS incomplete FROM edit_assignments ea, articles a LEFT JOIN published_articles pa ON (pa.article_id = a.article_id) WHERE pa.article_id IS NULL AND ea.article_id=a.article_id AND a.journal_id=? GROUP BY ea.editor_id', $journalId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['incomplete'] = $row['incomplete'];
			$result->MoveNext();
		}
		$result->Close();

		return $statistics;
	}

}

?>
