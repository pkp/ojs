<?php

/**
 * cmsPlugin.inc.php
 *
 * Copyright (c) 2006 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * CMS plugin class
 *
 * $Id$
 */
 
import('classes.plugins.GenericPlugin');

class Cms extends GenericPlugin {

	function getName() {
		return 'CmsPlugin';
	}
	
	function getDisplayName() {
		return Locale::translate('plugins.generic.cms.displayName');
	} 		

	function getDescription() {
		$description = Locale::translate('plugins.generic.cms.description'); 
		if ( !$this->isLayoutManagerInstalled() )
			$description .= "<br />".Locale::translate('plugins.generic.cms.requirement.layoutmanager');
		if ( !$this->isTinyMCEInstalled() )
			$description .= "<br />".Locale::translate('plugins.generic.cms.requirement.tinymce');
		return $description;
	}
	
	function isTinyMCEInstalled() {
		$tinyMCEPlugin = &PluginRegistry::getPlugin('generic', 'TinyMCEPlugin');
		
		if ( $tinyMCEPlugin ) 
			return $tinyMCEPlugin->getEnabled();
		
		return false;
	}
	
	function isLayoutManagerInstalled() {
		$layoutManagerPlugin = &PluginRegistry::getPlugin('generic', 'LayoutManager');
		if ( $layoutManagerPlugin ) 
			return  $layoutManagerPlugin->getEnabled();
			
		return false;
	}

	function register($category, $path) {
		if (!Config::getVar('general', 'installed')) return false;
		if (parent::register($category, $path)) {		
			$this->addLocaleData();			
			HookRegistry::register('TemplateManager::display',array(&$this, 'callbackSetTableOfContents'));
			HookRegistry::register( 'LoadHandler', array(&$this, 'callbackHandleContent') );			 
			HookRegistry::register( 'TinyMCEPlugin::getDisableTemplates', array(&$this, 'callbackAlterTinyMCE' ));

			return true;
		}
		return false;
	}

	/*
	 * Declare the handler function to process the actual page URL
	 */
	function callbackHandleContent($hookName, $args) {
		
		$templateMgr = &TemplateManager::getManager();

		if ( $this->getEnabled() ) {
			$page =& $args[0];
			$op =& $args[1];
			
			if ( $page == 'cms' ) {
				define('HANDLER_CLASS', 'CmsHandler');
				$this->import('CmsHandler');
				return true;
			}
		}
		return false;
	}

	/* 
	 * On every page view the table of contents has to be set in the sidebar
	 * Set the Table of Contents from the plugin settings when it hasn't been set already
	 */
	function callbackSetTableOfContents($hookName, $args) {
		if ( $this->getEnabled() ) {
			$templateManager =& $args[0];
	
			// set the table of contents to the default (all headings closed) 
			// if it has not been set (by the CmsHandler)
			if ( count($templateManager->get_template_vars('cmsPluginToc')) == 0 ) {
				$journal =& $templateManager->get_template_vars('currentJournal');					
				$templateManager->assign('cmsPluginToc', $this->getSetting($journal->getJournalId(), 'toc'));
			}
		}
	}		
	
	/* 
	 * this turns off the TinyMCE plugin and now we can incorporate our own
	 * This way we can incorporate our own butons
	 */
	function callbackAlterTinyMCE ( $hookName, $args ) {
		if ( $this->isTinyMCEInstalled() ) {
			$disableTemplates =& $args[1];	
			$disableTemplates[] = $this->getTemplatePath() . 'settingsForm.tpl';
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
 			
			$layoutManagerPlugin = &PluginRegistry::getPlugin('generic', 'LayoutManager');
			// register or deregister the sidebar links
  			if ( $enabled ) {
				$layoutManagerPlugin->registerBlock($this->getDisplayName(), $this->getTemplatePath().'tableofcontents.tpl', 4);

				$this->import('ContentManager');	
				$contentManager =& new ContentManager();
				
				$h = array();
				$c = array();
						
				// no current value because we don't want anything selected
				$contentManager->parseContents( $h, $c );
				
				// this sets the table of contents with nothing selected
				$this->updateSetting($journal->getJournalId(), 'toc', $h); 
  			}
			else
				$layoutManagerPlugin->deRegisterBlock($this->getDisplayName());
				
			return true;
		}	
		return false;
	}
	
	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		if ( !$this->isLayoutManagerInstalled() )
			return array();

		$verbs = array();			
		if ($this->getEnabled()) {
			$verbs[] = array(
				'disable',
				Locale::translate('manager.plugins.disable')
			);
			if ( $this->isTinyMCEInstalled() ) {
				$verbs[] = array(
					'edit', 
					Locale::translate('manager.plugins.content')
				);					
			}
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

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
		

		switch ($verb) {
			case 'edit':
				$journal =& Request::getJournal();
						
				$this->import('CmsSettingsForm');
				$form =& new CmsSettingsForm($this, $journal->getJournalId());
				
				// saving and staying on the form
				if ( Request::getUserVar('content') ) {
					$form->readInputData();
					
					if ($form->validate()) {
						// perform the save and reset the form
						$form->save();
						$form->initData( array($form->getData('current')) );
					} else {
						// add the tiny MCE script to the form 
						$form->addTinyMCE();
						
						// wipe out the 'currentHeading' because we are coming back to the 
						// same form (due to lack of validation) and so the content already
						// incorporates the heading
						$templateMgr->assign('currentHeading', '');						
						$templateMgr->assign('currentContent', Request::getUserVar('content'));
						
					}
					$form->display();
				} else {					
					$form->initData($args);
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
