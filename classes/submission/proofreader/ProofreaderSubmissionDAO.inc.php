<?php

/**
 * ProofreaderSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.proofreader
 *
 * Class for ProofreaderSubmission DAO.
 * Operations for retrieving and modifying ProofreaderSubmission objects.
 *
 * $Id$
 */

class ProofreaderSubmissionDAO extends DAO {

	/** Helper DAOs */
	var $articleDao;
	var $articleCommentDao;
	var $editAssignmentDao;
	var $proofAssignmentDao;
	var $layoutAssignmentDao;
	var $galleyDao;
	var $suppFileDao;

	/**
	 * Constructor.
	 */
	function ProofreaderSubmissionDAO() {
		parent::DAO();
		
		$this->articleDao = &DAORegistry::getDAO('ArticleDAO');
		$this->articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$this->proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$this->editAssignmentDao = DAORegistry::getDAO('EditAssignmentDAO');
		$this->layoutAssignmentDao = DAORegistry::getDAO('LayoutAssignmentDAO');
		$this->galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$this->suppFileDao = DAORegistry::getDAO('SuppFileDAO');
	}
	
	/**
	 * Retrieve a proofreader submission by article ID.
	 * @param $articleId int
	 * @return ProofreaderSubmission
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
	 * Internal function to return a ProofreaderSubmission object from a row.
	 * @param $row array
	 * @return ProofreaderSubmission
	 */
	function &_returnSubmissionFromRow(&$row) {
		$submission = &new ProofreaderSubmission();
		$this->articleDao->_articleFromRow($submission, $row);
		
		$submission->setMostRecentProofreadComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_PROOFREAD, $row['article_id']));
		$submission->setProofAssignment($this->proofAssignmentDao->getProofAssignmentByArticleId($row['article_id']));
		$submission->setSectionAbbrev($row['section_abbrev']);

		// Editor Assignment
		$submission->setEditor($this->editAssignmentDao->getEditAssignmentByArticleId($row['article_id']));

		// Layout reference information
		$submission->setLayoutAssignment($this->layoutAssignmentDao->getLayoutAssignmentByArticleId($row['article_id']));

		$submission->setGalleys($this->galleyDao->getGalleysByArticle($row['article_id']));

		$submission->setSuppFiles($this->suppFileDao->getSuppFilesByArticle($row['article_id']));

		$submission->setMostRecentLayoutComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_LAYOUT, $row['article_id']));

		return $submission;
	}
	
	/**
	 * Update an existing proofreader submission.
	 * @param $submission ProofreaderSubmission
	 */
	function updateSubmission(&$submission) {
		// Only update proofread-specific data
		$proofreadAssignment = $submission->getProofAssignment();
		$this->proofAssignmentDao->updateProofAssignment($proofAssignment);
	}
	
	/**
	 * Get set of proofreader assignments assigned to the specified proofreader.
	 * @param $proofreaderId int
	 * @param $active boolean true to select active assignments, false to select completed assignments
	 * @return array ProofreaderSubmission
	 */
	function &getSubmissions($proofreaderId, $journalId, $active = true) {
		$submissions = array();
		
		$sql = 'SELECT a.*, s.abbrev as section_abbrev, s.title AS section_title FROM articles a, proof_assignments p LEFT JOIN sections s ON s.section_id = a.section_id WHERE a.article_id = p.article_id AND p.proofreader_id = ? AND a.journal_id = ? AND p.date_proofreader_notified IS NOT NULL';
		
		if ($active) {
			$sql .= ' AND p.date_proofreader_completed IS NULL';
		} else {
			$sql .= ' AND p.date_proofreader_completed IS NOT NULL';		
		}

		$result = &$this->retrieve($sql, array($proofreaderId, $journalId));
		
		while (!$result->EOF) {
			$submissions[] = $this->_returnSubmissionFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		
		return $submissions;
	}

	/**
	 * Get count of active and complete assignments
	 * @param proofreaderId int
	 * @param journalId int
	 */
	function getSubmissionsCount($proofreaderId, $journalId) {
		$submissionsCount = array();
		$submissionsCount[0] = 0;
		$submissionsCount[1] = 0;

		$sql = 'SELECT p.date_proofreader_completed FROM articles a, proof_assignments p LEFT JOIN sections s ON s.section_id = a.section_id WHERE a.article_id = p.article_id AND p.proofreader_id = ? AND a.journal_id = ? AND p.date_proofreader_notified IS NOT NULL';

		$result = &$this->retrieve($sql, array($proofreaderId, $journalId));

		while (!$result->EOF) {
			if ($result->fields['date_proofreader_completed'] == null) {
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
