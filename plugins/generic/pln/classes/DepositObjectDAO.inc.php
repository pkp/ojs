<?php

/**
 * @file plugins/generic/pln/DepositObjectDAO.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DepositObjectDAO
 * @ingroup plugins_generic_pln
 *
 * @brief Operations for adding a PLN deposit object
 */

class DepositObjectDAO extends DAO {

	/**
	 * Constructor
	 */
	function DepositObjectDAO() {
		parent::DAO();
	}

	/**
	 * Retrieve a deposit object by deposit object id.
	 * @param $journalId int
	 * @param $depositId int
	 * @return DepositObject
	 */
	function &getDepositObject($journalId, $depositObjectId) {
		$result =& $this->retrieve(
			'SELECT * FROM pln_deposit_objects WHERE journal_id = ? and deposit_object_id = ?',
			array(
				(int) $journalId,
				(int) $depositObjectId
			)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnDepositObjectFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve all deposit objects by deposit id.
	 * @param $journalId int
	 * @param $depositId int
	 * @return array DepositObject ordered by sequence
	 */
	function &getByDepositId($journalId, $depositId) {
		$result =& $this->retrieve(
			'SELECT * FROM pln_deposit_objects WHERE journal_id = ? AND deposit_id = ?',
			array (
				(int) $journalId,
				(int) $depositId
			)
		);
		$returner = new DAOResultFactory($result, $this, '_returnDepositObjectFromRow');
		return $returner;
	}

	/**
	 * Retrieve all deposit objects with no deposit id.
	 * @param $journalId int
	 * @return array DepositObject ordered by sequence
	 */
	function &getNew($journalId) {
		$result =& $this->retrieve(
			'SELECT * FROM pln_deposit_objects WHERE journal_id = ? AND deposit_id is null',
			(int) $journalId
		);
		$returner = new DAOResultFactory($result, $this, '_returnDepositObjectFromRow');
		return $returner;
	}

	/**
	 * Retrieve all deposit objects with no deposit id.
	 * @param $journalId int
	 * @param $objectType string
	 */
	function markHavingUpdatedContent($journalId, $objectType) {
		$objects = array();
		$depositDao =& DAORegistry::getDAO('DepositDAO');
	
		switch ($objectType) {
			case PLN_PLUGIN_DEPOSIT_OBJECT_ARTICLE:
				$result =& $this->retrieve(
					'SELECT pdo.deposit_object_id, a.last_modified FROM pln_deposit_objects pdo
					LEFT JOIN articles a ON pdo.object_id = a.article_id 
					WHERE a.journal_id = ? AND pdo.journal_id = ? AND pdo.date_modified < a.last_modified',
					array (
						(int) $journalId,
						(int) $journalId
					)
				);
				while (!$result->EOF) {
					$row = $result->GetRowAssoc(false);
					$depositObject =& $this->getDepositObjectId($journalId,$row['deposit_object_id']);
					$depositObject->setDateModified($row['last_modified']);
					$this->updateDepositObject($depositObject);
					$deposit =& $depositDao->getDepositById($journalId, $depositObject->getDepositId());
					$deposit->setNewStatus();
					$deposit->setUpdateStatus();
					$depositDao->updateDeposit($deposit);
					$result->MoveNext();
				}
				$result->Close();
				break;
			case PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE:
				$result =& $this->retrieve(
					'SELECT DISTINCT pdo.deposit_object_id, i.last_modified as issue_modified, a.last_modified as article_modified
					FROM issues i
					LEFT JOIN pln_deposit_objects pdo ON pdo.object_id = i.issue_id
					LEFT JOIN published_articles pa ON pa.issue_id = i.issue_id
					LEFT JOIN articles a ON a.article_id = pa.article_id
					WHERE (pdo.date_modified < a.last_modified OR pdo.date_modified < i.last_modified)
					AND (pdo.journal_id = ?)
					GROUP BY pdo.deposit_object_id',
					(int) $journalId
				);
				while (!$result->EOF) {
					$row = $result->GetRowAssoc(false);
					$depositObject =& $this->getDepositObject($journalId,$row['deposit_object_id']);
					
					if ($row['issue_modified'] > $row['article_modified']) {
						$depositObject->setDateModified($row['issue_modified']);
					} else {
						$depositObject->setDateModified($row['article_modified']);
					}

					$this->updateDepositObject($depositObject);
					$deposit =& $depositDao->getDepositById($journalId, $depositObject->getDepositId());
					$deposit->setNewStatus();
					$deposit->setUpdateStatus();
					$depositDao->updateDeposit($deposit);
					$result->MoveNext();
				}
				$result->Close();
				break;
		}
	}

	/**
	 * Create a new deposit object for OJS content that doesn't yet have one
	 * @return array DepositObject ordered by sequence
	 */
	function &createNew($journalId, $objectType) {
		$objects = array();
	
		switch ($objectType) {
			case PLN_PLUGIN_DEPOSIT_OBJECT_ARTICLE:
				$published_article_dao =& DAORegistry::getDAO('PublishedArticleDAO');
				$result =& $this->retrieve(
					'SELECT pa.article_id FROM published_articles pa
					LEFT JOIN articles a ON pa.article_id = a.article_id 
					LEFT JOIN pln_deposit_objects pdo ON pa.article_id = pdo.object_id
					WHERE a.journal_id = ? AND pdo.object_id is null',
					(int) $journalId
				);
				while (!$result->EOF) {
					$row = $result->GetRowAssoc(false);
					$objects[] =& $published_article_dao->getPublishedArticleByArticleId($row['article_id']);
					$result->MoveNext();
				}
				$result->Close();
				break;
			case PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE:
				$issue_dao =& DAORegistry::getDAO('IssueDAO');
				$result =& $this->retrieve(
					'SELECT i.issue_id FROM issues i
					LEFT JOIN pln_deposit_objects pdo ON pdo.object_id = i.issue_id
					WHERE i.journal_id = ? AND i.published = 1 AND pdo.object_id is null',
					(int) $journalId
				);
				while (!$result->EOF) {
					$row = $result->GetRowAssoc(false);
					$objects[] =& $issue_dao->getIssueById($row['issue_id']);
					$result->MoveNext();
				}
				$result->Close();
				break;
		}
		$depositObjects = array();
		foreach($objects as $object) {
			$depositObject = $this->_newDataObject();
			$depositObject->setContent($object);
			$depositObject->setJournalId($journalId);
			$this->insertDepositObject($depositObject);
			$depositObjects[] = $depositObject;
		}
		return $depositObjects;
	}

	/**
	 * Insert deposit object
	 * @param $depositObject DepositObject
	 * @return int inserted DepositObject id
	 */
	function insertDepositObject(&$depositObject) {
		$ret = $this->update(
			sprintf('
				INSERT INTO pln_deposit_objects
					(journal_id,
					object_id,
					object_type,
					deposit_id,
					date_created,
					date_modified)
				VALUES
					(?, ?, ?, ?, NOW(), %s)',
				$this->datetimeToDB($depositObject->getDateModified())
			),
			array(
				(int) $depositObject->getJournalId(),
				(int) $depositObject->getObjectId(),
				$depositObject->getObjectType(),
				$depositObject->getDepositId()
			)
		);
		
		$depositObject->setId($this->getInsertDepositObjectId());
		return $depositObject->getId();
	}

	/**
	 * Update deposit object
	 * @param $depositObject DepositObject
	 */
	function updateDepositObject(&$depositObject) {
		$ret = $this->update(
			sprintf('
				UPDATE pln_deposit_objects SET
					journal_id = ?,
					object_type = ?,
					object_id = ?,
					deposit_id = ?,
					date_created = %s,
					date_modified = NOW()
				WHERE deposit_object_id = ?',
				$this->datetimeToDB($depositObject->getDateCreated())
			),
			array(
				(int) $depositObject->getJournalId(),
				$depositObject->getObjectType(),
				(int) $depositObject->getObjectId(),
				$depositObject->getDepositId(),
				(int) $depositObject->getId()
			)
		);
	}

	/**
	 * Delete deposit object
	 * @param $depositObject Deposit
	 */
	function deleteDepositObject(&$depositObject) {
		$ret = $this->update(
			'DELETE from pln_deposit_objects WHERE deposit_object_id = ?',
			(int) $depositObject->getId()
		);
	}

	/**
	 * Get the ID of the last inserted deposit object.
	 * @return int
	 */
	function getInsertDepositObjectId() {
		return $this->getInsertId('pln_deposit_objects', 'object_id');
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return DepositObject
	 */
	function _newDataObject() {
		$plnPlugin =& PluginRegistry::getPlugin('generic', PLN_PLUGIN_NAME);
		return new DepositObject();
	}

	/**
	 * Internal function to return a deposit object from a row.
	 * @param $row array
	 * @return DepositObject
	 */
	function &_returnDepositObjectFromRow(&$row) {
		$depositObject = $this->_newDataObject();
		$depositObject->setId($row['deposit_object_id']);
		$depositObject->setJournalId($row['journal_id']);
		$depositObject->setObjectType($row['object_type']);
		$depositObject->setObjectId($row['object_id']);
		$depositObject->setDepositId($row['deposit_id']);
		$depositObject->setDateCreated($this->datetimeFromDB($row['date_created']));
		$depositObject->setDateModified($this->datetimeFromDB($row['date_modified']));

		HookRegistry::call('DepositObjectDAO::_returnDepositObjectFromRow', array(&$depositObject, &$row));

		return $depositObject;
	}
}
?>
