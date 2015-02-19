<?php

/**
 * @file plugins/generic/thesisFeed/ThesisFeedBlockPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ThesisFeedBlockPlugin
 * @ingroup plugins_generic_thesisFeed
 *
 * @brief Class for block component of thesis feed plugin
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class ThesisFeedBlockPlugin extends BlockPlugin {
	/** @var $parentPluginName string Name of parent plugin */
	var $parentPluginName;

	/**
	 * Constructor
	 */
	function ThesisFeedBlockPlugin($parentPluginName) {
		parent::BlockPlugin();
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
		return 'ThesisFeedBlockPlugin';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.generic.thesisfeed.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.generic.thesisfeed.description');
	}

	/**
	 * Get the thesis feed plugin
	 * @return object
	 */
	function &getThesisFeedPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		return $plugin;
	}

	/**
	 * Override the builtin to get the correct plugin path.
	 * @return string
	 */
	function getPluginPath() {
		$plugin =& $this->getThesisFeedPlugin();
		return $plugin->getPluginPath();
	}

	/**
	 * Override the builtin to get the correct template path.
	 * @return string
	 */
	function getTemplatePath() {
		$plugin =& $this->getThesisFeedPlugin();
		return $plugin->getTemplatePath() . 'templates/';
	}

	/**
	 * Get the HTML contents for this block.
	 * @param $templateMgr object
	 * @return $string
	 */
	function getContents(&$templateMgr) {
		$journal =& Request::getJournal();
		if (!$journal) return '';

		// If the thesis plugin isn't enabled, don't do anything.
		$application =& PKPApplication::getApplication();
		$products =& $application->getEnabledProducts('plugins.generic');
		if (!isset($products['thesis'])) return '';

		$plugin =& $this->getThesisFeedPlugin();
		$displayPage = $plugin->getSetting($journal->getId(), 'displayPage');
		$requestedPage = Request::getRequestedPage();

		if (($displayPage == 'all') || ($displayPage == 'homepage' && (empty($requestedPage) || $requestedPage == 'index' || $requestedPage == 'thesis')) || ($displayPage == $requestedPage)) {
			return parent::getContents($templateMgr);
		} else {
			return '';
		}
	}
}

?>
