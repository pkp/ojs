<?php

/**
 * @file plugins/generic/pln/classes/Deposit.inc.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Deposit
 * @ingroup plugins_generic_pln
 *
 * @brief Container for deposit objects that are submitted to a PLN
 */

class Deposit extends DataObject {
	
	/**
	 * Constructor
	 * @param string
	 * @return Deposit
	 */
	function Deposit($uuid) {
		parent::DataObject();

		//Set up new deposits with a UUID
		$this->setUUID($uuid);
	}
	
	/**
	 * Get the type of deposit objects in this deposit.
	 * @return string One of PLN_PLUGIN_DEPOSIT_SUPPORTED_OBJECTS
	 */
	function getObjectType() {
		$depositObjects = $this->getDepositObjects();
		$depositObject =& $depositObjects->next();
		return ($depositObject?$depositObject->getObjectType():null);
	}
	
	/**
	 * Get all deposit objects of this deposit.
	 * @return array of DepositObject
	 */
	function &getDepositObjects() {
		$depositObjectDao =& DAORegistry::getDAO('DepositObjectDAO');
		return $depositObjectDao->getByDepositId($this->getJournalID(), $this->getId());
	}
	
	/**
	 * Get deposit uuid
	 * @return string
	 */
	function getUUID() {
		return $this->getData('uuid');
	}
	
	/**
	 * Set deposit uuid
	 * @param $uuid string
	 */
	function setUUID($uuid) {
		$this->setData('uuid', $uuid);
	}
	
	/**
	 * Get journal id
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journal_id');
	}

	/**
	 * Set journal id
	 * @param #journalId int
	 */
	function setJournalId($journalId) {
		$this->setData('journal_id', $journalId);
	}

	/**
	 * Get deposit status - this is the raw bit field, the other status
	 * functions are more immediately useful.
	 * @return int
	 */
	function getStatus() {
		return $this->getData('status');
	}

	/**
	 * Set deposit status - this is the raw bit field, the other status
	 * functions are more immediately useful.
	 * @param $status int
	 */
	function setStatus($status) {
		$this->setData('status', $status);
	}

	/**
	 * Return a string representation of the local status.
	 * 
	 * @return string
	 */
	function getLocalStatus() {
		if($this->getTransferredStatus()) {
			return 'plugins.generic.pln.status.transferred';
		}
		if($this->getPackagedStatus()) {
			return 'plugins.generic.pln.status.packaged';
		}
		if($this->getNewStatus()) {
			return 'plugins.generic.pln.status.new';
		}		
		return 'plugins.generic.pln.status.unknown';
	}
	
	/**
	 * Return a string representation of the processing status.
	 * 
	 * @return string
	 */
	function getProcessingStatus() {
		if($this->getSentStatus()) {
			return 'plugins.generic.pln.status.sent';
		}
		if($this->getValidatedStatus()) {
			return 'plugins.generic.pln.status.validated';
		}
		if($this->getReceivedStatus()) {
			return 'plugins.generic.pln.status.received';
		}
		return 'plugins.generic.pln.status.unknown';
	}
	
	/**
	 * Return a string representation of the LOCKSS status.
	 * 
	 * @return string
	 */
	function getLockssStatus() {
		if($this->getLockssAgreementStatus()) {
			return 'plugins.generic.pln.status.agreement';
		}
		if($this->getLockssSyncingStatus()) {
			return 'plugins.generic.pln.status.syncing';
		}
		if($this->getLockssReceivedStatus()) {
			return 'plugins.generic.pln.status.received';
		}
		return 'plugins.generic.pln.status.unknown';
	}
	
	/**
	 * Return a string representation of wether or not the deposit processing
	 * is complete ie. LOCKSS has acheived agreement.
	 * 
	 * @return string
	 */
	function getComplete() {
		if($this->getLockssAgreementStatus()) {
			return 'common.yes';
		}
		return 'common.no';
	}
	
	/**
	 * Get new (blank) deposit status
	 * @return int
	 */
	function getNewStatus() {
		return $this->getStatus() == PLN_PLUGIN_DEPOSIT_STATUS_NEW;
	}

	/**
	 * Set new (blank) deposit status
	 */
	function setNewStatus() {
		$this->setStatus(PLN_PLUGIN_DEPOSIT_STATUS_NEW);
	}

	/**
	 * Get a status from the bit field.
	 * 
	 * @param $field int one of the PLN_PLUGIN_DEPOSIT_STATUS_* constants.
	 * @return int
	 */
	function _getStatusField($field) {
		return $this->getStatus() & $field;
	}
	
	/**
	 * Set a status value.
	 * 
	 * @param boolean $value 
	 * @param int $field one of the PLN_PLUGIN_DEPOSIT_STATUS_* constants.
	 */
	function _setStatusField($value, $field) {
		if($value) {
			$this->setStatus($this->getStatus() | $field);
		} else {
			$this->setStatus($this->getStatus() & ~$field);
		}
	}
	
	/**
	 * Get whether the deposit has been packaged for the PLN
	 * @return int
	 */
	function getPackagedStatus() {
		return $this->_getStatusField(PLN_PLUGIN_DEPOSIT_STATUS_PACKAGED);
	}

