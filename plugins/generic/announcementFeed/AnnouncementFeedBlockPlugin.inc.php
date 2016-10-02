<?php

/**
 * @file plugins/generic/announcementFeed/AnnouncementFeedBlockPlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementFeedBlockPlugin
 * @ingroup plugins_generic_announcementFeed
 *
 * @brief Class for block component of announcement feed plugin
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class AnnouncementFeedBlockPlugin extends BlockPlugin {
	var $parentPluginName;

	/**
	 * Constructor
	 */
	function AnnouncementFeedBlockPlugin($parentPluginName) {
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
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'AnnouncementFeedBlockPlugin';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.generic.announcementfeed.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.generic.announcementfeed.description');
	}

	/**
	 * Get the announcement feed plugin
	 * @return object
	 */
	function &getAnnouncementFeedPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		return $plugin;
	}

	/**
	 * Override the builtin to get the correct plugin path.
	 * @return string
	 */
	function getPluginPath() {
		$plugin =& $this->getAnnouncementFeedPlugin();
		return $plugin->getPluginPath();
	}

	/**
	 * Override the builtin to get the correct template path.
	 * @return string
	 */
	function getTemplatePath() {
		$plugin =& $this->getAnnouncementFeedPlugin();
		return $plugin->getTemplatePath() . 'templates/';
	}

	/**
	 * @see BlockPlugin::getContents
	 */
	function getContents(&$templateMgr, $request = null) {
		$journal = $request->getJournal();
		if (!$journal) return '';

		if (!$journal->getSetting('enableAnnouncements')) return '';

		$plugin =& $this->getAnnouncementFeedPlugin();
		$displayPage = $plugin->getSetting($journal->getId(), 'displayPage');
		$requestedPage = $request->getRequestedPage();

		if (($displayPage == 'all') || ($displayPage == 'homepage' && (empty($requestedPage) || $requestedPage == 'index' || $requestedPage == 'announcement')) || ($displayPage == $requestedPage)) {
			return parent::getContents($templateMgr, $request);
		} else {
			return '';
		}
	}
}

?>
