<?php

/**
 * @file plugins/generic/pln/classes/Deposit.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
	 * Get whether the deposit has been packaged for the PLN
	 * @return int
	 */
	function getPackagedStatus() {
		return $this->getStatus() & PLN_PLUGIN_DEPOSIT_STATUS_PACKAGED;
	}

	/**
	 * Set whether the deposit has been packaged for the PLN
	 * @param $status boolean
	 */
	function setPackagedStatus($status = true) {
		$this->setStatus($status ? $this->getStatus() | PLN_PLUGIN_DEPOSIT_STATUS_PACKAGED : $this->getStatus() & ~PLN_PLUGIN_DEPOSIT_STATUS_PACKAGED);
	}

	/**
	 * Get whether the PLN has been notified of the available deposit
	 * @return int
	 */
	function getTransferredStatus() {
		return $this->getStatus() & PLN_PLUGIN_DEPOSIT_STATUS_TRANSFERRED;
	}

	/**
	 * Set whether the PLN has been notified of the available deposit
	 * @param $status boolean
	 */
	function setTransferredStatus($status = true) {
		$this->setStatus($status ? $this->getStatus() | PLN_PLUGIN_DEPOSIT_STATUS_TRANSFERRED : $this->getStatus() & ~PLN_PLUGIN_DEPOSIT_STATUS_TRANSFERRED);
	}

	/**
	 * Get whether the PLN has retrieved the deposit from the journal
	 * @return int
	 */
	function getReceivedStatus() {
		return $this->getStatus() & PLN_PLUGIN_DEPOSIT_STATUS_RECEIVED;
	}

	/**
	 * Set whether the PLN has retrieved the deposit from the journal
	 * @param $status boolean
	 */
	function setReceivedStatus($status = true) {
		$this->setStatus($status ? $this->getStatus() | PLN_PLUGIN_DEPOSIT_STATUS_RECEIVED : $this->getStatus() & ~PLN_PLUGIN_DEPOSIT_STATUS_RECEIVED);
	}

	/**
	 * Get whether the PLN is syncing the deposit across its nodes
	 * @return int
	 */
	function getSyncingStatus() {
		return $this->getStatus() & PLN_PLUGIN_DEPOSIT_STATUS_SYNCING;
	}

	/**
	 * Set whether the PLN is syncing the deposit across its nodes
	 * @param $status boolean
	 */
	function setSyncingStatus($status = true) {
		$this->setStatus($status ? $this->getStatus() | PLN_PLUGIN_DEPOSIT_STATUS_SYNCING : $this->getStatus() & ~PLN_PLUGIN_DEPOSIT_STATUS_SYNCING);
	}

	/**
	 * Get whether the deposit has been synced across its nodes
	 * @return int
	 */
	function getSyncedStatus() {
		return $this->getStatus() & PLN_PLUGIN_DEPOSIT_STATUS_SYNCED;
	}

	/**
	 * Set whether the deposit has been synced across its nodes
	 * @param $status boolean
	 */
	function setSyncedStatus($status = true) {
		$this->setStatus($status ? $this->getStatus() | PLN_PLUGIN_DEPOSIT_STATUS_SYNCED : $this->getStatus() & ~PLN_PLUGIN_DEPOSIT_STATUS_SYNCED);
	}

	/**
	 * Get whether there's been an error from the staging server
	 * @return int
	 */
	function getRemoteFailureStatus() {
		return $this->getStatus() & PLN_PLUGIN_DEPOSIT_STATUS_REMOTE_FAILURE;
	}

	/**
	 * Set whether there's been an error from the staging server
	 * @param $status boolean
	 */
	function setRemoteFailureStatus($status = true) {
		$this->setStatus($status ? $this->getStatus() | PLN_PLUGIN_DEPOSIT_STATUS_REMOTE_FAILURE : $this->getStatus() & ~PLN_PLUGIN_DEPOSIT_STATUS_REMOTE_FAILURE);
	}

	/**
	 * Get whether there's been a local error in the deposit process
	 * @return int
	 */
	function getLocalFailureStatus() {
		return $this->getStatus() & PLN_PLUGIN_DEPOSIT_STATUS_LOCAL_FAILURE;
	}

	/**
	 * Set whether there's been a local error in the deposit process
	 * @param $status boolean
	 */
	function setLocalFailureStatus($status = true) {
		$this->setStatus($status ? $this->getStatus() | PLN_PLUGIN_DEPOSIT_STATUS_LOCAL_FAILURE : $this->getStatus() & ~PLN_PLUGIN_DEPOSIT_STATUS_LOCAL_FAILURE);
	}

	/**
	 * Get whether there's been an update to a deposit
	 * @return int
	 */
	function getUpdateStatus() {
		return $this->getStatus() & PLN_PLUGIN_DEPOSIT_STATUS_UPDATE;
	}

	/**
	 * Set whether there's been an update to a deposit
	 * @param $status boolean
	 */
	function setUpdateStatus($status = true) {
		$this->setStatus($status ? $this->getStatus() | PLN_PLUGIN_DEPOSIT_STATUS_UPDATE : $this->getStatus() & ~PLN_PLUGIN_DEPOSIT_STATUS_UPDATE);
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
	 * @param $dateLastStatusd boolean
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
