<?php

/**
 * @file plugins/generic/openAds/OpenAdsBlockPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2009 Siavash Miri and Alec Smecher
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OpenAdsBlockPlugin
 * @ingroup plugins_generic_openAds
 *
 * @brief OpenAds plugin class, block component
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class OpenAdsBlockPlugin extends BlockPlugin {
	/** @var $parentPluginName string Name of parent plugin */
	var $parentPluginName;

	function OpenAdsBlockPlugin($parentPluginName) {
		$this->parentPluginName = $parentPluginName;
		parent::BlockPlugin();
	}

	/**
	 * Hide this plugin from the management interface (it's subsidiary)
	 */
	function getHideManagement() {
		return true;
	}

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
		return __('plugins.generic.openads');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.openads.description');
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
		$openAdsConnection = new OpenAdsConnection($plugin, $plugin->getInstallationPath());
		$sidebarAdHtml = $openAdsConnection->getAdHtml($plugin->getSetting($journal->getId(), 'sidebarAdId'));
		return '<div class="block">' . $sidebarAdHtml . '</div>';
	}

	/**
	 * Get the actual CMS plugin
	 * @return object
	 */
	function &getOpenAdsPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
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
