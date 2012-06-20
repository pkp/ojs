<?php

/**
 * @file plugins/generic/lucene/LucenePlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LucenePlugin
 * @ingroup plugins_generic_lucene
 *
 * @brief Lucene plugin class
 */


import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.lucene.SolrWebService');

class LucenePlugin extends GenericPlugin {

	/** @var SolrWebService */
	var $_solrWebService;

	//
	// Constructor
	//
	function LucenePlugin() {
		parent::GenericPlugin();
	}


	//
	// Implement template methods from PKPPlugin.
	//
	/**
	 * @see PKPPlugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
		if ($success && $this->getEnabled()) {
			// Register callbacks.
			HookRegistry::register('ArticleSearch::retrieveResults', array(&$this, 'callbackRetrieveResults'));
			HookRegistry::register('ArticleSearchIndex::deleteTextIndex', array(&$this, 'callbackDeleteTextIndex'));
			HookRegistry::register('ArticleSearchIndex::indexArticleFiles', array(&$this, 'callbackIndexArticleFiles'));
			HookRegistry::register('ArticleSearchIndex::indexArticleMetadata', array(&$this, 'callbackIndexArticleMetadata'));
			HookRegistry::register('ArticleSearchIndex::indexSuppFileMetadata', array(&$this, 'callbackIndexSuppFileMetadata'));
			HookRegistry::register('ArticleSearchIndex::updateFileIndex', array(&$this, 'callbackUpdateFileIndex'));
			HookRegistry::register('ArticleSearchIndex::rebuildIndex', array(&$this, 'callbackRebuildIndex'));

			// Instantiate the web service.
			$this->_solrWebService = new SolrWebService();
		}
		return $success;
	}

	/**
	 * @see PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.lucene.displayName');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.lucene.description');
	}

	/**
	 * @see PKPPlugin::getInstallSitePluginSettingsFile()
	 */
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * @see PKPPlugin::isSitePlugin()
	 */
	function isSitePlugin() {
		return true;
	}


	//
	// Public Search API
	//
	/**
	 * @see ArticleSearch::retrieveResults()
	 */
	function callbackRetrieveResults($hookName, $params) {
		assert($hookName == 'ArticleSearch::retrieveResults');

		// Unpack the parameters.
		list($journal, $search, $fromDate, $toDate) = $params;

		// Translate the search to the Lucene search fields.
		$searchTypes = array(
			ARTICLE_SEARCH_AUTHOR => 'authors',
			ARTICLE_SEARCH_TITLE => 'title',
			ARTICLE_SEARCH_ABSTRACT => 'abstract',
			ARTICLE_SEARCH_DISCIPLINE => 'discipline',
			ARTICLE_SEARCH_SUBJECT => 'subject',
			ARTICLE_SEARCH_TYPE => 'type',
			ARTICLE_SEARCH_COVERAGE => 'coverage',
			ARTICLE_SEARCH_GALLEY_FILE => 'galley_full_text',
			ARTICLE_SEARCH_SUPPLEMENTARY_FILE => 'suppFile_full_text',
			ARTICLE_SEARCH_INDEX_TERMS => 'index_terms'
		);
		$translatedSearch = array();
		foreach($search as $type => $query) {
			if (empty($type)) {
				$translatedSearch['all'] = $query;
			} else {
				assert(isset($searchTypes[$type]));
				$translatedSearch[$searchTypes[$type]] = $query;
			}
		}

		// Transform the date format. Lucene does not accept localized ISO-8601 dates
		// so we cannot use date('c',...).
		if (!empty($fromDate)) $fromDate = str_replace(' ', 'T', $fromDate) . 'Z';
		if (!empty($toDate)) $toDate = str_replace(' ', 'T', $fromDate) . 'Z';

		// Call the SOLR web service.
		return $this->_solrWebService->retrieveResults($journal, $translatedSearch, $fromDate, $toDate);
	}


	//
	// Public Indexing API
	//
	/**
	 * @see ArticleSearchIndex::deleteTextIndex()
	 */
	function callbackDeleteTextIndex($hookName, $params) {
		// FIXME: Not yet implemented.
	}

	/**
	 * @see ArticleSearchIndex::indexArticleFiles()
	 */
	function callbackIndexArticleFiles($hookName, $params) {
		// FIXME: Not yet implemented.
	}

	/**
	 * @see ArticleSearchIndex::indexArticleMetadata()
	 */
	function callbackIndexArticleMetadata($hookName, $params) {
		// FIXME: Not yet implemented.
	}

	/**
	 * @see ArticleSearchIndex::indexSuppFileMetadata()
	 */
	function callbackIndexSuppFileMetadata($hookName, $params) {
		// FIXME: Not yet implemented.
	}

	/**
	 * @see ArticleSearchIndex::updateFileIndex()
	 */
	function callbackUpdateFileIndex($hookName, $params) {
		// FIXME: Not yet implemented.
	}

	/**
	 * @see ArticleSearchIndex::rebuildIndex()
	 */
	function callbackRebuildIndex($hookName, $params) {
		assert($hookName == 'ArticleSearchIndex::rebuildIndex');

		// Unpack the parameters.
		list($log) = $params;

		// Clear index
		if ($log) echo 'LucenePlugin: Clearing index ... ';
		$this->_solrWebService->deleteAllArticlesFromIndex();
		if ($log) echo "done\n";

		// Build index
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journals =& $journalDao->getJournals();
		while (!$journals->eof()) {
			$journal =& $journals->next();
			$numIndexed = 0;

			if ($log) echo "LucenePlugin: Indexing \"", $journal->getLocalizedTitle(), "\" ... ";

			$numIndexed = $this->_solrWebService->indexJournal($journal);

			if ($log) echo $numIndexed, " articles indexed\n";
			unset($journal);
		}
		return true;
	}
}
?>
