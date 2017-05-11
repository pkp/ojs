<?php

/**
 * @file plugins/generic/usageStats/UsageStatsOptoutBlockPlugin.inc.php
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsageStatsOptoutBlockPlugin
 * @ingroup plugins_generic_usageStats
 *
 * @brief Opt-out component.
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class UsageStatsOptoutBlockPlugin extends BlockPlugin {

	/** @var string */
	var $_parentPluginName;


	/**
	 * Constructor
	 * @param $parentPluginName string
	 */
	function __construct($parentPluginName) {
		$this->_parentPluginName = $parentPluginName;
		parent::__construct();
	}


	//
	// Implement template methods from PKPPlugin.
	//
	/**
	 * @see PKPPlugin::getHideManagement()
	 */
	function getHideManagement() {
		return true;
	}

	/**
	 * @see PKPPlugin::getName()
	 */
	function getName() {
		return 'UsageStatsOptoutBlockPlugin';
	}

	/**
	 * @see PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.reports.usageStats.optout.displayName');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.usageStats.optout.description');
	}

	/**
	* @copydoc PKPPlugin::isSitePlugin()
	*/
	function isSitePlugin() {
		return false;
	}

	/**
	 * @copydoc PKPPlugin::getTemplatePath()
	 */
	function getTemplatePath($inCore = false) {
		$plugin =& $this->_getPlugin();
		return $plugin->getTemplatePath(true); // FIXME Should we be forcing $inCore?
	}

	/**
	 * @copydoc PKPPlugin::getSeq()
	 */
	function getSeq() {
		// Identify the position of the faceting block.
		$seq = parent::getSeq();

		// If nothing has been configured then show the privacy
		// block after all other blocks in the context.
		if (!is_numeric($seq)) $seq = 99;

		return $seq;
	}


	//
	// Implement template methods from LazyLoadPlugin
	//
	/**
	 * @see LazyLoadPlugin::getEnabled()
	 */
	function getEnabled() {
		$plugin =& $this->_getPlugin();
		return $plugin->getEnabled();
	}


	//
	// Implement template methods from BlockPlugin
	//
	/**
	 * @see BlockPlugin::getBlockContext()
	 */
	function getBlockContext() {
		$blockContext = parent::getBlockContext();

		// Place the block on the right by default.
		if (!in_array($blockContext, $this->getSupportedContexts())) {
			$blockContext = BLOCK_CONTEXT_SIDEBAR;
		}

		return $blockContext;
	}

	/**
	 * @see BlockPlugin::getBlockTemplateFilename()
	 */
	function getBlockTemplateFilename() {
		// Return the opt-out template.
		return 'optoutBlock.tpl';
	}

	//
	// Private helper methods
	//
	/**
	 * Get the plugin object
	 * @return OasPlugin
	 */
	function &_getPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', $this->_parentPluginName);
		return $plugin;
	}
}

?>
