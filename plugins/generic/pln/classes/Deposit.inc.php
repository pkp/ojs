<?php

/**
 * @file classes/Deposit.inc.php
 *
 * Copyright (c) 2013-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class Deposit
 * @brief Container for deposit objects that are submitted to a PLN
 */

class Deposit extends DataObject {

	/**
	 * Constructor
	 * @param $uuid string|null
	 * @return Deposit
	 */
	public function __construct($uuid) {
		parent::__construct();

		//Set up new deposits with a UUID
		$this->setUUID($uuid);
	}

	/**
	 * Get the type of deposit objects in this deposit.
	 * @return string One of PLN_PLUGIN_DEPOSIT_SUPPORTED_OBJECTS
	 */
	public function getObjectType() {
		$depositObjects = $this->getDepositObjects();
		$depositObject = $depositObjects->next();
		return ($depositObject?$depositObject->getObjectType():null);
	}

	/**
	 * Get the id of deposit objects in this deposit.
	 * @return int
	 */
	public function getObjectId() {
		$depositObjects = $this->getDepositObjects();
		$depositObject = $depositObjects->next();
		return ($depositObject?$depositObject->getObjectId():null);
	}

	/**
	 * Get all deposit objects of this deposit.
	 * @return array of DepositObject
	 */
	public function getDepositObjects() {
		$depositObjectDao = DAORegistry::getDAO('DepositObjectDAO');
		return $depositObjectDao->getByDepositId($this->getJournalId(), $this->getId());
	}

	/**
	 * Get deposit uuid
	 * @return string
	 */
	public function getUUID() {
		return $this->getData('uuid');
	}

	/**
	 * Set deposit uuid
	 * @param $uuid string
	 */
	public function setUUID($uuid) {
		$this->setData('uuid', $uuid);
	}

	/**
	 * Get journal id
	 * @return int
	 */
	public function getJournalId() {
		return $this->getData('journal_id');
	}

	/**
	 * Set journal id
	 * @param $journalId int
	 */
	public function setJournalId($journalId) {
		$this->setData('journal_id', $journalId);
	}

	/**
	 * Get deposit status - this is the raw bit field, the other status
	 * functions are more immediately useful.
	 * @return int
	 */
	public function getStatus() {
		return $this->getData('status');
	}

	/**
	 * Set deposit status - this is the raw bit field, the other status
	 * functions are more immediately useful.
	 * @param $status int
	 */
	public function setStatus($status) {
		$this->setData('status', $status);
	}

	/**
	 * Return a string representation of the local status.
	 * @return string
	 */
	public function getLocalStatus() {
		if ($this->getPackagingFailedStatus()) {
			return __('plugins.generic.pln.status.packagingFailed');
		}
		if ($this->getTransferredStatus()) {
			return __('plugins.generic.pln.status.transferred');
		}
		if ($this->getPackagedStatus()) {
			return __('plugins.generic.pln.status.packaged');
		}
		if ($this->getNewStatus()) {
			return __('plugins.generic.pln.status.new');
		}
		return __('plugins.generic.pln.status.unknown');
	}

	/**
	 * Return a string representation of the processing status.
	 * @return string
	 */
	public function getProcessingStatus() {
		if ($this->getSentStatus()) {
			return __('plugins.generic.pln.status.sent');
		}
		if ($this->getValidatedStatus()) {
			return __('plugins.generic.pln.status.validated');
		}
		if ($this->getReceivedStatus()) {
			return __('plugins.generic.pln.status.received');
		}
		return __('plugins.generic.pln.status.unknown');
	}

	/**
	 * Return a string representation of the LOCKSS status.
	 * @return string
	 */
	public function getLockssStatus() {
		if ($this->getLockssAgreementStatus()) {
			return __('plugins.generic.pln.status.agreement');
		}
		if ($this->getLockssSyncingStatus()) {
			return __('plugins.generic.pln.status.syncing');
		}
		if ($this->getLockssReceivedStatus()) {
			return __('plugins.generic.pln.status.received');
		}
		return __('plugins.generic.pln.status.unknown');
	}

