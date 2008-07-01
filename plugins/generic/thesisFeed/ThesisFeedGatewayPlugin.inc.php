<?php

/**
 * @file ThesisFeedGatewayPlugin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ThesisFeedGatewayPlugin
 * @ingroup plugins_generic_thesisFeed
 *
 * @brief Gateway component of thesis feed plugin
 *
 */

// $Id$


import('classes.plugins.GatewayPlugin');

class ThesisFeedGatewayPlugin extends GatewayPlugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'ThesisFeedGatewayPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.generic.thesisfeed.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.generic.thesisfeed.description');
	}

	/**
	 * Get the web feed plugin
	 * @return object
	 */
	function &getThesisFeedPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', 'ThesisFeedPlugin');
		return $plugin;
	}

	/**
	 * Get the thesis plugin
	 * @return object
	 */
	function &getThesisPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', 'ThesisPlugin');
		return $plugin;
	}

	/**
	 * Override the builtin to get the correct plugin path.
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
	 * Handle fetch requests for this plugin.
	 */
	function fetch($args) {
		// Make sure we're within a Journal context
		$journal =& Request::getJournal();
		if (!$journal) return false;

		// Make sure thesis abstracts and feed plugin are enabled
		$thesisPlugin =& $this->getThesisPlugin();
		$thesisEnabled = $thesisPlugin->getEnabled(); 
		$thesisFeedPlugin =& $this->getThesisFeedPlugin();
		$thesisFeedPluginEnabled = $thesisFeedPlugin->getEnabled();

		if (!$thesisEnabled || !$thesisFeedPluginEnabled) return false;

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
		$limitRecentItems = $thesisFeedPlugin->getSetting($journal->getJournalId(), 'limitRecentItems');
		$recentItems = (int) $thesisFeedPlugin->getSetting($journal->getJournalId(), 'recentItems');

		$thesisDao =& DAORegistry::getDAO('ThesisDAO');
		$journalId = $journal->getJournalId();
		if ($limitRecentItems && $recentItems > 0) {
			import('db.DBResultRange');
			$rangeInfo =& new DBResultRange($recentItems, 1);
			$theses =& $thesisDao->getActiveThesesByJournalId($journalId, null, null, null, null, null, null, $rangeInfo);
		} else {
			$theses =& $thesisDao->getActiveThesesByJournalId($journalId);
		}

		// Get date of most recent thesis
		$lastDateUpdated = $thesisFeedPlugin->getSetting($journal->getJournalId(), 'dateUpdated');
		if ($theses->wasEmpty()) {
			if (empty($lastDateUpdated)) { 
				$dateUpdated = Core::getCurrentDate(); 
				$thesisFeedPlugin->updateSetting($journal->getJournalId(), 'dateUpdated', $dateUpdated, 'string');			
			} else {
				$dateUpdated = $lastDateUpdated;
			}
		} else {
			$mostRecentThesis =& $thesisDao->getMostRecentActiveThesisByJournalId($journalId);
			$dateUpdated = $mostRecentThesis->getDateSubmitted();
			if (empty($lastDateUpdated) || (strtotime($dateUpdated) > strtotime($lastDateUpdated))) { 
				$thesisFeedPlugin->updateSetting($journal->getJournalId(), 'dateUpdated', $dateUpdated, 'string');			
			}
		}

		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$version =& $versionDao->getCurrentVersion();

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('ojsVersion', $version->getVersionString());
		$templateMgr->assign('selfUrl', Request::getCompleteUrl()); 
		$templateMgr->assign('dateUpdated', $dateUpdated);
		$templateMgr->assign_by_ref('theses', $theses->toArray());
		$templateMgr->assign_by_ref('journal', $journal);

		$templateMgr->display($this->getTemplatePath() . $typeMap[$type], $mimeTypeMap[$type]);

		return true;
	}
}

?>
