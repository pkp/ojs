<?php

/**
 * CopyeditorSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * Class for CopyeditorSubmission DAO.
 * Operations for retrieving and modifying CopyeditorSubmission objects.
 *
 * $Id$
 */

class CopyeditorSubmissionDAO extends DAO {

	var $authorDao;
	var $userDao;

	/**
	 * Constructor.
	 */
	function CopyeditorSubmissionDAO() {
		parent::DAO();
		$this->authorDao = DAORegistry::getDAO('AuthorDAO');
		$this->userDao = DAORegistry::getDAO('UserDAO');
	}
	
	/**
	 * Retrieve a copyeditor submission by article ID.
	 * @param $articleId int
	 * @return CopyeditorSubmission
	 */
	function &getCopyeditorSubmission($articleId) {
		$result = &$this->retrieve(
			'SELECT a.*, e.editor_id, c.*, s.title as section_title FROM articles a LEFT JOIN edit_assignments e on (a.article_id = e.article_id) LEFT JOIN sections s ON (s.section_id = a.section_id) LEFT JOIN copyed_assignments c ON (c.article_id = a.article_id) WHERE a.article_id = ?',
			$articleId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnCopyeditorSubmissionFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Internal function to return a CopyeditorSubmission object from a row.
	 * @param $row array
	 * @return CopyeditorSubmission
	 */
	function &_returnCopyeditorSubmissionFromRow(&$row) {
		$copyeditorSubmission = &new CopyeditorSubmission();

		$copyeditorSubmission->setEditor($this->userDao->getUser($row['editor_id']));
		
		$copyeditorSubmission->setCopyedId($row['copyed_id']);
		$copyeditorSubmission->setCopyeditorId($row['copyeditor_id']);
		$copyeditorSubmission->setComments($row['comments']);
		$copyeditorSubmission->setDateNotified($row['date_notified']);
		$copyeditorSubmission->setDateCompleted($row['date_completed']);
		$copyeditorSubmission->setDateAcknowledged($row['date_acknowledged']);
		$copyeditorSubmission->setDateAuthorNotified($row['date_author_notified']);
		$copyeditorSubmission->setDateAuthorCompleted($row['date_author_completed']);
		$copyeditorSubmission->setDateAuthorAcknowledged($row['date_author_acknowledged']);
		$copyeditorSubmission->setDateFinalNotified($row['date_final_notified']);
		$copyeditorSubmission->setDateFinalCompleted($row['date_final_completed']);
		$copyeditorSubmission->setDateFinalAcknowledged($row['date_final_acknowledged']);

		//$copyeditorSubmission->setSubmissionFile($this->articleFileDao->getSubmissionArticleFile($row['article_id']));
		//$copyeditorSubmission->setSuppFiles($this->suppFileDao->getSuppFilesByArticle($row['article_id']));
		
		// Article attributes
		$copyeditorSubmission->setArticleId($row['article_id']);
		$copyeditorSubmission->setUserId($row['user_id']);
		$copyeditorSubmission->setJournalId($row['journal_id']);
		$copyeditorSubmission->setSectionId($row['section_id']);
		$copyeditorSubmission->setSectionTitle($row['section_title']);
		$copyeditorSubmission->setTitle($row['title']);
		$copyeditorSubmission->setAbstract($row['abstract']);
		$copyeditorSubmission->setDiscipline($row['discipline']);
		$copyeditorSubmission->setSubjectClass($row['subject_class']);
		$copyeditorSubmission->setSubject($row['subject']);
		$copyeditorSubmission->setCoverageGeo($row['coverage_geo']);
		$copyeditorSubmission->setCoverageChron($row['coverage_chron']);
		$copyeditorSubmission->setCoverageSample($row['coverage_sample']);
		$copyeditorSubmission->setType($row['type']);
		$copyeditorSubmission->setLanguage($row['language']);
		$copyeditorSubmission->setSponsor($row['sponsor']);
		$copyeditorSubmission->setCommentsToEditor($row['comments_to_ed']);
		$copyeditorSubmission->setDateSubmitted($row['date_submitted']);
		$copyeditorSubmission->setStatus($row['status']);
		$copyeditorSubmission->setSubmissionProgress($row['submission_progress']);
		
		$copyeditorSubmission->setAuthors($this->authorDao->getAuthorsByArticle($row['article_id']));
		
		return $copyeditorSubmission;
	}
	
	/**
	 * Insert a new CopyeditorSubmission.
	 * @param $copyeditorSubmission CopyeditorSubmission
	 */	
	function insertCopyeditorSubmission(&$copyeditorSubmission) {
		$this->update(
			'INSERT INTO copyed_assignments
				(article_id, copyeditor_id, comments, date_notified, date_completed, date_acknowledged, date_author_notified, date_author_completed, date_author_acknowledged, date_final_notified, date_final_completed, date_final_acknowledged)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$copyeditorSubmission->getArticleId(),
				$copyeditorSubmission->getCopyeditorId(),
				$copyeditorSubmission->getComments(),
				$copyeditorSubmission->getDateNotified(),
				$copyeditorSubmission->getDateCompleted(),
				$copyeditorSubmission->getDateAcknowledged(),
				$copyeditorSubmission->getDateAuthorNotified(),
				$copyeditorSubmission->getDateAuthorCompleted(),
				$copyeditorSubmission->getDateAuthorAcknowledged(),
				$copyeditorSubmission->getDateFinalNotified(),
				$copyeditorSubmission->getDateFinalCompleted(),
				$copyeditorSubmission->getDateFinalAcknowledged()
			)
		);
		
		$copyeditorSubmission->setCopyedId($this->getInsertCopyedId());
	}

	/**
	 * Update an existing copyeditor submission.
	 * @param $copyeditorSubmission CopyeditorSubmission
	 */
	function updateCopyeditorSubmission(&$copyeditorSubmission) {
		$this->update(
			'UPDATE copyed_assignments
				SET
					article_id = ?,
					copyeditor_id = ?,
					comments = ?,
					date_notified = ?,
					date_completed = ?,
					date_acknowledged = ?,
					date_author_notified = ?,
					date_author_completed = ?,
					date_author_acknowledged = ?,
					date_final_notified = ?,
					date_final_completed = ?,
					date_final_acknowledged = ?
				WHERE copyed_id = ?',
			array(
				$copyeditorSubmission->getArticleId(),
				$copyeditorSubmission->getCopyeditorId(),
				$copyeditorSubmission->getComments(),
				$copyeditorSubmission->getDateNotified(),
				$copyeditorSubmission->getDateCompleted(),
				$copyeditorSubmission->getDateAcknowledged(),
				$copyeditorSubmission->getDateAuthorNotified(),
				$copyeditorSubmission->getDateAuthorCompleted(),
				$copyeditorSubmission->getDateAuthorAcknowledged(),
				$copyeditorSubmission->getDateFinalNotified(),
				$copyeditorSubmission->getDateFinalCompleted(),
				$copyeditorSubmission->getDateFinalAcknowledged(),
				$copyeditorSubmission->getCopyedId()
			)
		);
	}
	
	/**
	 * Get all submissions for a copyeditor of a journal.
	 * @param $copyeditorId int
	 * @param $journalId int
	 * @return array CopyeditorSubmissions
	 */
	function &getCopyeditorSubmissionsByCopyeditorId($copyeditorId, $journalId) {
		$copyeditorSubmissions = array();
		
		$result = &$this->retrieve(
			'SELECT a.*, c.*, e.editor_id, s.title as section_title FROM articles a LEFT JOIN edit_assignments e ON (e.article_id = a.article_id) LEFT JOIN sections s ON (s.section_id = a.section_id) LEFT JOIN copyed_assignments c ON (c.article_id = a.article_id) WHERE a.journal_id = ? AND c.copyeditor_id = ?',
			array($journalId, $copyeditorId)
		);
		
		while (!$result->EOF) {
			$copyeditorSubmissions[] = $this->_returnCopyeditorSubmissionFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		
		return $copyeditorSubmissions;
	}
	
	/**
	 * Get the ID of the last inserted copyeditor assignment.
	 * @return int
	 */
	function getInsertCopyedId() {
		return $this->getInsertId('copyed_assignments', 'copyed_id');
	}
	
}

?>
