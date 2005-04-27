<?php

/**
 * ProofAssignmentsDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * Class for DAO relating proofreaders to articles.
 *
 * $Id$
 */

import('submission.proofAssignment.ProofAssignment');

class ProofAssignmentDAO extends DAO {

	var $userDao;

	/**
	 * Constructor.
	 */
	function ProofAssignmentDAO() {
		parent::DAO();
		$this->userDao = DAORegistry::getDAO('UserDAO');
	}
	
	/**
	 * Retrieve a proof assignment by id.
	 * @param $proofId int
	 * @return ProofAssignment
	 */
	function &getProofAssignment($proofId) {
		$result = &$this->retrieve(
			'SELECT p.*, u.first_name, u.last_name, u.email FROM proof_assignments p LEFT JOIN users u ON (p.proofreader_id = u.user_id) WHERE p.proof_id = ?',
			$proofId
			);
		
		if ($result->RecordCount() == 0) {
			return null;
		} else {
			return $this->_returnProofAssignmentFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Retrieve a proof assignment by article id.
	 * @param $articleId int
	 * @return ProofAssignment
	 */
	function &getProofAssignmentByArticleId($articleId) {
		$result = &$this->retrieve(
			'SELECT p.*, u.first_name, u.last_name, u.email FROM proof_assignments p LEFT JOIN users u ON (p.proofreader_id = u.user_id) WHERE p.article_id = ?',
			$articleId
			);
		
		if ($result->RecordCount() == 0) {
			return null;
		} else {
			return $this->_returnProofAssignmentFromRow($result->GetRowAssoc(false));
		}
	}

	/**
	 * Internal function to return a proof assignment object from a row.
	 * @param $row array
	 * @return ProofAssignment
	 */
	function &_returnProofAssignmentFromRow(&$row) {
		$proofAssignment = &new ProofAssignment();
		$proofAssignment->setProofId($row['proof_id']);
		$proofAssignment->setArticleId($row['article_id']);
		$proofAssignment->setProofreaderId($row['proofreader_id']);
		$proofAssignment->setDateSchedulingQueue($row['date_scheduling_queue']);

		$proofAssignment->setAuthorComments($row['author_comments']);
		$proofAssignment->setDateAuthorNotified($row['date_author_notified']);
		$proofAssignment->setDateAuthorUnderway($row['date_author_underway']);
		$proofAssignment->setDateAuthorCompleted($row['date_author_completed']);
		$proofAssignment->setDateAuthorAcknowledged($row['date_author_acknowledged']);

		$proofAssignment->setProofreaderComments($row['proofreader_comments']);
		$proofAssignment->setDateProofreaderNotified($row['date_proofreader_notified']);
		$proofAssignment->setDateProofreaderUnderway($row['date_proofreader_underway']);
		$proofAssignment->setDateProofreaderCompleted($row['date_proofreader_completed']);
		$proofAssignment->setDateProofreaderAcknowledged($row['date_proofreader_acknowledged']);

		$proofAssignment->setLayoutEditorComments($row['layouteditor_comments']);
		$proofAssignment->setDateLayoutEditorNotified($row['date_layouteditor_notified']);
		$proofAssignment->setDateLayoutEditorUnderway($row['date_layouteditor_underway']);
		$proofAssignment->setDateLayoutEditorCompleted($row['date_layouteditor_completed']);
		$proofAssignment->setDateLayoutEditorAcknowledged($row['date_layouteditor_acknowledged']);

		$proofAssignment->setProofreaderFirstName($row['first_name']);
		$proofAssignment->setProofreaderLastName($row['last_name']);		
		$proofAssignment->setProofreaderEmail($row['email']);

		return $proofAssignment;
	}
	
	/**
	 * Insert a new ProofAssignment.
	 * @param $proofAssignment ProofAssignment
	 */	
	function insertProofAssignment(&$proofAssignment) {
		$this->update(
			'INSERT INTO proof_assignments
				(article_id, proofreader_id, date_scheduling_queue, author_comments, date_author_notified, date_author_underway, date_author_completed, date_author_acknowledged, proofreader_comments, date_proofreader_notified, date_proofreader_underway, date_proofreader_completed, date_proofreader_acknowledged, layouteditor_comments, date_layouteditor_notified, date_layouteditor_underway, date_layouteditor_completed, date_layouteditor_acknowledged)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$proofAssignment->getArticleId(),
				$proofAssignment->getProofreaderId(),
				$proofAssignment->getDateSchedulingQueue(),
				$proofAssignment->getAuthorComments(),
				$proofAssignment->getDateAuthorNotified(),
				$proofAssignment->getDateAuthorUnderway(),
				$proofAssignment->getDateAuthorCompleted(),
				$proofAssignment->getDateAuthorAcknowledged(),
				$proofAssignment->getProofreaderComments(),
				$proofAssignment->getDateProofreaderNotified(),
				$proofAssignment->getDateProofreaderUnderway(),
				$proofAssignment->getDateProofreaderCompleted(),
				$proofAssignment->getDateProofreaderAcknowledged(),
				$proofAssignment->getLayoutEditorComments(),
				$proofAssignment->getDateLayoutEditorNotified(),
				$proofAssignment->getDateLayoutEditorUnderway(),
				$proofAssignment->getDateLayoutEditorCompleted(),
				$proofAssignment->getDateLayoutEditorAcknowledged()
			)
		);
		
		$proofAssignment->setProofId($this->getInsertProofId());
	}
	
	/**
	 * Update an existing proof assignment.
	 * @param $proofAssignment ProofAssignment
	 */
	function updateProofAssignment(&$proofAssignment) {
		return $this->update(
			'UPDATE proof_assignments
				SET	article_id = ?,
					proofreader_id = ?,
					date_scheduling_queue = ?,
					author_comments = ?,
					date_author_notified = ?,
					date_author_underway = ?,
					date_author_completed = ?,
					date_author_acknowledged = ?,
					proofreader_comments = ?,
					date_proofreader_notified = ?,
					date_proofreader_underway = ?,
					date_proofreader_completed = ?,
					date_proofreader_acknowledged = ?,
					layouteditor_comments = ?,
					date_layouteditor_notified = ?,
					date_layouteditor_underway = ?,
					date_layouteditor_completed = ?,
					date_layouteditor_acknowledged = ?
				WHERE proof_id = ?',
			array(
				$proofAssignment->getArticleId(),
				$proofAssignment->getProofreaderId(),
				$proofAssignment->getDateSchedulingQueue() ? str_replace("'",'',$proofAssignment->getDateSchedulingQueue()) : null,
				$proofAssignment->getAuthorComments(),
				$proofAssignment->getDateAuthorNotified() ? str_replace("'",'',$proofAssignment->getDateAuthorNotified()) : null,
				$proofAssignment->getDateAuthorUnderway() ? str_replace("'",'',$proofAssignment->getDateAuthorUnderway()) : null,
				$proofAssignment->getDateAuthorCompleted() ? str_replace("'",'',$proofAssignment->getDateAuthorCompleted()) : null,
				$proofAssignment->getDateAuthorAcknowledged() ? str_replace("'",'',$proofAssignment->getDateAuthorAcknowledged()) : null,
				$proofAssignment->getProofreaderComments(),
				$proofAssignment->getDateProofreaderNotified() ? str_replace("'",'',$proofAssignment->getDateProofreaderNotified()) : null,
				$proofAssignment->getDateProofreaderUnderway() ? str_replace("'",'',$proofAssignment->getDateProofreaderUnderway()) : null,
				$proofAssignment->getDateProofreaderCompleted() ? str_replace("'",'',$proofAssignment->getDateProofreaderCompleted()) : null,
				$proofAssignment->getDateProofreaderAcknowledged() ? str_replace("'",'',$proofAssignment->getDateProofreaderAcknowledged()) : null,
				$proofAssignment->getLayoutEditorComments(),
				$proofAssignment->getDateLayoutEditorNotified() ? str_replace("'",'',$proofAssignment->getDateLayouteditorNotified()) : null,
				$proofAssignment->getDateLayoutEditorUnderway() ? str_replace("'",'',$proofAssignment->getDateLayouteditorUnderway()) : null,
				$proofAssignment->getDateLayoutEditorCompleted() ? str_replace("'",'',$proofAssignment->getDateLayouteditorCompleted()) : null,
				$proofAssignment->getDateLayoutEditorAcknowledged() ? str_replace("'",'',$proofAssignment->getDateLayouteditorAcknowledged()) : null,
				$proofAssignment->getProofId()
			)
		);
	}
	
	/**
	 * Delete proof assignment.
	 * @param $proofId int
	 */
	function deleteProofAssignmentById($proofId) {
		return $this->update(
			'DELETE FROM proof_assignments WHERE proof_id = ?',
			$proofId
		);
	}
	
	/**
	 * Delete proof assignments by article.
	 * @param $articleId int
	 */
	function deleteProofAssignmentsByArticle($articleId) {
		return $this->update(
			'DELETE FROM proof_assignments WHERE article_id = ?',
			$articleId
		);
	}

	/**
	 * Get the ID of the last inserted proof assignment.
	 * @return int
	 */
	function getInsertProofId() {
		return $this->getInsertId('proof_assignments', 'proof_id');
	}
	
}

?>
