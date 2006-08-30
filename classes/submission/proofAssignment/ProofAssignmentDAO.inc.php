<?php

/**
 * ProofAssignmentDAO.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
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
		$this->userDao = &DAORegistry::getDAO('UserDAO');
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

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnProofAssignmentFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
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

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnProofAssignmentFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
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

		$proofAssignment->setDateAuthorNotified($this->datetimeFromDB($row['date_author_notified']));
		$proofAssignment->setDateAuthorUnderway($this->datetimeFromDB($row['date_author_underway']));
		$proofAssignment->setDateAuthorCompleted($this->datetimeFromDB($row['date_author_completed']));
		$proofAssignment->setDateAuthorAcknowledged($this->datetimeFromDB($row['date_author_acknowledged']));

		$proofAssignment->setDateProofreaderNotified($this->datetimeFromDB($row['date_proofreader_notified']));
		$proofAssignment->setDateProofreaderUnderway($this->datetimeFromDB($row['date_proofreader_underway']));
		$proofAssignment->setDateProofreaderCompleted($this->datetimeFromDB($row['date_proofreader_completed']));
		$proofAssignment->setDateProofreaderAcknowledged($this->datetimeFromDB($row['date_proofreader_acknowledged']));

		$proofAssignment->setDateLayoutEditorNotified($this->datetimeFromDB($row['date_layouteditor_notified']));
		$proofAssignment->setDateLayoutEditorUnderway($this->datetimeFromDB($row['date_layouteditor_underway']));
		$proofAssignment->setDateLayoutEditorCompleted($this->datetimeFromDB($row['date_layouteditor_completed']));
		$proofAssignment->setDateLayoutEditorAcknowledged($this->datetimeFromDB($row['date_layouteditor_acknowledged']));

		$proofAssignment->setProofreaderFirstName($row['first_name']);
		$proofAssignment->setProofreaderLastName($row['last_name']);		
		$proofAssignment->setProofreaderEmail($row['email']);

		HookRegistry::call('ProofAssignmentDAO::_returnProofAssignmentFromRow', array(&$proofAssignment, &$row));

		return $proofAssignment;
	}
	
	/**
	 * Insert a new ProofAssignment.
	 * @param $proofAssignment ProofAssignment
	 */	
	function insertProofAssignment(&$proofAssignment) {
		$this->update(
			sprintf('INSERT INTO proof_assignments
				(article_id, proofreader_id, date_author_notified, date_author_underway, date_author_completed, date_author_acknowledged, date_proofreader_notified, date_proofreader_underway, date_proofreader_completed, date_proofreader_acknowledged, date_layouteditor_notified, date_layouteditor_underway, date_layouteditor_completed, date_layouteditor_acknowledged)
				VALUES
				(?, ?, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)',
				$this->datetimeToDB($proofAssignment->getDateAuthorNotified()),
				$this->datetimeToDB($proofAssignment->getDateAuthorUnderway()),
				$this->datetimeToDB($proofAssignment->getDateAuthorCompleted()),
				$this->datetimeToDB($proofAssignment->getDateAuthorAcknowledged()),
				$this->datetimeToDB($proofAssignment->getDateProofreaderNotified()),
				$this->datetimeToDB($proofAssignment->getDateProofreaderUnderway()),
				$this->datetimeToDB($proofAssignment->getDateProofreaderCompleted()),
				$this->datetimeToDB($proofAssignment->getDateProofreaderAcknowledged()),
				$this->datetimeToDB($proofAssignment->getDateLayoutEditorNotified()),
				$this->datetimeToDB($proofAssignment->getDateLayoutEditorUnderway()),
				$this->datetimeToDB($proofAssignment->getDateLayoutEditorCompleted()),
				$this->datetimeToDB($proofAssignment->getDateLayoutEditorAcknowledged())),
			array(
				$proofAssignment->getArticleId(),
				$proofAssignment->getProofreaderId()
			)
		);
		
		$proofAssignment->setProofId($this->getInsertProofId());
		return $proofAssignment->getProofId();
	}
	
	/**
	 * Update an existing proof assignment.
	 * @param $proofAssignment ProofAssignment
	 */
	function updateProofAssignment(&$proofAssignment) {
		return $this->update(
			sprintf('UPDATE proof_assignments
				SET	article_id = ?,
					proofreader_id = ?,
					date_author_notified = %s,
					date_author_underway = %s,
					date_author_completed = %s,
					date_author_acknowledged = %s,
					date_proofreader_notified = %s,
					date_proofreader_underway = %s,
					date_proofreader_completed = %s,
					date_proofreader_acknowledged = %s,
					date_layouteditor_notified = %s,
					date_layouteditor_underway = %s,
					date_layouteditor_completed = %s,
					date_layouteditor_acknowledged = %s
				WHERE proof_id = ?',
				$this->datetimeToDB($proofAssignment->getDateAuthorNotified()),
				$this->datetimeToDB($proofAssignment->getDateAuthorUnderway()),
				$this->datetimeToDB($proofAssignment->getDateAuthorCompleted()),
				$this->datetimeToDB($proofAssignment->getDateAuthorAcknowledged()),
				$this->datetimeToDB($proofAssignment->getDateProofreaderNotified()),
				$this->datetimeToDB($proofAssignment->getDateProofreaderUnderway()),
				$this->datetimeToDB($proofAssignment->getDateProofreaderCompleted()),
				$this->datetimeToDB($proofAssignment->getDateProofreaderAcknowledged()),
				$this->datetimeToDB($proofAssignment->getDateLayoutEditorNotified()),
				$this->datetimeToDB($proofAssignment->getDateLayoutEditorUnderway()),
				$this->datetimeToDB($proofAssignment->getDateLayoutEditorCompleted()),
				$this->datetimeToDB($proofAssignment->getDateLayoutEditorAcknowledged())),
			array(
				$proofAssignment->getArticleId(),
				$proofAssignment->getProofreaderId(),
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
