<?php

/**
 * FeedPlugin.inc.php
 *
 * Copyright (c) 2003-2006 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Web Feeds plugin class
 *
 * $Id$
 */
 
import('classes.plugins.GenericPlugin');

class FeedPlugin extends GenericPlugin {

	function getName() {
		return 'FeedPlugin';
	}
	
	function getDisplayName() {
		return Locale::translate('plugins.generic.webfeed.displayName');
	}
    
	function getDescription() {
		return Locale::translate('plugins.generic.webfeed.description');
	}   

	function register($category, $path) {
		if (parent::register($category, $path)) {
			HookRegistry::register('TemplateManager::display',array(&$this, 'callbackAddLinks'));
			
			HookRegistry::register( 'LoadHandler', array(&$this, 'callbackHandleFeed') ); 
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
	
			// if we have a journal selected,
			// append feed meta-links into the header
			// for the current issue
			if ($templateManager->get_template_vars('showToc') ) {
				$additionalHeadData = $templateManager->get_template_vars('additionalHeadData');
				$currentUrl = $templateManager->get_template_vars('currentUrl');
	
				$feedUrl1 = '<link rel="alternate" type="application/atom+xml" href="'.$baseUrl.'/index.php/'.$currentJournal->getPath().'/feed/atom">';
				$feedUrl2 = '<link rel="alternate" type="application/rdf+xml" href="'.$baseUrl.'/index.php/'.$currentJournal->getPath().'/feed/rss">';
	
				$templateManager->assign('additionalHeadData', $additionalHeadData."\n".$feedUrl1."\n".$feedUrl2);
			}
		
		}

		return false;
	}
	
	/*
	 * Declare the handler function to process the actual feed URL
	 */
	function callbackHandleFeed($hookName, $args) {
		
		if ( $this->getEnabled() ) {
			$page =& $args[0];
			$op =& $args[1];
			
			if ( $page == 'feed' ) {
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