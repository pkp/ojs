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

class LucenePlugin extends GenericPlugin {

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
			HookRegistry::register('ArticleSearch::retrieveResults', array(&$this, 'callbackRetrieveResults'));
			HookRegistry::register('ArticleSearchIndex::deleteTextIndex', array(&$this, 'callbackDeleteTextIndex'));
			HookRegistry::register('ArticleSearchIndex::indexArticleFiles', array(&$this, 'callbackIndexArticleFiles'));
			HookRegistry::register('ArticleSearchIndex::indexArticleMetadata', array(&$this, 'callbackIndexArticleMetadata'));
			HookRegistry::register('ArticleSearchIndex::indexSuppFileMetadata', array(&$this, 'callbackIndexSuppFileMetadata'));
			HookRegistry::register('ArticleSearchIndex::updateFileIndex', array(&$this, 'callbackUpdateFileIndex'));
			HookRegistry::register('ArticleSearchIndex::rebuildIndex', array(&$this, 'callbackRebuildIndex'));
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


	//
	// Public Search API
	//
	/**
	 * @see ArticleSearch::retrieveResults()
	 */
	function callbackRetrieveResults($hookName, $params) {
		// FIXME: Not yet implemented.
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
		// FIXME: Not yet implemented.
	}
}
?>
