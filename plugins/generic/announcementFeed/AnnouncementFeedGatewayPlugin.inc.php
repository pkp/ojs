<?php

/**
 * @file plugins/generic/announcementFeed/AnnouncementFeedGatewayPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementFeedGatewayPlugin
 * @ingroup plugins_generic_announcementFeed
 *
 * @brief Gateway component of announcement feed plugin
 *
 */

import('classes.plugins.GatewayPlugin');

class AnnouncementFeedGatewayPlugin extends GatewayPlugin {
	var $parentPluginName;

	/**
	 * Constructor
	 */
	function AnnouncementFeedGatewayPlugin($parentPluginName) {
		$this->parentPluginName = $parentPluginName;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'AnnouncementFeedGatewayPlugin';
	}

	/**
	 * Hide this plugin from the management interface (it's subsidiary)
	 */
	function getHideManagement() {
		return true;
	}

	function getDisplayName() {
		return __('plugins.generic.announcementfeed.displayName');
	}

	function getDescription() {
		return __('plugins.generic.announcementfeed.description');
	}

	/**
	 * Get the web feed plugin
	 * @return object
	 */
	function &getAnnouncementFeedPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		return $plugin;
	}

	/**
	 * Override the builtin to get the correct plugin path.
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
	 * Get whether or not this plugin is enabled. (Should always return true, as the
	 * parent plugin will take care of loading this one when needed)
	 * @return boolean
	 */
	function getEnabled() {
		$plugin =& $this->getAnnouncementFeedPlugin();
		return $plugin->getEnabled(); // Should always be true anyway if this is loaded
	}

	/**
	 * Get the management verbs for this plugin (override to none so that the parent
	 * plugin can handle this)
	 * @return array
	 */
	function getManagementVerbs() {
		return array();
	}

	/**
	 * Handle fetch requests for this plugin.
	 */
	function fetch($args) {
		// Make sure we're within a Journal context
		$journal =& Request::getJournal();
		if (!$journal) return false;

		// Make sure announcements and plugin are enabled
		$announcementsEnabled = $journal->getSetting('enableAnnouncements');
		$announcementFeedPlugin =& $this->getAnnouncementFeedPlugin();
		if (!$announcementsEnabled || !$announcementFeedPlugin->getEnabled()) return false;

		// Make sure the feed type is specified and valid
		$type = array_shift($args);
		$typeMap = array(
			'rss' => 'rss.tpl',
			'rss2' => 'rss2.tpl',
			'atom' => 'atom.tpl'
		);
		$mimeTypeMap = array(
			'rss' => 'application/rdf+xml',
			'rss2' => 'application/rss+xml',
			'atom' => 'application/atom+xml'
		);
		if (!isset($typeMap[$type])) return false;

		// Get limit setting, if any 
		$limitRecentItems = $announcementFeedPlugin->getSetting($journal->getId(), 'limitRecentItems');
		$recentItems = (int) $announcementFeedPlugin->getSetting($journal->getId(), 'recentItems');

		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		$journalId = $journal->getId();
		if ($limitRecentItems && $recentItems > 0) {
			import('lib.pkp.classes.db.DBResultRange');
			$rangeInfo = new DBResultRange($recentItems, 1);
			$announcements =& $announcementDao->getAnnouncementsNotExpiredByAssocId(ASSOC_TYPE_JOURNAL, $journalId, $rangeInfo);
		} else {
			$announcements =& $announcementDao->getAnnouncementsNotExpiredByAssocId(ASSOC_TYPE_JOURNAL, $journalId);
		}

		// Get date of most recent announcement
		$lastDateUpdated = $announcementFeedPlugin->getSetting($journal->getId(), 'dateUpdated');
		if ($announcements->wasEmpty()) {
			if (empty($lastDateUpdated)) { 
				$dateUpdated = Core::getCurrentDate(); 
				$announcementFeedPlugin->updateSetting($journal->getId(), 'dateUpdated', $dateUpdated, 'string');			
			} else {
				$dateUpdated = $lastDateUpdated;
			}
		} else {
			$mostRecentAnnouncement =& $announcementDao->getMostRecentAnnouncementByAssocId(ASSOC_TYPE_JOURNAL, $journalId);
			$dateUpdated = $mostRecentAnnouncement->getDatetimePosted();
			if (empty($lastDateUpdated) || (strtotime($dateUpdated) > strtotime($lastDateUpdated))) { 
				$announcementFeedPlugin->updateSetting($journal->getId(), 'dateUpdated', $dateUpdated, 'string');			
			}
		}

		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$version =& $versionDao->getCurrentVersion();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('ojsVersion', $version->getVersionString());
		$templateMgr->assign('selfUrl', Request::getCompleteUrl()); 
		$templateMgr->assign('dateUpdated', $dateUpdated);
		$templateMgr->assign_by_ref('announcements', $announcements->toArray());
		$templateMgr->assign_by_ref('journal', $journal);

		$templateMgr->display($this->getTemplatePath() . $typeMap[$type], $mimeTypeMap[$type]);

		return true;
	}
}

?>
