<?php

/**
 * @file KeywordCloudBlockPlugin.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class KeywordCloudBlockPlugin
 * @ingroup plugins_blocks_keyword_cloud
 *
 * @brief Class for keyword cloud block plugin
 */

// $Id$


import('plugins.BlockPlugin');

define('KEYWORD_BLOCK_MAX_ITEMS', 20);
define('KEYWORD_BLOCK_CACHE_DAYS', 2);

class KeywordCloudBlockPlugin extends BlockPlugin {
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			$this->addLocaleData();
		}
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'KeywordCloudBlockPlugin';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return Locale::translate('plugins.block.keywordCloud.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return Locale::translate('plugins.block.keywordCloud.description');
	}

	/**
	 * Get the supported contexts (e.g. BLOCK_CONTEXT_...) for this block.
	 * @return array
	 */
	function getSupportedContexts() {
		return array(BLOCK_CONTEXT_LEFT_SIDEBAR, BLOCK_CONTEXT_RIGHT_SIDEBAR);
	}

	function _cacheMiss(&$cache, $id) {
		$keywordMap = array();
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticles =& $publishedArticleDao->getPublishedArticlesByJournalId($cache->getCacheId());
		while ($publishedArticle =& $publishedArticles->next()) {
			$keywords = array_map('trim', explode(';', $publishedArticle->getLocalizedSubject()));
			foreach ($keywords as $keyword) if (!empty($keyword)) $keywordMap[$keyword]++;
			unset($publishedArticle);
		}
		arsort($keywordMap, SORT_NUMERIC);

		$i=0;
		$newKeywordMap = array();
		foreach ($keywordMap as $k => $v) {
			$newKeywordMap[$k] = $v;
			if ($i++ >= KEYWORD_BLOCK_MAX_ITEMS) break;
		}

		$cache->setEntireCache($newKeywordMap);
		return $newKeywordMap[$id];
	}

	/**
	 * Get the HTML contents for this block.
	 * @param $templateMgr object
	 * @return $string
	 */
	function getContents(&$templateMgr) {
		$journal =& Request::getJournal();

		import('cache.CacheManager');
		$cacheManager =& CacheManager::getManager();
		$cache =& $cacheManager->getFileCache('keywords_' . Locale::getLocale(), $journal->getJournalId(), array(&$this, '_cacheMiss'));
		// If the cache is older than a couple of days, regenerate it
		if (time() - $cache->getCacheTime() > 60 * 60 * 24 * KEYWORD_BLOCK_CACHE_DAYS) $cache->flush();

		$keywords =& $cache->getContents();
		if (empty($keywords)) return '';

		// Get the max occurrences for all keywords
		$maxOccurs = array_shift(array_values($keywords));

		// Now sort the array alphabetically
		ksort($keywords);

		$templateMgr->assign_by_ref('cloudKeywords', $keywords);
		$templateMgr->assign_by_ref('maxOccurs', $maxOccurs);

		return parent::getContents($templateMgr);
	}
}

?>
