<?php

/**
 * @file DonationBlockPlugin.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.blocks.user
 * @class DonationBlockPlugin
 *
 * Class for user block plugin
 *
 */

import('plugins.BlockPlugin');

class DonationBlockPlugin extends BlockPlugin {
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
		return 'DonationBlockPlugin';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return Locale::translate('plugins.block.donation.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return Locale::translate('plugins.block.user.description');
	}
	
	function getContents(&$templateMgr) {
		$journal =& Request::getJournal();
		if (!$journal) return '';
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('acceptDonationPayments', $journal->getSetting('acceptDonationPayments'));
		
		return parent::getContents($templateMgr);
	}
}

?>
