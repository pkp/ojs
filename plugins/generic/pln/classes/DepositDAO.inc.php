<?php

/**
 * @file plugins/generic/pln/DepositDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DepositDAO
 * @ingroup plugins_generic_pln
 *
 * @brief Operations for adding a PLN deposit
 */

class DepositDAO extends DAO {
  
	/** @var $_parentPluginName string Name of parent plugin */
	var $_parentPluginName;

	/**
	 * Constructor
	 */
	function DepositDAO($parentPluginName) {
		parent::DAO();
		$this->_parentPluginName = $parentPluginName;
	}

	/**
	 * Retrieve a deposit by deposit id.
	 * @param $journal_id int
	 * @param $deposit_id int
	 * @return Deposit
	 */
	function &getDepositById($journal_id, $deposit_id) {
		$result =& $this->retrieve(
			'SELECT * FROM pln_deposits WHERE journal_id = ? AND deposit_id = ?',
			array (
				(int) $journal_id,
				(int) $deposit_id
			)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnDepositFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}
	
	/**
	 * Retrieve a deposit by deposit uuid.
	 * @param $journal_id int
	 * @param $deposit_uuid string
	 * @return Deposit
	 */
	function &getDepositByUUID($journal_id, $deposit_uuid) {
		$result =& $this->retrieve(
			'SELECT * FROM pln_deposits WHERE journal_id = ? AND uuid = ?',
			array (
				(int) $journal_id,
				$deposit_uuid
			)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnDepositFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}
	
	/**
	 * Retrieve all deposits.
	 * @param $journal_id int
	 * @return array Deposit
	 */
	function &getDepositsByJournalId($journal_id) {
		$result =& $this->retrieve(
			'SELECT * FROM pln_deposits WHERE journal_id = ?',
			(int) $journal_id
		);

		$returner = array();
		while (!$result->EOF) {
			$returner[] =& $this->_returnDepositFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve all newly-created deposits (ones with new status)
	 * @return array Deposit
	 */
	function &getNew($journal_id) {
		$result =& $this->retrieve(
			'SELECT * FROM pln_deposits WHERE journal_id = ? AND status = ?',
			(int) $journal_id,
			(int) PLN_PLUGIN_DEPOSIT_STATUS_NEW
		);

		$returner = array();
		while (!$result->EOF) {
			$returner[] =& $this->_returnDepositFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		return $returner;
	}
	
	/**
	 * Retrieve all deposits that need packaging
	 * @return array Deposit
	 */
	function &getNeedTransferring($journal_id) {
		$deposits = $this->getDepositsByJournalId($journal_id);
		$returner = array();
		foreach ($deposits as $deposit) {
			if (!($deposit->getTransferredStatus() || $deposit->getLocalFailureStatus()))
				$returner[] = $deposit;
		}
		return $returner;
	}
	
	/**
	 * Retrieve all deposits that need packaging
	 * @return array Deposit
	 */
	function &getNeedPackaging($journal_id) {
		$deposits = $this->getDepositsByJournalId($journal_id);
		$returner = array();
		foreach ($deposits as $deposit) {
			if (!($deposit->getPackagedStatus() || $deposit->getLocalFailureStatus()))
				$returner[] = $deposit;
		}
		return $returner;
	}
	
	/**
	 * Retrieve all deposits that need packaging
	 * @return array Deposit
	 */
	function &getNeedStagingStatusUpdate($journal_id) {
		$deposits = $this->getDepositsByJournalId($journal_id);
		$returner = array();
		foreach ($deposits as $deposit) {
			if ($deposit->getTransferredStatus())
				$returner[] = $deposit;
		}
		return $returner;
	}
	
	/**
	 * Insert deposit object
	 * @param $deposit Deposit
	 * @return int inserted Deposit id
	 */
	function insertDeposit(&$deposit) {
		$ret = $this->update(
			sprintf('
				INSERT INTO pln_deposits
					(journal_id,
					uuid,
					status,
					date_status,
					date_created,
					date_modified)
				VALUES
					(?, ?, ?, %s, NOW(), %s)',
				$this->datetimeToDB($deposit->getLastStatusDate()),
				$this->datetimeToDB($deposit->getDateModified())
			),
			array(
				(int) $deposit->getJournalId(),
				$deposit->getUUID(),
				(int) $deposit->getStatus()
			)
		);
		$deposit->setId($this->getInsertDepositId());
		return $deposit->getId();
	}

	/**
	 * Update deposit
	 * @param $deposit Deposit
	 * @return int updated Deposit id
	 */
	function updateDeposit(&$deposit) {
		$ret = $this->update(
			sprintf('
				UPDATE pln_deposits SET
					journal_id = ?,
					uuid = ?,
					status = ?,
					date_status = %s,
					date_created = %s,
					date_modified = NOW()
				WHERE deposit_id = ?',
				$this->datetimeToDB($deposit->getLastStatusDate()),
				$this->datetimeToDB($deposit->getDateCreated())
			),
			array(
				(int) $deposit->getJournalId(),
				$deposit->getUUID(),
				(int) $deposit->getStatus(),
				(int) $deposit->getId()
			)
		);
		return $ret;
	}
	
	/**
	 * Delete deposit
	 * @param $deposit Deposit
	 * @return int deleted Deposit id
	 */
	function deleteDeposit(&$deposit) {
		$deposit_object_dao =& DAORegistry::getDAO('DepositObjectDAO');
		foreach($deposit->getDepositObjects() as $deposit_object) {
			$deposit_object_dao->deleteDepositObject($deposit_object);
		}
	
		$ret = $this->update(
			'DELETE from pln_deposits WHERE deposit_id = ?',
			(int) $deposit->getId()
		);
		return $ret;
	}
	
	/**
	 * Delete deposit
	 * @param $deposit Deposit
	 * @return int deleted Deposit id
	 */
	function deleteDepositsByJournalId($journal_id) {
		$deposits = $this->getDepositsByJournalId($journal_id);
		foreach($deposit as $deposit) {
			$this->deleteDeposit($deposit);
		}
	}

	/**
	 * Get the ID of the last inserted deposit.
	 * @return int
	 */
	function getInsertDepositId() {
		return $this->getInsertId('pln_deposits', 'deposit_id');
	}
  
	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return Deposit
	 */
	function newDataObject() {
		$plnPlugin =& PluginRegistry::getPlugin('generic', $this->_parentPluginName);
		$plnPlugin->import('classes.Deposit');
		return new Deposit();
	}

	/**
	 * Internal function to return a deposit from a row.
	 * @param $row array
	 * @return Deposit
	 */
	function &_returnDepositFromRow(&$row) {
		$deposit = $this->newDataObject();
		$deposit->setId($row['deposit_id']);
		$deposit->setJournalId($row['journal_id']);
		$deposit->setUUID($row['uuid']);
		$deposit->setStatus($row['status']);
		$deposit->setLastStatusDate($this->datetimeFromDB($row['date_status']));
		$deposit->setDateCreated($this->datetimeFromDB($row['date_created']));
		$deposit->setDateModified($this->datetimeFromDB($row['date_modified']));

		HookRegistry::call('DepositDAO::_returnDepositFromRow', array(&$deposit, &$row));

		return $deposit;
	}
}

?>
