<?php

/**
 * @file plugins/blocks/information/InformationBlockPlugin.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
	 * @see BlockPlugin::getContents
	 */
	function getContents($templateMgr, $request = null) {
		$journal = $request->getJournal();
		if (!$journal) return '';

		$templateMgr->assign(array(
			'forReaders' => $journal->getLocalizedData('readerInformation'),
			'forAuthors' => $journal->getLocalizedData('authorInformation'),
			'forLibrarians' => $journal->getLocalizedData('librarianInformation'),
		));
		return parent::getContents($templateMgr, $request);
	}
}


