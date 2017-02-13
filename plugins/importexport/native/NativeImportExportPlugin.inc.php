<?php

/**
 * @file plugins/importexport/native/NativeImportExportPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeImportExportPlugin
 * @ingroup plugins_importexport_native
 *
 * @brief Native XML import/export plugin
 */

import('lib.pkp.classes.plugins.ImportExportPlugin');

class NativeImportExportPlugin extends ImportExportPlugin {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @param $path string
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		$this->import('NativeImportExportDeployment');
		return $success;
	}

	/**
	 * @see Plugin::getTemplatePath($inCore)
	 */
	function getTemplatePath($inCore = false) {
		return parent::getTemplatePath($inCore) . 'templates/';
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'NativeImportExportPlugin';
	}

	/**
	 * Get the display name.
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.importexport.native.displayName');
	}

	/**
	 * Get the display description.
	 * @return string
	 */
	function getDescription() {
		return __('plugins.importexport.native.description');
	}

	/**
	 * @copydoc ImportExportPlugin::getPluginSettingsPrefix()
	 */
	function getPluginSettingsPrefix() {
		return 'native';
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
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
				break;
			case 'uploadImportXML':
				$user = $request->getUser();
				import('lib.pkp.classes.file.TemporaryFileManager');
				$temporaryFileManager = new TemporaryFileManager();
				$temporaryFile = $temporaryFileManager->handleUpload('uploadedFile', $user->getId());
				if ($temporaryFile) {
					$json = new JSONMessage(true);
					$json->setAdditionalAttributes(array(
						'temporaryFileId' => $temporaryFile->getId()
					));
				} else {
					$json = new JSONMessage(false, __('common.uploadFailed'));
				}

				return $json->getString();
			case 'importBounce':
				$json = new JSONMessage(true);
				$json->setEvent('addTab', array(
					'title' => __('plugins.importexport.native.results'),
					'url' => $request->url(null, null, null, array('plugin', $this->getName(), 'import'), array('temporaryFileId' => $request->getUserVar('temporaryFileId'))),
				));
				return $json->getString();
			case 'import':
				AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);
				$temporaryFileId = $request->getUserVar('temporaryFileId');
				$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
				$user = $request->getUser();
				$temporaryFile = $temporaryFileDao->getTemporaryFile($temporaryFileId, $user->getId());
				if (!$temporaryFile) {
					$json = new JSONMessage(true, __('plugins.inportexport.native.uploadFile'));
					return $json->getString();
				}
				$temporaryFilePath = $temporaryFile->getFilePath();

				$filter = 'native-xml=>issue';
				// is this articles import:
				$xmlString = file_get_contents($temporaryFilePath);
				$document = new DOMDocument();
				$document->loadXml($xmlString);
				$requirementsErrors = null;
				if (in_array($document->documentElement->tagName, array('article', 'articles'))) {
					$filter = 'native-xml=>article';
				}

				$deployment = new NativeImportExportDeployment($journal, $user);

				libxml_use_internal_errors(true);
				$content = $this->importSubmissions(file_get_contents($temporaryFilePath), $filter, $deployment);
				$templateMgr->assign('content', $content);
				$validationErrors = array_filter(libxml_get_errors(), create_function('$a', 'return $a->level == LIBXML_ERR_ERROR ||  $a->level == LIBXML_ERR_FATAL;'));
				$templateMgr->assign('validationErrors', $validationErrors);
				libxml_clear_errors();

				// Are there any submissions import errors
				$processedSubmissionsIds = $deployment->getProcessedObjectsIds(ASSOC_TYPE_SUBMISSION);
				if (!empty($processedSubmissionsIds)) {
					$submissionsErrors = array_filter($processedSubmissionsIds, create_function('$a', 'return !empty($a);'));
					if (!empty($submissionsErrors)) {
						$templateMgr->assign('submissionsErrors', $processedSubmissionsIds);
					}
				}
				// Are there any issues import errors
				$processedIssuesIds = $deployment->getProcessedObjectsIds(ASSOC_TYPE_ISSUE);
				if (!empty($processedIssuesIds)) {
					$issuesErrors = array_filter($processedIssuesIds, create_function('$a', 'return !empty($a);'));
					if (!empty($issuesErrors)) {
						$templateMgr->assign('issuesErrors', $processedIssuesIds);
					}
				}
				// If there are any submissions or validataion errors
				// delete imported submissions and issues.
				// Issues errors can be ignored here, because they only contain section mismatch errors,
				// that shall only be displayed to the user but that do not influence the import objects.
				if (!empty($submissionsErrors) || !empty($validationErrors)) {
					// remove all imported issues and sumissions
					$deployment->removeImportedObjects(ASSOC_TYPE_ISSUE);
					$deployment->removeImportedObjects(ASSOC_TYPE_SUBMISSION);
				}
				// Display the results
				$json = new JSONMessage(true, $templateMgr->fetch($this->getTemplatePath() . 'results.tpl'));
				return $json->getString();
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
				$fileManager->downloadFile($exportFileName);
				$fileManager->deleteFile($exportFileName);
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
				$fileManager->downloadFile($exportFileName);
				$fileManager->deleteFile($exportFileName);
				break;
			default:
				$dispatcher = $request->getDispatcher();
				$dispatcher->handle404();
		}
	}

	/**
	 * Get the XML for a set of submissions.
	 * @param $submissionIds array Array of submission IDs
	 * @param $context Context
	 * @param $user User|null
	 * @return string XML contents representing the supplied submission IDs.
	 */
	function exportSubmissions($submissionIds, $context, $user) {
		$submissionDao = Application::getSubmissionDAO();
		$xml = '';
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$nativeExportFilters = $filterDao->getObjectsByGroup('article=>native-xml');
		assert(count($nativeExportFilters) == 1); // Assert only a single serialization filter
		$exportFilter = array_shift($nativeExportFilters);
		$exportFilter->setDeployment(new NativeImportExportDeployment($context, $user));
		$submissions = array();
		foreach ($submissionIds as $submissionId) {
			$submission = $submissionDao->getById($submissionId, $context->getId());
			if ($submission) $submissions[] = $submission;
		}
		libxml_use_internal_errors(true);
		$submissionXml = $exportFilter->execute($submissions, true);
		$xml = $submissionXml->saveXml();
		$errors = array_filter(libxml_get_errors(), create_function('$a', 'return $a->level == LIBXML_ERR_ERROR || $a->level == LIBXML_ERR_FATAL;'));
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
		$xml = '';
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$nativeExportFilters = $filterDao->getObjectsByGroup('issue=>native-xml');
		assert(count($nativeExportFilters) == 1); // Assert only a single serialization filter
		$exportFilter = array_shift($nativeExportFilters);
		$exportFilter->setDeployment(new NativeImportExportDeployment($context, $user));
		$issues = array();
		foreach ($issueIds as $issueId) {
			$issue = $issueDao->getById($issueId, $context->getId());
			if ($issue) $issues[] = $issue;
		}
		libxml_use_internal_errors(true);
		$issueXml = $exportFilter->execute($issues, true);
		$xml = $issueXml->saveXml();
		$errors = array_filter(libxml_get_errors(), create_function('$a', 'return $a->level == LIBXML_ERR_ERROR || $a->level == LIBXML_ERR_FATAL;'));
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
	 * Get the XML for a set of submissions wrapped in a(n) issue(s).
	 * @param $importXml string XML contents to import
	 * @param $filter string Filter to be used
	 * @param $deployment PKPImportExportDeployment
	 * @return array Set of imported submissions
	 */
	function importSubmissions($importXml, $filter, $deployment) {
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$nativeImportFilters = $filterDao->getObjectsByGroup($filter);
		assert(count($nativeImportFilters) == 1); // Assert only a single unserialization filter
		$importFilter = array_shift($nativeImportFilters);
		$importFilter->setDeployment($deployment);
		return $importFilter->execute($importXml);
	}

	/**
	 * @copydoc PKPImportExportPlugin::usage
	 */
	function usage($scriptName) {
		echo __('plugins.importexport.native.cliUsage', array(
			'scriptName' => $scriptName,
			'pluginName' => $this->getName()
		)) . "\n";
	}

	/**
	 * @see PKPImportExportPlugin::executeCLI()
	 */
	function executeCLI($scriptName, &$args) {
		$command = array_shift($args);
		$xmlFile = array_shift($args);
		$journalPath = array_shift($args);

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER);

		$journalDao = DAORegistry::getDAO('JournalDAO');
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$userDao = DAORegistry::getDAO('UserDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');

		$journal = $journalDao->getByPath($journalPath);

		if (!$journal) {
			if ($journalPath != '') {
				echo __('plugins.importexport.common.cliError') . "\n";
				echo __('plugins.importexport.common.error.unknownJournal', array('journalPath' => $journalPath)) . "\n\n";
			}
			$this->usage($scriptName);
			return;
		}

		if ($xmlFile && $this->isRelativePath($xmlFile)) {
			$xmlFile = PWD . '/' . $xmlFile;
		}
		$outputDir = dirname($xmlFile);
		if (!is_writable($outputDir) || (file_exists($xmlFile) && !is_writable($xmlFile))) {
			echo __('plugins.importexport.common.cliError') . "\n";
			echo __('plugins.importexport.common.export.error.outputFileNotWritable', array('param' => $xmlFile)) . "\n\n";
			$this->usage($scriptName);
			return;
		}

		switch ($command) {
			case 'import':
				$userName = array_shift($args);
				$user = $userDao->getByUsername($userName);

				if (!$user) {
					if ($userName != '') {
						echo __('plugins.importexport.common.cliError') . "\n";
						echo __('plugins.importexport.native.error.unknownUser', array('userName' => $userName)) . "\n\n";
					}
					$this->usage($scriptName);
					return;
				}

				$this->importSubmissions(file_get_contents($xmlFile), $journal, $user);
				return;
			case 'export':
				if ($xmlFile != '') switch (array_shift($args)) {
					case 'article':
					case 'articles':
						file_put_contents($xmlFile, $this->exportSubmissions(
							$args,
							$journal,
							null
						));
						return;
					case 'issue':
					case 'issues':
						file_put_contents($xmlFile, $this->exportIssues(
							$args,
							$journal,
							null
						));
						return;
				}
				break;
		}
		$this->usage($scriptName);
	}

}

?>
