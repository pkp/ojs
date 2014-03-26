<?php

/**
 * @file plugins/importexport/crossref/ArticleInfoSender.php
 *
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleInfoSender
 * @ingroup plugins_importexport_crossref
 *
 * @brief Scheduled task to send article information to the ALM server.
 */

import('lib.pkp.classes.scheduledTask.ScheduledTask');
import('lib.pkp.classes.core.JSONManager');


class ArticleInfoSender extends ScheduledTask {

	/** @var $_plugin CrossRefExportPlugin */
	var $_plugin;

	/**
	 * Constructor.
	 * @param $argv array task arguments
	 */
	function ArticleInfoSender($args) {
		PluginRegistry::loadCategory('importexport');
		$plugin =& PluginRegistry::getPlugin('importexport', 'CrossRefExportPlugin'); /* @var $plugin CrossRefExportPlugin */
		$this->_plugin =& $plugin;

		if (is_a($plugin, 'CrossRefExportPlugin')) {
			$plugin->addLocaleData();
		}

		parent::ScheduledTask($args);
	}

	/**
	 * @see ScheduledTask::getName()
	 */
	function getName() {
		return __('plugins.importexport.crossref.senderTask.name');
	}

	/**
	 * @see FileLoader::execute()
	 */
	function execute() {
		if (!$this->_plugin) return false;

		$plugin = $this->_plugin;

		$journals = $this->_getJournals();
		$request =& Application::getRequest();

		foreach ($journals as $journal) {
			$unregisteredArticles = $plugin->_getUnregisteredArticles($journal);

			$unregisteredArticlesIds = array();
			foreach ($unregisteredArticles as $articleData) {
				$article = $articleData['article'];
				if (is_a($article, 'PublishedArticle')) {
					$unregisteredArticlesIds[$article->getId()] = $article;
				}
			}

			$toBeDepositedIds = array();
			foreach ($unregisteredArticlesIds as $id => $article) {
				$status = $plugin->updateDepositStatus($request, $journal, $article->getPubId('doi'));

				if ('not registered') {

				} else {
					array_push($toBeDepositedIds, $id);
				}

			}

			// If there are unregistered things and we want automatic deposits
			if (count($toBeDepositedIds) && $plugin->getSetting($journal->getId(), 'automaticRegistration')) {
				$exportSpec = array(DOI_EXPORT_ARTICLES => $unregisteredArticlesIds);

				$result = $plugin->registerObjects($request, $exportSpec, $journal);
			}
		}

	}

	/**
	 * Get all journals that meet the requirements to have
	 * their articles DOIs sent to Crossref .
	 * @return array
	 */
	function _getJournals() {
		$plugin =& $this->_plugin;
		$journalDao =& DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$journalFactory =& $journalDao->getJournals(true);

		$journals = array();
		while($journal =& $journalFactory->next()) {
			$journalId = $journal->getId();
			if (!$plugin->getSetting($journalId, 'enabled') && !$plugin->getSetting($journalId, 'automaticRegistration')) continue;

			$doiPrefix = null;
			$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true, $journalId);
			if (isset($pubIdPlugins['DOIPubIdPlugin'])) {
				$doiPubIdPlugin =& $pubIdPlugins['DOIPubIdPlugin'];
				$doiPrefix = $doiPubIdPlugin->getSetting($journalId, 'doiPrefix');
			}

			if ($doiPrefix) {
				$journals[] =& $journal;
			} else {
				$this->notify(SCHEDULED_TASK_MESSAGE_TYPE_WARNING,
					__('plugins.importexport.crossref.senderTask.warning.noDOIprefix', array('path' => $journal->getPath())));
			}
			unset($journal);
		}

		return $journals;
	}
}
?>
