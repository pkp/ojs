<?php

/**
 * @file plugins/importexport/duracloud/DuraCloudImportExportPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DuraCloudImportExportPlugin
 * @ingroup plugins_importexport_duracloud
 *
 * @brief DuraCloud import/export plugin
 */

import('classes.plugins.ImportExportPlugin');

class DuraCloudImportExportPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'DuraCloudImportExportPlugin';
	}

	function getDisplayName() {
		return __('plugins.importexport.duracloud.displayName');
	}

	function getDescription() {
		return __('plugins.importexport.duracloud.description');
	}

	function display(&$args, $request) {
		$templateMgr =& TemplateManager::getManager();
		parent::display($args, $request);

		// Load the DuraCloud-PHP library.
		require_once('lib/DuraCloud-PHP/DuraCloudPHP.inc.php');

		$issueDao =& DAORegistry::getDAO('IssueDAO');

		$journal =& $request->getJournal();
		$user =& $request->getUser();
		switch (array_shift($args)) {
			case 'importIssue':
				$contentId = array_shift($args);
				$issue =& $this->importIssue($user, $journal, $contentId);
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign('results', array($contentId => $issue));
				$templateMgr->display($this->getTemplatePath() . 'importResults.tpl');
				return;
				break;
			case 'importIssues':
				$results =& $this->importIssues($user, $journal, $request->getUserVar('contentId'));
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign('results', $results);
				$templateMgr->display($this->getTemplatePath() . 'importResults.tpl');
				return;
				break;
			case 'exportIssues':
				$issueIds = $request->getUserVar('issueId');
				if (!isset($issueIds)) $issueIds = array();
				$issues = array();
				foreach ($issueIds as $issueId) {
					$issue =& $issueDao->getIssueById($issueId, $journal->getId());
					if (!$issue) $request->redirect();
					$issues[$issue->getId()] =& $issue;
					unset($issue);
				}
				$results = $this->exportIssues($journal, $issues);
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign('results', $results);
				$templateMgr->assign_by_ref('issues', $issues);
				$templateMgr->display($this->getTemplatePath() . 'exportResults.tpl');
				return;
			case 'exportIssue':
				$issueId = array_shift($args);
				$issue =& $issueDao->getIssueById($issueId, $journal->getId());
				if (!$issue) $request->redirect();
				$results = array($issue->getId() => $this->exportIssue($journal, $issue));
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign('results', $results);
				$templateMgr->assign('issues', array($issue->getId() => $issue));
				$templateMgr->display($this->getTemplatePath() . 'exportResults.tpl');
				return;
			case 'exportableIssues':
				// Display a list of issues for export
				$this->setBreadcrumbs(array(), true);
				AppLocale::requireComponents(LOCALE_COMPONENT_OJS_EDITOR);
				$issueDao =& DAORegistry::getDAO('IssueDAO');
				$issues =& $issueDao->getIssues($journal->getId(), Handler::getRangeInfo('issues'));

				$templateMgr->assign_by_ref('issues', $issues);
				$templateMgr->display($this->getTemplatePath() . 'exportableIssues.tpl');
				return;
			case 'importableIssues':
				// Display a list of issues for import
				$this->setBreadcrumbs(array(), true);
				AppLocale::requireComponents(LOCALE_COMPONENT_OJS_EDITOR);
				$templateMgr->assign('issues', $this->getImportableIssues());
				$templateMgr->display($this->getTemplatePath() . 'importableIssues.tpl');
				return;
			case 'signIn':
				$this->setBreadcrumbs();
				$this->import('DuraCloudLoginForm');
				$duraCloudLoginForm = new DuraCloudLoginForm($this);
				$duraCloudLoginForm->readInputData();
				if ($duraCloudLoginForm->validate()) {
					$duraCloudLoginForm->execute($this);
				}
				$duraCloudLoginForm->display($this);
				return;
			case 'signOut':
				$this->forgetDuraCloudConfiguration();
				break;
			case 'selectSpace':
				$this->setDuraCloudSpace($request->getUserVar('duracloudSpace'));
				break;
		}

		// If we fall through: display the form.
		$this->setBreadcrumbs();
		$this->import('DuraCloudLoginForm');
		$duraCloudLoginForm = new DuraCloudLoginForm($this);
		$duraCloudLoginForm->display($this);
	}

	/**
	 * Get the native import/export plugin.
	 */
	function &getNativeImportExportPlugin() {
		// Get the native import/export plugin.
		$nativeImportExportPlugin =& PluginRegistry::getPlugin('importexport', 'NativeImportExportPlugin');
		return $nativeImportExportPlugin;
	}

	/**
	 * Store an issue in DuraCloud.
	 * @param $journal Journal
	 * @param $issue Issue
	 * @return string location iff success; false otherwise
	 */
	function exportIssue(&$journal, &$issue) {
		// Export the native XML to a file.
		$nativeImportExportPlugin =& $this->getNativeImportExportPlugin();
		$filename = tempnam('duracloud', 'dcissue');
		$nativeImportExportPlugin->exportIssue($journal, $issue, $filename);

		// Store the file in DuraCloud.
		$dcc = $this->getDuraCloudConnection();
		$ds = new DuraStore($dcc);
		$descriptor = new DuraCloudContentDescriptor(array('creator' => $this->getName(), 'identification' => $issue->getIssueIdentification(), 'date_published' => $issue->getDatePublished(), 'num_articles' => $issue->getNumArticles()));
		$content = new DuraCloudFileContent($descriptor);
		$fp = fopen($filename, 'r');
		$content->setResource($fp);
		$location = $ds->storeContent($this->getDuraCloudSpace(), 'issue-' . $issue->getId(), $content);

		// Clean up temporary file
		unlink($filename);

		return $location;
	}

	/**
	 * Store several issues in DuraCloud.
	 * @param $journal Journal
	 * @param $issue Issue
	 * @return array of results for each issue (see exportIssue)
	 */
	function exportIssues(&$journal, &$issues) {
		$results = array();
		foreach ($issues as $issue) {
			$results[$issue->getId()] = $this->exportIssue($journal, $issue);
		}
		return $results;
	}

	/**
	 * Import an issue from DuraCloud.
	 * @param $user User
	 * @param $journal Journal
	 * @param $contentId string
	 * @return Issue iff success; false otherwise
	 */
	function importIssue(&$user, &$journal, $contentId) {
		// Get the file from DuraCloud.
		$dcc = $this->getDuraCloudConnection();
		$ds = new DuraStore($dcc);
		$content = $ds->getContent($this->getDuraCloudSpace(), $contentId);
		if (!$content) return false;

		// Get and reset the resource
		$fp =& $content->getResource();
		fseek($fp, 0);

		// Parse the document
		$nativeImportExportPlugin =& $this->getNativeImportExportPlugin();
		$doc =& $nativeImportExportPlugin->getDocument($fp);

		// Import the issue
		$nativeImportExportPlugin->import('NativeImportDom');
		$dependentItems = $errors = array();
		$issue = null;
		if (!NativeImportDom::importIssue($journal, $doc, $issue, $errors, $user, false, $dependentItems)) return false;

		return $issue;
	}

	/**
	 * Import issues from DuraCloud.
	 * @param $user User
	 * @param $journal Journal
	 * @param $contentId string
	 * @return array with result for each contentId (see importIssue)
	 */
	function importIssues(&$user, &$journal, $contentIds) {
		// Get the file from DuraCloud.
		$dcc = $this->getDuraCloudConnection();
		$ds = new DuraStore($dcc);
		$result = array();
		$dependentItems = $errors = array();
		$nativeImportExportPlugin =& $this->getNativeImportExportPlugin();

		foreach ($contentIds as $contentId) {
			$content = $ds->getContent($this->getDuraCloudSpace(), $contentId);
			if (!$content) {
				$result[$contentId] = false;
				continue;
			}

			// Get and reset the resource
			$fp =& $content->getResource();
			fseek($fp, 0);

			// Parse the document
			$doc =& $nativeImportExportPlugin->getDocument($fp);

			// Import the issue
			$nativeImportExportPlugin->import('NativeImportDom');
			$issue = null;
			NativeImportDom::importIssue($journal, $doc, $issue, $errors, $user, false, $dependentItems);
			$result[$contentId] =& $issue;

			unset($issue, $fp);
		}

		return $result;
	}


	/**
	 * Execute import/export tasks using the command-line interface.
	 * @param $args Parameters to the plugin
	 */ 
	function executeCLI($scriptName, &$args) {
		// First, DuraCloud access info
		$baseUrl = array_shift($args);
		$username = array_shift($args);
		$password = array_shift($args);

		// Load the DuraCloud-PHP library.
		require_once('lib/DuraCloud-PHP/DuraCloudPHP.inc.php');

		// Context and commands
		$journalPath = array_shift($args);
		$spaceId = array_shift($args);
		$command = array_shift($args);

		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');

		$journal =& $journalDao->getJournalByPath($journalPath);

		if (!$journal) {
			if ($journalPath != '') {
				echo __('plugins.importexport.duracloud.cliError') . "\n";
				echo __('plugins.importexport.duracloud.error.unknownJournal', array('journalPath' => $journalPath)) . "\n";
				return;
			}
			$this->usage($scriptName);
			return;
		}

		$this->storeDuraCloudConfiguration($baseUrl, $username, $password);
		$this->setDuraCloudSpace($spaceId);
		// Verify that the configuration and space ID are valid
		$dcc =& $this->getDuraCloudConnection();
		$ds = new DuraStore($dcc);
		if ($ds->getSpace($spaceId, $metadata) === false) {
			echo __('plugins.importexport.duracloud.cliError') . "\n";
			echo __('plugins.importexport.duracloud.configuration.credentialsInvalid') . "\n";
			return;
		}

		switch ($command) {
			case 'importIssues':
				$userName = array_shift($args);
				$user =& $userDao->getByUsername($userName);

				if (!$user) {
					if ($userName != '') {
						echo __('plugins.importexport.duracloud.cliError') . "\n";
						echo __('plugins.importexport.duracloud.error.unknownUser', array('userName' => $userName)) . "\n\n";
					}
					$this->usage($scriptName);
					return;
				}

				$results =& $this->importIssues($user, $journal, $args);
				AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON);
				foreach ($results as $id => $result) {
					echo "	$id: " . ($result?$result->getIssueIdentification():'') . "\n";
				}
				return;
				break;
			case 'exportIssues':
				$issues = array();
				foreach ($args as $issueId) {
					$issue =& $issueDao->getIssueById($issueId, $journal->getId());
					$issues[$issue->getId()] =& $issue;
					unset($issue);
				}
				$results = $this->exportIssues($journal, $issues);
				foreach ($results as $id => $result) {
					echo "	$id: $result\n";
				}
				return;
				break;
		}
		$this->usage($scriptName);
	}

	/**
	 * Display the command-line usage information
	 */
	function usage($scriptName) {
		echo __('plugins.importexport.duracloud.cliUsage', array(
			'scriptName' => $scriptName,
			'pluginName' => $this->getName()
		)) . "\n";
	}

	/**
	 * Store the DuraCloud configuration details for this session.
	 * @param $url string
	 * @param $username string
	 * @param $password string
	 */
	function storeDuraCloudConfiguration($url, $username, $password) {
		$sessionManager =& SessionManager::getManager();
		$session =& $sessionManager->getUserSession();
		$session->setSessionVar('duracloudUrl', $url);
		$session->setSessionVar('duracloudUsername', $username);
		$session->setSessionVar('duracloudPassword', $password);
	}

	/**
	 * Store the DuraCloud space to be used for this session.
	 * @param $space string
	 */
	function setDuraCloudSpace($space) {
		$sessionManager =& SessionManager::getManager();
		$session =& $sessionManager->getUserSession();
		$session->setSessionVar('duracloudSpace', $space);
	}

	/**
	 * Forget the stored DuraCloud configuration.
	 */
	function forgetDuraCloudConfiguration() {
		$this->storeDuraCloudConfiguration(null, null, null);
	}

	/**
	 * Get a DuraCloudConnection object corresponding to the current
	 * configuration.
	 * @return DuraCloudConnection
	 */
	function getDuraCloudConnection() {
		$sessionManager =& SessionManager::getManager();
		$session =& $sessionManager->getUserSession();
		return new DuraCloudConnection(
			$session->getSessionVar('duracloudUrl'),
			$session->getSessionVar('duracloudUsername'),
			$session->getSessionVar('duracloudPassword')
		);
	}

	/**
	 * Get the currently configured DuraCloud URL.
	 * @return string
	 */
	function getDuraCloudUrl() {
		$sessionManager =& SessionManager::getManager();
		$session =& $sessionManager->getUserSession();
		return $session->getSessionVar('duracloudUrl');
	}

	/**
	 * Get the currently configured DuraCloud username.
	 * @return string
	 */
	function getDuraCloudUsername() {
		$sessionManager =& SessionManager::getManager();
		$session =& $sessionManager->getUserSession();
		return $session->getSessionVar('duracloudUsername');
	}

	/**
	 * Get the currently configured DuraCloud username.
	 * @return string
	 */
	function getDuraCloudSpace() {
		$sessionManager =& SessionManager::getManager();
		$session =& $sessionManager->getUserSession();
		return $session->getSessionVar('duracloudSpace');
	}

	/**
	 * Check whether or not the DuraCloud connection is configured.
	 * @return boolean
	 */
	function isDuraCloudConfigured() {
		$sessionManager =& SessionManager::getManager();
		$session =& $sessionManager->getUserSession();
		return (boolean) $session->getSessionVar('duracloudUrl');
	}

	/**
	 * Get a list of importable issues from the DuraSpace instance.
	 * @return array(contentId => issueIdentification)
	 */
	function getImportableIssues() {
		$dcc =& $this->getDuraCloudConnection();
		$duraStore = new DuraStore($dcc);
		$spaceId = $this->getDuraCloudSpace();
		$contents = $duraStore->getSpace($spaceId, $metadata, null, 'issue-');
		if (!$contents) return $contents;

		$returner = array();
		foreach ($contents as $contentId) {
			$content = $duraStore->getContent($spaceId, $contentId);
			if (!$content) continue; // Could not fetch content

			$descriptor =& $content->getDescriptor();
			if (!$descriptor) continue; // Could not get descriptor

			$metadata = $descriptor->getMetadata();
			if (!$metadata) continue; // Could not get metadata

			if (!isset($metadata['creator']) || $metadata['creator'] != $this->getName()) continue; // Not created by this plugin

			if (!isset($metadata['identification'])) continue; // Could not get identification

			$returner[$contentId] = $metadata;
			unset($metadata);
		}

		return $returner;
	}
}

?>
