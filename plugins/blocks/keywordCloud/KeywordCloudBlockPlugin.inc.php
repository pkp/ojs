<?php

/**
 * @file plugins/blocks/keywordCloud/KeywordCloudBlockPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class KeywordCloudBlockPlugin
 * @ingroup plugins_blocks_keyword_cloud
 *
 * @brief Class for keyword cloud block plugin
 */

import('lib.pkp.classes.plugins.BlockPlugin');

define('KEYWORD_BLOCK_MAX_ITEMS', 20);
define('KEYWORD_BLOCK_CACHE_DAYS', 2);

class KeywordCloudBlockPlugin extends BlockPlugin {
	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.block.keywordCloud.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.block.keywordCloud.description');
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

		$cacheManager =& CacheManager::getManager();
		$cache =& $cacheManager->getFileCache('keywords_' . AppLocale::getLocale(), $journal->getId(), array(&$this, '_cacheMiss'));
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
