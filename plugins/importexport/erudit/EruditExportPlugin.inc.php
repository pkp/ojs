<?php

/**
 * EruditImportExportPlugin.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Erudit english DTD article export plugin
 *
 * $Id$
 */

import('classes.plugins.ImportExportPlugin');

import('xml.XMLCustomWriter');

class EruditExportPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param @category String Name of category plugin was registered to
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
		return 'EruditExportPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.importexport.erudit.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.importexport.erudit.description');
	}

	function display(&$args) {
		$templateMgr = &TemplateManager::getManager();
		parent::display();

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$articleGalleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');

		$journal = &Request::getJournal();
		switch (array_shift($args)) {
			case 'exportGalley':
				$articleId = array_shift($args);
				$galleyId = array_shift($args);

				$article = &$publishedArticleDao->getPublishedArticleByArticleId($articleId);
				$galley = &$articleGalleyDao->getGalley($galleyId, $articleId);
				if ($article && $galley && ($issue = &$issueDao->getIssueById($article->getIssueId(), $journal->getJournalId()))) {
					$this->exportArticle($journal, $issue, $article, $galley);
					break;
				}
			default:
				// Display a list of articles for export
				$this->setBreadcrumbs();
				$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
				$rangeInfo = Handler::getRangeInfo('articles');
				$articleIds = $publishedArticleDao->getPublishedArticleIdsAlphabetizedByJournal($journal->getJournalId(), &$rangeInfo);
				$totalArticles = count($articleIds);
				$articleIds = array_slice(&$articleIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
				$iterator = &new VirtualArrayIterator(ArticleSearch::formatResults($articleIds), $totalArticles, $rangeInfo->getPage(), $rangeInfo->getCount());
				$templateMgr->assign_by_ref('articles', $iterator);
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
				break;
		}
	}

	function exportArticle(&$journal, &$issue, &$article, &$galley, $outputFile = null) {
		$this->import('EruditExportDom');
		$doc = &XMLCustomWriter::createDocument('article', '-//ERUDIT//Erudit Article DTD 3.0.0//EN', 'http://www.erudit.org/dtd/article/3.0.0/en/eruditarticle.dtd');
		$articleNode = &EruditExportDom::generateArticleDom($doc, $journal, $issue, $article, $galley);
		XMLCustomWriter::appendChild(&$doc, &$articleNode);

		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'w'))===false) return false;
			fwrite($h, XMLCustomWriter::getXML(&$doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			XMLCustomWriter::printXML(&$doc);
		}
		return true;
	}

	/**
	 * Execute export tasks using the command-line interface.
	 * @param $args Parameters to the plugin
	 */ 
	function executeCLI($scriptName, &$args) {
		$xmlFile = array_shift($args);
		$journalPath = array_shift($args);
		$articleId = array_shift($args);
		$galleyLabel = array_shift($args);

		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');

		$journal = &$journalDao->getJournalByPath($journalPath);

		if (!$journal) {
			if ($journalPath != '') {
				echo Locale::translate('plugins.importexport.erudit.cliError') . "\n";
				echo Locale::translate('plugins.importexport.erudit.error.unknownJournal', array('journalPath' => $journalPath)) . "\n\n";
			}
			$this->usage($scriptName);
			return;
		}

		$publishedArticle = &$publishedArticleDao->getPublishedArticleByBestArticleId($journal->getJournalId(), $articleId);
		if ($publishedArticle == null) {
			echo Locale::translate('plugins.importexport.erudit.cliError') . "\n";
			echo Locale::translate('plugins.importexport.erudit.export.error.articleNotFound', array('articleId' => $articleId)) . "\n\n";
			return;
		}
		foreach ($publishedArticle->getGalleys() as $thisGalley) {
			if ($thisGalley->getLabel() == $galleyLabel) {
				$galley =& $thisGalley;
				break;
			}
		}
		if (!isset($galley)) {
			echo Locale::translate('plugins.importexport.erudit.export.error.galleyNotFound', array('galleyLabel' => $galleyLabel)) . "\n\n";
			return;
		}
		$issue = &$issueDao->getIssueById($publishedArticle->getIssueId());
		if (!$this->exportArticle(&$journal, &$issue, &$publishedArticle, $galley, $xmlFile)) {
			echo Locale::translate('plugins.importexport.erudit.cliError') . "\n";
			echo Locale::translate('plugins.importexport.erudit.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
		}
	}

	/**
	 * Display the command-line usage information
	 */
	function usage($scriptName) {
		echo Locale::translate('plugins.importexport.erudit.cliUsage', array(
			'scriptName' => $scriptName,
			'pluginName' => $this->getName()
		)) . "\n";
	}
}

?>
