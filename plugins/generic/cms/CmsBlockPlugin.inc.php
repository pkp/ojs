<?php

/**
 * @file CmsBlockPlugin.inc.php
 *
 * Copyright (c) 2006-2007 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.cms
 * @class CmsBlockPlugin
 *
 * CMS plugin class, block component
 *
 * $Id$
 */

import('classes.plugins.BlockPlugin');

class CmsBlockPlugin extends BlockPlugin {
	/**
	 * Get the symbolic name of this plugin
	 * @return string
	 */
	function getName() {
		return 'CmsBlockPlugin';
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return Locale::translate('plugins.generic.cms.displayName');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		$description = Locale::translate('plugins.generic.cms.description');
		$plugin =& $this->getCmsPlugin();
		if ( !$plugin->isTinyMCEInstalled() )
			$description .= "<br />".Locale::translate('plugins.generic.cms.requirement.tinymce');
		return $description;
	}

	/**
	 * Override the builtin to get the correct plugin path.
	 * @return string
	 */
	function getPluginPath() {
		$plugin =& $this->getCmsPlugin();
		return $plugin->getPluginPath();
	}

	/**
	 * Override the builtin to get the correct template path.
	 * @return string
	 */
	function getTemplatePath() {
		$plugin =& $this->getCmsPlugin();
		return $plugin->getTemplatePath();
	}

	/**
	 * Get the supported contexts (e.g. BLOCK_CONTEXT_...) for this block.
	 * @return array
	 */
	function getSupportedContexts() {
		return array(BLOCK_CONTEXT_LEFT_SIDEBAR, BLOCK_CONTEXT_RIGHT_SIDEBAR);
	}

	/**
	 * Get the contents of the CMS plugin TOC.
	 * @param $templateMgr object
	 * @return string
	 */
	function getContents(&$templateMgr) {
		// Set the table of contents to the default (all headings closed)
		// if it has not been set (by the CmsHandler)
		$journal =& Request::getJournal();
		if (!$journal) return '';

		$plugin =& $this->getCmsPlugin();
		if ( is_null($templateMgr->get_template_vars('cmsPluginToc')) ) {
			$templateMgr->assign('cmsPluginToc', $plugin->getSetting($journal->getJournalId(), 'toc'));
		}
		return parent::getContents($templateMgr);
	}

	/**
	 * Get the actual CMS plugin
	 * @return object
	 */
	function &getCmsPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', 'CmsPlugin');
		return $plugin;
	}

	/**
	 * Determine whether or not this plugin is enabled.
	 * @return boolean
	 */
	function getEnabled() {
		$plugin =& $this->getCmsPlugin();
		return $plugin->getEnabled();
	}
}

?>
