<?php

/**
 * @file plugins/importexport/crossref/CrossrefInfoSender.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CrossrefInfoSender
 * @ingroup plugins_importexport_crossref
 *
 * @brief Scheduled task to send deposits to Crossref and update statuses.
 */

import('lib.pkp.classes.scheduledTask.ScheduledTask');


class CrossrefInfoSender extends ScheduledTask {
	/** @var $_plugin CrossRefExportPlugin */
	var $_plugin;

	/**
	 * Constructor.
	 * @param $argv array task arguments
	 */
	function __construct($args) {
		PluginRegistry::loadCategory('importexport');
		$plugin = PluginRegistry::getPlugin('importexport', 'CrossRefExportPlugin'); /* @var $plugin CrossRefExportPlugin */
		$this->_plugin = $plugin;

		if (is_a($plugin, 'CrossRefExportPlugin')) {
			$plugin->addLocaleData();
		}

		parent::__construct($args);
	}

	/**
	 * @copydoc ScheduledTask::getName()
	 */
	function getName() {
		return __('plugins.importexport.crossref.senderTask.name');
	}

	/**
	 * @copydoc ScheduledTask::executeActions()
	 */
	function executeActions() {
		if (!$this->_plugin) return false;

		$plugin = $this->_plugin;
		$journals = $this->_getJournals();

		foreach ($journals as $journal) {
			$notify = false;

			$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $journal->getId());
			$doiPubIdPlugin = $pubIdPlugins['doipubidplugin'];

			if ($doiPubIdPlugin->getSetting($journal->getId(), 'enableSubmissionDoi')) {
				// Get unregistered articles
				$unregisteredArticles = $plugin->getUnregisteredArticles($journal);
				// Update the status and construct an array of the articles to be deposited
				$articlesToBeDeposited = $this->_getObjectsToBeDeposited($unregisteredArticles, $journal, $notify);
				// If there are articles to be deposited and we want automatic deposits
				if (count($articlesToBeDeposited) && $plugin->getSetting($journal->getId(), 'automaticRegistration')) {
					$this->_registerObjects($articlesToBeDeposited, 'article=>crossref-xml', $journal, 'articles');
				}
			}

			// Notify journal managers if there is a new failed DOI status
			if ($notify) {
				$roleDao = DAORegistry::getDAO('RoleDAO');
				$journalManagers = $roleDao->getUsersByRoleId(ROLE_ID_MANAGER, $journal->getId());
				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				while ($journalManager = $journalManagers->next()) {
					$notificationManager->createTrivialNotification($journalManager->getId(), NOTIFICATION_TYPE_ERROR, array('contents' => __('plugins.importexport.crossref.notification.failed')));
				}
			}
		}
		return true;
	}

	/**
	 * Get all journals that meet the requirements to have
	 * their articles or issues DOIs sent to Crossref.
	 * @return array
	 */
	function _getJournals() {
		$plugin = $this->_plugin;
		$contextDao = Application::getContextDAO(); /* @var $contextDao JournalDAO */
		$journalFactory = $contextDao->getAll(true);

		$journals = array();
		while($journal = $journalFactory->next()) {
			$journalId = $journal->getId();
			if (!$plugin->getSetting($journalId, 'username') || !$plugin->getSetting($journalId, 'password')) continue;

			$doiPrefix = null;
			$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $journalId);
			if (isset($pubIdPlugins['doipubidplugin'])) {
				$doiPubIdPlugin = $pubIdPlugins['doipubidplugin'];
				if (!$doiPubIdPlugin->getSetting($journalId, 'enabled')) continue;
				$doiPrefix = $doiPubIdPlugin->getSetting($journalId, 'doiPrefix');
			}

			if ($doiPrefix) {
				$journals[] = $journal;
			} else {
				$this->addExecutionLogEntry(__('plugins.importexport.common.senderTask.warning.noDOIprefix', array('path' => $journal->getPath())), SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
			}
		}
		return $journals;
	}

	/**
	 * Update the status and construct an array of the objects to be deposited
	 * @param $unregisteredObjects array Array of all not fully registered objects
	 * @param $journal Journal
	 * @param $notify boolean
	 * @return array Array of objects to be deposited
	 */
	function _getObjectsToBeDeposited($unregisteredObjects, $journal, &$notify) {
		$plugin = $this->_plugin;
		$objectsToBeDeposited = array();
		foreach ($unregisteredObjects as $object) {
			$plugin->updateDepositStatus($journal, $object);
			// get the current object status
			$currentStatus = $object->getData($plugin->getDepositStatusSettingName());
			// deposit only not submitted objects
			if (!$currentStatus) {
				array_push($objectsToBeDeposited, $object);
			}
			// check if the new status after the update == failed to notify the users
			$newStatus = $object->getData($plugin->getDepositStatusSettingName());
			if (!$notify && $newStatus == CROSSREF_STATUS_FAILED && $currentStatus != CROSSREF_STATUS_FAILED) {
				$notify = true;
			}
		}
		return $objectsToBeDeposited;
	}

	/**
	 * Register objects
	 * @param $objects array
	 * @param $filter string
	 * @param $journal Journal
	 * @param $objectsFileNamePart string
	 */
	function _registerObjects($objects, $filter, $journal, $objectsFileNamePart) {
		$plugin = $this->_plugin;
		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();
		// export XML
		$exportXml = $plugin->exportXML($objects, $filter, $journal);
		// Write the XML to a file.
		$exportFileName = $plugin->getExportFileName($plugin->getExportPath(), $objectsFileNamePart, $journal, '.xml');
		$fileManager->writeFile($exportFileName, $exportXml);
		// Deposit the XML file.
		$result = $plugin->depositXML($objects, $journal, $exportFileName);
		if ($result !== true) {
			$this->_addLogEntry($result);
		}
		// Remove all temporary files.
		$fileManager->deleteFileByPath($exportFileName);
	}

	/**
	 * Add execution log entry
	 * @param $result array
	 */
	function _addLogEntry($result) {
		if (is_array($result)) {
			foreach($result as $error) {
				assert(is_array($error) && count($error) >= 1);
				$this->addExecutionLogEntry(
					__($error[0], array('param' => (isset($error[1]) ? $error[1] : null))),
					SCHEDULED_TASK_MESSAGE_TYPE_WARNING
				);
			}
		}
	}

}
?>
