<?php

/**
 * @file InformationBlockPlugin.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.blocks.information
 * @class InformationBlockPlugin
 *
 * Class for information block plugin
 *
 * $Id$
 */

import('plugins.BlockPlugin');

class InformationBlockPlugin extends BlockPlugin {
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			$this->addLocaleData();
		}
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'InformationBlockPlugin';
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
		return Locale::translate('plugins.block.information.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return Locale::translate('plugins.block.information.description');
	}

	/**
	 * Get the supported contexts (e.g. BLOCK_CONTEXT_...) for this block.
	 * @return array
	 */
	function getSupportedContexts() {
		return array(BLOCK_CONTEXT_LEFT_SIDEBAR, BLOCK_CONTEXT_RIGHT_SIDEBAR);
	}

	/**
	 * Get the HTML contents for this block.
	 * @param $templateMgr object
	 * @return $string
	 */
	function getContents(&$templateMgr) {
		$journal =& Request::getJournal();
		if (!$journal) return '';

		$templateMgr->assign('forReaders', $journal->getLocalizedSetting('readerInformation'));
		$templateMgr->assign('forAuthors', $journal->getLocalizedSetting('authorInformation'));
		$templateMgr->assign('forLibrarians', $journal->getLocalizedSetting('librarianInformation'));
		return parent::getContents($templateMgr);
	}
}

?>
