<?php

/**
 * @file plugins/generic/pln/DepositObjectDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DepositObjectDAO
 * @ingroup plugins_generic_pln
 *
 * @brief Operations for adding a PLN deposit object
 */

class DepositObjectDAO extends DAO {
  
	/** @var $_parentPluginName string Name of parent plugin */
	var $_parentPluginName;

	/**
	 * Constructor
	 */
	function DepositObjectDAO($parentPluginName) {
		parent::DAO();
		$this->_parentPluginName = $parentPluginName;
	}

	/**
	 * Retrieve a deposit object by deposit object id.
	 * @param $journal_id int
	 * @param $deposit_id int
	 * @return DepositObject
	 */
	function &getDepositObject($journal_id, $deposit_object_id) {
		$result =& $this->retrieve(
			'SELECT * FROM pln_deposit_objects WHERE journal_id = ? and deposit_object_id = ?',
			array(
				(int) $journal_id,
				(int) $deposit_object_id
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
	 * @param $journal_id int
	 * @param $deposit_id int
	 * @return array DepositObject ordered by sequence
	 */
	function &getByDepositId($journal_id, $deposit_id) {
		$result =& $this->retrieve(
			'SELECT * FROM pln_deposit_objects WHERE journal_id = ? AND deposit_id = ?',
			array (
				(int) $journal_id,
				(int) $deposit_id
			)
		);

		$returner = array();
		while (!$result->EOF) {
			$returner[] =& $this->_returnDepositObjectFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		return $returner;
	}
	
	/**
	 * Retrieve all deposit objects with no deposit id.
	 * @param $journal_id int
	 * @return array DepositObject ordered by sequence
	 */
	function &getNew($journal_id) {
		$result =& $this->retrieve(
			'SELECT * FROM pln_deposit_objects WHERE journal_id = ? AND deposit_id is null',
			(int) $journal_id
		);

		$returner = array();
		while (!$result->EOF) {
			$returner[] =& $this->_returnDepositObjectFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		return $returner;
	}
	
	/**
	 * Retrieve all deposit objects with no deposit id.
	 * @param $journal_id int
	 */
	function markHavingUpdatedContent($journal_id, $object_type) {
	
		$result = "";
		$objects = array();
		$object_types = unserialize(PLN_PLUGIN_DEPOSIT_SUPPORTED_OBJECTS);
		$deposit_dao =& DAORegistry::getDAO('DepositDAO');
	
		switch ($object_type) {
			case $object_types[PLN_PLUGIN_DEPOSIT_OBJECT_ARTICLE]:
				$result =& $this->retrieve(
					'SELECT pdo.deposit_object_id, a.last_modified FROM pln_deposit_objects pdo
					LEFT JOIN articles a ON pdo.object_id = a.article_id 
					WHERE a.journal_id = ? AND pdo.journal_id = ? AND pdo.date_modified < a.last_modified',
					array (
						(int) $journal_id,
						(int) $journal_id
					)
				);
				while (!$result->EOF) {
					$row = $result->GetRowAssoc(false);
					$deposit_object =& $this->getDepositObjectId($journal_id,$row['deposit_object_id']);
					$deposit_object->setDateModified($row['last_modified']);
					$this->updateDepositObject($deposit_object);
					$deposit =& $deposit_dao->getDepositById($journal_id, $deposit_object->getDepositId());
					$deposit->setNewStatus();
					$deposit->setUpdateStatus();
					$deposit_dao->updateDeposit($deposit);
					$result->MoveNext();
				}
				break;
			case $object_types[PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE]:
				$result =& $this->retrieve(
					'SELECT DISTINCT pdo.deposit_object_id, i.last_modified as issue_modified, a.last_modified as article_modified
					FROM issues i
					LEFT JOIN pln_deposit_objects pdo ON pdo.object_id = i.issue_id
					LEFT JOIN published_articles pa ON pa.issue_id = i.issue_id
					LEFT JOIN articles a ON a.article_id = pa.article_id
					WHERE (pdo.date_modified < a.last_modified OR pdo.date_modified < i.last_modified)
					AND (pdo.journal_id = ?)
					GROUP BY pdo.deposit_object_id',
					(int) $journal_id
				);
				while (!$result->EOF) {
					$row = $result->GetRowAssoc(false);
					$deposit_object =& $this->getDepositObject($journal_id,$row['deposit_object_id']);
					
					if ($row['issue_modified'] > $row['article_modified']) {
						$deposit_object->setDateModified($row['issue_modified']);
					} else {
						$deposit_object->setDateModified($row['article_modified']);
					}

					$this->updateDepositObject($deposit_object);
					$deposit =& $deposit_dao->getDepositById($journal_id, $deposit_object->getDepositId());
					$deposit->setNewStatus();
					$deposit->setUpdateStatus();
					$deposit_dao->updateDeposit($deposit);
					$result->MoveNext();
				}
				break;
			default:
		}
		
		$result->Close();

	}
	
	
	/**
	 * Create new deposit objects for OJS content that doesn't yet have one
	 * @return array DepositObject ordered by sequence
	 */
	function &createNew($journal_id, $object_type) {
	
		$result = "";
		$objects = array();
		$object_types = unserialize(PLN_PLUGIN_DEPOSIT_SUPPORTED_OBJECTS);
	
		switch ($object_type) {
			case $object_types[PLN_PLUGIN_DEPOSIT_OBJECT_ARTICLE]:
				$published_article_dao =& DAORegistry::getDAO('PublishedArticleDAO');
				$result =& $this->retrieve(
					'SELECT pa.article_id FROM published_articles pa
					LEFT JOIN articles a ON pa.article_id = a.article_id 
					LEFT JOIN pln_deposit_objects pdo ON pa.article_id = pdo.object_id
					WHERE a.journal_id = ? AND pdo.object_id is null',
					(int) $journal_id
				);
				while (!$result->EOF) {
					$row = $result->GetRowAssoc(false);
					$objects[] =& $published_article_dao->getPublishedArticleByArticleId($row['article_id']);
					$result->MoveNext();
				}
				break;
			case $object_types[PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE]:
				$issue_dao =& DAORegistry::getDAO('IssueDAO');
				$result =& $this->retrieve(
					'SELECT i.issue_id FROM issues i
					LEFT JOIN pln_deposit_objects pdo ON pdo.object_id = i.issue_id
					WHERE i.journal_id = ? AND i.published = 1 AND pdo.object_id is null',
					(int) $journal_id
				);
				while (!$result->EOF) {
					$row = $result->GetRowAssoc(false);
					$objects[] =& $issue_dao->getIssueById($row['issue_id']);
					$result->MoveNext();
				}
				break;
			default:
		}
		
		$result->Close();
		
		$deposit_objects = array();
		foreach($objects as $object) {
			$deposit_object = $this->newDataObject();
			$deposit_object->setContent($object);
			$deposit_object->setJournalId($journal_id);
			$this->insertDepositObject($deposit_object);
			$deposit_objects[] = $deposit_object;
		}
		return $deposit_objects;
	}

	/**
	 * Insert deposit object
	 * @param $deposit_object DepositObject
	 * @return int inserted DepositObject id
	 */
	function insertDepositObject(&$deposit_object) {
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
				$this->datetimeToDB($deposit_object->getDateModified())
			),
			array(
				(int) $deposit_object->getJournalId(),
				(int) $deposit_object->getObjectId(),
				$deposit_object->getObjectType(),
				$deposit_object->getDepositId()
			)
		);
		
		$deposit_object->setId($this->getInsertDepositObjectId());
		return $deposit_object->getId();
	}

	/**
	 * Update deposit object
	 * @param $deposit_object DepositObject
	 * @return int updated DepositObject id
	 */
	function updateDepositObject(&$deposit_object) {
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
				$this->datetimeToDB($deposit_object->getDateCreated())
			),
			array(
				(int) $deposit_object->getJournalId(),
				$deposit_object->getObjectType(),
				(int) $deposit_object->getObjectId(),
				$deposit_object->getDepositId(),
				(int) $deposit_object->getId()
			)
		);
		return $ret;
	}
	
	/**
	 * Delete deposit object
	 * @param $deposit_object Deposit
	 * @return int deleted Deposit id
	 */
	function deleteDepositObject(&$deposit_object) {
		$ret = $this->update(
			'DELETE from pln_deposit_objects WHERE deposit_object_id = ?',
			(int) $deposit_object->getId()
		);
		return $ret;
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
	function newDataObject() {
		$plnPlugin =& PluginRegistry::getPlugin('generic', $this->_parentPluginName);
		$plnPlugin->import('classes.DepositObject');
		return new DepositObject();
	}

	/**
	 * Internal function to return a deposit object from a row.
	 * @param $row array
	 * @return DepositObject
	 */
	function &_returnDepositObjectFromRow(&$row) {
		$depositObject = $this->newDataObject();
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
