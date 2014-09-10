<?php

/**
 * @file plugins/generic/pln/classes/tasks/PLNPluginDepositor.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PLNPluginDepositor
 * @ingroup plugins_generic_pln_tasks
 *
 * @brief Class to perform automated deposits of PLN object.
 */

import('lib.pkp.classes.scheduledTask.ScheduledTask');

class Depositor extends ScheduledTask {
	
	var $_plugin;
	
	/**
	 * Constructor.
	 * @param $argv array task arguments
	 */
	function Depositor($args) {
		PluginRegistry::loadCategory('generic');
		$this->_plugin =& PluginRegistry::getPlugin('generic', 'plnplugin');
		parent::ScheduledTask($args);
	}

	/**
	 * @see ScheduledTask::getName()
	 */
	function getName() {
		return __('plugins.generic.pln.depositorTask.name');
	}

	/**
	 * @see ScheduledTask::executeActions()
	 */
	function executeActions() {

		if (!$this->_plugin) return false;

		import('classes.file.JournalFileManager');
		$journal_dao =& DAORegistry::getDAO('JournalDAO');
		
		// Get all journals
		$journals =& $journal_dao->getJournals(true);
		
		// For all journals
		while ($journal =& $journals->next()) {
			
			// if the plugin isn't enabled for this journal, skip it
			if (!$this->_plugin->getSetting($journal->getId(), 'enabled')) continue;
			
			$this->addExecutionLogEntry(__(PLN_PLUGIN_NOTIFICATION_PROCESSING_FOR) . ' ' . $journal->getLocalizedTitle(), SCHEDULED_TASK_MESSAGE_TYPE_NOTICE);
			
			// get the sword service document
			$sd_result = $this->_plugin->getServiceDocument($journal->getId());
			
			// if for some reason we didn't get a valid reponse, skip this journal
			if ($sd_result != PLN_PLUGIN_HTTP_STATUS_OK) {
				$this->addExecutionLogEntry(__(PLN_PLUGIN_NOTIFICATION_HTTP_ERROR), SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
				$this->_plugin->createJournalManagerNotification($journal->getId(),PLN_PLUGIN_NOTIFICATION_TYPE_HTTP_ERROR);
				continue;
			}
			
			// if the pln isn't accepting deposits, skip this journal
			if (!$this->_plugin->getSetting($journal->getId(), 'pln_accepting')) {
				$this->addExecutionLogEntry(__(PLN_PLUGIN_NOTIFICATION_PLN_NOT_ACCEPTING), SCHEDULED_TASK_MESSAGE_TYPE_NOTICE);
				continue;
			}
			
			// if the terms haven't been agreed to, skip transfer
			if (!$this->_plugin->termsAgreed($journal->getId())) {
				$this->addExecutionLogEntry(__(PLN_PLUGIN_NOTIFICATION_TERMS_UPDATED), SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
				$this->_plugin->createJournalManagerNotification($journal->getId(),PLN_PLUGIN_NOTIFICATION_TYPE_TERMS_UPDATED);
				continue;
			}
			
			// it's necessary that the journal have an issn set
			if (!$journal->getSetting('onlineIssn') &&
				!$journal->getSetting('printIssn') &&
				!$journal->getSetting('issn')) {
				$this->addExecutionLogEntry(__(PLN_PLUGIN_NOTIFICATION_ISSN_MISSING), SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
				$this->_plugin->createJournalManagerNotification($journal->getId(),PLN_PLUGIN_NOTIFICATION_TYPE_ISSN_MISSING);	
				continue;
			}
			
			// update the statuses of existing deposits
			$this->_processStatusUpdates($journal);
			
			// flag any deposits that have been updated and need to be rebuilt
			$this->_processHavingUpdatedContent($journal);
			
			// create new deposits for new deposit objects
			$this->_processNewDepositObjects($journal);
			
			// package any deposits that need packaging
			$this->_processNeedPackaging($journal);

			// transfer the deposit atom documents
			$this->_processNeedTransferring($journal);

			unset($journal);
		}
		return true;
	}
	
	function _processStatusUpdates($journal) {
			
		// get deposits that need status updates
		$deposit_dao =& DAORegistry::getDAO('DepositDAO');
		$deposits =	$deposit_dao->getNeedStagingStatusUpdate($journal->getId());
		
		foreach($deposits as $deposit) {
			$deposit->updateDepositStatus();
		}
		
	}
	
	function _processHavingUpdatedContent($journal) {
							
		// get deposits that have updated content
		$object_types = unserialize(PLN_PLUGIN_DEPOSIT_SUPPORTED_OBJECTS);
		$object_type = $object_types[$this->_plugin->getSetting($journal->getId(), 'object_type')];
		$deposit_object_dao =& DAORegistry::getDAO('DepositObjectDAO');
		$deposit_object_dao->markHavingUpdatedContent($journal->getId(),$object_type);
		
	}
	
	function _processNeedTransferring(&$journal) {
	
		// fetch the deposits we need to send to the pln
		$deposit_dao =& DAORegistry::getDAO('DepositDAO');
		$deposit_queue = $deposit_dao->getNeedTransferring($journal->getId());
		
		foreach ($deposit_queue as $deposit) {
			$deposit->transferDeposit();
		}
	}
	
	function _processNeedPackaging(&$journal) {
			
		$deposit_dao =& DAORegistry::getDAO('DepositDAO');
		$file_manager = new JournalFileManager($journal);
		$pln_dir = $file_manager->filesDir . PLN_PLUGIN_ARCHIVE_FOLDER;
		
		// make sure the pln work directory exists
		if (is_dir($pln_dir) !== TRUE) { mkdir($pln_dir); }

		// loop though all of the deposits that need packaging
		foreach ($deposit_dao->getNeedPackaging($journal->getId()) as $deposit) {
			$deposit->packageDeposit();
		}
	}
	
	function _processNewDepositObjects(&$journal) {
			
		// get the object type we'll be dealing with
		$object_types = unserialize(PLN_PLUGIN_DEPOSIT_SUPPORTED_OBJECTS);
		$object_type = $object_types[$this->_plugin->getSetting($journal->getId(), 'object_type')];
		
		// create new deposit objects for any new OJS content
		$deposit_dao =& DAORegistry::getDAO('DepositDAO');
		$deposit_object_dao =& DAORegistry::getDAO('DepositObjectDAO');
		$deposit_object_dao->createNew($journal->getId(),$object_type);
		
		// retrieve all deposit objects that don't belong to a deposit
		$new_objects =& $deposit_object_dao->getNew($journal->getId(),$object_type);

		switch ($object_type) {
			case $object_types[PLN_PLUGIN_DEPOSIT_OBJECT_ARTICLE]:
			
				// get the new object threshold per deposit and split the objects into arrays of that size
				$object_threshold = $this->_plugin->getSetting($journal->getId(), 'object_threshold');
				foreach (array_chunk($new_objects,$object_threshold) as $new_object_array) {
					
					// only create a deposit for the complete threshold, we'll worry about the remainder another day
					if (count($new_object_array) == $object_threshold) {

						//create a new deposit
						$new_deposit = new Deposit();
						$new_deposit->setJournalId($journal->getId());
						$deposit_dao->insertDeposit($new_deposit);
						
						// add each object to the deposit
						foreach ($new_object_array as $new_object) {
							$new_object->setDepositId($new_deposit->getId());
							$deposit_object_dao->updateDepositObject($new_object);
						}
					}
				}
			
				break;
			case $object_types[PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE]:
			
				// create a new deposit for reach deposit object
				foreach ($new_objects as $new_object) {
					$new_deposit = new Deposit();
					$new_deposit->setJournalId($journal->getId());
					$deposit_dao->insertDeposit($new_deposit);
					$new_object->setDepositId($new_deposit->getId());
					$deposit_object_dao->updateDepositObject($new_object);
				}
			
				break;
			default:
		}
	}
}

?>
