<?php

/**
 * @file plugins/generic/metadataExport/MetadataExportHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.metadataExport
 * @class MetadataExportHandler
 */

import('classes.handler.Handler');

class MetadataExportHandler extends Handler {

	/* @var journal object Current journal */
	var $journal;
	
	/* @var array Articles of the exported issue/journal */
	var $articles;
	
	/* @var publishedArticleDAO object */
	var $publishedArticleDao;
	
	/* @var issueDAO object */
	var $issueDao;
	
	/* @var array Plugins of the metadataexport category */
	var $metadataExportPlugins;
	
	/* @var string Function defining the scope of the export (article/issue/journal) */
	var $exportFunction;
	
	/* @var string Name of the current export plugin */
	var $exportPluginName;
	
	/* @var metadataExportPlugin object Current export plugin object */
	var $exportPlugin;
	
	/* @var int Id of the current article/issue */
	var $elementId;
	
	/* @var string URL of the previously visited page */
	var $referrer;
	
	
	/**
	 * Call the respective export function (for article, issue or journal).
	 * @param $args Array
	 */
	function export($args) {
		$scope = Request::getUserVar('exportScope');
		
		$this->exportPluginName = Request::getUserVar('exportPluginName');
		$this->elementId = Request::getUserVar('elementId');
		$this->referrer = Request::getUserVar('referrer');
		$this->metadataExportPlugins = PluginRegistry::loadCategory('generic/metadataExport/metadataExportFormats');
		$this->exportPlugin = $this->metadataExportPlugins[$this->exportPluginName];
		$this->journal = Request::getJournal();
		$this->publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$this->issueDao = DAORegistry::getDAO('IssueDAO');

		switch ($scope) {
			case 'exportArticle':
				$this->_exportArticle();
				break;
			case 'exportIssue':
				$this->_exportIssue();
				break;
			case 'exportJournal':
				$this->_exportJournal();
				break;
			default:
				assert(false);
		}
	}
	
	/**
	 * Export the current article
	 */
	function _exportArticle() {
		$journalId = $this->journal->getId();
		$articleId = $this->elementId;
		$article = $this->publishedArticleDao->getPublishedArticleByArticleId($articleId, $journalId);
		
		if ($article) {
			$this->articles = array($article);
			$this->_createFile();
		} else {
			Request::redirectUrl($this->referrer);
		}
	}
	
	/**
	 * Export the current issue
	 */
	function _exportIssue() {
		$journalId = $this->journal->getId();
		$issueId = $this->elementId;
		$issue = $this->issueDao->getIssueById($issueId, $journalId);
		
		if ($issue->getPublished()) {
			$this->articles = $this->publishedArticleDao->getPublishedArticles($issueId);
			$this->_createFile();
		} else {
			Request::redirectUrl($this->referrer);
		}
	}
	
	/**
	 * Export the current journal
	 */
	function _exportJournal() {
		$journalId = $this->journal->getId();
		$articleIterator = $this->publishedArticleDao->getPublishedArticlesByJournalId($journalId);
		$this->articles = array();
		
		while ($article = $articleIterator->next()) {
			$this->articles[] = $article;
		}
		unset($articleIterator);
		
		$this->_createFile();
	}
	
	/**
	 * Create text or xml file
	 */
	function _createFile() {
		if ($this->exportPlugin->isXML()) {
			$this->exportPlugin->createXmlFile($this->journal, $this->articles);
		} else {
			$this->exportPlugin->createTextFile($this->journal, $this->articles);
		}
	}
	
	/**
	 * Display plugin info page
	 */
	function info() {
		$metadataExportPlugin = PluginRegistry::getPlugin('generic', METADATA_EXPORT_PLUGIN_NAME);
		
		$templateMgr = TemplateManager::getManager();
		$templateMgr->display($metadataExportPlugin->getTemplatePath() . '/info.tpl');
	}
}
?>