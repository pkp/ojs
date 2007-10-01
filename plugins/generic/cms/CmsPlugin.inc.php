<?php

/**
 * @file CmsPlugin.inc.php
 *
 * Copyright (c) 2006-2007 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.cms
 * @class CmsPlugin
 *
 * CMS plugin class
 *
 * $Id$
 */

import('classes.plugins.GenericPlugin');

class CmsPlugin extends GenericPlugin {

	function getName() {
		return 'CmsPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.generic.cms.displayName');
	} 		

	function getDescription() {
		$description = Locale::translate('plugins.generic.cms.description');
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

	/**
	 * Register the plugin, attaching to hooks as necessary.
	 * @param $category string
	 * @param $path string
	 * @return boolean
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {		
			$this->addLocaleData();
			if ($this->getEnabled()) {
				HookRegistry::register('LoadHandler', array(&$this, 'callbackHandleContent'));
				HookRegistry::register('PluginRegistry::loadCategory', array(&$this, 'callbackLoadCategory'));
			}
			return true;
		}
		return false;
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
				$this->import('CmsBlockPlugin');
				$plugins[$category][] =& new CmsBlockPlugin();
				break;
		}
		return false;
	}

	/*
	 * Declare the handler function to process the actual page URL
	 */
	function callbackHandleContent($hookName, $args) {
		$templateMgr = &TemplateManager::getManager();

		$page =& $args[0];
		$op =& $args[1];

		if ( $page == 'cms' ) {
			define('HANDLER_CLASS', 'CmsHandler');
			$this->import('CmsHandler');
			return true;
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
