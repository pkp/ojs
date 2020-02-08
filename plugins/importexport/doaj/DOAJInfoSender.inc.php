<?php

/**
 * @file plugins/importexport/doaj/DOAJInfoSender.inc.php
 *
 * Copyright (c) 2013-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DOAJInfoSender
 * @ingroup plugins_importexport_doaj
 *
 * @brief Scheduled task to send deposits to DOAJ.
 */

import('lib.pkp.classes.scheduledTask.ScheduledTask');


class DOAJInfoSender extends ScheduledTask {
	/** @var $_plugin DOAJExportPlugin */
	var $_plugin;

	/**
	 * Constructor.
	 * @param $argv array task arguments
	 */
	function __construct($args) {
		PluginRegistry::loadCategory('importexport');
		$plugin = PluginRegistry::getPlugin('importexport', 'DOAJExportPlugin'); /* @var $plugin DOAJExportPlugin */
		$this->_plugin = $plugin;

		if (is_a($plugin, 'DOAJExportPlugin')) {
			$plugin->addLocaleData();
		}

		parent::__construct($args);
	}

	/**
	 * @copydoc ScheduledTask::getName()
	 */
	function getName() {
		return __('plugins.importexport.doaj.senderTask.name');
	}

	/**
	 * @copydoc ScheduledTask::executeActions()
	 */
	function executeActions() {
		if (!$this->_plugin) return false;

		$plugin = $this->_plugin;
		$journals = $this->_getJournals();

		foreach ($journals as $journal) {
			// Get unregistered articles
			$unregisteredArticles = $plugin->getUnregisteredArticles($journal);
			// If there are articles to be deposited
			if (count($unregisteredArticles)) {
				$this->_registerObjects($unregisteredArticles, 'article=>doaj-json', $journal, 'articles');
			}
		}
		return true;
	}

	/**
	 * Get all journals that meet the requirements to have
	 * their articles automatically sent to DOAJ.
	 * @return array
	 */
	function _getJournals() {
		$plugin = $this->_plugin;
		$contextDao = Application::getContextDAO(); /* @var $contextDao JournalDAO */
		$journalFactory = $contextDao->getAll(true);

		$journals = array();
		while($journal = $journalFactory->next()) {
			$journalId = $journal->getId();
			if (!$plugin->getSetting($journalId, 'apiKey') || !$plugin->getSetting($journalId, 'automaticRegistration')) continue;
			$journals[] = $journal;
		}
		return $journals;
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
		foreach ($objects as $object) {
			// Get the JSON
			$exportJson = $plugin->exportJSON($object, $filter, $journal);
			// Deposit the JSON
			$result = $plugin->depositXML($object, $journal, $exportJson);
			if ($result !== true) {
				$this->_addLogEntry($result);
			}
		}
	}

	/**
	 * Add execution log entry
	 * @param $errors array
	 */
	function _addLogEntry($errors) {
		if (is_array($errors)) {
			foreach($errors as $error) {
				assert(is_array($error) && count($error) >= 1);
				$this->addExecutionLogEntry(
					__($error[0], array('param' => (isset($error[1]) ? $error[1] : null))),
					SCHEDULED_TASK_MESSAGE_TYPE_WARNING
				);
			}
		}
	}

}

