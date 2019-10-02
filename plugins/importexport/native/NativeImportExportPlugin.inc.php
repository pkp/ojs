<?php

/**
 * @file plugins/importexport/native/NativeImportExportPlugin.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
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
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		$this->addLocaleData();
		$this->import('NativeImportExportDeployment');
		return $success;
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
		$context = $request->getContext();
		switch (array_shift($args)) {
			case 'index':
			case '':
				$exportSubmissionsListPanel = new \PKP\components\listPanels\PKPSelectSubmissionsListPanel(
					'exportSubmissionsListPanel',
					__('plugins.importexport.native.exportSubmissionsSelect'),
					[
						'apiUrl' => $request->getDispatcher()->url(
							$request,
							ROUTE_API,
							$context->getPath(),
							'_submissions'
						),
						'canSelect' => true,
						'canSelectAll' => true,
						'count' => 100,
						'lazyLoad' => true,
						'selectorName' => 'selectedSubmissions[]',
					]
				);
				$templateMgr->assign('exportSubmissionsListData', [
					'components' => [
						'exportSubmissionsListPanel' => $exportSubmissionsListPanel->getConfig()
					]
				]);
				$templateMgr->display($this->getTemplateResource('index.tpl'));
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
				header('Content-Type: application/json');
				return $json->getString();
			case 'importBounce':
				$json = new JSONMessage(true);
				$json->setEvent('addTab', array(
					'title' => __('plugins.importexport.native.results'),
					'url' => $request->url(null, null, null, array('plugin', $this->getName(), 'import'), array('temporaryFileId' => $request->getUserVar('temporaryFileId'))),
				));
				header('Content-Type: application/json');
				return $json->getString();
			case 'import':
				AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);
				$temporaryFileId = $request->getUserVar('temporaryFileId');
				$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
				$user = $request->getUser();
				$temporaryFile = $temporaryFileDao->getTemporaryFile($temporaryFileId, $user->getId());
				if (!$temporaryFile) {
					$json = new JSONMessage(true, __('plugins.inportexport.native.uploadFile'));
					header('Content-Type: application/json');
					return $json->getString();
				}
				$temporaryFilePath = $temporaryFile->getFilePath();

				$filter = 'native-xml=>issue';
				// is this articles import:
				$xmlString = file_get_contents($temporaryFilePath);
				$document = new DOMDocument();
				$document->loadXml($xmlString);
				if (in_array($document->documentElement->tagName, array('article', 'articles'))) {
					$filter = 'native-xml=>article';
				}

				$deployment = new NativeImportExportDeployment($context, $user);

				libxml_use_internal_errors(true);
				$content = $this->importSubmissions(file_get_contents($temporaryFilePath), $filter, $deployment);
				$templateMgr->assign('content', $content);
				$validationErrors = array_filter(libxml_get_errors(), function($a) {
					return $a->level == LIBXML_ERR_ERROR || $a->level == LIBXML_ERR_FATAL;
				});
				$templateMgr->assign('validationErrors', $validationErrors);
				libxml_clear_errors();

				// Are there any import warnings? Display them.
				$warningTypes = array(
					ASSOC_TYPE_ISSUE => 'issuesWarnings',
					ASSOC_TYPE_SUBMISSION => 'submissionsWarnings',
					ASSOC_TYPE_SECTION => 'sectionWarnings',
				);
				foreach ($warningTypes as $assocType => $templateVar) {
					$foundWarnings = $deployment->getProcessedObjectsWarnings($assocType);
					if (!empty($foundWarnings)) {
						$templateMgr->assign($templateVar, $foundWarnings);
					}
				}

				// Are there any import errors? Display them.
				$errorTypes = array(
					ASSOC_TYPE_ISSUE => 'issuesErrors',
					ASSOC_TYPE_SUBMISSION => 'submissionsErrors',
					ASSOC_TYPE_SECTION => 'sectionErrors',
				);
				$foundErrors = false;
				foreach ($errorTypes as $assocType => $templateVar) {
					$currentErrors = $deployment->getProcessedObjectsErrors($assocType);
					if (!empty($currentErrors)) {
						$templateMgr->assign($templateVar, $currentErrors);
						$foundErrors = true;
					}
				}
				// If there are any data or validataion errors
				// delete imported objects.
				if ($foundErrors || !empty($validationErrors)) {
					// remove all imported issues and sumissions
					foreach (array_keys($errorTypes) as $assocType) {
						$deployment->removeImportedObjects($assocType);
					}
				}
				// Display the results
				$json = new JSONMessage(true, $templateMgr->fetch($this->getTemplateResource('results.tpl')));
				header('Content-Type: application/json');
				return $json->getString();
			case 'exportSubmissions':
				$exportXml = $this->exportSubmissions(
					(array) $request->getUserVar('selectedSubmissions'),
					$request->getContext(),
					$request->getUser()
				);
				import('lib.pkp.classes.file.FileManager');
				$fileManager = new FileManager();
				$exportFileName = $this->getExportFileName($this->getExportPath(), 'articles', $context, '.xml');
				$fileManager->writeFile($exportFileName, $exportXml);
				$fileManager->downloadByPath($exportFileName);
				$fileManager->deleteByPath($exportFileName);
				break;
			case 'exportIssues':
				$exportXml = $this->exportIssues(
					(array) $request->getUserVar('selectedIssues'),
					$request->getContext(),
					$request->getUser()
				);
				import('lib.pkp.classes.file.FileManager');
				$fileManager = new FileManager();
				$exportFileName = $this->getExportFileName($this->getExportPath(), 'issues', $context, '.xml');
				$fileManager->writeFile($exportFileName, $exportXml);
				$fileManager->downloadByPath($exportFileName);
				$fileManager->deleteByPath($exportFileName);
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
		$errors = array_filter(libxml_get_errors(), function($a) {
			return $a->level == LIBXML_ERR_ERROR || $a->level == LIBXML_ERR_FATAL;
		});
		if (!empty($errors)) {
			$this->displayXMLValidationErrors($errors, $xml);
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
		$errors = array_filter(libxml_get_errors(), function($a) {
			return $a->level == LIBXML_ERR_ERROR || $a->level == LIBXML_ERR_FATAL;
		});
		if (!empty($errors)) {
			$this->displayXMLValidationErrors($errors, $xml);
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

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER, LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_PKP_SUBMISSION);

		$journalDao = DAORegistry::getDAO('JournalDAO');
		$userDao = DAORegistry::getDAO('UserDAO');

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

				if (!file_exists($xmlFile)) {
					echo __('plugins.importexport.common.cliError') . "\n";
					echo __('plugins.importexport.common.export.error.inputFileNotReadable', array('param' => $xmlFile)) . "\n\n";
					$this->usage($scriptName);
					return;
				}

				$filter = 'native-xml=>issue';
				// is this articles import:
				$xmlString = file_get_contents($xmlFile);
				$document = new DOMDocument();
				$document->loadXml($xmlString);
				if (in_array($document->documentElement->tagName, array('article', 'articles'))) {
					$filter = 'native-xml=>article';
				}
				$deployment = new NativeImportExportDeployment($journal, $user);
				$deployment->setImportPath(dirname($xmlFile));
				$content = $this->importSubmissions($xmlString, $filter, $deployment);
				$validationErrors = array_filter(libxml_get_errors(), function($a) {
					return $a->level == LIBXML_ERR_ERROR || $a->level == LIBXML_ERR_FATAL;
				});

				// Are there any import warnings? Display them.
				$errorTypes = array(
					ASSOC_TYPE_ISSUE => 'issue.issue',
					ASSOC_TYPE_SUBMISSION => 'submission.submission',
					ASSOC_TYPE_SECTION => 'section.section',
				);
				foreach ($errorTypes as $assocType => $localeKey) {
					$foundWarnings = $deployment->getProcessedObjectsWarnings($assocType);
					if (!empty($foundWarnings)) {
						echo __('plugins.importexport.common.warningsEncountered') . "\n";
						$i = 0;
						foreach ($foundWarnings as $foundWarningMessages) {
							if (count($foundWarningMessages) > 0) {
								echo ++$i . '.' . __($localeKey) . "\n";
								foreach ($foundWarningMessages as $foundWarningMessage) {
									echo '- ' . $foundWarningMessage . "\n";
								}
							}
						}
					}
				}

				// Are there any import errors? Display them.
				$foundErrors = false;
				foreach ($errorTypes as $assocType => $localeKey) {
					$currentErrors = $deployment->getProcessedObjectsErrors($assocType);
					if (!empty($currentErrors)) {
						echo __('plugins.importexport.common.errorsOccured') . "\n";
						$i = 0;
						foreach ($currentErrors as $currentErrorMessages) {
							if (count($currentErrorMessages) > 0) {
								echo ++$i . '.' . __($localeKey) . "\n";
								foreach ($currentErrorMessages as $currentErrorMessage) {
									echo '- ' . $currentErrorMessage . "\n";
								}
							}
						}
						$foundErrors = true;
					}
				}
				// If there are any data or validataion errors
				// delete imported objects.
				if ($foundErrors || !empty($validationErrors)) {
					// remove all imported issues and sumissions
					foreach (array_keys($errorTypes) as $assocType) {
						$deployment->removeImportedObjects($assocType);
					}
				}
				return;
			case 'export':
				$outputDir = dirname($xmlFile);
				if (!is_writable($outputDir) || (file_exists($xmlFile) && !is_writable($xmlFile))) {
					echo __('plugins.importexport.common.cliError') . "\n";
					echo __('plugins.importexport.common.export.error.outputFileNotWritable', array('param' => $xmlFile)) . "\n\n";
					$this->usage($scriptName);
					return;
				}
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


