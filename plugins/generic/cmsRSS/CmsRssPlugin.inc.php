<?php

/**
 * @file CmsRssPlugin.inc.php
 *
 * Copyright (c) 2006-2007 Gunther Eysenbach, Juan Pablo Alperin
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.cmsRss
 * @class CmsRssPlugin
 *
 * CmsRss plugin class
 *
 */

import('classes.plugins.GenericPlugin');

class CmsRssPlugin extends GenericPlugin {

	function getName() {
		return 'CmsRssPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.generic.cmsrss.displayName');
	} 		

	function getDescription() {
		$description = Locale::translate('plugins.generic.cmsrss.description'); 
		if ( !$this->isCmsInstalled() )
			$description .= "<br />".Locale::translate('plugins.generic.cmsrss.requirement.cms');
		return $description;
	}

	function isCmsInstalled() {
		$cmsPlugin = &PluginRegistry::getPlugin('generic', 'CmsPlugin');

		if ( $cmsPlugin ) 
			return $cmsPlugin->getEnabled();

		return false;
	}

	function register($category, $path) {
		if (!Config::getVar('general', 'installed')) return false;
		if (parent::register($category, $path)) {		
			$this->addLocaleData();			

			HookRegistry::register( 'Plugins::CmsHandler', array(&$this, 'callbackAddRssFeeds') );			 


			return true;
		}
		return false;
	}

	/*
	 * Declare the handler function to process the actual page URL
	 */
	function callbackAddRssFeeds($hookName, $args) {
		if ( $this->getEnabled() ) {
			$current = $args[0];
			$output =& $args[1];

			$journal =& Request::getJournal();
			$journalId = $journal->getJournalId();

			$templateMgr =& TemplateManager::getManager();

			$this->import('SimplePie');

			$urls = $this->getSetting($journalId, 'urls'); 


			$months = $this->getSetting($journalId, 'months');
			$aggregate = $this->getSetting($journalId, 'aggregate');

			$feeds = array();
			foreach ( $urls as $feedInfo ) {
				$webSafe = array();
				foreach ( explode(":", $feedInfo['pageName']) as $pageName ) {
					$webSafe[] = ContentManager::websafe($pageName) ;
				}	
				$webSafe = implode(":", $webSafe);

				// skip all the ones that wont go on this page
				if ( strcmp($webSafe, $current) != 0) 
					continue;		

				$feed = new SimplePie();
				$feed->feed_url($feedInfo['url']);
				$feed->cache_location(Core::getBaseDir() . DIRECTORY_SEPARATOR . 'cache');
				$feed->replace_headers(true);
				$feed->init();

				if ( $feed->data ) {
					$max = $feed->get_item_quantity(0);
					$templateMgr->assign('feed', $feed);
					for ($x = 0; $x < $max; $x++) {
						$item = $feed->get_item($x);

						$templateMgr->assign('item', $item);	

						$items[$item->get_date('U')] = trim($templateMgr->fetch($this->getTemplatePath().'rss.tpl'));
					}
				}
			}

			if ( is_array($items) && count($items) > 0 ) {
				if ( $aggregate )
					krsort($items);


				foreach ( $items as $time => $post) {
					if ( $months > 0 ) {		
						if ( $time > strtotime("-".$months." month") ) {
							$output .= $post;
						}
					} else {
						$output .= $post;	
					}
				}
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
		if ( !$this->isCmsInstalled() )
			return array();

		$verbs = array();			
		if ($this->getEnabled()) {
			$verbs[] = array(
				'disable',
				Locale::translate('manager.plugins.disable')
			);
			$verbs[] = array(
				'settings', 
				Locale::translate('manager.plugins.cmsrss.edit')
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
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 * @param $subclass boolean
	 */
	function setBreadcrumbs() {
		$templateMgr = &TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				Request::url(null, 'user'),
				'navigation.user'
			),
			array(
				Request::url(null, 'manager'),
				'user.role.manager'
			),
			array(
				Request::url(null, 'manager', 'plugins'),
				'manager.plugins'
			)
		);

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}	
	/**
	 * Perform management functions
	 */
	function manage($verb, $args) {
		$returner = true;

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));


		switch ($verb) {
			case 'settings':
				$journal =& Request::getJournal();

				$this->import('CmsRssSettingsForm');
				$form =& new CmsRssSettingsForm($this, $journal->getJournalId());

				$this->setBreadcrumbs();
				$form->readInputData();

				if (Request::getUserVar('addUrl')) {
					// Add a sponsor
					$editData = true;
					$urls = $form->getData('urls');
					array_push($urls, array());
					$form->_data['urls'] = $urls;

				} else if (($delUrl = Request::getUserVar('delUrl')) && count($delUrl) == 1) {
					// Delete an url
					$editData = true;
					list($delUrl) = array_keys($delUrl);
					$delUrl = (int) $delUrl;
					$urls = $form->getData('urls');
					if (isset($urls[$delUrl]['urlId']) && !empty($urls[$delUrl]['urlId'])) {
						$deletedUrls = explode(':', $form->getData('deletedUrls'));
						array_push($deletedUrls, $urls[$delUrl]['urlId']);
						$form->setData('deletedUrls', join(':', $deletedUrls));
					}
					array_splice($urls, $delUrl, 1);
					$form->_data['urls'] = $urls;

				} else if ( Request::getUserVar('save') ) {
					$editData = true;
					$form->execute(); 
				} else {					
					$form->initData();
				}

				if ( !isset($editData) && $form->validate()) {
					$form->execute();
					$form->display();	
				} else {
					$form->display();
				}
				$returner = true;
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
