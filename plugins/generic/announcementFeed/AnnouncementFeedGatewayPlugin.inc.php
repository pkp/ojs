<?php

/**
 * @file AnnouncementFeedGatewayPlugin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementFeedGatewayPlugin
 * @ingroup plugins_generic_announcementFeed
 *
 * @brief Gateway component of announcement feed plugin
 *
 */

// $Id$


import('classes.plugins.GatewayPlugin');

class AnnouncementFeedGatewayPlugin extends GatewayPlugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'AnnouncementFeedGatewayPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.generic.announcementfeed.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.generic.announcementfeed.description');
	}

	/**
	 * Get the web feed plugin
	 * @return object
	 */
	function &getAnnouncementFeedPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', 'AnnouncementFeedPlugin');
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
		$limitRecentItems = $announcementFeedPlugin->getSetting($journal->getJournalId(), 'limitRecentItems');
		$recentItems = (int) $announcementFeedPlugin->getSetting($journal->getJournalId(), 'recentItems');

		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		$journalId = $journal->getJournalId();
		if ($limitRecentItems && $recentItems > 0) {
			import('db.DBResultRange');
			$rangeInfo =& new DBResultRange($recentItems, 1);
			$announcements = &$announcementDao->getAnnouncementsNotExpiredByJournalId($journalId, $rangeInfo);
		} else {
			$announcements = &$announcementDao->getAnnouncementsNotExpiredByJournalId($journalId);
		}

		// Get date of most recent announcement
		$lastDateUpdated = $announcementFeedPlugin->getSetting($journal->getJournalId(), 'dateUpdated');
		if ($announcements->wasEmpty()) {
			if (empty($lastDateUpdated)) { 
				$dateUpdated = Core::getCurrentDate(); 
				$announcementFeedPlugin->updateSetting($journal->getJournalId(), 'dateUpdated', $dateUpdated, 'string');			
			} else {
				$dateUpdated = $lastDateUpdated;
			}
		} else {
			$mostRecentAnnouncement = &$announcementDao->getMostRecentAnnouncementByJournalId($journalId);
			$dateUpdated = $mostRecentAnnouncement->getDatetimePosted();
			if (empty($lastDateUpdated) || (strtotime($dateUpdated) > strtotime($lastDateUpdated))) { 
				$announcementFeedPlugin->updateSetting($journal->getJournalId(), 'dateUpdated', $dateUpdated, 'string');			
			}
		}

		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$version =& $versionDao->getCurrentVersion();

		$templateMgr = &TemplateManager::getManager();
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