	/**
	 * Return a string representation of wether or not the deposit processing
	 * is complete ie. LOCKSS has acheived agreement.
	 *
	 * @return string
	 */
	public function getComplete() {
		return __($this->getLockssAgreementStatus()?'common.yes':'common.no');
	}

	/**
	 * Get new (blank) deposit status
	 * @return int
	 */
	public function getNewStatus() {
		return $this->getStatus() == PLN_PLUGIN_DEPOSIT_STATUS_NEW;
	}

	/**
	 * Set new (blank) deposit status
	 */
	public function setNewStatus() {
		$this->setStatus(PLN_PLUGIN_DEPOSIT_STATUS_NEW);
	}

	/**
	 * Get a status from the bit field.
	 * @param $field int one of the PLN_PLUGIN_DEPOSIT_STATUS_* constants.
	 * @return int
	 */
	protected function _getStatusField($field) {
		return $this->getStatus() & $field;
	}

	/**
	 * Set a status value.
	 * @param boolean $value
	 * @param int $field one of the PLN_PLUGIN_DEPOSIT_STATUS_* constants.
	 */
	protected function _setStatusField($value, $field) {
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
	public function getPackagedStatus() {
		return $this->_getStatusField(PLN_PLUGIN_DEPOSIT_STATUS_PACKAGED);
	}

	/**
	 * Set whether the deposit has been packaged for the PLN
	 * @param $status boolean
	 */
	public function setPackagedStatus($status = true) {
		$this->_setStatusField($status, PLN_PLUGIN_DEPOSIT_STATUS_PACKAGED);
	}

	/**
	 * Get whether the PLN has been notified of the available deposit
	 * @return int
	 */
	public function getTransferredStatus() {
		return $this->_getStatusField(PLN_PLUGIN_DEPOSIT_STATUS_TRANSFERRED);
	}

	/**
	 * Set whether the PLN has been notified of the available deposit
	 * @param $status boolean
	 */
	public function setTransferredStatus($status = true) {
		$this->_setStatusField($status, PLN_PLUGIN_DEPOSIT_STATUS_TRANSFERRED);
	}

	/**
	 * Get whether the PLN has been notified of the available deposit
	 * @return int
	 */
	public function getPackagingFailedStatus() {
		return $this->_getStatusField(PLN_PLUGIN_DEPOSIT_STATUS_PACKAGING_FAILED);
	}

	/**
	 * Set whether the PLN has been notified of the available deposit
	 * @param $status boolean
	 */
	public function setPackagingFailedStatus($status = true) {
		$this->_setStatusField($status, PLN_PLUGIN_DEPOSIT_STATUS_PACKAGING_FAILED);
	}

	/**
	 * Get whether the PLN has retrieved the deposit from the journal
	 * @return int
	 */
	public function getReceivedStatus() {
		return $this->_getStatusField(PLN_PLUGIN_DEPOSIT_STATUS_RECEIVED);
	}

	/**
	 * Set whether the PLN has retrieved the deposit from the journal
	 * @param $status boolean
	 */
	public function setReceivedStatus($status = true) {
		$this->_setStatusField($status, PLN_PLUGIN_DEPOSIT_STATUS_RECEIVED);
	}

	/**
	 * Get whether the PLN is syncing the deposit across its nodes
	 * @return int
	 */
	public function getValidatedStatus() {
		return $this->_getStatusField(PLN_PLUGIN_DEPOSIT_STATUS_VALIDATED);
	}

	/**
	 * Set whether the PLN is syncing the deposit across its nodes
	 * @param $status boolean
	 */
	public function setValidatedStatus($status = true) {
		$this->_setStatusField($status, PLN_PLUGIN_DEPOSIT_STATUS_VALIDATED);
	}

	/**
	 * Get whether the deposit has been synced across its nodes
	 * @return int
	 */
	public function getSentStatus() {
		return $this->_getStatusField(PLN_PLUGIN_DEPOSIT_STATUS_SENT);
	}

	/**
	 * Set whether the deposit has been synced across its nodes
	 * @param $status boolean
	 */
	public function setSentStatus($status = true) {
		$this->_setStatusField($status, PLN_PLUGIN_DEPOSIT_STATUS_SENT);
	}

	/**
	 * Get whether there's been an error from the staging server
	 * @return int
	 */
	public function getLockssReceivedStatus() {
		return $this->_getStatusField(PLN_PLUGIN_DEPOSIT_STATUS_LOCKSS_RECEIVED);
	}

	/**
	 * Set whether there's been an error from the staging server
	 * @param $status boolean
	 */
	public function setLockssReceivedStatus($status = true) {
		$this->_setStatusField($status, PLN_PLUGIN_DEPOSIT_STATUS_LOCKSS_RECEIVED);
	}

	/**
	 * Get whether there's been a local error in the deposit process
	 * @return int
	 */
	public function getLockssSyncingStatus() {
		return $this->_getStatusField(PLN_PLUGIN_DEPOSIT_STATUS_LOCKSS_SYNCING);
	}

	/**
	 * Set whether there's been a local error in the deposit process
	 * @param $status boolean
	 */
	public function setLockssSyncingStatus($status = true) {
		$this->_setStatusField($status, PLN_PLUGIN_DEPOSIT_STATUS_LOCKSS_SYNCING);
	}

	/**
	 * Get whether there's been an update to a deposit
	 * @return int
	 */
	public function getLockssAgreementStatus() {
		return $this->_getStatusField(PLN_PLUGIN_DEPOSIT_STATUS_LOCKSS_AGREEMENT);
	}

	/**
	 * Set whether there's been an update to a deposit
	 * @param $status boolean
	 */
	public function setLockssAgreementStatus($status = true) {
		$this->_setStatusField($status, PLN_PLUGIN_DEPOSIT_STATUS_LOCKSS_AGREEMENT);
	}

	/**
	 * Get the date of the last status change
	 * @return DateTime
	 */
	public function getLastStatusDate() {
		return $this->getData('dateStatus');
	}

	/**
	 * Set set the date of the last status change
	 * @param $dateLastStatus DateTime
	 */
	public function setLastStatusDate($dateLastStatus) {
		$this->setData('dateStatus', $dateLastStatus);
	}

	/**
	 * Get the date of deposit creation
	 * @return DateTime
	 */
	public function getDateCreated() {
		return $this->getData('dateCreated');
	}

	/**
	 * Set the date of deposit creation
	 * @param $dateCreated boolean
	 */
	public function setDateCreated($dateCreated) {
		$this->setData('dateCreated', $dateCreated);
	}

	/**
	 * Get the modification date of the deposit
	 * @return DateTime
	 */
	public function getDateModified() {
		return $this->getData('dateModified');
	}

	/**
	 * Set the modification date of the deposit
	 * @param $dateModified boolean
	 */
	public function setDateModified($dateModified) {
		$this->setData('dateModified', $dateModified);
	}

	/**
	 * Set the export deposit error message.
	 * @param $exportDepositError string
	 */
	public function setExportDepositError($exportDepositError) {
		$this->setData('exportDepositError', $exportDepositError);
	}

	/**
	 * Get the export deposit error message.
	 * @return string|null
	 */
	public function getExportDepositError() {
		return $this->getData('exportDepositError');
	}

	/**
	 * Get Displayed status locale string
	 * @return string
	 */
	public function getDisplayedStatus() {
		if (!empty($this->getExportDepositError())) {
			$displayedStatus = __('plugins.generic.pln.displayedstatus.error');
		} else if ($this->getLockssAgreementStatus()) {
			$displayedStatus = __('plugins.generic.pln.displayedstatus.completed');
		} else if ($this->getStatus() == PLN_PLUGIN_DEPOSIT_STATUS_NEW) {
			$displayedStatus = __('plugins.generic.pln.displayedstatus.pending');
		} else {
			$displayedStatus = __('plugins.generic.pln.displayedstatus.inprogress');
		}

		return $displayedStatus;
	}

	/**
	 * Resets the deposit
	 */
	public function reset() {
		$this->setStatus(PLN_PLUGIN_DEPOSIT_STATUS_NEW);
		$this->setLastStatusDate(null);
		$this->setExportDepositError(null);
	}
}
