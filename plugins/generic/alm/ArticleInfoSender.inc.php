<?php

/**
 * @file plugins/generic/alm/ArticleInfoSender.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleInfoSender
 * @ingroup plugins_generic_alm
 *
 * @brief Scheduled task to send article information to the ALM server.
 */

import('lib.pkp.classes.scheduledTask.ScheduledTask');
import('lib.pkp.classes.core.JSONManager');

class ArticleInfoSender extends ScheduledTask {

	/** @var $_plugin AlmPlugin */
	var $_plugin;

	/** @var $_depositUrl string */
	var $_depositUrl;

	/**
	 * Constructor.
	 * @param $argv array task arguments
	 */
	function ArticleInfoSender($args) {
		PluginRegistry::loadCategory('generic');
		$plugin =& PluginRegistry::getPlugin('generic', 'almplugin'); /* @var $plugin AlmPlugin */
		$this->_plugin =& $plugin;

		if (is_a($plugin, 'AlmPlugin')) {
			$plugin->addLocaleData();
			$this->_depositUrl = $plugin->getSetting(CONTEXT_ID_NONE, 'depositUrl');
		}

		parent::ScheduledTask($args);
	}

	/**
	 * @see ScheduledTask::getName()
	 */
	function getName() {
		return __('plugins.generic.alm.senderTask.name');
	}

	/**
	 * @see ScheduledTask::executeActions()
	 */
	function executeActions() {
		if (!$this->_plugin) return false;

		if (!$this->_depositUrl) {
			$this->addExecutionLogEntry(__('plugins.generic.alm.senderTask.error.noDepositUrl'), SCHEDULED_TASK_MESSAGE_TYPE_ERROR);
			return false;
		}

		$plugin = $this->_plugin;

		$journals = $this->_getJournals();

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */

		foreach ($journals as $journal) {
			$journalId = $journal->getId();
			$lastExport = $plugin->getSetting($journalId, 'lastExport');

			$articles =& $publishedArticleDao->getPublishedArticlesByJournalId($journalId, null, true);
			$articlesSinceLast = array();
			while ($article =& $articles->next() ){
				if (strtotime($article->getDatePublished()) > $lastExport) {
					$articlesSinceLast[] = $article;
				} else {
					break;
				}
				unset($article);
			}

			if ($this->_exportArticles($journal, $articlesSinceLast)) {
				$plugin->updateSetting($journalId, 'lastExport', Core::getCurrentDate(), 'date');
			}
		}

		if (empty($journals)) {
			$this->addExecutionLogEntry(__('plugins.generic.alm.senderTask.warning.noJournal'), SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
		}

		return true;
	}

	/**
	 * Get all journals that meet the requirements to have
	 * their articles information sent to ALM server.
	 * @return array
	 */
	function _getJournals() {
		$plugin =& $this->_plugin;
		$journalDao =& DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$journalFactory =& $journalDao->getJournals(true);

		$journals = array();
		while($journal =& $journalFactory->next()) {
			$journalId = $journal->getId();
			if (!$plugin->getSetting($journalId, 'enabled') || !$plugin->getSetting($journalId, 'depositArticles')) {
				unset($journal);
				continue;
			}

			$doiPrefix = null;
			$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true, $journalId);
			if (isset($pubIdPlugins['DOIPubIdPlugin'])) {
				$doiPubIdPlugin =& $pubIdPlugins['DOIPubIdPlugin'];
				$doiPrefix = $doiPubIdPlugin->getSetting($journalId, 'doiPrefix');
			}

			if ($doiPrefix) {
				$journals[] =& $journal;
			} else {
				$this->addExecutionLogEntry(__('plugins.generic.alm.senderTask.warning.noDOIprefix',
						array('path' => $journal->getPath())), SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
			}
			unset($journal);
		}

		return $journals;
	}

	/**
	 *
	 * @param $journal Journal
	 * @param $articles array
	 * @return boolean
	 */
	function _exportArticles($journal, &$articles) {
		if (!isset($articles) || count($articles) == 0) return false;
		$plugin = $this->_plugin;
		$journalPath = $journal->getPath();
		$journalId = $journal->getId();

		$payload = '';
		foreach ($articles as $article) {
			$doi = $article->getPubId('doi');
			$publishedDate = date('Y-m-d', strtotime($article->getDatePublished()));
			$title = preg_replace('/s+/', ' ', $article->getLocalizedTitle());
			if ($doi && $publishedDate && $title)
			$payload .= "$doi $publishedDate $title\n";
		}

		$depositUrl = $this->_depositUrl;

		$apiKey = $plugin->getSetting($journalId, 'apiKey');
		if (!$apiKey) {
			$this->addExecutionLogEntry(__('plugins.generic.alm.senderTask.warning.noApiKey',
					array('path' => $journalPath)), SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
		}

		$params = array(
			'api_key' => $apiKey,
			'payload' => $payload
		);

		$jsonManager = new JSONManager();
		if ($payload && $depositUrl && $apiKey) {
			$webServiceRequest = new WebServiceRequest($depositUrl, $params, 'POST');

			// Configure and call the web service
			$webService = new WebService();
			$result = $webService->call($webServiceRequest);

			if ($result) $resultDecoded = $jsonManager->decode($result);

			if (is_null($result)) {
				$this->addExecutionLogEntry(__('plugins.generic.alm.senderTask.error.noServerResponse',
						array('path' => $journalPath)), SCHEDULED_TASK_MESSAGE_TYPE_ERROR);
			}

			if ($resultDecoded && isset($resultDecoded->success) && isset($resultDecoded->count)
				&& $resultDecoded->count == count($articles)) {
				return true;
			} else {
				$this->addExecutionLogEntry(__('plugins.generic.alm.senderTask.error.returnError', array(
						'error' => $result,
						'articlesNumber' => count($articles),
						'payload' => $payload)),
					SCHEDULED_TASK_MESSAGE_TYPE_ERROR);
			}
		}

		return false;
	}
}
?>
