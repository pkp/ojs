<?php

/**
 * @file plugins/importexport/native/NativeImportExportPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NativeImportExportPlugin
 * @ingroup plugins_importexport_native
 *
 * @brief Native XML import/export plugin
 */
use Colors\Color;

import('lib.pkp.plugins.importexport.native.PKPNativeImportExportPlugin');

class NativeImportExportPlugin extends PKPNativeImportExportPlugin {

	/**
	 * Display the plugin.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function display($args, $request) {
		$context = $request->getContext();
		$user = $request->getUser();
		$deployment = new NativeImportExportDeployment($context, $user);

		$this->setDeployment($deployment);

		$templateMgr = TemplateManager::getManager($request);

		list ($returnString, $managed) = parent::display($args, $request);

		if ($managed) {
			if ($returnString) {
				return $returnString;
			}

			return;
		}

		switch (array_shift($args)) {
			case 'exportIssuesBounce':
				return $this->getBounceTab($request,
					__('plugins.importexport.native.export.issues.results'),
						'exportIssues',
						array('selectedIssues' => $request->getUserVar('selectedIssues'))
				);
			case 'exportSubmissions':
				$submissionIds = (array) $request->getUserVar('selectedSubmissions');
				$deployment = new NativeImportExportDeployment($request->getContext(), $request->getUser());

				$this->getExportSubmissionsDeployment($submissionIds, $deployment);

				return $this->getExportTemplateResult($deployment, $templateMgr, 'articles');
			case 'exportIssues':
				$selectedEntitiesIds = (array) $request->getUserVar('selectedIssues');
				$deployment = new NativeImportExportDeployment($request->getContext(), $request->getUser());

				$this->getExportIssuesDeployment($selectedEntitiesIds, $deployment);

				return $this->getExportTemplateResult($deployment, $templateMgr, 'issues');
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
	 * @param $opts array
	 * @return string XML contents representing the supplied submission IDs.
	 */
	function exportSubmissions($submissionIds, $context, $user, $opts = array()) {
		$deployment = new NativeImportExportDeployment($context, $user);
		$this->getExportSubmissionsDeployment($submissionIds, $deployment, $opts);

		return $this->exportResultXML($deployment);
	}

	function getExportIssuesDeployment($issueIds, &$deployment, $opts = array()) {
		$issueDao = DAORegistry::getDAO('IssueDAO'); /** @var $issueDao IssueDAO */
		$issues = array();
		foreach ($issueIds as $issueId) {
			$issue = $issueDao->getById($issueId, $deployment->getContext()->getId());
			if ($issue) $issues[] = $issue;
		}

		$deployment->export('issue=>native-xml', $issues, $opts);
	}

	/**
	 * Get the XML for a set of issues.
	 * @param $issueIds array Array of issue IDs
	 * @param $context Context
	 * @param $user User
	 * @return string XML contents representing the supplied issue IDs.
	 */
	function exportIssues($issueIds, $context, $user, $opts = array()) {
		$deployment = new NativeImportExportDeployment($context, $user);
		$this->getExportIssuesDeployment($issueIds, $deployment, $opts);

		return $this->exportResultXML($deployment);
	}

	function getImportFilter($xmlFile) {
		$filter = 'native-xml=>issue';
		// is this articles import:
		$xmlString = file_get_contents($xmlFile);
		$document = new DOMDocument();
		$document->loadXml($xmlString);
		if (in_array($document->documentElement->tagName, array('article', 'articles'))) {
			$filter = 'native-xml=>article';
		}

		return array($filter, $xmlString);
	}

	function getExportFilter($exportType) {
		$filter = 'issue=>native-xml';
		if ($exportType == 'exportSubmissions') {
			$filter = 'article=>native-xml';
		}

		return $filter;
	}


	/**
	 * @see PKPImportExportPlugin::executeCLI()
	 */
	function executeCLI($scriptName, &$args) {
		$opts = $this->parseOpts($args, ['no-embed', 'use-file-urls']);
		$command = array_shift($args);
		$xmlFile = array_shift($args);
		$journalPath = array_shift($args);

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER, LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_PKP_SUBMISSION);

		$journalDao = DAORegistry::getDAO('JournalDAO'); /** @var $journalDao JournalDAO */
		$userDao = DAORegistry::getDAO('UserDAO'); /** @var $userDao UserDAO */

		$journal = $journalDao->getByPath($journalPath);

		if (!$journal) {
			if ($journalPath != '') {
				$this->echoCLIError(__('plugins.importexport.common.error.unknownJournal', array('journalPath' => $journalPath)));
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
						$this->echoCLIError(__('plugins.importexport.native.error.unknownUser', array('userName' => $userName)));
					}
					$this->usage($scriptName);
					return;
				}

				if (!file_exists($xmlFile)) {
					$this->echoCLIError(__('plugins.importexport.common.export.error.inputFileNotReadable', array('param' => $xmlFile)));

					$this->usage($scriptName);
					return;
				}

				list ($filter, $xmlString) = $this->getImportFilter($xmlFile);

				$deployment = new NativeImportExportDeployment($journal, $user);
				$deployment->setImportPath(dirname($xmlFile));

				$deployment->import($filter, $xmlString);

				$this->getCLIImportResult($deployment);
				$this->getCLIProblems($deployment);
				return;
			case 'export':
				$deployment = new NativeImportExportDeployment($journal, null);

				$outputDir = dirname($xmlFile);
				if (!is_writable($outputDir) || (file_exists($xmlFile) && !is_writable($xmlFile))) {
					$this->echoCLIError(__('plugins.importexport.common.export.error.outputFileNotWritable', array('param' => $xmlFile)));

					$this->usage($scriptName);
					return;
				}
				if ($xmlFile != '') switch (array_shift($args)) {
					case 'article':
					case 'articles':
						$this->getExportSubmissionsDeployment(
							$args,
							$deployment,
							$opts
						);

						$this->getCLIExportResult($deployment, $xmlFile);
						$this->getCLIProblems($deployment);
						return;
					case 'issue':
					case 'issues':
						$this->getExportIssuesDeployment(
							$args,
							$deployment,
							$opts
						);

						$this->getCLIExportResult($deployment, $xmlFile);
						$this->getCLIProblems($deployment);

						return;
				}
				break;
		}
		$this->usage($scriptName);
	}

}


