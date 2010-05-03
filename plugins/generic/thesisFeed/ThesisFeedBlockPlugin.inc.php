<?php

/**
 * @file ThesisFeedBlockPlugin.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ThesisFeedBlockPlugin
 * @ingroup plugins_generic_thesisFeed
 *
 * @brief Class for block component of thesis feed plugin
 */

// $Id$


import('lib.pkp.classes.plugins.BlockPlugin');

class ThesisFeedBlockPlugin extends BlockPlugin {
	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return Locale::translate('plugins.generic.thesisfeed.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return Locale::translate('plugins.generic.thesisfeed.description');
	}

	/**
	 * Get the thesis feed plugin
	 * @return object
	 */
	function &getThesisFeedPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', 'ThesisFeedPlugin');
		return $plugin;
	}

	/**
	 * Get the thesis plugin
	 * @return object
	 */
	function &getThesisPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', 'ThesisPlugin');
		return $plugin;
	}

	/**
	 * Override the builtin to get the correct plugin path.
	 * @return string
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
	 * Get the HTML contents for this block.
	 * @param $templateMgr object
	 * @return $string
	 */
	function getContents(&$templateMgr) {
		$journal =& Request::getJournal();
		if (!$journal) return '';

		$thesisPlugin =& $this->getThesisPlugin();
		if (!$thesisPlugin->getEnabled()) return '';

		$plugin =& $this->getThesisFeedPlugin();
		$displayPage = $plugin->getSetting($journal->getId(), 'displayPage');
		$requestedPage = Request::getRequestedPage();

		if (($displayPage == 'all') || ($displayPage == 'homepage' && (empty($requestedPage) || $requestedPage == 'index' || $requestedPage == 'thesis')) || ($displayPage == $requestedPage)) {
			return parent::getContents($templateMgr);
		} else {
			return '';
		}
	}
}

?>
