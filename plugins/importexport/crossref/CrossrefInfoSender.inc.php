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
 * @brief Scheduled task to send article information to the ALM server.
 */

import('lib.pkp.classes.scheduledTask.ScheduledTask');


class CrossrefInfoSender extends ScheduledTask {
	/** @var $_plugin CrossRefExportPlugin */
	var $_plugin;

	/**
	 * Constructor.
	 * @param $argv array task arguments
	 */
	function CrossrefInfoSender($args) {
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
	 * @see ScheduledTask::executeActions()
	 */
	function executeActions() {
		if (!$this->_plugin) return false;

		$plugin = $this->_plugin;
		$journals = $this->_getJournals();
		$request =& Application::getRequest();
		$errors = array();

		foreach ($journals as $journal) {
			// Get unregistered articles
			$unregisteredArticles = $plugin->_getUnregisteredArticles($journal);
			$unregisteredArticlesIds = array();
			foreach ($unregisteredArticles as $articleData) {
				$article = $articleData['article'];
				if (is_a($article, 'PublishedArticle') && $plugin->canBeExported($article, $errors)) {
					$unregisteredArticlesIds[$article->getId()] = $article;
				}
			}

			// Update the status and construct an array of an articles to be deposited
			$toBeDepositedIds = array();
			$notify = false;
			foreach ($unregisteredArticlesIds as $id => $article) {
				// first update the status -- some could be manually submitted
				$plugin->updateDepositStatus($request, $journal, $article);
				// get the current article status
				$currentStatus = $article->getData($plugin->getDepositStatusSettingName());
				// deposit only not submitted articles
				if (!$currentStatus) {
					array_push($toBeDepositedIds, $id);
				}
				// check if the new status after the update == failed to notify the users
				$newStatus = $article->getData($plugin->getDepositStatusSettingName());
				if (!$notify && $newStatus == CROSSREF_STATUS_FAILED && $currentStatus != CROSSREF_STATUS_FAILED) {
					$notify = true;
				}
			}

			if ($notify) {
				$roleDao =& DAORegistry::getDAO('RoleDAO');
				$journalManagers = $roleDao->getUsersByRoleId(ROLE_ID_JOURNAL_MANAGER, $journal->getId());
				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				while ($journalManager =& $journalManagers->next()) {
					$notificationManager->createTrivialNotification($journalManager->getId(), NOTIFICATION_TYPE_ERROR, array('contents' => __('plugins.importexport.crossref.notification.failed')));
					unset($journalManager);
				}
			}

			// If there are articles to be deposited and we want automatic deposits
			if (count($toBeDepositedIds) && $plugin->getSetting($journal->getId(), 'automaticRegistration')) {
				$exportSpec = array(DOI_EXPORT_ARTICLES => $toBeDepositedIds);
				$result = $plugin->registerObjects($request, $exportSpec, $journal);
				if ($result !== true) {
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
		}
		return true;
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
			if (!$plugin->getSetting($journalId, 'username') || !$plugin->getSetting($journalId, 'password') || !$plugin->getSetting($journalId, 'automaticRegistration')) continue;

			$doiPrefix = null;
			$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true, $journalId);
			if (isset($pubIdPlugins['DOIPubIdPlugin'])) {
				$doiPubIdPlugin =& $pubIdPlugins['DOIPubIdPlugin'];
				if (!$doiPubIdPlugin->getSetting($journalId, 'enabled')) continue;
				$doiPrefix = $doiPubIdPlugin->getSetting($journalId, 'doiPrefix');
			}

			if ($doiPrefix) {
				$journals[] =& $journal;
			} else {
				$this->addExecutionLogEntry(
						__('plugins.importexport.crossref.senderTask.warning.noDOIprefix', array('path' => $journal->getPath())),
						SCHEDULED_TASK_MESSAGE_TYPE_WARNING
				);
			}
			unset($journal);
		}

		return $journals;
	}
}
?>
