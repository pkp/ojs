<?php

/**
 * @file PubMedExportPlugin.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubMedExportPlugin
 * @ingroup plugins_importexport_pubmed
 *
 * @brief PubMed/MEDLINE XML metadata export plugin
 */

// $Id$


import('classes.plugins.ImportExportPlugin');

class PubMedExportPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
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
		return 'PubMedExportPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.importexport.pubmed.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.importexport.pubmed.description');
	}

	function display(&$args) {
		$templateMgr = &TemplateManager::getManager();
		parent::display($args);

		$issueDao = &DAORegistry::getDAO('IssueDAO');

		$journal = &Request::getJournal();

		switch (array_shift($args)) {
			case 'exportIssues':
				$issueIds = Request::getUserVar('issueId');
				if (!isset($issueIds)) $issueIds = array();
				$issues = array();
				foreach ($issueIds as $issueId) {
					$issue = &$issueDao->getIssueById($issueId);
					if (!$issue) Request::redirect();
					$issues[] = &$issue;
				}
				$this->exportIssues($journal, $issues);
				break;
			case 'exportIssue':
				$issueId = array_shift($args);
				$issue = &$issueDao->getIssueById($issueId);
				if (!$issue) Request::redirect();
				$issues = array($issue);
				$this->exportIssues($journal, $issues);
				break;
			case 'exportArticle':
				$articleIds = array(array_shift($args));
				$result = ArticleSearch::formatResults($articleIds);
				$this->exportArticles($result);
				break;
			case 'exportArticles':
				$articleIds = Request::getUserVar('articleId');
				if (!isset($articleIds)) $articleIds = array();
				$results = &ArticleSearch::formatResults($articleIds);
				$this->exportArticles($results);
				break;
			case 'issues':
				// Display a list of issues for export
				$this->setBreadcrumbs(array(), true);
				$issueDao = &DAORegistry::getDAO('IssueDAO');
				$issues = &$issueDao->getIssues($journal->getJournalId(), Handler::getRangeInfo('issues'));

				$templateMgr->assign_by_ref('issues', $issues);
				$templateMgr->display($this->getTemplatePath() . 'issues.tpl');
				break;
			case 'articles':
				// Display a list of articles for export
				$this->setBreadcrumbs(array(), true);
				$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
				$rangeInfo = Handler::getRangeInfo('articles');
				$articleIds = $publishedArticleDao->getPublishedArticleIdsByJournal($journal->getJournalId(), false);
				$totalArticles = count($articleIds);
				if ($rangeInfo->isValid()) $articleIds = array_slice($articleIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
				$iterator = &new VirtualArrayIterator(ArticleSearch::formatResults($articleIds), $totalArticles, $rangeInfo->getPage(), $rangeInfo->getCount());
				$templateMgr->assign_by_ref('articles', $iterator);
				$templateMgr->display($this->getTemplatePath() . 'articles.tpl');
				break;
			default:
				$this->setBreadcrumbs();
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
		}
	}

	function exportArticles(&$results, $outputFile = null) {
		$this->import('PubMedExportDom');
		$doc = &PubMedExportDom::generatePubMedDom();
		$articleSetNode = &PubMedExportDom::generateArticleSetDom($doc);

		foreach ($results as $result) {
			$journal = &$result['journal'];
			$issue = &$result['issue'];
			$section = &$result['section'];
			$article = &$result['publishedArticle'];

			$articleNode = &PubMedExportDom::generateArticleDom($doc, $journal, $issue, $section, $article);
			XMLCustomWriter::appendChild($articleSetNode, $articleNode);
		}

		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'w'))===false) return false;
			fwrite($h, XMLCustomWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: attachment; filename=\"pubmed.xml\"");
			XMLCustomWriter::printXML($doc);
		}
		return true;
	}

	function exportIssues(&$journal, &$issues, $outputFile = null) {
		$this->import('PubMedExportDom');
		$doc = &PubMedExportDom::generatePubMedDom();
		$articleSetNode = &PubMedExportDom::generateArticleSetDom($doc);

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');

		foreach ($issues as $issue) {
			foreach ($sectionDao->getSectionsForIssue($issue->getIssueId()) as $section) {
				foreach ($publishedArticleDao->getPublishedArticlesBySectionId($section->getSectionId(), $issue->getIssueId()) as $article) {
					$articleNode = &PubMedExportDom::generateArticleDom($doc, $journal, $issue, $section, $article);
					XMLCustomWriter::appendChild($articleSetNode, $articleNode);
				}
			}
		}

		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'w'))===false) return false;
			fwrite($h, XMLCustomWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: attachment; filename=\"pubmed.xml\"");
			XMLCustomWriter::printXML($doc);
		}
		return true;
	}

	/**
	 * Execute import/export tasks using the command-line interface.
	 * @param $args Parameters to the plugin
	 */
	function executeCLI($scriptName, &$args) {
//		$command = array_shift($args);
		$xmlFile = array_shift($args);
		$journalPath = array_shift($args);

		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');

		$journal = &$journalDao->getJournalByPath($journalPath);

		if (!$journal) {
			if ($journalPath != '') {
				echo Locale::translate('plugins.importexport.pubmed.cliError') . "\n";
				echo Locale::translate('plugins.importexport.pubmed.error.unknownJournal', array('journalPath' => $journalPath)) . "\n\n";
			}
			$this->usage($scriptName);
			return;
		}

		if ($xmlFile != '') switch (array_shift($args)) {
			case 'articles':
				$results = &ArticleSearch::formatResults($args);
				if (!$this->exportArticles($results, $xmlFile)) {
					echo Locale::translate('plugins.importexport.pubmed.cliError') . "\n";
					echo Locale::translate('plugins.importexport.pubmed.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
				}
				return;
			case 'issue':
				$issueId = array_shift($args);
				$issue = &$issueDao->getIssueByBestIssueId($issueId, $journal->getJournalId());
				if ($issue == null) {
					echo Locale::translate('plugins.importexport.pubmed.cliError') . "\n";
					echo Locale::translate('plugins.importexport.pubmed.export.error.issueNotFound', array('issueId' => $issueId)) . "\n\n";
					return;
				}
				$issues = array($issue);
				if (!$this->exportIssues($journal, $issues, $xmlFile)) {
					echo Locale::translate('plugins.importexport.pubmed.cliError') . "\n";
					echo Locale::translate('plugins.importexport.pubmed.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
				}
				return;
		}
		$this->usage($scriptName);

	}

	/**
	 * Display the command-line usage information
	 */
	function usage($scriptName) {
		echo Locale::translate('plugins.importexport.pubmed.cliUsage', array(
			'scriptName' => $scriptName,
			'pluginName' => $this->getName()
		)) . "\n";
	}
}

?>
