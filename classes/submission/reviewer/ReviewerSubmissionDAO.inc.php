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
	 * @param $reviewerId int
	 * @return ReviewerSubmission
	 */
	function &getReviewerSubmission($articleId, $reviewerId) {
		$result = &$this->retrieve(
			'SELECT a.*, r.*, u.first_name, u.last_name, s.title as section_title, e.editor_id from articles a LEFT JOIN review_assignments r ON (a.article_id = r.article_id) LEFT JOIN sections s ON (s.section_id = a.section_id) LEFT JOIN edit_assignments e ON (e.article_id = a.article_id) LEFT JOIN users u ON (r.reviewer_id = u.user_id) WHERE a.article_id = ? AND r.reviewer_id = ?',
			array($articleId, $reviewerId)
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnReviewerSubmissionFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Internal function to return a ReviewerSubmission object from a row.
	 * @param $row array
	 * @return ReviewerSubmission
	 */
	function &_returnReviewerSubmissionFromRow(&$row) {
		$reviewerSubmission = &new ReviewerSubmission();

		$reviewerSubmission->setEditor($this->userDao->getUser($row['editor_id']));

		// Files
		$reviewerSubmission->setSubmissionFile($this->articleFileDao->getArticleFile($row['submission_file_id']));
		$reviewerSubmission->setRevisedFile($this->articleFileDao->getArticleFile($row['revised_file_id']));
		$reviewerSubmission->setSuppFiles($this->suppFileDao->getSuppFilesByArticle($row['article_id']));
		$reviewerSubmission->setReviewFile($this->articleFileDao->getArticleFile($row['review_file_id']));
		
		// Review Assignment 
		$reviewerSubmission->setReviewId($row['review_id']);
		$reviewerSubmission->setReviewerId($row['reviewer_id']);
		$reviewerSubmission->setReviewerFullName($row['first_name'].' '.$row['last_name']);
		$reviewerSubmission->setComments($row['comments']);
		$reviewerSubmission->setRecommendation($row['recommendation']);
		$reviewerSubmission->setDateAssigned($row['date_assigned']);
		$reviewerSubmission->setDateNotified($row['date_notified']);
		$reviewerSubmission->setDateConfirmed($row['date_confirmed']);
		$reviewerSubmission->setDateCompleted($row['date_completed']);
		$reviewerSubmission->setDateAcknowledged($row['date_acknowledged']);
		$reviewerSubmission->setDateDue($row['date_due']);
		$reviewerSubmission->setDeclined($row['declined']);
		$reviewerSubmission->setReplaced($row['replaced']);
		$reviewerSubmission->setAssignedFileId($row['assigned_file_id']);
		$reviewerSubmission->setAssignedRevision($row['assigned_revision']);
		$reviewerSubmission->setReviewFileId($row['review_file_id']);
		$reviewerSubmission->setReviewFileViewable($row['review_file_viewable']);
		$reviewerSubmission->setTimeliness($row['timeliness']);
		$reviewerSubmission->setQuality($row['quality']);

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
		$reviewerSubmission->setSubmissionFileId($row['submission_file_id']);
		$reviewerSubmission->setRevisedFileId($row['revised_file_id']);
		$reviewerSubmission->setAuthors($this->authorDao->getAuthorsByArticle($row['article_id']));
		
		return $reviewerSubmission;
	}

	/**
	 * Update an existing review submission.
	 * @param $reviewSubmission ReviewSubmission
	 */
	function updateReviewerSubmission(&$reviewerSubmission) {
		return $this->update(
			'UPDATE review_assignments
				SET	article_id = ?,
					reviewer_id = ?,
					comments = ?,
					recommendation = ?,
					declined = ?,
					replaced = ?,
					date_assigned = ?,
					date_notified = ?,
					date_confirmed = ?,
					date_completed = ?,
					date_acknowledged = ?,
					date_due = ?,
					assigned_file_id = ?,
					assigned_revision = ?,
					review_file_id = ?,
					review_file_viewable = ?,
					timeliness = ?,
					quality = ?
				WHERE review_id = ?',
			array(
				$reviewerSubmission->getArticleId(),
				$reviewerSubmission->getReviewerId(),
				$reviewerSubmission->getComments(),
				$reviewerSubmission->getRecommendation(),
				$reviewerSubmission->getDeclined(),
				$reviewerSubmission->getReplaced(),
				$reviewerSubmission->getDateAssigned(),
				$reviewerSubmission->getDateNotified(),
				$reviewerSubmission->getDateConfirmed(),
				$reviewerSubmission->getDateCompleted(),
				$reviewerSubmission->getDateAcknowledged(),
				$reviewerSubmission->getDateDue(),
				$reviewerSubmission->getAssignedFileId(),
				$reviewerSubmission->getAssignedRevision(),
				$reviewerSubmission->getReviewFileId(),
				$reviewerSubmission->getTimeliness(),
				$reviewerSubmission->getQuality(),
				$reviewerSubmission->getReviewId()
			)
		);
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
			'SELECT a.*, r.*, u.first_name, u.last_name, s.title as section_title, e.editor_id from articles a LEFT JOIN review_assignments r ON (a.article_id = r.article_id) LEFT JOIN sections s ON (s.section_id = a.section_id) LEFT JOIN edit_assignments e ON (e.article_id = a.article_id) LEFT JOIN users u ON (r.reviewer_id = u.user_id) WHERE a.journal_id = ? AND r.reviewer_id = ? AND r.date_notified IS NOT NULL',
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
