<?php

/**
 * NavigationBlockPlugin.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Class for navigation block plugin
 *
 * $Id$
 */

import('plugins.BlockPlugin');

class NavigationBlockPlugin extends BlockPlugin {
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			$this->addLocaleData();
		}
		return $success;
	}

	/**
	 * Get the supported contexts (e.g. BLOCK_CONTEXT_...) for this block.
	 * @return array
	 */
	function getSupportedContexts() {
		return array(BLOCK_CONTEXT_LEFT_SIDEBAR, BLOCK_CONTEXT_RIGHT_SIDEBAR);
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'NavigationBlockPlugin';
	}

	/**
	 * Install default settings on system install.
	 * @return string
	 */
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Install default settings on journal creation.
	 * @return string
	 */
	function getNewJournalPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return Locale::translate('plugins.block.navigation.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return Locale::translate('plugins.block.navigation.description');
	}

	/**
	 * Get the contents for this block.
	 * @param $templateMgr object
	 * @return string
	 */
	function getContents(&$templateMgr) {
		$templateMgr->assign('articleSearchByOptions', array(
			'' => 'search.allFields',
			ARTICLE_SEARCH_AUTHOR => 'search.author',
			ARTICLE_SEARCH_TITLE => 'article.title',
			ARTICLE_SEARCH_ABSTRACT => 'search.abstract',
			ARTICLE_SEARCH_INDEX_TERMS => 'search.indexTerms',
			ARTICLE_SEARCH_GALLEY_FILE => 'search.fullText'
		));
		return parent::getContents($templateMgr);
	}
}

?>
