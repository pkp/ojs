<?php

/**
 * @file plugins/generic/externalFeed/ExternalFeedBlockPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ExternalFeedBlockPlugin
 * @ingroup plugins_generic_externalFeed
 *
 * @brief Class for block component of external feed plugin
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class ExternalFeedBlockPlugin extends BlockPlugin {
	/** @var $parentPluginName string Name of parent plugin */
	var $parentPluginName;

	function ExternalFeedBlockPlugin($parentPluginName) {
		$this->parentPluginName = $parentPluginName;
	}

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
		return __('plugins.generic.externalFeed.block.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.generic.externalFeed.description');
	}

	/**
	 * Get the external feed plugin
	 * @return object
	 */
	function &getExternalFeedPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
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

		$journalId = $journal->getId();
		$plugin =& $this->getExternalFeedPlugin();
		if (!$plugin->getEnabled()) return '';

		$requestedPage = Request::getRequestedPage();
		$externalFeedDao =& DAORegistry::getDAO('ExternalFeedDAO');
		$plugin->import('simplepie.SimplePie');

		$feeds =& $externalFeedDao->getExternalFeedsByJournalId($journal->getId());

		while ($currentFeed =& $feeds->next()) {
			$displayBlock = $currentFeed->getDisplayBlock();
			if (($displayBlock == EXTERNAL_FEED_DISPLAY_BLOCK_NONE) ||
				(($displayBlock == EXTERNAL_FEED_DISPLAY_BLOCK_HOMEPAGE &&
				(!empty($requestedPage)) && $requestedPage != 'index'))
			) continue;

			$feed = new SimplePie();
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
