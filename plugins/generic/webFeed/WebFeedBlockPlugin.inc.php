<?php

/**
 * @file plugins/generic/webFeed/WebFeedBlockPlugin.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WebFeedBlockPlugin
 * @ingroup plugins_generic_webFeed
 *
 * @brief Class for block component of web feed plugin
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class WebFeedBlockPlugin extends BlockPlugin {
	/** @var string Name of parent plugin */
	var $parentPluginName;

	function __construct($parentPluginName) {
		parent::__construct();
		$this->parentPluginName = $parentPluginName;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'WebFeedBlockPlugin';
	}

	/**
	 * Hide this plugin from the management interface (it's subsidiary)
	 */
	function getHideManagement() {
		return true;
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.generic.webfeed.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.generic.webfeed.description');
	}

	/**
	 * Get the supported contexts (e.g. BLOCK_CONTEXT_...) for this block.
	 * @return array
	 */
	function getSupportedContexts() {
		return array(BLOCK_CONTEXT_SIDEBAR);
	}

	/**
	 * Get the web feed plugin
	 * @return WebFeedPlugin
	 */
	function getWebFeedPlugin() {
		return PluginRegistry::getPlugin('generic', $this->parentPluginName);
	}

	/**
	 * Override the builtin to get the correct plugin path.
	 * @return string
	 */
	function getPluginPath() {
		return $this->getWebFeedPlugin()->getPluginPath();
	}

	/**
	 * @copydoc PKPPlugin::getTemplatePath
	 */
	function getTemplatePath($inCore = false) {
		return $this->getWebFeedPlugin()->getTemplatePath($inCore);
	}

	/**
	 * Get the HTML contents for this block.
	 * @param $templateMgr object
	 * @param $request PKPRequest
	 * @return $string
	 */
	function getContents($templateMgr, $request = null) {
		$journal = $request->getJournal();
		$plugin = $this->getWebFeedPlugin();
		$issueDao = DAORegistry::getDAO('IssueDAO');
		if ($issueDao->getCurrent($journal->getId(), true)) {
			return parent::getContents($templateMgr, $request);
		}
		return '';
	}
}

?>
