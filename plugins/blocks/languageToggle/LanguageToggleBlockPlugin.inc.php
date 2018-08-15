<?php

/**
 * @file plugins/blocks/languageToggle/LanguageToggleBlockPlugin.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LanguageToggleBlockPlugin
 * @ingroup plugins_blocks_languageToggle
 *
 * @brief Class for language selector block plugin
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class LanguageToggleBlockPlugin extends BlockPlugin {
	/**
	 * Determine whether the plugin is enabled. Overrides parent so that
	 * the plugin will be displayed during install.
	 *
	 * @param $contextId int Context ID (journal/press)
	 * @return boolean
	 */
	function getEnabled($contextId = null) {
		if (!Config::getVar('general', 'installed')) return true;
		return parent::getEnabled($contextId);
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
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the block context. Overrides parent so that the plugin will be
	 * displayed during install.
	 *
	 * @param $contextId int Context ID (journal/press)
	 * @return int
	 */
	function getBlockContext($contextId = null) {
		if (!Config::getVar('general', 'installed')) return BLOCK_CONTEXT_SIDEBAR;
		return parent::getBlockContext($contextId);
	}

	/**
	 * Determine the plugin sequence. Overrides parent so that
	 * the plugin will be displayed during install.
	 *
	 * @param $contextId int Context ID (journal/press)
	 */
	function getSeq($contextId = null) {
		if (!Config::getVar('general', 'installed')) return 2;
		return parent::getSeq($contextId);
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.block.languageToggle.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.block.languageToggle.description');
	}

	/**
	 * Get the HTML contents for this block.
	 * @param $templateMgr object
	 * @param $request PKPRequest
	 */
	function getContents($templateMgr, $request = null) {
		$templateMgr->assign('isPostRequest', $request->isPost());
		if (!defined('SESSION_DISABLE_INIT')) {
			$journal = $request->getJournal();
			if (isset($journal)) {
				$locales = $journal->getSupportedLocaleNames();

			} else {
				$site = $request->getSite();
				$locales = $site->getSupportedLocaleNames();
			}
		} else {
			$locales = AppLocale::getAllLocales();
			$templateMgr->assign('languageToggleNoUser', true);
		}

		if (isset($locales) && count($locales) > 1) {
			$templateMgr->assign('enableLanguageToggle', true);
			$templateMgr->assign('languageToggleLocales', $locales);
		}

		return parent::getContents($templateMgr, $request);
	}
}


