<?php

/**
 * EditorSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * Class for EditorSubmission DAO.
 * Operations for retrieving and modifying EditorSubmission objects.
 *
 * $Id$
 */

class EditorSubmissionDAO extends DAO {

	var $authorDao;
	var $userDao;
	var $reviewAssignmentDao;

	/**
	 * Constructor.
	 */
	function EditorSubmissionDAO() {
		parent::DAO();
		$this->authorDao = DAORegistry::getDAO('AuthorDAO');
		$this->userDao = DAORegistry::getDAO('UserDAO');
		$this->reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
	}
	
	/**
	 * Retrieve an editor submission by article ID.
	 * @param $articleId int
	 * @return EditorSubmission
	 */
	function &getEditorSubmission($articleId) {
		$result = &$this->retrieve(
			'SELECT a.*, e.edit_id, e.editor_id, e.comments, e.recommendation, e.date_notified, e.date_completed, e.date_acknowledged, s.title as section_title from articles a LEFT JOIN edit_assignments e on (a.article_id = e.article_id) LEFT JOIN sections s ON s.section_id = a.section_id WHERE a.article_id = ?', $articleId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnEditorSubmissionFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Internal function to return an EditorSubmission object from a row.
	 * @param $row array
	 * @return EditorSubmission
	 */
	function &_returnEditorSubmissionFromRow(&$row) {
		$editorSubmission = &new EditorSubmission();
		$editorSubmission->setEditId($row['edit_id']);
		$editorSubmission->setArticleId($row['article_id']);
		$editorSubmission->setEditorId($row['editor_id']);
		$editorSubmission->setComments($row['comments']);
		$editorSubmission->setRecommendation($row['recommendation']);
		$editorSubmission->setDateNotified($row['date_notified']);
		$editorSubmission->setDateCompleted($row['date_completed']);
		$editorSubmission->setDateAcknowledged($row['date_acknowledged']);
		
		$editorSubmission->setEditor($this->userDao->getUser($row['editor_id']));
		$editorSubmission->setReviewAssignments($this->reviewAssignmentDao->getReviewAssignmentsByArticleId($row['article_id']));
		
		// Article attributes
		$editorSubmission->setArticleId($row['article_id']);
		$editorSubmission->setUserId($row['user_id']);
		$editorSubmission->setJournalId($row['journal_id']);
		$editorSubmission->setSectionId($row['section_id']);
		$editorSubmission->setSectionTitle($row['section_title']);
		$editorSubmission->setTitle($row['title']);
		$editorSubmission->setAbstract($row['abstract']);
		$editorSubmission->setDiscipline($row['discipline']);
		$editorSubmission->setSubjectClass($row['subject_class']);
		$editorSubmission->setSubject($row['subject']);
		$editorSubmission->setCoverageGeo($row['coverage_geo']);
		$editorSubmission->setCoverageChron($row['coverage_chron']);
		$editorSubmission->setCoverageSample($row['coverage_sample']);
		$editorSubmission->setType($row['type']);
		$editorSubmission->setLanguage($row['language']);
		$editorSubmission->setSponsor($row['sponsor']);
		$editorSubmission->setCommentsToEditor($row['comments_to_ed']);
		$editorSubmission->setDateSubmitted($row['date_submitted']);
		$editorSubmission->setStatus($row['status']);
		$editorSubmission->setSubmissionProgress($row['submission_progress']);
		
		$editorSubmission->setAuthors($this->authorDao->getAuthorsByArticle($row['article_id']));
		
		return $editorSubmission;
	}

	/**
	 * Insert a new EditorSubmission.
	 * @param $editorSubmission EditorSubmission
	 */	
	function insertEditorSubmission(&$editorSubmission) {
		$this->update(
			'INSERT INTO edit_assignments
				(article_id, editor_id, comments, recommendation, date_notified, date_completed, date_acknowledged)
				VALUES
				(?, ?, ?, ?, ?, ?, ?)',
			array(
				$editorSubmission->getArticleId(),
				$editorSubmission->getEditorId(),
				$editorSubmission->getComments(),
				$editorSubmission->getRecommendation(),
				$editorSubmission->getDateNotified(),
				$editorSubmission->getDateCompleted(),
				$editorSubmission->getDateAcknowledged()
			)
		);
		
		$editorSubmission->setEditId($this->getInsertEditId());
		
		// Insert review assignments.
		$reviewAssignments = &$editorSubmission->getReviewAssignments();
		for ($i=0, $count=count($reviewAssignments); $i < $count; $i++) {
			$reviewAssignments[$i]->setArticleId($editorSubmission->getArticleId());
			$this->reviewAssignmentDao->insertReviewAssignment(&$reviewAssignments[$i]);
		}
	}
	
	/**
	 * Update an existing article.
	 * @param $article Article
	 */
	function updateEditorSubmission(&$editorSubmission) {
		$this->update(
			'UPDATE edit_assignments
				SET
					article_id = ?,
					editor_id = ?,
					comments = ?,
					recommendation = ?,
					date_notified = ?,
					date_completed = ?,
					date_acknowledged = ?
				WHERE edit_id = ?',
			array(
				$editorSubmission->getArticleId(),
				$editorSubmission->getEditorId(),
				$editorSubmission->getComments(),
				$editorSubmission->getRecommendation(),
				$editorSubmission->getDateNotified(),
				$editorSubmission->getDateCompleted(),
				$editorSubmission->getDateAcknowledged(),
				$editorSubmission->getEditId(),
			)
		);
		
		// update review assignments
		$reviewAssignments = &$editorSubmission->getReviewAssignments();
		for ($i=0, $count=count($reviewAssignments); $i < $count; $i++) {
			if ($reviewAssignments[$i]->getReviewId() > 0) {
				$this->reviewAssignmentDao->updateReviewAssignment(&$reviewAssignments[$i]);
			} else {
				$this->reviewAssignmentDao->insertReviewAssignment(&$reviewAssignments[$i]);
			}
		}
		
	}
	
	/**
	 * Get all submissions for a journal.
	 * @param $journalId int
	 * @return array EditorSubmission
	 */
	function &getEditorSubmissions($journalId) {
		$editorSubmissions = array();
		
		$result = &$this->retrieve(
			'SELECT a.*, e.edit_id, e.editor_id, e.comments, e.recommendation, e.date_notified, e.date_completed, e.date_acknowledged, s.title as section_title from articles a LEFT JOIN edit_assignments e on (a.article_id = e.article_id) LEFT JOIN sections s ON (s.section_id = a.section_id) WHERE a.journal_id = ?', $journalId
		);
		
		while (!$result->EOF) {
			$editorSubmissions[] = $this->_returnEditorSubmissionFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		
		return $editorSubmissions;
	}
	
	/**
	 * Get the ID of the last inserted editor assignment.
	 * @return int
	 */
	function getInsertEditId() {
		return $this->getInsertId('edit_assignments', 'edit_id');
	}
	
}

?>
