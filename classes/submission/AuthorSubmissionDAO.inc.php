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

	var $authorDao;
	var $userDao;
	var $reviewAssignmentDao;
	var $articleFileDao;
	var $suppFileDao;

	/**
	 * Constructor.
	 */
	function AuthorSubmissionDAO() {
		parent::DAO();
		$this->authorDao = DAORegistry::getDAO('AuthorDAO');
		$this->userDao = DAORegistry::getDAO('UserDAO');
		$this->reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->articleFileDao = DAORegistry::getDAO('ArticleFileDAO');
		$this->suppFileDao = DAORegistry::getDAO('SuppFileDAO');
	}
	
	/**
	 * Retrieve a author submission by article ID.
	 * @param $articleId int
	 * @return AuthorSubmission
	 */
	function &getAuthorSubmission($articleId) {
		$result = &$this->retrieve(
			'SELECT a.*, e.editor_id, s.title as section_title from articles a LEFT JOIN edit_assignments e on (a.article_id = e.article_id) LEFT JOIN sections s ON (s.section_id = a.section_id) WHERE a.article_id = ?', $articleId
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
		$authorSubmission->setArticleId($row['article_id']);

		$authorSubmission->setEditor($this->userDao->getUser($row['editor_id']));
		$authorSubmission->setReviewAssignments($this->reviewAssignmentDao->getReviewAssignmentsByArticleId($row['article_id']));
		
		$authorSubmission->setSubmissionFile($this->articleFileDao->getSubmissionArticleFile($row['article_id']));
		$authorSubmission->setSuppFiles($this->suppFileDao->getSuppFilesByArticle($row['article_id']));
		
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
		
		$authorSubmission->setAuthors($this->authorDao->getAuthorsByArticle($row['article_id']));
		
		return $authorSubmission;
	}
	
	/**
	 * Update an existing author submission.
	 * @param $authorSubmission AuthorSubmission
	 */
	function updateAuthorSubmission(&$authorSubmission) {
		
	}
	
	/**
	 * Get all author submissions for an author.
	 * @param $authorId int
	 * @return array AuthorSubmissions
	 */
	function &getAuthorSubmissions($authorId, $journalId) {
		$authorSubmissions = array();
		
		$result = &$this->retrieve(
			'SELECT a.*, e.editor_id, s.title as section_title from articles a LEFT JOIN edit_assignments e on (a.article_id = e.article_id) LEFT JOIN sections s ON (s.section_id = a.section_id) WHERE a.journal_id = ? AND a.user_id = ?',
			array($journalId, $authorId)
		);
		
		while (!$result->EOF) {
			$authorSubmissions[] = $this->_returnAuthorSubmissionFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		
		return $authorSubmissions;
	}
	
}

?>
