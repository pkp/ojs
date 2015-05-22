<?php

/**
 * @file plugins/generic/thesisFeed/ThesisFeedGatewayPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ThesisFeedGatewayPlugin
 * @ingroup plugins_generic_thesisFeed
 *
 * @brief Gateway component of thesis feed plugin
 *
 */

import('classes.plugins.GatewayPlugin');

class ThesisFeedGatewayPlugin extends GatewayPlugin {
	/** @var $parentPluginName string Name of parent plugin */
	var $parentPluginName;

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'ThesisFeedGatewayPlugin';
	}

	/**
	 * Constructor
	 */
	function ThesisFeedGatewayPlugin($parentPluginName) {
		parent::GatewayPlugin();
		$this->parentPluginName = $parentPluginName;
	}

	function getDisplayName() {
		return __('plugins.generic.thesisfeed.displayName');
	}

	function getDescription() {
		return __('plugins.generic.thesisfeed.description');
	}

	/**
	 * Get the web feed plugin
	 * @return object
	 */
	function &getThesisFeedPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
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
	 * Get whether or not this plugin is enabled. (Should always return true, as the
	 * parent plugin will take care of loading this one when needed)
	 * @return boolean
	 */
	function getEnabled() {
		$plugin =& $this->getThesisFeedPlugin();
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

		// Make sure thesis abstracts and feed plugin are enabled
		$application =& PKPApplication::getApplication();
		$products = $application->getEnabledProducts('plugins.generic');
		$thesisEnabled = $products['thesis'];
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
		$limitRecentItems = $thesisFeedPlugin->getSetting($journal->getId(), 'limitRecentItems');
		$recentItems = (int) $thesisFeedPlugin->getSetting($journal->getId(), 'recentItems');

		$thesisDao =& DAORegistry::getDAO('ThesisDAO');
		$journalId = $journal->getId();
		if ($limitRecentItems && $recentItems > 0) {
			import('lib.pkp.classes.db.DBResultRange');
			$rangeInfo = new DBResultRange($recentItems, 1);
			$theses =& $thesisDao->getActiveThesesByJournalId($journalId, null, null, null, null, null, null, $rangeInfo);
		} else {
			$theses =& $thesisDao->getActiveThesesByJournalId($journalId);
		}

		// Get date of most recent thesis
		$lastDateUpdated = $thesisFeedPlugin->getSetting($journal->getId(), 'dateUpdated');
		if ($theses->wasEmpty()) {
			if (empty($lastDateUpdated)) { 
				$dateUpdated = Core::getCurrentDate(); 
				$thesisFeedPlugin->updateSetting($journal->getId(), 'dateUpdated', $dateUpdated, 'string');			
			} else {
				$dateUpdated = $lastDateUpdated;
			}
		} else {
			$mostRecentThesis =& $thesisDao->getMostRecentActiveThesisByJournalId($journalId);
			$dateUpdated = $mostRecentThesis->getDateSubmitted();
			if (empty($lastDateUpdated) || (strtotime($dateUpdated) > strtotime($lastDateUpdated))) { 
				$thesisFeedPlugin->updateSetting($journal->getId(), 'dateUpdated', $dateUpdated, 'string');			
			}
		}

		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$version =& $versionDao->getCurrentVersion();

		$templateMgr =& TemplateManager::getManager();
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
