<?php

/**
 * CopyAssignmentDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * Class for CopyAssignment DAO.
 * Operations for retrieving and modifying CopyAssignment objects.
 *
 * $Id$
 */

import('submission.copyAssignment.CopyAssignment');

class CopyAssignmentDAO extends DAO {

	var $articleFileDao;

	/**
	 * Constructor.
	 */
	function CopyAssignmentDAO() {
		parent::DAO();
		$this->articleFileDao = DAORegistry::getDAO('ArticleFileDAO');
	}
	
	/**
	 * Retrieve a copyed assignment by article ID.
	 * @param $copyedId int
	 * @return copyAssignment
	 */
	function &getCopyAssignmentById($copyedId) {
		$result = &$this->retrieve(
			'SELECT c.*, u.first_name, u.last_name FROM copyed_assignments c LEFT JOIN users u ON (c.copyeditor_id = u.user_id) WHERE c.copyed_id = ?',
			$copyedId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnCopyAssignmentFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Retrieve a copy assignment by article ID.
	 * @param $articleId int
	 * @return CopyAssignment
	 */
	function &getCopyAssignmentByArticleId($articleId) {
		$result = &$this->retrieve(
			'SELECT c.*, a.copyedit_file_id, u.first_name, u.last_name FROM copyed_assignments c LEFT JOIN articles a ON (c.article_id = a.article_id) LEFT JOIN users u ON (c.copyeditor_id = u.user_id) WHERE c.article_id = ?',
			$articleId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnCopyAssignmentFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Internal function to return a CopyAssignment object from a row.
	 * @param $row array
	 * @return CopyAssignment
	 */
	function &_returnCopyAssignmentFromRow(&$row) {
		$copyAssignment = &new CopyAssignment();

		// Copyedit Assignment
		$copyAssignment->setCopyedId($row['copyed_id']);
		$copyAssignment->setCopyeditorId($row['copyeditor_id']);
		$copyAssignment->setCopyeditorFullName($row['first_name'].' '.$row['last_name']);
		$copyAssignment->setDateNotified($row['date_notified']);
		$copyAssignment->setDateUnderway($row['date_underway']);
		$copyAssignment->setDateCompleted($row['date_completed']);
		$copyAssignment->setDateAcknowledged($row['date_acknowledged']);
		$copyAssignment->setDateAuthorNotified($row['date_author_notified']);
		$copyAssignment->setDateAuthorUnderway($row['date_author_underway']);
		$copyAssignment->setDateAuthorCompleted($row['date_author_completed']);
		$copyAssignment->setDateAuthorAcknowledged($row['date_author_acknowledged']);
		$copyAssignment->setDateFinalNotified($row['date_final_notified']);
		$copyAssignment->setDateFinalUnderway($row['date_final_underway']);
		$copyAssignment->setDateFinalCompleted($row['date_final_completed']);
		$copyAssignment->setDateFinalAcknowledged($row['date_final_acknowledged']);
		$copyAssignment->setInitialRevision($row['initial_revision']);
		$copyAssignment->setEditorAuthorRevision($row['editor_author_revision']);
		$copyAssignment->setFinalRevision($row['final_revision']);

		// Files
		
		// Initial Copyedit File
		if ($row['initial_revision'] != null) {
			$copyAssignment->setInitialCopyeditFile($this->articleFileDao->getArticleFile($row['copyedit_file_id'], $row['initial_revision']));
		}
		
		// Editor / Author Copyedit File
		if ($row['editor_author_revision'] != null) {
			$copyAssignment->setEditorAuthorCopyeditFile($this->articleFileDao->getArticleFile($row['copyedit_file_id'], $row['editor_author_revision']));
		}
		
		// Final Copyedit File
		if ($row['final_revision'] != null) {
			$copyAssignment->setFinalCopyeditFile($this->articleFileDao->getArticleFile($row['copyedit_file_id'], $row['final_revision']));
		}

		return $copyAssignment;
	}
	
	/**
	 * Insert a new copyAssignment.
	 * @param $copyAssignment copyAssignment
	 */	
	function insertcopyAssignment(&$copyAssignment) {
		$this->update(
			'INSERT INTO copyed_assignments
				(article_id, copyeditor_id, comments, date_notified, date_underway, date_completed, date_acknowledged, date_author_notified, date_author_underway, date_author_completed, date_author_acknowledged, date_final_notified, date_final_underway, date_final_completed, date_final_acknowledged, initial_revision, editor_author_revision, final_revision)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$copyAssignment->getArticleId(),
				$copyAssignment->getCopyeditorId(),
				$copyAssignment->getComments(),
				$copyAssignment->getDateNotified(),
				$copyAssignment->getDateUnderway(),
				$copyAssignment->getDateCompleted(),
				$copyAssignment->getDateAcknowledged(),
				$copyAssignment->getDateAuthorNotified(),
				$copyAssignment->getDateAuthorUnderway(),
				$copyAssignment->getDateAuthorCompleted(),
				$copyAssignment->getDateAuthorAcknowledged(),
				$copyAssignment->getDateFinalNotified(),
				$copyAssignment->getDateFinalUnderway(),
				$copyAssignment->getDateFinalCompleted(),
				$copyAssignment->getDateFinalAcknowledged(),
				$copyAssignment->getInitialRevision(),
				$copyAssignment->getEditorAuthorRevision(),
				$copyAssignment->getFinalRevision()
			)
		);
		
		$copyAssignment->setCopyedId($this->getInsertCopyedId());
	}

	/**
	 * Update an existing copyed assignment.
	 * @param $copyeditorSubmission CopyeditorSubmission
	 */
	function updatecopyAssignment(&$copyAssignment) {
		$this->update(
			'UPDATE copyed_assignments
				SET
					article_id = ?,
					copyeditor_id = ?,
					comments = ?,
					date_notified = ?,
					date_underway = ?,
					date_completed = ?,
					date_acknowledged = ?,
					date_author_notified = ?,
					date_author_underway = ?,
					date_author_completed = ?,
					date_author_acknowledged = ?,
					date_final_notified = ?,
					date_final_underway = ?,
					date_final_completed = ?,
					date_final_acknowledged = ?,
					initial_revision = ?,
					editor_author_revision = ?,
					final_revision = ?
				WHERE copyed_id = ?',
			array(
				$copyAssignment->getArticleId(),
				$copyAssignment->getCopyeditorId(),
				$copyAssignment->getComments(),
				$copyAssignment->getDateNotified(),
				$copyAssignment->getDateUnderway(),
				$copyAssignment->getDateCompleted(),
				$copyAssignment->getDateAcknowledged(),
				$copyAssignment->getDateAuthorNotified(),
				$copyAssignment->getDateAuthorUnderway(),
				$copyAssignment->getDateAuthorCompleted(),
				$copyAssignment->getDateAuthorAcknowledged(),
				$copyAssignment->getDateFinalNotified(),
				$copyAssignment->getDateFinalUnderway(),
				$copyAssignment->getDateFinalCompleted(),
				$copyAssignment->getDateFinalAcknowledged(),
				$copyAssignment->getInitialRevision(),
				$copyAssignment->getEditorAuthorRevision(),
				$copyAssignment->getFinalRevision(),
				$copyAssignment->getCopyedId()
			)
		);
	}
	
	/**
	 * Delete copyed assignment.
	 * @param $copyedId int
	 */
	function deletecopyAssignmentById($copyedId) {
		return $this->update(
			'DELETE FROM copyed_assignments WHERE copyed_id = ?',
			$copyedId
		);
	}

	/**
	 * Delete copyediting assignments by article.
	 * @param $articleId int
	 */
	function deleteCopyAssignmentsByArticle($articleId) {
		return $this->update(
			'DELETE FROM copyed_assignments WHERE article_id = ?',
			$articleId
		);
	}
	
	/**
	 * Get the ID of the last inserted copyeditor assignment.
	 * @return int
	 */
	function getInsertCopyedId() {
		return $this->getInsertId('copyed_assignments', 'copyed_id');
	}
	
}

?>
