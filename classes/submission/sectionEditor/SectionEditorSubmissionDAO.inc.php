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

	var $articleDao;
	var $authorDao;
	var $userDao;
	var $reviewAssignmentDao;
	var $copyeditorSubmissionDao;
	var $articleFileDao;
	var $suppFileDao;

	/**
	 * Constructor.
	 */
	function SectionEditorSubmissionDAO() {
		parent::DAO();
		$this->articleDao = DAORegistry::getDAO('ArticleDAO');
		$this->authorDao = DAORegistry::getDAO('AuthorDAO');
		$this->userDao = DAORegistry::getDAO('UserDAO');
		$this->reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->copyeditorSubmissionDao = DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$this->articleFileDao = DAORegistry::getDAO('ArticleFileDAO');
		$this->suppFileDao = DAORegistry::getDAO('SuppFileDAO');
	}
	
	/**
	 * Retrieve a section editor submission by article ID.
	 * @param $articleId int
	 * @return EditorSubmission
	 */
	function &getSectionEditorSubmission($articleId) {
		$result = &$this->retrieve(
			'SELECT a.*, e.edit_id, e.editor_id, e.comments, e.recommendation, e.date_notified, e.date_completed, e.date_acknowledged, e.post_review_file_id, s.title as section_title, c.copyed_id, c.copyeditor_id, c.comments AS copyeditor_comments, c.date_notified AS copyeditor_date_notified, c.date_completed AS copyeditor_date_completed, c.date_acknowledged AS copyeditor_date_acknowledged, c.date_author_notified AS copyeditor_date_author_notified, c.date_author_completed AS copyeditor_date_author_completed, c.date_author_acknowledged AS copyeditor_date_author_acknowledged, c.date_final_notified AS copyeditor_date_final_notified, c.date_final_completed AS copyeditor_date_final_completed, c.date_final_acknowledged AS copyeditor_date_final_acknowledged FROM articles a LEFT JOIN edit_assignments e ON (a.article_id = e.article_id) LEFT JOIN sections s ON (s.section_id = a.section_id) LEFT JOIN copyed_assignments c ON (a.article_id = c.article_id) WHERE a.article_id = ?', $articleId
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
		$sectionEditorSubmission->setEditor($this->userDao->getUser($row['editor_id']));
		$sectionEditorSubmission->setComments($row['comments']);
		$sectionEditorSubmission->setRecommendation($row['recommendation']);
		$sectionEditorSubmission->setDateNotified($row['date_notified']);
		$sectionEditorSubmission->setDateCompleted($row['date_completed']);
		$sectionEditorSubmission->setDateAcknowledged($row['date_acknowledged']);
		$sectionEditorSubmission->setPostReviewFileId($row['post_review_file_id']);
				
		// Files
		$sectionEditorSubmission->setSubmissionFile($this->articleFileDao->getArticleFile($row['submission_file_id']));
		$sectionEditorSubmission->setRevisedFile($this->articleFileDao->getArticleFile($row['revised_file_id']));
		$sectionEditorSubmission->setSuppFiles($this->suppFileDao->getSuppFilesByArticle($row['article_id']));
		$sectionEditorSubmission->setPostReviewFile($this->articleFileDao->getArticleFile($row['post_review_file_id']));
				
		// Review Assignments
		$sectionEditorSubmission->setReviewAssignments($this->reviewAssignmentDao->getReviewAssignmentsByArticleId($row['article_id']));
		
		// Copyeditor Assignment
		$sectionEditorSubmission->setCopyedId($row['copyed_id']);
		$sectionEditorSubmission->setCopyeditorId($row['copyeditor_id']);
		$sectionEditorSubmission->setCopyeditor($this->userDao->getUser($row['copyeditor_id']));
		$sectionEditorSubmission->setCopyeditorComments($row['copyeditor_comments']);
		$sectionEditorSubmission->setCopyeditorDateNotified($row['copyeditor_date_notified']);
		$sectionEditorSubmission->setCopyeditorDateCompleted($row['copyeditor_date_completed']);
		$sectionEditorSubmission->setCopyeditorDateAcknowledged($row['copyeditor_date_acknowledged']);
		$sectionEditorSubmission->setCopyeditorDateAuthorNotified($row['copyeditor_date_author_notified']);
		$sectionEditorSubmission->setCopyeditorDateAuthorCompleted($row['copyeditor_date_author_completed']);
		$sectionEditorSubmission->setCopyeditorDateAuthorAcknowledged($row['copyeditor_date_author_acknowledged']);
		$sectionEditorSubmission->setCopyeditorDateFinalNotified($row['copyeditor_date_final_notified']);
		$sectionEditorSubmission->setCopyeditorDateFinalCompleted($row['copyeditor_date_final_completed']);
		$sectionEditorSubmission->setCopyeditorDateFinalAcknowledged($row['copyeditor_date_final_acknowledged']);
		
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
		$sectionEditorSubmission->setSubmissionFileId($row['submission_file_id']);
		$sectionEditorSubmission->setRevisedFileId($row['revised_file_id']);
		
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
					date_acknowledged = ?,
					post_review_file_id = ?
				WHERE edit_id = ?',
			array(
				$sectionEditorSubmission->getArticleId(),
				$sectionEditorSubmission->getEditorId(),
				$sectionEditorSubmission->getComments(),
				$sectionEditorSubmission->getRecommendation(),
				$sectionEditorSubmission->getDateNotified(),
				$sectionEditorSubmission->getDateCompleted(),
				$sectionEditorSubmission->getDateAcknowledged(),
				$sectionEditorSubmission->getPostReviewFileId(),
				$sectionEditorSubmission->getEditId(),
			)
		);
		
		// Update copyeditor assignment
		if ($sectionEditorSubmission->getCopyeditorId()) {
			$copyeditorSubmission = new CopyeditorSubmission();
			$copyeditorSubmission->setArticleId($sectionEditorSubmission->getArticleId());
			$copyeditorSubmission->setCopyedId($sectionEditorSubmission->getCopyedId());
			$copyeditorSubmission->setCopyeditorId($sectionEditorSubmission->getCopyeditorId());
			$copyeditorSubmission->setComments($sectionEditorSubmission->getCopyeditorComments());
			$copyeditorSubmission->setDateNotified($sectionEditorSubmission->getCopyeditorDateNotified());
			$copyeditorSubmission->setDateCompleted($sectionEditorSubmission->getCopyeditorDateCompleted());
			$copyeditorSubmission->setDateAcknowledged($sectionEditorSubmission->getCopyeditorDateAcknowledged());
			$copyeditorSubmission->setDateAuthorNotified($sectionEditorSubmission->getCopyeditorDateAuthorNotified());
			$copyeditorSubmission->setDateAuthorCompleted($sectionEditorSubmission->getCopyeditorDateAuthorCompleted());
			$copyeditorSubmission->setDateAuthorAcknowledged($sectionEditorSubmission->getCopyeditorDateAuthorAcknowledged());
			$copyeditorSubmission->setDateFinalNotified($sectionEditorSubmission->getCopyeditorDateFinalNotified());
			$copyeditorSubmission->setDateFinalCompleted($sectionEditorSubmission->getCopyeditorDateFinalCompleted());
			$copyeditorSubmission->setDateFinalAcknowledged($sectionEditorSubmission->getCopyeditorDateFinalAcknowledged());
			
			if ($copyeditorSubmission->getCopyedId() != null) {
				$this->copyeditorSubmissionDao->updateCopyeditorSubmission($copyeditorSubmission);
			} else {
				$this->copyeditorSubmissionDao->insertCopyeditorSubmission($copyeditorSubmission);
			}
		}
		
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
		
		// Update article
		if ($sectionEditorSubmission->getArticleId()) {
			$article = &$this->articleDao->getArticle($sectionEditorSubmission->getArticleId());

			// Only update fields that can actually be edited.
			$article->setSectionId($sectionEditorSubmission->getSectionId());
		
			$this->articleDao->updateArticle($article);
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
			'SELECT a.*, e.edit_id, e.editor_id, e.comments, e.recommendation, e.date_notified, e.date_completed, e.date_acknowledged, e.post_review_file_id, s.title as section_title, c.copyed_id, c.copyeditor_id, c.comments AS copyeditor_comments, c.date_notified AS copyeditor_date_notified, c.date_completed AS copyeditor_date_completed, c.date_acknowledged AS copyeditor_date_acknowledged, c.date_author_notified AS copyeditor_date_author_notified, c.date_author_completed AS copyeditor_date_author_completed, c.date_author_acknowledged AS copyeditor_date_author_acknowledged, c.date_final_notified AS copyeditor_date_final_notified, c.date_final_completed AS copyeditor_date_final_completed, c.date_final_acknowledged AS copyeditor_date_final_acknowledged FROM articles a LEFT JOIN edit_assignments e ON (a.article_id = e.article_id) LEFT JOIN sections s ON (s.section_id = a.section_id) LEFT JOIN copyed_assignments c ON (a.article_id = c.article_id) WHERE a.journal_id = ? AND e.editor_id = ?',
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
