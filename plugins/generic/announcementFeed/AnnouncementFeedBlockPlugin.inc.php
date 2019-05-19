<?php

/**
 * @file plugins/generic/announcementFeed/AnnouncementFeedBlockPlugin.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementFeedBlockPlugin
 * @ingroup plugins_generic_announcementFeed
 *
 * @brief Class for block component of announcement feed plugin
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class AnnouncementFeedBlockPlugin extends BlockPlugin {
	protected $_parentPlugin;

	/**
	 * Constructor
	 * @param $parentPlugin AnnouncementFeedPlugin
	 */
	public function __construct($parentPlugin) {
		$this->_parentPlugin = $parentPlugin;
		parent::__construct();
	}

	/**
	 * Hide this plugin from the management interface (it's subsidiary)
	 */
	public function getHideManagement() {
		return true;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	public function getName() {
		return 'AnnouncementFeedBlockPlugin';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	public function getDisplayName() {
		return __('plugins.generic.announcementfeed.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	public function getDescription() {
		return __('plugins.generic.announcementfeed.description');
	}

	/**
	 * Override the builtin to get the correct plugin path.
	 * @return string
	 */
	public function getPluginPath() {
		return $this->_parentPlugin->getPluginPath();
	}

	/**
	 * @see BlockPlugin::getContents
	 */
	public function getContents($templateMgr, $request = null) {
		$journal = $request->getJournal();
		if (!$journal) return '';

		if (!$journal->getData('enableAnnouncements')) return '';

		$displayPage = $this->_parentPlugin->getSetting($journal->getId(), 'displayPage');
		$requestedPage = $request->getRequestedPage();

		if (($displayPage == 'all') || ($displayPage == 'homepage' && (empty($requestedPage) || $requestedPage == 'index' || $requestedPage == 'announcement')) || ($displayPage == $requestedPage)) {
			return parent::getContents($templateMgr, $request);
		} else {
			return '';
		}
	}
}
