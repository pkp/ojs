<?php

/**
 * LayoutEditorSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.layoutEditor
 *
 * Class for LayoutEditorSubmission DAO.
 * Operations for retrieving and modifying LayoutEditorSubmission objects.
 *
 * $Id$
 */

class LayoutEditorSubmissionDAO extends DAO {

	/** Helper DAOs */
	var $articleDao;
	var $layoutDao;
	var $galleyDao;
	var $editorDao;
	var $suppFileDao;
	var $proofAssignmentDao;
	var $articleCommentDao;

	/**
	 * Constructor.
	 */
	function LayoutEditorSubmissionDAO() {
		parent::DAO();
		
		$this->articleDao = &DAORegistry::getDAO('ArticleDAO');
		$this->layoutDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
		$this->galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$this->editorDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$this->suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$this->proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$this->articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
	}
	
	/**
	 * Retrieve a layout editor submission by article ID.
	 * @param $articleId int
	 * @return LayoutEditorSubmission
	 */
	function getSubmission($articleId, $journalId =  null) {
		if (isset($journalId)) {
			$result = &$this->retrieve(
				'SELECT a.*, s.abbrev as section_abbrev, s.title AS section_title
				FROM articles a
				LEFT JOIN sections s ON s.section_id = a.section_id
				WHERE article_id = ? AND a.journal_id = ?',
				array($articleId, $journalId)
			);
			
		} else {
			$result = &$this->retrieve(
				'SELECT a.*, s.abbrev as section_abbrev, s.title AS section_title
				FROM articles a
				LEFT JOIN sections s ON s.section_id = a.section_id
				WHERE article_id = ?',
				$articleId
			);
		}
		
		if ($result->RecordCount() == 0) {
			return null;
		
		} else {
			return $this->_returnSubmissionFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Internal function to return a LayoutEditorSubmission object from a row.
	 * @param $row array
	 * @return LayoutEditorSubmission
	 */
	function &_returnSubmissionFromRow(&$row) {
		$submission = &new LayoutEditorSubmission();
		$this->articleDao->_articleFromRow($submission, $row);
		$submission->setLayoutAssignment($this->layoutDao->getLayoutAssignmentByArticleId($row['article_id']));
		
		// Comments
		$submission->setMostRecentLayoutComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_LAYOUT, $row['article_id']));
		$submission->setMostRecentProofreadComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_PROOFREAD, $row['article_id']));

		$submission->setSuppFiles($this->suppFileDao->getSuppFilesByArticle($row['article_id']));

		$submission->setGalleys($this->galleyDao->getGalleysByArticle($row['article_id']));
		
		$submission->setEditor($this->editorDao->getEditAssignmentByArticleId($row['article_id']));

		$submission->setProofAssignment($this->proofAssignmentDao->getProofAssignmentByArticleId($row['article_id']));

		$submission->setSectionAbbrev($row['section_abbrev']);

		return $submission;
	}
	
	/**
	 * Update an existing layout editor sbusmission.
	 * @param $submission LayoutEditorSubmission
	 */
	function updateSubmission(&$submission) {
		// Only update layout-specific data
		$layoutAssignment = $submission->getLayoutAssignment();
		$this->layoutDao->updateLayoutAssignment($layoutAssignment);
	}
	
	/**
	 * Get set of layout editing assignments assigned to the specified layout editor.
	 * @param $editorId int
	 * @param $active boolean true to select active assignments, false to select completed assignments
	 * @return array LayoutEditorSubmission
	 */
	function &getSubmissions($editorId, $journalId, $active = true) {
		$submissions = array();

		$sql = 'SELECT a.*, l.*, s.abbrev as section_abbrev, s.title AS section_title FROM articles a, layouted_assignments l, proof_assignments p LEFT JOIN sections s ON s.section_id = a.section_id WHERE a.article_id = l.article_id AND a.article_id = p.article_id AND l.editor_id = ? AND a.journal_id = ? AND l.date_notified IS NOT NULL';
		
		if ($active) {
			$sql .= ' AND (l.date_completed IS NULL OR p.date_layouteditor_completed IS NULL)'; 
		} else {
			$sql .= ' AND (l.date_completed IS NOT NULL AND p.date_layouteditor_completed IS NOT NULL)';
		}

		$result = &$this->retrieve($sql, array($editorId, $journalId));
		
		while (!$result->EOF) {
			$submissions[] = $this->_returnSubmissionFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		
		return $submissions;
	}

	/**
	 * Get count of active and complete assignments
	 * @param editorId int
	 * @param journalId int
	 */
	function getSubmissionsCount($editorId, $journalId) {
		$submissionsCount = array();
		$submissionsCount[0] = 0;
		$submissionsCount[1] = 0;

		$sql = 'SELECT l.date_completed, p.date_layouteditor_completed FROM articles a, layouted_assignments l, proof_assignments p LEFT JOIN sections s ON s.section_id = a.section_id WHERE a.article_id = l.article_id AND a.article_id = p.article_id AND l.editor_id = ? AND a.journal_id = ? AND l.date_notified IS NOT NULL';

		$result = &$this->retrieve($sql, array($editorId, $journalId));
		while (!$result->EOF) {
			if ($result->fields['date_completed'] == null || $result->fields['date_layouteditor_completed'] == null) {
				$submissionsCount[0] += 1;
			} else {
				$submissionsCount[1] += 1;
			}
			$result->moveNext();
		}
		return $submissionsCount;
	}

}

?>
