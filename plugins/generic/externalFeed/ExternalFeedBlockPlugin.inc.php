<?php

/**
 * @file plugins/generic/externalFeed/ExternalFeedBlockPlugin.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ExternalFeedBlockPlugin
 * @ingroup plugins_generic_externalFeed
 *
 * @brief Class for block component of external feed plugin
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class ExternalFeedBlockPlugin extends BlockPlugin {
	/** @var ExternalFeedPlugin reference to external feed plugin */
	protected $_parentPlugin = null;

	/**
	 * Constructor
	 * @param $plugin ExternalFeedPlugin
	 */
	public function __construct($plugin) {
		$this->_parentPlugin = $plugin;
		parent::__construct();
	}

	/**
	 * @copydoc Plugin::getHideManagement()
	 */
	public function getHideManagement() {
		return true;
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	public function getDisplayName() {
		return __('plugins.generic.externalFeed.block.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	public function getDescription() {
		return __('plugins.generic.externalFeed.description');
	}

	/**
	 * Get the external feed plugin
	 * @return object
	 */
	public function getExternalFeedPlugin() {
		return $this->_parentPlugin;
	}

	/**
	 * @copydoc Plugin::getPluginPath()
	 */
	public function getPluginPath() {
		$plugin = $this->getExternalFeedPlugin();
		return $plugin->getPluginPath();
	}

	/**
	 * @copydoc Plugin::getTemplatePath()
	 */
	public function getTemplatePath($inCore = false) {
		return $this->getExternalFeedPlugin()->getTemplatePath($inCore);
	}

	/**
	 * Get the HTML contents for this block.
	 * @param $templateMgr object
	 * @param $request PKPRequest
	 * @return $string
	 */
	public function getContents($templateMgr, $request = null) {
		$context = $request->getContext();
		if (!$context) return '';

		$plugin = $this->getExternalFeedPlugin();
		if (!$plugin->getEnabled()) return '';

		$requestedPage = $request->getRequestedPage();
		$externalFeedDao = DAORegistry::getDAO('ExternalFeedDAO');

		$feeds = $externalFeedDao->getByContextId($context->getId());
		while ($currentFeed = $feeds->next()) {
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

		$templateMgr->assign('externalFeeds', $externalFeeds);
		return parent::getContents($templateMgr, $request);
	}
}

?>
