<?php

/**
 * @file plugins/importexport/crossref/CrossrefInfoSender.php
 *
 * Copyright (c) 2013-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
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
	function CrossrefInfoSender($args) {
		PluginRegistry::loadCategory('importexport');
		$plugin = PluginRegistry::getPlugin('importexport', 'CrossRefExportPlugin'); /* @var $plugin CrossRefExportPlugin */
		$this->_plugin = $plugin;

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
		$request = Application::getRequest();

		foreach ($journals as $journal) {
			$notify = false;

			$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $journal->getId());
			$doiPubIdPlugin = $pubIdPlugins['doipubidplugin'];

			if ($doiPubIdPlugin->getSetting($journal->getId(), 'enableIssueDoi')) {
				// Get unregistered issues
				$unregisteredIssues = $plugin->getUnregisteredIssues($journal);
				// Update the status and construct an array of the issues to be deposited
				$issuesToBeDeposited = array();
				foreach ($unregisteredIssues as $issue) {
					$plugin->updateDepositStatus($request, $journal, $issue);
					// get the current issue status
					$currentIssueStatus = $issue->getData($plugin->getDepositStatusSettingName());
					// update status and select only not submitted issues for the automatic deposit
					if (!$currentIssueStatus) {
						array_push($issuesToBeDeposited, $issue);
					}
					// check if the new status after the update == failed to notify the users
					$newIssueStatus = $issue->getData($plugin->getDepositStatusSettingName());
					if (!$notify && $newIssueStatus == CROSSREF_STATUS_FAILED && $currentIssueStatus != CROSSREF_STATUS_FAILED) {
						$notify = true;
					}
				}
				// If there are issues to be deposited and we want automatic deposit
				if (count($issuesToBeDeposited) && $plugin->getSetting($journal->getId(), 'automaticRegistration')) {
					// export XML
					$exportIssueXml = $plugin->exportXML($issuesToBeDeposited, 'issue=>crossref-xml', $journal, $request->getUser());
					// Write the XML to a file.
					$exportIssueFileName = $plugin->getExportPath() . date('Ymd-His') . '.xml';
					file_put_contents($exportIssueFileName, $exportIssueXml);
					// Deposit the XML file.
					$result = $plugin->depositXML($request, $issuesToBeDeposited, $request->getContext(), $exportIssueFileName);
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
					// Remove all temporary files.
					$this->cleanTmpfile($exportIssueFileName);
				}
			}

			if ($doiPubIdPlugin->getSetting($journal->getId(), 'enableSubmissionDoi')) {
				// Get unregistered articles
				$unregisteredArticles = $plugin->getUnregisteredArticles($journal);
				// Update the status and construct an array of the articles to be deposited
				$articlesToBeDeposited = array();
				foreach ($unregisteredArticles as $article) {
					$plugin->updateDepositStatus($request, $journal, $article);
					// get the current article status
					$currentArticleStatus = $article->getData($plugin->getDepositStatusSettingName());
					// deposit only not submitted articles
					if (!$currentArticleStatus) {
						array_push($articlesToBeDeposited, $article);
					}
					// check if the new status after the update == failed to notify the users
					$newArticleStatus = $article->getData($plugin->getDepositStatusSettingName());
					if (!$notify && $newArticleStatus == CROSSREF_STATUS_FAILED && $currentArticleStatus != CROSSREF_STATUS_FAILED) {
						$notify = true;
					}
				}
				// If there are articles to be deposited and we want automatic deposits
				if (count($articlesToBeDeposited) && $plugin->getSetting($journal->getId(), 'automaticRegistration')) {
					// export XML
					$exportArticleXml = $plugin->exportXML($articlesToBeDeposited, 'article=>crossref-xml', $journal, $request->getUser());
					// Write the XML to a file.
					$exportArticleFileName = $plugin->getExportPath() . date('Ymd-His') . '.xml';
					file_put_contents($exportArticleFileName, $exportArticleXml);
					// Deposit the XML file.
					$result = $plugin->depositXML($request, $articlesToBeDeposited, $request->getContext(), $exportArticleFileName);
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
					// Remove all temporary files.
					$this->cleanTmpfile($exportArticleFileName);
				}
			}

			// Notify journal managers if there is a new failed DOI status
			if ($notify) {
				$roleDao = DAORegistry::getDAO('RoleDAO');
				$journalManagers = $roleDao->getUsersByRoleId(ROLE_ID_JOURNAL_MANAGER, $journal->getId());
				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				while ($journalManager = $journalManagers->next()) {
					$notificationManager->createTrivialNotification($journalManager->getId(), NOTIFICATION_TYPE_ERROR, array('contents' => __('plugins.importexport.crossref.notification.failed')));
					unset($journalManager);
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
				$this->addExecutionLogEntry(__('plugins.importexport.crossref.senderTask.warning.noDOIprefix', array('path' => $journal->getPath())), SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
			}
			unset($journal);
		}
		return $journals;
	}

}
?>
