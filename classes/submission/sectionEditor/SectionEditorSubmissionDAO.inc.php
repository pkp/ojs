<?php

/**
 * SectionEditorSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * Class for SectionEditorSubmission DAO.
 * Operations for retrieving and modifying SectionEditorSubmission objects.
 *
 * $Id$
 */

class SectionEditorSubmissionDAO extends DAO {

	var $authorDao;
	var $userDao;
	var $reviewAssignmentDao;

	/**
	 * Constructor.
	 */
	function SectionEditorSubmissionDAO() {
		parent::DAO();
		$this->authorDao = DAORegistry::getDAO('AuthorDAO');
		$this->userDao = DAORegistry::getDAO('UserDAO');
		$this->reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
	}
	
	/**
	 * Retrieve a section editor submission by article ID.
	 * @param $articleId int
	 * @return EditorSubmission
	 */
	function &getSectionEditorSubmission($articleId) {
		$result = &$this->retrieve(
			'SELECT a.*, e.edit_id, e.editor_id, e.comments, e.recommendation, e.date_notified, e.date_completed, e.date_acknowledged, s.title as section_title from articles a LEFT JOIN edit_assignments e on (a.article_id = e.article_id) LEFT JOIN sections s ON s.section_id = a.section_id WHERE a.article_id = ?', $articleId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnSectionEditorSubmissionFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Internal function to return a SectionEditorSubmission object from a row.
	 * @param $row array
	 * @return SectionEditorSubmission
	 */
	function &_returnSectionEditorSubmissionFromRow(&$row) {
		$sectionEditorSubmission = &new SectionEditorSubmission();
		$sectionEditorSubmission->setEditId($row['edit_id']);
		$sectionEditorSubmission->setArticleId($row['article_id']);
		$sectionEditorSubmission->setEditorId($row['editor_id']);
		$sectionEditorSubmission->setComments($row['comments']);
		$sectionEditorSubmission->setRecommendation($row['recommendation']);
		$sectionEditorSubmission->setDateNotified($row['date_notified']);
		$sectionEditorSubmission->setDateCompleted($row['date_completed']);
		$sectionEditorSubmission->setDateAcknowledged($row['date_acknowledged']);
		
		$sectionEditorSubmission->setEditor($this->userDao->getUser($row['editor_id']));
		$sectionEditorSubmission->setReviewAssignments($this->reviewAssignmentDao->getReviewAssignmentsByArticleId($row['article_id']));
		
		// Article attributes
		$sectionEditorSubmission->setArticleId($row['article_id']);
		$sectionEditorSubmission->setUserId($row['user_id']);
		$sectionEditorSubmission->setJournalId($row['journal_id']);
		$sectionEditorSubmission->setSectionId($row['section_id']);
		$sectionEditorSubmission->setSectionTitle($row['section_title']);
		$sectionEditorSubmission->setTitle($row['title']);
		$sectionEditorSubmission->setAbstract($row['abstract']);
		$sectionEditorSubmission->setDiscipline($row['discipline']);
		$sectionEditorSubmission->setSubjectClass($row['subject_class']);
		$sectionEditorSubmission->setSubject($row['subject']);
		$sectionEditorSubmission->setCoverageGeo($row['coverage_geo']);
		$sectionEditorSubmission->setCoverageChron($row['coverage_chron']);
		$sectionEditorSubmission->setCoverageSample($row['coverage_sample']);
		$sectionEditorSubmission->setType($row['type']);
		$sectionEditorSubmission->setLanguage($row['language']);
		$sectionEditorSubmission->setSponsor($row['sponsor']);
		$sectionEditorSubmission->setCommentsToEditor($row['comments_to_ed']);
		$sectionEditorSubmission->setDateSubmitted($row['date_submitted']);
		$sectionEditorSubmission->setStatus($row['status']);
		$sectionEditorSubmission->setSubmissionProgress($row['submission_progress']);
		
		$sectionEditorSubmission->setAuthors($this->authorDao->getAuthorsByArticle($row['article_id']));
		
		return $sectionEditorSubmission;
	}
	
	/**
	 * Update an existing section editor submission.
	 * @param $sectionEditorSubmission SectionEditorSubmission
	 */
	function updateSectionEditorSubmission(&$sectionEditorSubmission) {
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
				$sectionEditorSubmission->getArticleId(),
				$sectionEditorSubmission->getEditorId(),
				$sectionEditorSubmission->getComments(),
				$sectionEditorSubmission->getRecommendation(),
				$sectionEditorSubmission->getDateNotified(),
				$sectionEditorSubmission->getDateCompleted(),
				$sectionEditorSubmission->getDateAcknowledged(),
				$sectionEditorSubmission->getEditId(),
			)
		);
		
		// update review assignments
		$reviewAssignments = &$sectionEditorSubmission->getReviewAssignments();
		for ($i=0, $count=count($reviewAssignments); $i < $count; $i++) {
			if ($reviewAssignments[$i]->getReviewId() > 0) {
				$this->reviewAssignmentDao->updateReviewAssignment(&$reviewAssignments[$i]);
			} else {
				$this->reviewAssignmentDao->insertReviewAssignment(&$reviewAssignments[$i]);
			}
		}
		
		// Remove deleted review assignments
		$removedReviewAssignments = $sectionEditorSubmission->getRemovedReviewAssignments();
		for ($i=0, $count=count($removedReviewAssignments); $i < $count; $i++) {
			$this->reviewAssignmentDao->deleteReviewAssignmentById($removedReviewAssignments[$i]);
		}
		
	}
	
	/**
	 * Get all section editor submissions for a section editor.
	 * @param $sectionEditorId int
	 * @return array SectionEditorSubmission
	 */
	function &getSectionEditorSubmissions($sectionEditorId, $journalId) {
		$sectionEditorSubmissions = array();
		
		$result = &$this->retrieve(
			'SELECT a.*, e.edit_id, e.editor_id, e.comments, e.recommendation, e.date_notified, e.date_completed, e.date_acknowledged, s.title as section_title from articles a LEFT JOIN edit_assignments e on (a.article_id = e.article_id) LEFT JOIN sections s ON (s.section_id = a.section_id) WHERE a.journal_id = ? AND e.editor_id = ?',
			array($journalId, $sectionEditorId)
		);
		
		while (!$result->EOF) {
			$sectionEditorSubmissions[] = $this->_returnSectionEditorSubmissionFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		
		return $sectionEditorSubmissions;
	}
	
}

?>
