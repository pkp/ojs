<?php

/**
 * @file plugins/importexport/pubmed/PubMedExportPlugin.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubMedExportPlugin
 * @ingroup plugins_importexport_pubmed
 *
 * @brief PubMed/MEDLINE XML metadata export plugin
 */

import('lib.pkp.classes.plugins.ImportExportPlugin');

class PubMedExportPlugin extends ImportExportPlugin {
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'PubMedExportPlugin';
	}

	/**
	 * Get the display name.
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.importexport.pubmed.displayName');
	}

	/**
	 * Get the display description.
	 * @return string
	 */
	function getDescription() {
		return __('plugins.importexport.pubmed.description');
	}

	/**
	 * Display the plugin.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function display($args, $request) {
		parent::display($args, $request);
		$templateMgr = TemplateManager::getManager($request);
		$journal = $request->getJournal();
		switch (array_shift($args)) {
			case 'index':
			case '':
				import('lib.pkp.controllers.list.submissions.SelectSubmissionsListHandler');
				$exportSubmissionsListHandler = new SelectSubmissionsListHandler(array(
					'title' => 'plugins.importexport.native.exportSubmissionsSelect',
					'count' => 100,
					'inputName' => 'selectedSubmissions[]',
				));
				$templateMgr->assign('exportSubmissionsListData', json_encode($exportSubmissionsListHandler->getConfig()));
				$templateMgr->display($this->getTemplateResource('index.tpl'));
				break;
			case 'exportSubmissions':
				$exportXml = $this->exportSubmissions(
					(array) $request->getUserVar('selectedSubmissions'),
					$request->getContext(),
					$request->getUser()
				);
				import('lib.pkp.classes.file.FileManager');
				$fileManager = new FileManager();
				$exportFileName = $this->getExportFileName($this->getExportPath(), 'articles', $journal, '.xml');
				$fileManager->writeFile($exportFileName, $exportXml);
				$fileManager->downloadFileByPath($exportFileName);
				$fileManager->deleteFileByPath($exportFileName);
				break;
			case 'exportIssues':
				$exportXml = $this->exportIssues(
					(array) $request->getUserVar('selectedIssues'),
					$request->getContext(),
					$request->getUser()
				);
				import('lib.pkp.classes.file.FileManager');
				$fileManager = new FileManager();
				$exportFileName = $this->getExportFileName($this->getExportPath(), 'issues', $journal, '.xml');
				$fileManager->writeFile($exportFileName, $exportXml);
				$fileManager->downloadFileByPath($exportFileName);
				$fileManager->deleteFileByPath($exportFileName);
				break;
			default:
				$dispatcher = $request->getDispatcher();
				$dispatcher->handle404();
		}
	}

	/**
	 * @copydoc ImportExportPlugin::getPluginSettingsPrefix()
	 */
	function getPluginSettingsPrefix() {
		return 'pubmed';
	}

	function exportSubmissions($submissionIds, $context, $user) {
		$submissionDao = Application::getSubmissionDAO();
		$xml = '';
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$pubmedExportFilters = $filterDao->getObjectsByGroup('article=>pubmed-xml');
		assert(count($pubmedExportFilters) == 1); // Assert only a single serialization filter
		$exportFilter = array_shift($pubmedExportFilters);
		$submissions = array();
		foreach ($submissionIds as $submissionId) {
			$submission = $submissionDao->getById($submissionId, $context->getId());
			if ($submission) $submissions[] = $submission;
		}
		libxml_use_internal_errors(true);
		$submissionXml = $exportFilter->execute($submissions, true);
		$xml = $submissionXml->saveXml();
		$errors = array_filter(libxml_get_errors(), function($a) {
			return $a->level == LIBXML_ERR_ERROR || $a->level == LIBXML_ERR_FATAL;
		});
		if (!empty($errors)) {
			$charset = Config::getVar('i18n', 'client_charset');
			header('Content-type: text/html; charset=' . $charset);
			echo '<html><body>';
			$this->displayXMLValidationErrors($errors, $xml);
			echo '</body></html>';
			fatalError(__('plugins.importexport.common.error.validation'));
		}
		return $xml;
	}

	/**
	 * Get the XML for a set of issues.
	 * @param $issueIds array Array of issue IDs
	 * @param $context Context
	 * @param $user User
	 * @return string XML contents representing the supplied issue IDs.
	 */
	function exportIssues($issueIds, $context, $user) {
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$submissionIds = array();
		foreach ($issueIds as $issueId) {
			$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);
			foreach ($publishedArticles as $publishedArticle) {
				$submissionIds[] = $publishedArticle->getId();
			}
		}

		$submissionDao = Application::getSubmissionDAO();
		$xml = '';
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$pubmedExportFilters = $filterDao->getObjectsByGroup('article=>pubmed-xml');
		assert(count($pubmedExportFilters) == 1); // Assert only a single serialization filter
		$exportFilter = array_shift($pubmedExportFilters);
		$submissions = array();
		foreach ($submissionIds as $submissionId) {
			$submission = $submissionDao->getById($submissionId, $context->getId());
			if ($submission) $submissions[] = $submission;
		}
		libxml_use_internal_errors(true);
		$submissionXml = $exportFilter->execute($submissions, true);
		$xml = $submissionXml->saveXml();
		$errors = array_filter(libxml_get_errors(), function($a) {
			return $a->level == LIBXML_ERR_ERROR || $a->level == LIBXML_ERR_FATAL;
		});
		if (!empty($errors)) {
			$charset = Config::getVar('i18n', 'client_charset');
			header('Content-type: text/html; charset=' . $charset);
			echo '<html><body>';
			$this->displayXMLValidationErrors($errors, $xml);
			echo '</body></html>';
			fatalError(__('plugins.importexport.common.error.validation'));
		}
		return $xml;
	}

	/**
	 * Execute import/export tasks using the command-line interface.
	 * @param $args Parameters to the plugin
	 */
	function executeCLI($scriptName, &$args) {
//		$command = array_shift($args);
		$xmlFile = array_shift($args);
		$journalPath = array_shift($args);

		$journalDao = DAORegistry::getDAO('JournalDAO');
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$userDao = DAORegistry::getDAO('UserDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');

		$journal = $journalDao->getByPath($journalPath);

		if (!$journal) {
			if ($journalPath != '') {
				echo __('plugins.importexport.pubmed.cliError') . "\n";
				echo __('plugins.importexport.pubmed.error.unknownJournal', array('journalPath' => $journalPath)) . "\n\n";
			}
			$this->usage($scriptName);
			return;
		}

		if ($xmlFile != '') switch (array_shift($args)) {
			case 'articles':
				$articleSearch = new ArticleSearch();
				$results = $articleSearch->formatResults($args);
				if (!$this->exportArticles($results, $xmlFile)) {
					echo __('plugins.importexport.pubmed.cliError') . "\n";
					echo __('plugins.importexport.pubmed.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
				}
				return;
			case 'issue':
				$issueId = array_shift($args);
				$issue = $issueDao->getByBestId($issueId, $journal->getId());
				if ($issue == null) {
					echo __('plugins.importexport.pubmed.cliError') . "\n";
					echo __('plugins.importexport.pubmed.export.error.issueNotFound', array('issueId' => $issueId)) . "\n\n";
					return;
				}
				$issues = array($issue);
				if (!$this->exportIssues($journal, $issues, $xmlFile)) {
					echo __('plugins.importexport.pubmed.cliError') . "\n";
					echo __('plugins.importexport.pubmed.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
				}
				return;
		}
		$this->usage($scriptName);

	}

	/**
	 * Display the command-line usage information
	 */
	function usage($scriptName) {
		echo __('plugins.importexport.pubmed.cliUsage', array(
			'scriptName' => $scriptName,
			'pluginName' => $this->getName()
		)) . "\n";
	}
}

?>
