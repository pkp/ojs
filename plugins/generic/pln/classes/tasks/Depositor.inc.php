<?php

/**
 * @file plugins/generic/pln/classes/tasks/Depositor.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PLNPluginDepositor
 * @ingroup plugins_generic_pln_tasks
 *
 * @brief Class to perform automated deposits of PLN object.
 */

import('classes.file.JournalFileManager');
import('lib.pkp.classes.scheduledTask.ScheduledTask');

class Depositor extends ScheduledTask {

	/*
	 * @var $_plugin Ooject
	 */
	var $_plugin;

	/**
	 * Constructor.
	 * @param $argv array task arguments
	 */
	function Depositor($args) {
		PluginRegistry::loadCategory('generic');
		$this->_plugin =& PluginRegistry::getPlugin('generic', PLN_PLUGIN_NAME);
		parent::ScheduledTask($args);
	}

	/**
	 * @copydoc ScheduledTask::getName()
	 */
	function getName() {
		return __('plugins.generic.pln.depositorTask.name');
	}

	/**
	 * @copydoc ScheduledTask::executeActions()
	 */
	function executeActions() {

		if (!$this->_plugin) return false;

		$journal_dao =& DAORegistry::getDAO('JournalDAO');
		
		// Get all journals
		$journals =& $journal_dao->getJournals(true);
		
		// For all journals
		while ($journal =& $journals->next()) {
			
			// if the plugin isn't enabled for this journal, skip it
			if (!$this->_plugin->getSetting($journal->getId(), 'enabled')) continue;

			$this->_plugin->registerDAOs();
			$this->_plugin->import('classes.Deposit');
			$this->_plugin->import('classes.DepositObject');
			$this->_plugin->import('classes.DepositPackage');
			
			$this->addExecutionLogEntry(__('plugins.generic.pln.notifications.processing_for') . ' ' . $journal->getLocalizedTitle(), SCHEDULED_TASK_MESSAGE_TYPE_NOTICE);
			
			// check to make sure curl is installed
			if (!$this->_plugin->curlInstalled()) {
				$this->addExecutionLogEntry(__('plugins.generic.pln.notifications.curl_missing'), SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
				$this->_plugin->createJournalManagerNotification($journal->getId(),PLN_PLUGIN_NOTIFICATION_TYPE_CURL_MISSING);
				continue;
			}
			
			// check to make sure zip is installed
			if (!$this->_plugin->zipInstalled()) {
				$this->addExecutionLogEntry(__('plugins.generic.pln.notifications.zip_missing'), SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
				$this->_plugin->createJournalManagerNotification($journal->getId(),PLN_PLUGIN_NOTIFICATION_TYPE_ZIP_MISSING);
				continue;
			}
			
                        if(!$this->_plugin->tarInstalled()) {
				$this->addExecutionLogEntry(__('plugins.generic.pln.notifications.tar_missing'), SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
				$this->_plugin->createJournalManagerNotification($journal->getId(),PLN_PLUGIN_NOTIFICATION_TYPE_TAR_MISSING);
				continue;
                        }
                        
			// get the sword service document
			$sdResult = $this->_plugin->getServiceDocument($journal->getId());
			
			// if for some reason we didn't get a valid reponse, skip this journal
			if ($sdResult != PLN_PLUGIN_HTTP_STATUS_OK) {
				$this->addExecutionLogEntry(__('plugins.generic.pln.notifications.http_error'), SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
				$this->_plugin->createJournalManagerNotification($journal->getId(),PLN_PLUGIN_NOTIFICATION_TYPE_HTTP_ERROR);
				continue;
			}
			
			// if the pln isn't accepting deposits, skip this journal
			if (!$this->_plugin->getSetting($journal->getId(), 'pln_accepting')) {
				$this->addExecutionLogEntry(__('plugins.generic.pln.notifications.pln_not_accepting'), SCHEDULED_TASK_MESSAGE_TYPE_NOTICE);
				continue;
			}
			
			// if the terms haven't been agreed to, skip transfer
			if (!$this->_plugin->termsAgreed($journal->getId())) {
				$this->addExecutionLogEntry(__('plugins.generic.pln.notifications.terms_updated'), SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
				$this->_plugin->createJournalManagerNotification($journal->getId(),PLN_PLUGIN_NOTIFICATION_TYPE_TERMS_UPDATED);
				continue;
			}
			
			// it's necessary that the journal have an issn set
			if (!$journal->getSetting('onlineIssn') &&
				!$journal->getSetting('printIssn') &&
				!$journal->getSetting('issn')) {
				$this->addExecutionLogEntry(__('plugins.generic.pln.notifications.issn_missing'), SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
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

	/**
	 * Go through existing deposits and fetch their status from the PLN
	 */
	function _processStatusUpdates($journal) {
		// get deposits that need status updates
		$depositDao =& DAORegistry::getDAO('DepositDAO');
		$depositQueue = $depositDao->getNeedStagingStatusUpdate($journal->getId());

		while ($deposit =& $depositQueue->next()) {
			$depositPackage = new DepositPackage($deposit);
			$depositPackage->updateDepositStatus();
			unset($deposit);
		}
	}

	/**
	 * Go thourgh the deposits and mark them as updated if they have been
	 */
	function _processHavingUpdatedContent($journal) {
		// get deposits that have updated content
		$depositObjectDao =& DAORegistry::getDAO('DepositObjectDAO');
		$depositObjectDao->markHavingUpdatedContent($journal->getId(),$this->_plugin->getSetting($journal->getId(), 'object_type'));
	}

	/**
	 * If a deposit hasn't been transferred, transfer it
	 */
	function _processNeedTransferring(&$journal) {
		// fetch the deposits we need to send to the pln
		$depositDao =& DAORegistry::getDAO('DepositDAO');
		$depositQueue =& $depositDao->getNeedTransferring($journal->getId());
		
		while ($deposit =& $depositQueue->next()) {
			$depositPackage = new DepositPackage($deposit);
			$depositPackage->transferDeposit();
			unset($deposit);
		}
	}

	/**
	 * Create packages for any deposits that don't have any or have been updated
	 */
	function _processNeedPackaging(&$journal) {
		$depositDao =& DAORegistry::getDAO('DepositDAO');
		$depositQueue =& $depositDao->getNeedPackaging($journal->getId());
		$fileManager = new JournalFileManager($journal);
		$plnDir = $fileManager->filesDir . PLN_PLUGIN_ARCHIVE_FOLDER;
		
		// make sure the pln work directory exists
		// TOOD: use FileManager calls instead of PHP ones where possible
		if ($fileManager->fileExists($plnDir,'dir') !== true) { $fileManager->mkdirtree($plnDir); }

		// loop though all of the deposits that need packaging
		while ($deposit =& $depositQueue->next()) {
			$depositPackage = new DepositPackage($deposit);
			$depositPackage->packageDeposit();
			unset($deposit);
		}
	}

	/**
	 * Create new deposits for deposit objects
	 */
	function _processNewDepositObjects(&$journal) {
		// get the object type we'll be dealing with
		$objectType = $this->_plugin->getSetting($journal->getId(), 'object_type');
		
		// create new deposit objects for any new OJS content
		$depositDao =& DAORegistry::getDAO('DepositDAO');
		$depositObjectDao =& DAORegistry::getDAO('DepositObjectDAO');
		$depositObjectDao->createNew($journal->getId(),$objectType);
		
		// retrieve all deposit objects that don't belong to a deposit
		$newObjects =& $depositObjectDao->getNew($journal->getId(),$objectType);

		switch ($objectType) {
			case PLN_PLUGIN_DEPOSIT_OBJECT_ARTICLE:
			
				// get the new object threshold per deposit and split the objects into arrays of that size
				$objectThreshold = $this->_plugin->getSetting($journal->getId(), 'object_threshold');
				foreach (array_chunk($newObjects->toArray(),$objectThreshold) as $newObject_array) {
					
					// only create a deposit for the complete threshold, we'll worry about the remainder another day
					if (count($newObject_array) == $objectThreshold) {

						//create a new deposit
						$newDeposit = new Deposit($this->_plugin->newUUID());
						$newDeposit->setJournalId($journal->getId());
						$depositDao->insertDeposit($newDeposit);
						
						// add each object to the deposit
						foreach ($newObject_array as $newObject) {
							$newObject->setDepositId($newDeposit->getId());
							$depositObjectDao->updateDepositObject($newObject);
						}
					}
				}
			
				break;
			case PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE:
			
				// create a new deposit for reach deposit object
				while ($newObject =& $newObjects->next()) {
					$newDeposit = new Deposit($this->_plugin->newUUID());
					$newDeposit->setJournalId($journal->getId());
					$depositDao->insertDeposit($newDeposit);
					$newObject->setDepositId($newDeposit->getId());
					$depositObjectDao->updateDepositObject($newObject);
					unset($newObject);
				}
			
				break;
			default:
		}
	}
}

?>
