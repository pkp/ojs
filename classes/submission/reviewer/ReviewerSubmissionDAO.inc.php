<?php

/**
 * ReviewerSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * Class for ReviewerSubmission DAO.
 * Operations for retrieving and modifying ReviewerSubmission objects.
 *
 * $Id$
 */

class ReviewerSubmissionDAO extends DAO {

	var $authorDao;
	var $userDao;
	var $reviewAssignmentDao;
	var $articleFileDao;
	var $suppFileDao;

	/**
	 * Constructor.
	 */
	function ReviewerSubmissionDAO() {
		parent::DAO();
		$this->authorDao = DAORegistry::getDAO('AuthorDAO');
		$this->userDao = DAORegistry::getDAO('UserDAO');
		$this->reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->articleFileDao = DAORegistry::getDAO('ArticleFileDAO');
		$this->suppFileDao = DAORegistry::getDAO('SuppFileDAO');
	}
	
	/**
	 * Retrieve a reviewer submission by article ID.
	 * @param $articleId int
	 * @return ReviewerSubmission
	 */
	function &getReviewerSubmission($articleId, $reviewerId) {
		$result = &$this->retrieve(
			'SELECT a.*, r.reviewer_id, s.title as section_title, e.editor_id from articles a LEFT JOIN review_assignments r ON (a.article_id = r.article_id) LEFT JOIN sections s ON (s.section_id = a.section_id) LEFT JOIN edit_assignments e ON (e.article_id = a.article_id) WHERE a.article_id = ? AND r.reviewer_id = ?',
			array($articleId, $reviewerId)
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnReviewerSubmissionFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Internal function to return an EditorSubmission object from a row.
	 * @param $row array
	 * @return EditorSubmission
	 */
	function &_returnReviewerSubmissionFromRow(&$row) {
		$reviewerSubmission = &new ReviewerSubmission();

		$reviewerSubmission->setEditor($this->userDao->getUser($row['editor_id']));
		$reviewerSubmission->setReviewAssignment($this->reviewAssignmentDao->getReviewAssignment($row['article_id'], $row['reviewer_id']));

		$reviewerSubmission->setSubmissionFile($this->articleFileDao->getSubmissionArticleFile($row['article_id']));
		$reviewerSubmission->setSuppFiles($this->suppFileDao->getSuppFilesByArticle($row['article_id']));
		
		// Article attributes
		$reviewerSubmission->setArticleId($row['article_id']);
		$reviewerSubmission->setUserId($row['user_id']);
		$reviewerSubmission->setJournalId($row['journal_id']);
		$reviewerSubmission->setSectionId($row['section_id']);
		$reviewerSubmission->setSectionTitle($row['section_title']);
		$reviewerSubmission->setTitle($row['title']);
		$reviewerSubmission->setAbstract($row['abstract']);
		$reviewerSubmission->setDiscipline($row['discipline']);
		$reviewerSubmission->setSubjectClass($row['subject_class']);
		$reviewerSubmission->setSubject($row['subject']);
		$reviewerSubmission->setCoverageGeo($row['coverage_geo']);
		$reviewerSubmission->setCoverageChron($row['coverage_chron']);
		$reviewerSubmission->setCoverageSample($row['coverage_sample']);
		$reviewerSubmission->setType($row['type']);
		$reviewerSubmission->setLanguage($row['language']);
		$reviewerSubmission->setSponsor($row['sponsor']);
		$reviewerSubmission->setCommentsToEditor($row['comments_to_ed']);
		$reviewerSubmission->setDateSubmitted($row['date_submitted']);
		$reviewerSubmission->setStatus($row['status']);
		$reviewerSubmission->setSubmissionProgress($row['submission_progress']);
		
		$reviewerSubmission->setAuthors($this->authorDao->getAuthorsByArticle($row['article_id']));
		
		return $reviewerSubmission;
	}

	/**
	 * Update an existing review submission.
	 * @param $reviewSubmission ReviewSubmission
	 */
	function updateReviewerSubmission(&$reviewerSubmission) {
	
		// update review assignment
		$reviewAssignment = &$reviewerSubmission->getReviewAssignment();
		
		if ($reviewAssignment->getReviewId() > 0) {
			$this->reviewAssignmentDao->updateReviewAssignment(&$reviewAssignment);
		} else {
			$this->reviewAssignmentDao->insertReviewAssignment(&$reviewAssignment);
		}
		
	}
	
	/**
	 * Get all submissions for a reviewer of a journal.
	 * @param $reviewerId int
	 * @param $journalId int
	 * @return array ReviewerSubmissions
	 */
	function &getReviewerSubmissionsByReviewerId($reviewerId, $journalId) {
		$reviewerSubmissions = array();
		
		$result = &$this->retrieve(
			'SELECT a.*, r.reviewer_id, s.title as section_title, e.editor_id from articles a LEFT JOIN review_assignments r ON (a.article_id = r.article_id) LEFT JOIN sections s ON (s.section_id = a.section_id) LEFT JOIN edit_assignments e ON (e.article_id = a.article_id) WHERE a.journal_id = ? AND r.reviewer_id = ?',
			array($journalId, $reviewerId)
		);
		
		while (!$result->EOF) {
			$reviewerSubmissions[] = $this->_returnReviewerSubmissionFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		
		return $reviewerSubmissions;
	}
	
}

?>
