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
	var $editAssignmentDao;

	/**
	 * Constructor.
	 */
	function EditorSubmissionDAO() {
		parent::DAO();
		$this->authorDao = DAORegistry::getDAO('AuthorDAO');
		$this->userDao = DAORegistry::getDAO('UserDAO');
		$this->editAssignmentDao = DAORegistry::getDAO('EditAssignmentDAO');
	}
	
	/**
	 * Retrieve an editor submission by article ID.
	 * @param $articleId int
	 * @return EditorSubmission
	 */
	function &getEditorSubmission($articleId) {
		$result = &$this->retrieve(
			'SELECT a.*, s.title as section_title from articles a LEFT JOIN sections s ON s.section_id = a.section_id WHERE a.article_id = ?', $articleId
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
		$editorSubmission->setCurrentRound($row['current_round']);
		$editorSubmission->setSubmissionFileId($row['submission_file_id']);
		$editorSubmission->setRevisedFileId($row['revised_file_id']);
		$editorSubmission->setReviewFileId($row['review_file_id']);
		$editorSubmission->setCopyeditFileId($row['copyedit_file_id']);
		$editorSubmission->setEditorFileId($row['editor_file_id']);
				
		$editorSubmission->setAuthors($this->authorDao->getAuthorsByArticle($row['article_id']));	
		
		// Editor Assignment
		$editorSubmission->setEditor($this->editAssignmentDao->getEditAssignmentByArticleId($row['article_id']));
		
		// Replaced Editors
		$editorSubmission->setReplacedEditors($this->editAssignmentDao->getReplacedEditAssignmentsByArticleId($row['article_id']));
		
		// Editor Decisions
		for ($i = 1; $i <= $row['current_round']; $i++) {
			$editorSubmission->setDecisions($this->getEditorDecisions($row['article_id'], $i), $i);
		}
		
		return $editorSubmission;
	}

	/**
	 * Insert a new EditorSubmission.
	 * @param $editorSubmission EditorSubmission
	 */	
	function insertEditorSubmission(&$editorSubmission) {
		$this->update(
			'INSERT INTO edit_assignments
				(article_id, editor_id, comments, recommendation, date_notified, date_completed, date_acknowledged, replaced)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$editorSubmission->getArticleId(),
				$editorSubmission->getEditorId(),
				$editorSubmission->getComments(),
				$editorSubmission->getRecommendation(),
				$editorSubmission->getDateNotified(),
				$editorSubmission->getDateCompleted(),
				$editorSubmission->getDateAcknowledged(),
				$editorSubmission->getReplaced()
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
		// update edit assignment
		$editAssignment = $editorSubmission->getEditor();
		if ($editAssignment->getEditId() > 0) {
			$this->editAssignmentDao->updateEditAssignment(&$editAssignment);
		} else {
			$this->editAssignmentDao->insertEditAssignment(&$editAssignment);
		}
		
		// update replaced edit assignment
		foreach ($editorSubmission->getReplacedEditors() as $editAssignment) {
			if ($editAssignment->getEditId() > 0) {
				$this->editAssignmentDao->updateEditAssignment(&$editAssignment);
			} else {
				$this->editAssignmentDao->insertEditAssignment(&$editAssignment);
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
			'SELECT a.*, s.title as section_title from articles a LEFT JOIN sections s ON (s.section_id = a.section_id) WHERE a.journal_id = ?', $journalId
		);
		
		while (!$result->EOF) {
			$editorSubmissions[] = $this->_returnEditorSubmissionFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		
		return $editorSubmissions;
	}
	
	//
	// Miscellaneous
	//
	
	/**
	 * Get the editor decisions for a review round of an article.
	 * @param $articleId int
	 * @param $round int
	 */
	function getEditorDecisions($articleId, $round = null) {
		$decisions = array();
	
		if ($round == null) {
			$result = &$this->retrieve(
				'SELECT edit_decision_id, editor_id, decision, date_decided FROM edit_decisions WHERE article_id = ?', $articleId
			);
		} else {
			$result = &$this->retrieve(
				'SELECT edit_decision_id, editor_id, decision, date_decided FROM edit_decisions WHERE article_id = ? AND round = ?',
				array($articleId, $round)
			);
		}
		
		while (!$result->EOF) {
			$decisions[] = array('editDecisionId' => $result->fields[0], 'editorId' => $result->fields[1], 'decision' => $result->fields[2], 'dateDecided' => $result->fields[3]);
			$result->moveNext();
		}
		$result->Close();
	
		return $decisions;
	}
	
	/**
	 * Retrieve a list of all section editors not assigned to the specified article.
	 * @param $journalId int
	 * @param $articleId int
	 * @return array matching Users
	 */
	function &getSectionEditorsNotAssignedToArticle($journalId, $articleId) {
		$users = array();
		
		$userDao = &DAORegistry::getDAO('UserDAO');
				
		$result = &$this->retrieve(
			'SELECT DISTINCT u.* FROM users u, roles r LEFT JOIN edit_assignments e ON (e.editor_id = u.user_id AND e.article_id = ?) WHERE u.user_id = r.user_id AND r.journal_id = ? AND r.role_id = ? AND (e.article_id IS NULL OR e.replaced = 1) ORDER BY last_name, first_name',
			array($articleId, $journalId, RoleDAO::getRoleIdFromPath('sectionEditor'))
		);
		
		while (!$result->EOF) {
			$users[] = &$userDao->_returnUserFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $users;
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
