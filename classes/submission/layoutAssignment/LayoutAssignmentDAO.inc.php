<?php

/**
 * LayoutAssignmentDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.layoutAssignment
 *
 * DAO class for layout editing assignments.
 *
 * $Id$
 */

import('submission.layoutAssignment.LayoutAssignment');

class LayoutAssignmentDAO extends DAO {

	var $articleFileDao;

	/**
	 * Constructor.
	 */
	function LayoutAssignmentDAO() {
		parent::DAO();
		$this->articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
	}
	
	/**
	 * Retrieve a layout assignment by assignment ID.
	 * @param $layoutId int
	 * @return LayoutAssignment
	 */
	function &getLayoutAssignmentById($layoutId) {
		$result = &$this->retrieve(
			'SELECT l.*, u.first_name, u.last_name, u.email
				FROM layouted_assignments l
				LEFT JOIN users u ON (l.editor_id = u.user_id)
				WHERE layouted_id = ?',
			$layoutId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
		} else {
			return $this->_returnLayoutAssignmentFromRow($result->GetRowAssoc(false));
		}
	}
	

	/**
	 * Retrieve the layout editing assignment for an article.
	 * @param $articleId int
	 * @return LayoutAssignment
	 */
	function &getLayoutAssignmentByArticleId($articleId) {
		$result = &$this->retrieve(
			'SELECT l.*, u.first_name, u.last_name, u.email
				FROM layouted_assignments l
				LEFT JOIN users u ON (l.editor_id = u.user_id)
				WHERE article_id = ?',
			$articleId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
		} else {
			return $this->_returnLayoutAssignmentFromRow($result->GetRowAssoc(false));
		}
	}

	/**
	 * Internal function to return a layout assignment object from a row.
	 * @param $row array
	 * @return LayoutAssignment
	 */
	function &_returnLayoutAssignmentFromRow(&$row) {
		$layoutAssignment = &new LayoutAssignment();
		$layoutAssignment->setLayoutId($row['layouted_id']);
		$layoutAssignment->setArticleId($row['article_id']);
		$layoutAssignment->setEditorId($row['editor_id']);
		$layoutAssignment->setEditorFullName($row['first_name'].' '.$row['last_name']);
		$layoutAssignment->setEditorEmail($row['email']);
		$layoutAssignment->setDateNotified($row['date_notified']);
		$layoutAssignment->setDateUnderway($row['date_underway']);
		$layoutAssignment->setDateCompleted($row['date_completed']);
		$layoutAssignment->setDateAcknowledged($row['date_acknowledged']);
		$layoutAssignment->setLayoutFileId($row['layout_file_id']);
		
		if ($row['layout_file_id'] && $row['layout_file_id']) {
			$layoutAssignment->setLayoutFile($this->articleFileDao->getArticleFile($row['layout_file_id']));
		}
			
		return $layoutAssignment;
	}
	
	/**
	 * Insert a new layout assignment.
	 * @param $layoutAssignment LayoutAssignment
	 */	
	function insertLayoutAssignment(&$layoutAssignment) {
		$this->update(
			'INSERT INTO layouted_assignments
				(article_id, editor_id, date_notified, date_underway, date_completed, date_acknowledged, layout_file_id)
				VALUES
				(?, ?, ?, ?, ?, ?, ?)',
			array(
				$layoutAssignment->getArticleId(),
				$layoutAssignment->getEditorId(),
				$layoutAssignment->getDateNotified(),
				$layoutAssignment->getDateUnderway(),
				$layoutAssignment->getDateCompleted(),
				$layoutAssignment->getDateAcknowledged(),
				$layoutAssignment->getLayoutFileId()
			)
		);
		
		$layoutAssignment->setLayoutId($this->getInsertLayoutId());
		return $layoutAssignment->getLayoutId();
	}
	
	/**
	 * Update an layout assignment.
	 * @param $layoutAssignment LayoutAssignment
	 */
	function updateLayoutAssignment(&$layoutAssignment) {
		return $this->update(
			'UPDATE layouted_assignments
				SET	article_id = ?,
					editor_id = ?,
					date_notified = ?,
					date_underway = ?,
					date_completed = ?,
					date_acknowledged = ?,
					layout_file_id = ?
				WHERE layouted_id = ?',
			array(
				$layoutAssignment->getArticleId(),
				$layoutAssignment->getEditorId(),
				$layoutAssignment->getDateNotified(),
				$layoutAssignment->getDateUnderway(),
				$layoutAssignment->getDateCompleted(),
				$layoutAssignment->getDateAcknowledged(),
				$layoutAssignment->getLayoutFileId(),
				$layoutAssignment->getLayoutId()
			)
		);
	}
	
	/**
	 * Delete layout assignment.
	 * @param $layoutId int
	 */
	function deleteLayoutAssignmentById($layoutId) {
		return $this->update(
			'DELETE FROM layouted_assignments WHERE layouted_id = ?',
			$layoutId
		);
	}
	
	/**
	 * Delete layout assignments by article.
	 * @param $articleId int
	 */
	function deleteLayoutAssignmentsByArticle($articleId) {
		return $this->update(
			'DELETE FROM layouted_assignments WHERE article_id = ?',
			$articleId
		);
	}

	/**
	 * Get the ID of the last inserted layout assignment.
	 * @return int
	 */
	function getInsertLayoutId() {
		return $this->getInsertId('layouted_assignments', 'layouted_id');
	}
	
}

?>
