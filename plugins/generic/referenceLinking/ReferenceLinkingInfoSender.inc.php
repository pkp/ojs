<?php

/**
 * @file plugins/generic/referenceLinking/ReferenceLinkingInfoSender.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReferenceLinkingInfoSender
 * @ingroup plugins_generic_referenceLinking
 *
 * @brief Scheduled task to check for found Crossref references DOIs.
 */

import('lib.pkp.classes.scheduledTask.ScheduledTask');


class ReferenceLinkingInfoSender extends ScheduledTask {
	/** @var $_plugin ReferenceLinkingPlugin */
	var $_plugin;

	/**
	 * Constructor.
	 * @param $args array task arguments
	 */
	function __construct($args) {
		PluginRegistry::loadCategory('generic');
		$plugin = PluginRegistry::getPlugin('generic', 'referencelinkingplugin'); /* @var $plugin ReferenceLinkingPlugin */

		$this->_plugin = $plugin;

		if (is_a($plugin, 'ReferenceLinkingPlugin')) {
			$plugin->addLocaleData();
		}

		parent::__construct($args);
	}

	/**
	 * @copydoc ScheduledTask::getName()
	 */
	function getName() {
		return __('plugins.generic.referenceLinking.senderTask.name');
	}

	/**
	 * @copydoc ScheduledTask::executeActions()
	 */
	function executeActions() {
		if (!$this->_plugin) return false;

		$plugin = $this->_plugin;
		$journals = $this->_getJournals();

		foreach ($journals as $journal) {
			// Call the plugin register function, in order to be able to save the new article and citation settings in the DB
			$plugin->register('generic', $plugin->getPluginPath(), $journal->getId());
			// Get published articles to check
			$articlesToCheck = $plugin->getArticlesToCheck($journal);
			while ($article = $articlesToCheck->next()) {
				$plugin->getCrossrefReferencesDOIs($article);
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
		PluginRegistry::loadCategory('importexport');
		$crossrefExportPlugin = PluginRegistry::getPlugin('importexport', 'CrossRefExportPlugin');

		$contextDao = Application::getContextDAO(); /* @var $contextDao JournalDAO */
		$journalFactory = $contextDao->getAll(true);
		$journals = array();
		while($journal = $journalFactory->next()) {
			$journalId = $journal->getId();
			if (!$journal->getSetting('citationsEnabledSubmission') || !$crossrefExportPlugin->getSetting($journalId, 'username') || !$crossrefExportPlugin->getSetting($journalId, 'password') || !$crossrefExportPlugin->getSetting($journalId, 'automaticRegistration')) continue;
			$journals[] = $journal;
		}
		return $journals;
	}
}
?>
