<?php

/**
 * AuthorSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * Class for AuthorSubmission DAO.
 * Operations for retrieving and modifying AuthorSubmission objects.
 *
 * $Id$
 */

class AuthorSubmissionDAO extends DAO {

	var $articleDao;
	var $authorDao;
	var $userDao;
	var $reviewAssignmentDao;
	var $articleFileDao;
	var $suppFileDao;
	var $copyeditorSubmissionDao;

	/**
	 * Constructor.
	 */
	function AuthorSubmissionDAO() {
		parent::DAO();
		$this->articleDao = DAORegistry::getDAO('ArticleDAO');
		$this->authorDao = DAORegistry::getDAO('AuthorDAO');
		$this->userDao = DAORegistry::getDAO('UserDAO');
		$this->reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->editAssignmentDao = DAORegistry::getDAO('EditAssignmentDAO');
		$this->articleFileDao = DAORegistry::getDAO('ArticleFileDAO');
		$this->suppFileDao = DAORegistry::getDAO('SuppFileDAO');
		$this->copyeditorSubmissionDao = DAORegistry::getDAO('CopyeditorSubmissionDAO');
	}
	
	/**
	 * Retrieve a author submission by article ID.
	 * @param $articleId int
	 * @return AuthorSubmission
	 */
	function &getAuthorSubmission($articleId) {
		$result = &$this->retrieve(
			'SELECT a.*, s.title as section_title, c.copyed_id, c.copyeditor_id, c.comments AS copyeditor_comments, c.date_notified AS copyeditor_date_notified, c.date_completed AS copyeditor_date_completed, c.date_acknowledged AS copyeditor_date_acknowledged, c.date_author_notified AS copyeditor_date_author_notified, c.date_author_completed AS copyeditor_date_author_completed, c.date_author_acknowledged AS copyeditor_date_author_acknowledged FROM articles a LEFT JOIN sections s ON (s.section_id = a.section_id) LEFT JOIN copyed_assignments c on (a.article_id = c.article_id) WHERE a.article_id = ?', $articleId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnAuthorSubmissionFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Internal function to return a AuthorSubmission object from a row.
	 * @param $row array
	 * @return AuthorSubmission
	 */
	function &_returnAuthorSubmissionFromRow(&$row) {
		$authorSubmission = &new AuthorSubmission();

		// Article attributes
		$authorSubmission->setArticleId($row['article_id']);
		$authorSubmission->setUserId($row['user_id']);
		$authorSubmission->setJournalId($row['journal_id']);
		$authorSubmission->setSectionId($row['section_id']);
		$authorSubmission->setSectionTitle($row['section_title']);
		$authorSubmission->setTitle($row['title']);
		$authorSubmission->setAbstract($row['abstract']);
		$authorSubmission->setDiscipline($row['discipline']);
		$authorSubmission->setSubjectClass($row['subject_class']);
		$authorSubmission->setSubject($row['subject']);
		$authorSubmission->setCoverageGeo($row['coverage_geo']);
		$authorSubmission->setCoverageChron($row['coverage_chron']);
		$authorSubmission->setCoverageSample($row['coverage_sample']);
		$authorSubmission->setType($row['type']);
		$authorSubmission->setLanguage($row['language']);
		$authorSubmission->setSponsor($row['sponsor']);
		$authorSubmission->setCommentsToEditor($row['comments_to_ed']);
		$authorSubmission->setDateSubmitted($row['date_submitted']);
		$authorSubmission->setStatus($row['status']);
		$authorSubmission->setSubmissionProgress($row['submission_progress']);
		$authorSubmission->setCurrentRound($row['current_round']);
		$authorSubmission->setSubmissionFileId($row['submission_file_id']);
		$authorSubmission->setRevisedFileId($row['revised_file_id']);
		$authorSubmission->setReviewFileId($row['review_file_id']);
		$authorSubmission->setEditorFileId($row['editor_file_id']);
		$authorSubmission->setCopyeditFileId($row['copyedit_file_id']);
		
		$authorSubmission->setAuthors($this->authorDao->getAuthorsByArticle($row['article_id']));
		
		// Editor Assignment
		$authorSubmission->setEditor($this->editAssignmentDao->getEditAssignmentByArticleId($row['article_id']));
		
		// Editor Decisions
		for ($i = 1; $i <= $row['current_round']; $i++) {
			$authorSubmission->setDecisions($this->getEditorDecisions($row['article_id'], $i), $i);
		}
				
		// Review Assignments
		for ($i = 1; $i <= $row['current_round']; $i++) {
			$authorSubmission->setReviewAssignments($this->reviewAssignmentDao->getReviewAssignmentsByArticleId($row['article_id'], $i), $i);
		}
		
		// Files
		$authorSubmission->setSubmissionFile($this->articleFileDao->getArticleFile($row['submission_file_id']));
		$authorSubmission->setRevisedFile($this->articleFileDao->getArticleFile($row['revised_file_id']));
		$authorSubmission->setSuppFiles($this->suppFileDao->getSuppFilesByArticle($row['article_id']));
		
		// Copyeditor Assignment
		$authorSubmission->setCopyedId($row['copyed_id']);
		$authorSubmission->setCopyeditorId($row['copyeditor_id']);
		$authorSubmission->setCopyeditor($this->userDao->getUser($row['copyeditor_id']));
		$authorSubmission->setCopyeditorComments($row['copyeditor_comments']);
		$authorSubmission->setCopyeditorDateNotified($row['copyeditor_date_notified']);
		$authorSubmission->setCopyeditorDateCompleted($row['copyeditor_date_completed']);
		$authorSubmission->setCopyeditorDateAcknowledged($row['copyeditor_date_acknowledged']);
		$authorSubmission->setCopyeditorDateAuthorNotified($row['copyeditor_date_author_notified']);
		$authorSubmission->setCopyeditorDateAuthorCompleted($row['copyeditor_date_author_completed']);
		$authorSubmission->setCopyeditorDateAuthorAcknowledged($row['copyeditor_date_author_acknowledged']);
		
		return $authorSubmission;
	}
	
	/**
	 * Update an existing author submission.
	 * @param $authorSubmission AuthorSubmission
	 */
	function updateAuthorSubmission(&$authorSubmission) {
		// Update article
		if ($authorSubmission->getArticleId()) {
			$article = &$this->articleDao->getArticle($authorSubmission->getArticleId());
			
			// Only update fields that an author can actually edit.
			$article->setRevisedFileId($authorSubmission->getRevisedFileId());
			
			$this->articleDao->updateArticle($article);
		}
	
		
		// Update copyeditor assignment
		if ($authorSubmission->getCopyedId()) {
			$copyeditorSubmission = &$this->copyeditorSubmissionDao->getCopyeditorSubmission($authorSubmission->getCopyedId());

			// Only update fields that an author can actually edit.
			$copyeditorSubmission->setDateAuthorCompleted($authorSubmission->getCopyeditorDateAuthorCompleted());
		
			$this->copyeditorSubmissionDao->updateCopyeditorSubmission($copyeditorSubmission);
		}
	}
	
	/**
	 * Get all author submissions for an author.
	 * @param $authorId int
	 * @return array AuthorSubmissions
	 */
	function &getAuthorSubmissions($authorId, $journalId) {
		$authorSubmissions = array();
		
		$result = &$this->retrieve(
			'SELECT a.*, s.title as section_title, c.copyed_id, c.copyeditor_id, c.comments AS copyeditor_comments, c.date_notified AS copyeditor_date_notified, c.date_completed AS copyeditor_date_completed, c.date_acknowledged AS copyeditor_date_acknowledged, c.date_author_notified AS copyeditor_date_author_notified, c.date_author_completed AS copyeditor_date_author_completed, c.date_author_acknowledged AS copyeditor_date_author_acknowledged FROM articles a LEFT JOIN sections s ON (s.section_id = a.section_id) LEFT JOIN copyed_assignments c on (a.article_id = c.article_id) WHERE a.journal_id = ? AND a.user_id = ?',
			array($journalId, $authorId)
		);
		
		while (!$result->EOF) {
			$authorSubmissions[] = $this->_returnAuthorSubmissionFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		
		return $authorSubmissions;
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
			$decisions[] = array(
				'editDecisionId' => $result->fields['edit_decision_id'],
				'editorId' => $result->fields['editor_id'],
				'decision' => $result->fields['decision'],
				'dateDecided' => $result->fields['date_decided']
			);
			$result->moveNext();
		}
		$result->Close();
	
		return $decisions;
	}
	
}

?>
