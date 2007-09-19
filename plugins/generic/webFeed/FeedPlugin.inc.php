<?php

/**
 * @file FeedPlugin.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.webFeed
 * @class FeedPlugin
 *
 * Web Feeds plugin class
 *
 * $Id$
 */

import('classes.plugins.GenericPlugin');

class FeedPlugin extends GenericPlugin {

	function getName() {
		return 'WebFeedPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.generic.webfeed.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.generic.webfeed.description');
	}   

	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->getEnabled()) {
				HookRegistry::register('TemplateManager::display',array(&$this, 'callbackAddLinks'));
				HookRegistry::register( 'LoadHandler', array(&$this, 'callbackHandleFeed') ); 
			}

			$this->addLocaleData();
			return true;
		}
		return false;
	}

	function callbackAddLinks($hookName, $args) {

		if ( $this->getEnabled() ) {
			$templateManager =& $args[0];

			$currentJournal =& $templateManager->get_template_vars('currentJournal');
			$baseUrl = $templateManager->get_template_vars('baseUrl');
			$displayPage = $this->getSetting($currentJournal->getJournalId(), 'displayPage');
			$templateManager->assign('displayPage', $displayPage);

			// if we have a journal selected, append feed meta-links into the header
			$additionalHeadData = $templateManager->get_template_vars('additionalHeadData');

			$feedUrl1 = '<link rel="alternate" type="application/atom+xml" href="'.$currentJournal->getUrl().'/feed/atom">';
			$feedUrl2 = '<link rel="alternate" type="application/rdf+xml" href="'.$currentJournal->getUrl().'/feed/rss">';
			$feedUrl3 = '<link rel="alternate" type="application/rss+xml" href="'.$currentJournal->getUrl().'/feed/rss2">';

			$templateManager->assign('additionalHeadData', $additionalHeadData."\n".$feedUrl1."\n".$feedUrl2."\n".$feedUrl3);

			// if no explicit sidebar template is specified, include the web feed links on the sidebar
			$sidebarTemplate = $templateManager->get_template_vars('sidebarTemplate');
			if ($sidebarTemplate == "") {
				$templateManager->assign('sidebarTemplate', $this->getTemplatePath().'templates/links.tpl');
			}
		}

		return false;
	}

	/**
	 * Declare the handler function to process the actual feed URL
	 */
	function callbackHandleFeed($hookName, $args) {

		if ( $this->getEnabled() ) {
			$page =& $args[0];
			$op =& $args[1];

			// only display feed if the journal has a current issue
			$journal = &Request::getJournal();        
			$issueDao = &DAORegistry::getDAO('IssueDAO');
			$issue = &$issueDao->getCurrentIssue($journal->getJournalId());

			if ( $page == 'feed' && !is_null($issue)) {
				define('HANDLER_CLASS', 'FeedHandler');
				$this->import('FeedHandler');
				return true;
			}
		}
		return false;
	}

	/**
	 * Determine whether or not this plugin is enabled.
	 */
	function getEnabled() {
		$journal = &Request::getJournal();
		if (!$journal) return false;
		return $this->getSetting($journal->getJournalId(), 'enabled');
	}

	/**
	 * Set the enabled/disabled state of this plugin
	 */
	function setEnabled($enabled) {
		$journal = &Request::getJournal();
		if ($journal) {
			$this->updateSetting($journal->getJournalId(), 'enabled', $enabled ? true : false);

			// set default settings
			if ($this->getSetting($journal->getJournalId(), 'displayPage') == "") {
				$this->updateSetting($journal->getJournalId(), 'displayPage', 'issue');
			}
			if ($this->getSetting($journal->getJournalId(), 'displayItems') == "") {
				$this->updateSetting($journal->getJournalId(), 'displayItems', 'issue');
			}

			// register against the Layout Manager plugin if it's installed
			$LayoutManagerPlugin = &PluginRegistry::getPlugin('generic', 'LayoutManager');

			if ($LayoutManagerPlugin) {
				// register or deregister the sidebar links
				if ( $enabled )
					$LayoutManagerPlugin->registerBlock($this->getDisplayName(), $this->getTemplatePath().'templates/links.tpl', 10);
				else
					$LayoutManagerPlugin->deRegisterBlock($this->getDisplayName());
			}

			return true;
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

		switch ($verb) {
			case 'settings':
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));

				$journal =& Request::getJournal();

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
				$this->setEnabled(true);
				$returner = false;
				break;
			case 'disable':
				$this->setEnabled(false);
				$returner = false;
				break;	
		}

		return $returner;		
	}

}
?>
