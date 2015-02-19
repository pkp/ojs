<?php

/**
 * @file plugins/blocks/information/InformationBlockPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class InformationBlockPlugin
 * @ingroup plugins_blocks_information
 *
 * @brief Class for information block plugin
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class InformationBlockPlugin extends BlockPlugin {
	/**
	 * Install default settings on journal creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.block.information.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.block.information.description');
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
