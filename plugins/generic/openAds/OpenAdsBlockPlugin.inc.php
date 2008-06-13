<?php

/**
 * @file OpenAdsBlockPlugin.inc.php
 *
 * Copyright (c) 2003-2008 Siavash Miri and Alec Smecher
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.openAds
 * @class OpenAdsBlockPlugin
 *
 * OpenAds plugin class, block component
 *
 * $Id$
 */

import('classes.plugins.BlockPlugin');

class OpenAdsBlockPlugin extends BlockPlugin {
	/**
	 * Get the symbolic name of this plugin
	 * @return string
	 */
	function getName() {
		return 'OpenAdsBlockPlugin';
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return Locale::translate('plugins.generic.openads');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return Locale::translate('plugins.generic.openads.description');
	}

	/**
	 * Override the builtin to get the correct plugin path.
	 * @return string
	 */
	function getPluginPath() {
		$plugin =& $this->getOpenAdsPlugin();
		return $plugin->getPluginPath();
	}

	/**
	 * Override the builtin to get the correct template path.
	 * @return string
	 */
	function getTemplatePath() {
		$plugin =& $this->getOpenAdsPlugin();
		return $plugin->getTemplatePath();
	}

	/**
	 * Get the supported contexts (e.g. BLOCK_CONTEXT_...) for this block.
	 * @return array
	 */
	function getSupportedContexts() {
		return array(BLOCK_CONTEXT_LEFT_SIDEBAR, BLOCK_CONTEXT_RIGHT_SIDEBAR);
	}

	/**
	 * Get the contents of the CMS plugin TOC.
	 * @param $templateMgr object
	 * @return string
	 */
	function getContents(&$templateMgr) {
		$journal =& Request::getJournal();
		if (!$journal) return '';

		// Get the ad settings.
		$plugin =& $this->getOpenAdsPlugin();
		$this->import('OpenAdsConnection');
		$openAdsConnection =& new OpenAdsConnection($plugin, $plugin->getInstallationPath());
		$sidebarAdHtml = $openAdsConnection->getAdHtml($plugin->getSetting($journal->getJournalId(), 'sidebarAdId'));
		return '<div class="block">' . $sidebarAdHtml . '</div>';
	}

	/**
	 * Get the actual CMS plugin
	 * @return object
	 */
	function &getOpenAdsPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', 'OpenAdsPlugin');
		return $plugin;
	}

	/**
	 * Determine whether or not this plugin is enabled.
	 * @return boolean
	 */
	function getEnabled() {
		$plugin =& $this->getOpenAdsPlugin();
		return $plugin->getEnabled();
	}
}

?>
