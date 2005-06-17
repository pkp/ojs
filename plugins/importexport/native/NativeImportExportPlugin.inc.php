<?php

/**
 * NativeImportExportPlugin.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Native import/export plugin
 *
 * $Id$
 */

import('classes.plugins.ImportExportPlugin');

import('xml.XMLWriter');

class NativeImportExportPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param @category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		// Additional registration / initialization code
		// should go here. For example, load additional locale data:
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		// This should not be used as this is an abstract class
		return 'NativeImportExportPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.importexport.native.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.importexport.native.description');
	}

	function display(&$args) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pluginUrl', $this->getPluginUrl());

		$issueDao = &DAORegistry::getDAO('IssueDAO');

		$journal = &Request::getJournal();
		switch (array_shift($args)) {
			case 'exportIssues':
				$issueIds = Request::getUserVar('issueId');
				$issues = array();
				foreach ($issueIds as $issueId) {
					$issue = &$issueDao->getIssueById($issueId);
					if (!$issue) Request::redirect($this->getPluginUrl());
					$issues[] = &$issue;
				}
				$this->exportIssues(&$journal, &$issues);
				break;
			case 'exportIssue':
				$issueId = array_shift($args);
				$issue = &$issueDao->getIssueById($issueId);
				if (!$issue) Request::redirect($this->getPluginUrl());
				$this->exportIssue(&$journal, &$issue);
				break;
			case 'exportArticle':
				$articleIds = array(array_shift($args));
				$result = array_shift(ArticleSearch::formatResults($articleIds));
				$this->exportArticle(&$journal, $result['issue'], $result['publishedArticle']);
				break;
			case 'exportArticles':
				$articleIds = Request::getUserVar('articleId');
				$results = &ArticleSearch::formatResults($articleIds);
				$this->exportArticles(&$results);
				break;
			case 'issues':
				// Display a list of issues for export
				$this->setBreadcrumbs(array(), true);
				$issueDao = &DAORegistry::getDAO('IssueDAO');
				$issues = $issueDao->getIssues($journal->getJournalId(), Handler::getRangeInfo('issues'));

				$templateMgr->assign_by_ref('issues', $issues);
				$templateMgr->display($this->getTemplatePath() . 'issues.tpl');
				break;
			case 'articles':
				// Display a list of articles for export
				$this->setBreadcrumbs(array(), true);
				$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
				$rangeInfo = Handler::getRangeInfo('articles');
				$articleIds = $publishedArticleDao->getPublishedArticleIdsAlphabetizedByJournal($journal->getJournalId(), &$rangeInfo);
				$totalArticles = count($articleIds);
				$articleIds = &array_slice(&$articleIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
				$iterator = new VirtualArrayIterator(ArticleSearch::formatResults($articleIds), $totalArticles, $rangeInfo->getPage(), $rangeInfo->getCount());
				$templateMgr->assign_by_ref('articles', $iterator);
				$templateMgr->display($this->getTemplatePath() . 'articles.tpl');
				break;
			case 'import':
				import('file.TemporaryFileManager');
				$user = &Request::getUser();
				$temporaryFileManager = new TemporaryFileManager();
				$temporaryFile = $temporaryFileManager->handleUpload('importFile', $user->getUserId());
				$this->handleImport(&$temporaryFile);
				break;
			default:
				$this->setBreadcrumbs();
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
		}
	}

	function exportIssue(&$journal, &$issue) {
		require_once(dirname(__FILE__) . '/NativeExportDom.inc.php');
		$doc = &XMLWriter::createDocument('issue', '/native.dtd');
		$issueNode = &NativeExportDom::generateIssueDom(&$doc, &$journal, &$issue);
		XMLWriter::appendChild(&$doc, &$issueNode);

		header("Content-Type: application/xml");
		echo XMLWriter::getXML(&$doc);
	}

	function exportArticle(&$journal, &$issue, &$article) {
		require_once(dirname(__FILE__) . '/NativeExportDom.inc.php');
		$doc = &XMLWriter::createDocument('article', '/native.dtd');
		$articleNode = &NativeExportDom::generateArticleDom(&$doc, &$journal, &$issue, &$article);
		XMLWriter::appendChild(&$doc, &$articleNode);

		header("Content-Type: application/xml");
		echo XMLWriter::getXML(&$doc);
	}

	function exportIssues(&$journal, &$issues) {
		require_once(dirname(__FILE__) . '/NativeExportDom.inc.php');
		$doc = &XMLWriter::createDocument('issues', '/native.dtd');
		$issuesNode = &XMLWriter::createElement(&$doc, 'issues');
		XMLWriter::appendChild(&$doc, &$issuesNode);

		foreach ($issues as $issue) {
			$issueNode = &NativeExportDom::generateIssueDom(&$doc, &$journal, &$issue);
			XMLWriter::appendChild(&$issuesNode, &$issueNode);
		}

		header("Content-Type: application/xml");
		echo XMLWriter::getXML(&$doc);
	}

	function exportArticles(&$results) {
		require_once(dirname(__FILE__) . '/NativeExportDom.inc.php');
		$doc = &XMLWriter::createDocument('articles', '/native.dtd');
		$articlesNode = &XMLWriter::createElement(&$doc, 'articles');
		XMLWriter::appendChild(&$doc, &$articlesNode);

		foreach ($results as $result) {
			$article = &$result['publishedArticle'];
			$issue = &$result['issue'];
			$journal = &$result['journal'];
			$articleNode = &NativeExportDom::generateArticleDom(&$doc, &$journal, &$issue, &$article);
			XMLWriter::appendChild(&$articlesNode, &$articleNode);
		}

		header("Content-Type: application/xml");
		echo XMLWriter::getXML(&$doc);
	}

	function handleImport(&$tempFile) {
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$templateMgr = &TemplateManager::getManager();

		$parser = new XMLParser();
		$doc = &$parser->parse($tempFile->getFilePath());

		$rootNodeName = $doc->name;

		switch ($rootNodeName) {
			case 'issues':
				require_once(dirname(__FILE__) . '/NativeImportDom.inc.php');
				$result = &NativeImportDom::importIssues(&$journal, &$doc->children, &$issues, &$errors, &$user, false);
				if ($result !== true) {
					$templateMgr->assign('errors', $errors);
					return $templateMgr->display($this->getTemplatePath() . 'importError.tpl');
				} else {
					$templateMgr->assign_by_ref('issues',  $issues);
					return $templateMgr->display($this->getTemplatePath() . 'importSuccess.tpl');
				}
				break;
			case 'issue':
				require_once(dirname(__FILE__) . '/NativeImportDom.inc.php');
				$result = &NativeImportDom::importIssue(&$journal, &$doc, &$issue, &$errors, &$user, false);
				if ($result !== true) {
					$templateMgr->assign('errors', $errors);
					return $templateMgr->display($this->getTemplatePath() . 'importError.tpl');
				} else {
					$templateMgr->assign('issues', array($issue));
					return $templateMgr->display($this->getTemplatePath() . 'importSuccess.tpl');
				}
				break;
			case 'articles':
				echo "FIXME: ARTICLES<br/>\n";
				break;
			case 'article':
				echo "FIXME: ARTICLE<br/>\n";
				break;
			default:
				$templateMgr->assign('error', 'plugins.importexport.native.import.error.unsupportedRoot');
				return $templateMgr->display($this->getTemplatePath() . 'importError.tpl');
				break;
		}
	}

	/**
	 * Execute import/export tasks using the command-line interface.
	 * @param $args Parameters to the plugin
	 */ 
	function executeCLI($scriptName, &$args) {
		switch (array_shift($args)) {
			case 'import':
				$xmlFile = array_shift($args);
				return;
			case 'export':
				$xmlFile = array_shift($args);
				return;
		}
		$this->usage($scriptName);
	}

	/**
	 * Display the command-line usage information
	 */
	function usage($scriptName) {
		echo Locale::translate('plugins.importexport.native.cliUsage', array(
			'scriptName' => $scriptName,
			'pluginName' => $this->getName()
		)) . "\n";
	}
}

?>
