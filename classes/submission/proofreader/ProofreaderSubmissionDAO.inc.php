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
	var $proofAssignmentDao;

	/**
	 * Constructor.
	 */
	function ProofreaderSubmissionDAO() {
		parent::DAO();
		
		$this->articleDao = &DAORegistry::getDAO('ArticleDAO');
		$this->proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
	}
	
	/**
	 * Retrieve a proofreader submission by article ID.
	 * @param $articleId int
	 * @return ProofreaderSubmission
	 */
	function getSubmission($articleId, $journalId =  null) {
		if (isset($journalId)) {
			$result = &$this->retrieve(
				'SELECT a.*, s.title AS section_title
				FROM articles a
				LEFT JOIN sections s ON s.section_id = a.section_id
				WHERE article_id = ? AND a.journal_id = ?',
				array($articleId, $journalId)
			);
			
		} else {
			$result = &$this->retrieve(
				'SELECT a.*, s.title AS section_title
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
		$submission->setProofAssignment($this->proofAssignmentDao->getProofAssignmentByArticleId($row['article_id']));
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
		
		$result = &$this->retrieve(
			'SELECT a.*, s.title AS section_title
			FROM articles a, proof_assignments p
			LEFT JOIN sections s ON s.section_id = a.section_id
			WHERE a.article_id = p.article_id AND p.proofreader_id = ? AND a.journal_id = ?
			AND p.date_proofreader_completed IS ' . ($active ? 'NULL' : 'NOT NULL'),
			array($proofreaderId, $journalId)
		);
		
		while (!$result->EOF) {
			$submissions[] = $this->_returnSubmissionFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		
		return $submissions;
	}

}

?>
