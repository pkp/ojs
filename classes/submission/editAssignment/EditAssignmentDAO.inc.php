<?php

/**
 * @file classes/submission/editAssignment/EditAssignmentDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditAssignmentDAO
 * @ingroup submission
 * @see EditAssignment
 *
 * @brief Class for DAO relating editors to articles.
 */

import('classes.submission.editAssignment.EditAssignment');

class EditAssignmentDAO extends DAO {
	/**
	 * Retrieve an edit assignment by id.
	 * @param $editId int
	 * @return EditAssignment
	 */
	function &getEditAssignment($editId) {
		$result =& $this->retrieve(
			'SELECT e.*, u.first_name, u.last_name, u.email, u.initials, r.role_id AS editor_role_id FROM articles a LEFT JOIN edit_assignments e ON (a.article_id = e.article_id) LEFT JOIN users u ON (e.editor_id = u.user_id) LEFT JOIN roles r ON (r.user_id = e.editor_id AND r.role_id = ' . ROLE_ID_EDITOR . ' AND r.journal_id = a.journal_id) WHERE e.edit_id = ? AND a.article_id = e.article_id',
			$editId
			);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnEditAssignmentFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve edit assignments by article id.
	 * @param $articleId int
	 * @return EditAssignment
	 */
	function &getEditAssignmentsByArticleId($articleId) {
		$result =& $this->retrieve(
			'SELECT e.*, u.first_name, u.last_name, u.email, u.initials, r.role_id AS editor_role_id FROM articles a LEFT JOIN edit_assignments e ON (a.article_id = e.article_id) LEFT JOIN users u ON (e.editor_id = u.user_id) LEFT JOIN roles r ON (r.user_id = e.editor_id AND r.role_id = ' . ROLE_ID_EDITOR . ' AND r.journal_id = a.journal_id) WHERE e.article_id = ? AND a.article_id = e.article_id ORDER BY e.date_notified ASC',
			$articleId
			);

		$returner = new DAOResultFactory($result, $this, '_returnEditAssignmentFromRow');
		return $returner;
	}

	/**
	 * Retrieve those edit assignments that relate to full editors.
	 * @param $articleId int
	 * @return EditAssignment
	 */
	function &getEditorAssignmentsByArticleId($articleId) {
		$result =& $this->retrieve(
			'SELECT e.*, u.first_name, u.last_name, u.email, u.initials, r.role_id AS editor_role_id FROM articles a, edit_assignments e, users u, roles r WHERE r.user_id = e.editor_id AND r.role_id = ' . ROLE_ID_EDITOR . ' AND e.article_id = ? AND r.journal_id = a.journal_id AND a.article_id = e.article_id AND e.editor_id = u.user_id ORDER BY e.date_notified ASC',
			$articleId
			);

		$returner = new DAOResultFactory($result, $this, '_returnEditAssignmentFromRow');
		return $returner;
	}

	/**
	 * Retrieve those edit assignments that relate to section editors with
	 * review access.
	 * @param $articleId int
	 * @return EditAssignment
	 */
	function &getReviewingSectionEditorAssignmentsByArticleId($articleId) {
		$result =& $this->retrieve(
			'SELECT e.*, u.first_name, u.last_name, u.email, u.initials, r.role_id AS editor_role_id FROM articles a LEFT JOIN edit_assignments e ON (a.article_id = e.article_id) LEFT JOIN users u ON (e.editor_id = u.user_id) LEFT JOIN roles r ON (r.user_id = e.editor_id AND r.role_id = ' . ROLE_ID_EDITOR . ' AND r.journal_id = a.journal_id) WHERE e.article_id = ? AND a.article_id = e.article_id AND r.role_id IS NULL AND e.can_review = 1 ORDER BY e.date_notified ASC',
			$articleId
		);

		$returner = new DAOResultFactory($result, $this, '_returnEditAssignmentFromRow');
		return $returner;
	}

	/**
	 * Retrieve those edit assignments that relate to section editors with
	 * editing access.
	 * @param $articleId int
	 * @return EditAssignment
	 */
	function &getEditingSectionEditorAssignmentsByArticleId($articleId) {
		$result =& $this->retrieve(
			'SELECT e.*, u.first_name, u.last_name, u.email, u.initials, r.role_id AS editor_role_id FROM articles a LEFT JOIN edit_assignments e ON (a.article_id = e.article_id) LEFT JOIN users u ON (e.editor_id = u.user_id) LEFT JOIN roles r ON (r.user_id = e.editor_id AND r.role_id = ' . ROLE_ID_EDITOR . ' AND r.journal_id = a.journal_id) WHERE e.article_id = ? AND a.article_id = e.article_id AND r.role_id IS NULL AND e.can_edit = 1 ORDER BY e.date_notified ASC',
			$articleId
			);

		$returner = new DAOResultFactory($result, $this, '_returnEditAssignmentFromRow');
		return $returner;
	}

	/**
	 * Retrieve edit assignments by user id.
	 * @param $articleId int
	 * @return EditAssignment
	 */
	function &getEditAssignmentsByUserId($userId) {
		$result =& $this->retrieve(
			'SELECT e.*, u.first_name, u.last_name, u.email, u.initials, r.role_id AS editor_role_id FROM articles a LEFT JOIN edit_assignments e ON (a.article_id = e.article_id) LEFT JOIN users u ON (e.editor_id = u.user_id) LEFT JOIN roles r ON (r.user_id = e.editor_id AND r.role_id = ' . ROLE_ID_EDITOR . ' AND r.journal_id = a.journal_id) WHERE e.editor_id = ? AND a.article_id = e.article_id ORDER BY e.date_notified ASC',
			$userId
			);

		$returner = new DAOResultFactory($result, $this, '_returnEditAssignmentFromRow');
		return $returner;
	}

	/**
	 * Internal function to return an edit assignment object from a row.
	 * @param $row array
	 * @return EditAssignment
	 */
	function &_returnEditAssignmentFromRow(&$row) {
		$editAssignment = new EditAssignment();
		$editAssignment->setEditId($row['edit_id']);
		$editAssignment->setArticleId($row['article_id']);
		$editAssignment->setEditorId($row['editor_id']);
		$editAssignment->setCanReview($row['can_review']);
		$editAssignment->setCanEdit($row['can_edit']);
		$editAssignment->setEditorFullName($row['first_name'].' '.$row['last_name']);
		$editAssignment->setEditorFirstName($row['first_name']);
		$editAssignment->setEditorLastName($row['last_name']);
		$editAssignment->setEditorInitials($row['initials']);
		$editAssignment->setEditorEmail($row['email']);
		$editAssignment->setIsEditor($row['editor_role_id']==ROLE_ID_EDITOR?1:0);
		$editAssignment->setDateUnderway($this->datetimeFromDB($row['date_underway']));
		$editAssignment->setDateNotified($this->datetimeFromDB($row['date_notified']));

		HookRegistry::call('EditAssignmentDAO::_returnEditAssignmentFromRow', array(&$editAssignment, &$row));

		return $editAssignment;
	}

	/**
	 * Insert a new EditAssignment.
	 * @param $editAssignment EditAssignment
	 */	
	function insertEditAssignment(&$editAssignment) {
		$this->update(
			sprintf('INSERT INTO edit_assignments
				(article_id, editor_id, can_edit, can_review, date_notified, date_underway)
				VALUES
				(?, ?, ?, ?, %s, %s)',
				$this->datetimeToDB($editAssignment->getDateNotified()),
				$this->datetimeToDB($editAssignment->getDateUnderway())),
			array(
				$editAssignment->getArticleId(),
				$editAssignment->getEditorId(),
				$editAssignment->getCanEdit()?1:0,
				$editAssignment->getCanReview()?1:0
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
			sprintf('UPDATE edit_assignments
				SET	article_id = ?,
					editor_id = ?,
					can_review = ?,
					can_edit = ?,
					date_notified = %s,
					date_underway = %s
				WHERE edit_id = ?',
				$this->datetimeToDB($editAssignment->getDateNotified()),
				$this->datetimeToDB($editAssignment->getDateUnderway())),
			array(
				$editAssignment->getArticleId(),
				$editAssignment->getEditorId(),
				$editAssignment->getCanReview() ? 1:0,
				$editAssignment->getCanEdit() ? 1:0,
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
		$result =& $this->retrieve(
			'SELECT	ea.editor_id,
				COUNT(ea.article_id) AS complete
			FROM	edit_assignments ea,
				articles a
			WHERE	ea.article_id = a.article_id AND
				a.journal_id = ? AND (
					a.status = ' . STATUS_ARCHIVED . ' OR
					a.status = ' . STATUS_PUBLISHED . ' OR
					a.status = ' . STATUS_DECLINED . '
				)
			GROUP BY ea.editor_id',
			$journalId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['complete'] = $row['complete'];
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		// Get counts of incomplete submissions
		$result =& $this->retrieve(
			'SELECT	ea.editor_id,
				COUNT(ea.article_id) AS incomplete
			FROM	edit_assignments ea,
				articles a
			WHERE	ea.article_id = a.article_id AND
				a.journal_id = ? AND
				a.status = ' . STATUS_QUEUED . '
			GROUP BY ea.editor_id',
			$journalId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['incomplete'] = $row['incomplete'];
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		return $statistics;
	}
}

?>
