<?php

/**
 * @file WebFeedPlugin.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.block.webFeed
 * @class WebFeedPlugin
 *
 * Web Feeds plugin class
 *
 * $Id$
 */

import('classes.plugins.GenericPlugin');

class WebFeedPlugin extends GenericPlugin {
	/**
	 * Get the symbolic name of this plugin
	 * @return string
	 */
	function getName() {
		return 'WebFeedPlugin';
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return Locale::translate('plugins.generic.webfeed.displayName');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return Locale::translate('plugins.generic.webfeed.description');
	}   

	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->getEnabled()) {
				HookRegistry::register('TemplateManager::display',array(&$this, 'callbackAddLinks'));
				HookRegistry::register('PluginRegistry::loadCategory', array(&$this, 'callbackLoadCategory'));
			}
			$this->addLocaleData();
			return true;
		}
		return false;
	}

	/**
	 * Check whether or not this plugin is enabled
	 * @return boolean
	 */
	function getEnabled() {
		$journal =& Request::getJournal();
		$journalId = $journal?$journal->getJournalId():0;
		return $this->getSetting($journalId, 'enabled');
	}

	/**
	 * Register as a block plugin, even though this is a generic plugin.
	 * This will allow the plugin to behave as a block plugin, i.e. to
	 * have layout tasks performed on it.
	 * @param $hookName string
	 * @param $args array
	 */
	function callbackLoadCategory($hookName, $args) {
		$category =& $args[0];
		$plugins =& $args[1];
		switch ($category) {
			case 'blocks':
				$this->import('WebFeedBlockPlugin');
				$blockPlugin =& new WebFeedBlockPlugin();
				$plugins[$blockPlugin->getSeq()][$blockPlugin->getPluginPath()] =& $blockPlugin;
				break;
			case 'gateways':
				$this->import('WebFeedGatewayPlugin');
				$gatewayPlugin =& new WebFeedGatewayPlugin();
				$plugins[$gatewayPlugin->getSeq()][$gatewayPlugin->getPluginPath()] =& $gatewayPlugin;
				break;
		}
		return false;
	}

	function callbackAddLinks($hookName, $args) {
		if ($this->getEnabled()) {
			$templateManager =& $args[0];

			$currentJournal =& $templateManager->get_template_vars('currentJournal');
			$requestedPage = Request::getRequestedPage();
			if ($currentJournal) {
				$issueDao = &DAORegistry::getDAO('IssueDAO');
				$currentIssue =& $issueDao->getCurrentIssue($currentJournal->getJournalId());
				$displayPage = $this->getSetting($currentJournal->getJournalId(), 'displayPage');
			} 

			if ( ($currentIssue) && (($displayPage == 'all') || ($displayPage == 'issue' && $displayPage == $requestedPage)) ) { 
				$additionalHeadData = $templateManager->get_template_vars('additionalHeadData');

				$feedUrl1 = '<link rel="alternate" type="application/atom+xml" href="'.$currentJournal->getUrl().'/gateway/plugin/WebFeedGatewayPlugin/atom" />';
				$feedUrl2 = '<link rel="alternate" type="application/rdf+xml" href="'.$currentJournal->getUrl().'/gateway/plugin/WebFeedGatewayPlugin/rss" />';
				$feedUrl3 = '<link rel="alternate" type="application/rss+xml" href="'.$currentJournal->getUrl().'/gateway/plugin/WebFeedGatewayPlugin/rss2" />';

				$templateManager->assign('additionalHeadData', $additionalHeadData."\n\t".$feedUrl1."\n\t".$feedUrl2."\n\t".$feedUrl3);
			}
		}

		return false;
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array(
				'disable',
				Locale::translate('manager.plugins.disable')
			);
			$verbs[] = array(
				'settings',
				Locale::translate('plugins.generic.webfeed.settings')
			);
		} else {
			$verbs[] = array(
				'enable',
				Locale::translate('manager.plugins.enable')
			);
		}
		return $verbs;
	}

	/**
	 * Perform management functions
	 */
	function manage($verb, $args) {
		$returner = true;
		$journal =& Request::getJournal();

		switch ($verb) {
			case 'settings':
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));

				$this->import('SettingsForm');
				$form =& new SettingsForm($this, $journal->getJournalId());

				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						Request::redirect(null, null, 'plugins');
					} else {
						$form->display();
					}
				} else {
					$form->initData();
					$form->display();
				}
				break;
			case 'enable':
				$this->updateSetting($journal->getJournalId(), 'enabled', true);
				$returner = false;
				break;
			case 'disable':
				$this->updateSetting($journal->getJournalId(), 'enabled', false);
				$returner = false;
				break;	
		}

		return $returner;		
	}
}

?>