	/**
	 * Set whether the deposit has been packaged for the PLN
	 * @param $status boolean
	 */
	function setPackagedStatus($status = true) {
		$this->_setStatusField($status, PLN_PLUGIN_DEPOSIT_STATUS_PACKAGED);
	}

	/**
	 * Get whether the PLN has been notified of the available deposit
	 * @return int
	 */
	function getTransferredStatus() {
		return $this->_getStatusField(PLN_PLUGIN_DEPOSIT_STATUS_TRANSFERRED);
	}

	/**
	 * Set whether the PLN has been notified of the available deposit
	 * @param $status boolean
	 */
	function setTransferredStatus($status = true) {
		$this->_setStatusField($status, PLN_PLUGIN_DEPOSIT_STATUS_TRANSFERRED);
	}

	/**
	 * Get whether the PLN has retrieved the deposit from the journal
	 * @return int
	 */
	function getReceivedStatus() {
		return $this->_getStatusField(PLN_PLUGIN_DEPOSIT_STATUS_RECEIVED);
	}

	/**
	 * Set whether the PLN has retrieved the deposit from the journal
	 * @param $status boolean
	 */
	function setReceivedStatus($status = true) {
		$this->_setStatusField($status, PLN_PLUGIN_DEPOSIT_STATUS_RECEIVED);
	}

	/**
	 * Get whether the PLN is syncing the deposit across its nodes
	 * @return int
	 */
	function getValidatedStatus() {
		return $this->_getStatusField(PLN_PLUGIN_DEPOSIT_STATUS_VALIDATED);
	}

	/**
	 * Set whether the PLN is syncing the deposit across its nodes
	 * @param $status boolean
	 */
	function setValidatedStatus($status = true) {
		$this->_setStatusField($status, PLN_PLUGIN_DEPOSIT_STATUS_VALIDATED);
	}

	/**
	 * Get whether the deposit has been synced across its nodes
	 * @return int
	 */
	function getSentStatus() {
		return $this->_getStatusField(PLN_PLUGIN_DEPOSIT_STATUS_SENT);
	}

	/**
	 * Set whether the deposit has been synced across its nodes
	 * @param $status boolean
	 */
	function setSentStatus($status = true) {
		$this->_setStatusField($status, PLN_PLUGIN_DEPOSIT_STATUS_SENT);
	}

	/**
	 * Get whether there's been an error from the staging server
	 * @return int
	 */
	function getLockssReceivedStatus() {
		return $this->_getStatusField(PLN_PLUGIN_DEPOSIT_STATUS_LOCKSS_RECEIVED);
	}

	/**
	 * Set whether there's been an error from the staging server
	 * @param $status boolean
	 */
	function setLockssReceivedStatus($status = true) {
		$this->_setStatusField($status, PLN_PLUGIN_DEPOSIT_STATUS_LOCKSS_RECEIVED);
	}

	/**
	 * Get whether there's been a local error in the deposit process
	 * @return int
	 */
	function getLockssSyncingStatus() {
		return $this->_getStatusField(PLN_PLUGIN_DEPOSIT_STATUS_LOCKSS_SYNCING);
	}

	/**
	 * Set whether there's been a local error in the deposit process
	 * @param $status boolean
	 */
	function setLockssSyncingStatus($status = true) {
		$this->_setStatusField($status, PLN_PLUGIN_DEPOSIT_STATUS_LOCKSS_SYNCING);
	}

	/**
	 * Get whether there's been an update to a deposit
	 * @return int
	 */
	function getLockssAgreementStatus() {
		return $this->_getStatusField(PLN_PLUGIN_DEPOSIT_STATUS_LOCKSS_AGREEMENT);
	}

	/**
	 * Set whether there's been an update to a deposit
	 * @param $status boolean
	 */
	function setLockssAgreementStatus($status = true) {
		$this->_setStatusField($status, PLN_PLUGIN_DEPOSIT_STATUS_LOCKSS_AGREEMENT);
	}

	/**
	 * Get the date of the last status change
	 * @return DateTime
	 */
	function getLastStatusDate() {
		return $this->getData('date_status');
	}

	/**
	 * Set set the date of the last status change
	 * @param $dateLastStatus date
	 */
	function setLastStatusDate($dateLastStatus) {
		$this->setData('date_status', $dateLastStatus);
	}

	/**
	 * Get the date of deposit creation
	 * @return DateTime
	 */
	function getDateCreated() {
		return $this->getData('date_created');
	}

	/**
	 * Set the date of deposit creation
	 * @param $dateCreated boolean
	 */
	function setDateCreated($dateCreated) {
		$this->setData('date_created', $dateCreated);
	}

	/**
	 * Get the modification date of the deposit
	 * @return DateTime
	 */
	function getDateModified() {
		return $this->getData('date_modified');
	}

	/**
	 * Set the modification date of the deposit
	 * @param $dateModified boolean
	 */
	function setDateModified($dateModified) {
		$this->setData('date_modified', $dateModified);
	}

}

?>
