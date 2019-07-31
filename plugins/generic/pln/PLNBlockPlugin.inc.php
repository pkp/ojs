<?php

/**
 * @file plugins/generic/pln/PLNBlockPlugin.inc.php
 * 
 * Copyright (c) 2016 Simon Fraser University Library
 * Copyright (c) 2016 John Willlinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING
 * 
 * @class PLNBlockPlugin
 * @ingroup plugins_generic_pln
 * 
 * @brief Class for the "Preserved In" block plugin.
 * 
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class PLNBlockPlugin extends BlockPlugin {

	/** @var $parentPluginName string Name of the parent plugin */
	var $parentPluginName;
	
	function PLNBlockPlugin($parentPluginName) {
		parent::BlockPlugin();
		$this->parentPluginName = $parentPluginName;
	}
	
	/**
	 * Get the name of this block plugin. 
	 *  
	 * @return string name of the plugin
	 */
	function getName() {
		return 'PLNBlockPlugin';
	}

	/**
	 * Get a description of the block.
	 * 
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.plnblock.description');
	}
	
	/**
	 * Hide this plugin from the management interface.
	 * 
	 * @return boolean true
	 */
	function getHideManagement() {
		return true;
	}
	
	/**
	 * Get the display name of this plugin.
	 * 
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.plnblock.name');
	}
		
	/**
	 * Get the supported contexts (e.g. BLOCK_CONTEXT_...) for this block.
	 * @return array
	 */
	function getSupportedContexts() {
		return array(BLOCK_CONTEXT_LEFT_SIDEBAR, BLOCK_CONTEXT_RIGHT_SIDEBAR);
	}
	
	/**
	 * Get the PLNPlugin (this plugin's parent).
	 * 
	 * @return PLNPlugin
	 */
	function &getPlnPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		return $plugin;
	}
	
	/**
	 * Override the builtin function to get the correct plugin path.
	 * 
	 * @return string
	 */
	function getPluginPath() {
		$plugin =& $this->getPlnPlugin();
		return $plugin->getPluginPath();
	}
	
	/**
	 * Override the builtin function to get the correct template path.
	 * 
	 * @return string
	 */
	function getTemplatePath() {
		$plugin =& $this->getPlnPlugin();
		return $plugin->getTemplatePath();
	}
	
	/**
	 * Get the HTML content for this block. Returns the empty string if the 
	 * current request is not for a journal page or if the PLN terms of use
	 * are not accepted.
	 * 
	 * @param $templateMgr TemplateManager
	 * @return string
	 */
	function getContents(&$templateMgr) {
		$journal =& Request::getJournal();
		if (!$journal) return '';

		$plugin = $this->getPlnPlugin();
		$termsAccepted = $plugin->termsAgreed($journal->getId());
		if(!$termsAccepted) return '';
		
		return parent::getContents($templateMgr);
	}
}
