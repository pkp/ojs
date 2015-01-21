<?php

/**
 * @file plugins/generic/pln/DepositDAO.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DepositDAO
 * @ingroup plugins_generic_pln
 *
 * @brief Operations for adding a PLN deposit
 */

class DepositDAO extends DAO {

	/**
	 * Constructor
	 */
	function DepositDAO() {
		parent::DAO();
	}

	/**
	 * Retrieve a deposit by deposit id.
	 * @param $journalId int
	 * @param $depositId int
	 * @return Deposit
	 */
	function &getDepositById($journalId, $depositId) {
		$result =& $this->retrieve(
			'SELECT * FROM pln_deposits WHERE journal_id = ? AND deposit_id = ?',
			array (
				(int) $journalId,
				(int) $depositId
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
	 * @param $journalId int
	 * @param $depositUuid string
	 * @return Deposit
	 */
	function &getDepositByUUID($journalId, $depositUuid) {
		$result =& $this->retrieve(
			'SELECT * FROM pln_deposits WHERE journal_id = ? AND uuid = ?',
			array (
				(int) $journalId,
				$depositUuid
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
	 * @param $journalId int
	 * @return array Deposit
	 */
	function &getDepositsByJournalId($journalId, $dbResultRange = null) {
		$result =& $this->retrieveRange(
			'SELECT * FROM pln_deposits WHERE journal_id = ? ORDER BY date_status DESC',
			(int) $journalId,
			$dbResultRange
		);
		$returner = new DAOResultFactory($result, $this, '_returnDepositFromRow');
		return $returner;
	}

	/**
	 * Retrieve all newly-created deposits (ones with new status)
	 * @return array Deposit
	 */
	function &getNew($journalId) {
		$result =& $this->retrieve(
			'SELECT * FROM pln_deposits WHERE journal_id = ? AND status = ?',
			array(
				(int) $journalId,
				(int) PLN_PLUGIN_DEPOSIT_STATUS_NEW
			)
		);
		$returner = new DAOResultFactory($result, $this, '_returnDepositFromRow');
		return $returner;
	}

	/**
	 * Retrieve all deposits that need packaging
	 * @return array Deposit
	 */
	function &getNeedTransferring($journalId) {
		$result =& $this->retrieve(
			'SELECT * FROM pln_deposits AS d WHERE d.journal_id = ? AND d.status & ? = 0 AND d.status & ? = 0',
			array (
				(int) $journalId,
				(int) PLN_PLUGIN_DEPOSIT_STATUS_TRANSFERRED,
				(int) PLN_PLUGIN_DEPOSIT_STATUS_LOCAL_FAILURE
			)
		);
		$returner = new DAOResultFactory($result, $this, '_returnDepositFromRow');
		return $returner;
	}

	/**
	 * Retrieve all deposits that need packaging
	 * @return array Deposit
	 */
	function &getNeedPackaging($journalId) {
		$result =& $this->retrieve(
			'SELECT * FROM pln_deposits AS d WHERE d.journal_id = ? AND d.status & ? = 0 AND d.status & ? = 0',
			array(
				(int) $journalId,
				(int) PLN_PLUGIN_DEPOSIT_STATUS_PACKAGED,
				(int) PLN_PLUGIN_DEPOSIT_STATUS_LOCAL_FAILURE
			)
		);
		$returner = new DAOResultFactory($result, $this, '_returnDepositFromRow');
		return $returner;
	}

	/**
	 * Retrieve all deposits that need packaging
	 * @return array Deposit
	 */
	function &getNeedStagingStatusUpdate($journalId) {
		$result =& $this->retrieve(
			'SELECT * FROM pln_deposits AS d WHERE d.journal_id = ? AND d.status & ? <> 0 AND d.status & ? = 0',
			array (
				(int) $journalId,
				(int) PLN_PLUGIN_DEPOSIT_STATUS_TRANSFERRED,
				(int) PLN_PLUGIN_DEPOSIT_STATUS_LOCAL_FAILURE
			)
		);
		$returner = new DAOResultFactory($result, $this, '_returnDepositFromRow');
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
	}

	/**
	 * Delete deposit
	 * @param $deposit Deposit
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
	}

	/**
	 * Delete deposits by journal id
	 * @param $journalId
	 */
	function deleteDepositsByJournalId($journalId) {
		$deposits = $this->getDepositsByJournalId($journalId);
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
	function _newDataObject() {
		$plnPlugin =& PluginRegistry::getPlugin('generic', PLN_PLUGIN_NAME);
		return new Deposit(null);
	}

	/**
	 * Internal function to return a deposit from a row.
	 * @param $row array
	 * @return Deposit
	 */
	function &_returnDepositFromRow(&$row) {
		$deposit = $this->_newDataObject();
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
