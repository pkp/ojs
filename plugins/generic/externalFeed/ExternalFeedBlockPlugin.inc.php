<?php

/**
 * @file ExternalFeedBlockPlugin.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ExternalFeedBlockPlugin
 * @ingroup plugins_generic_externalFeed
 *
 * @brief Class for block component of external feed plugin
 */

// $Id$


import('plugins.BlockPlugin');

class ExternalFeedBlockPlugin extends BlockPlugin {
	/**
	 * Hide this plugin from the management interface (it's subsidiary)
	 */
	function getHideManagement() {
		return true;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'ExternalFeedBlockPlugin';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return Locale::translate('plugins.generic.externalFeed.block.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return Locale::translate('plugins.generic.externalFeed.description');
	}

	/**
	 * Get the supported contexts (e.g. BLOCK_CONTEXT_...) for this block.
	 * @return array
	 */
	function getSupportedContexts() {
		return array(BLOCK_CONTEXT_LEFT_SIDEBAR, BLOCK_CONTEXT_RIGHT_SIDEBAR);
	}

	/**
	 * Get the external feed plugin
	 * @return object
	 */
	function &getExternalFeedPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', 'ExternalFeedPlugin');
		return $plugin;
	}

	/**
	 * Override the builtin to get the correct plugin path.
	 * @return string
	 */
	function getPluginPath() {
		$plugin =& $this->getExternalFeedPlugin();
		return $plugin->getPluginPath();
	}

	/**
	 * Get the HTML contents for this block.
	 * @param $templateMgr object
	 * @return $string
	 */
	function getContents(&$templateMgr) {
		$journal =& Request::getJournal();
		if (!$journal) return '';

		$journalId = $journal->getJournalId();
		$plugin =& $this->getExternalFeedPlugin();
		if (!$plugin->getEnabled()) return '';

		$requestedPage = Request::getRequestedPage();
		$externalFeedDao =& DAORegistry::getDAO('ExternalFeedDAO');
		$plugin->import('simplepie.SimplePie');
		import('cache.CacheManager');

		$feeds =& $externalFeedDao->getExternalFeedsByJournalId($journal->getJournalId());

		while ($currentFeed =& $feeds->next()) {		
			$displayBlock = $currentFeed->getDisplayBlock();
			if (($displayBlock == EXTERNAL_FEED_DISPLAY_BLOCK_NONE) || 
				(($displayBlock == EXTERNAL_FEED_DISPLAY_BLOCK_HOMEPAGE &&
				(!empty($requestedPage)) && $requestedPage != 'index'))
			) continue;

			$feed =& new SimplePie();
			$feed->set_feed_url($currentFeed->getUrl());
			$feed->enable_order_by_date(false);
			$feed->set_cache_location(CacheManager::getFileCachePath());
			$feed->init();

			if ($currentFeed->getLimitItems()) {
				$recentItems = $currentFeed->getRecentItems();
			} else {
				$recentItems = 0;
			}

			$externalFeeds[] = array(
				'title' => $currentFeed->getLocalizedTitle(),
				'items' => $feed->get_items(0, $recentItems)
			);
		}

		if (!isset($externalFeeds)) return '';

		$templateMgr->assign_by_ref('externalFeeds', $externalFeeds);
		return parent::getContents($templateMgr);
	}
}

?>
