<?php

/**
 * @file NotificationBlockPlugin.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NotificationBlockPlugin
 * @ingroup plugins_blocks_notification
 *
 * @brief Class for "notification" block plugin
 */

// $Id$


import('plugins.BlockPlugin');

class NotificationBlockPlugin extends BlockPlugin {
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			$this->addLocaleData();
		}
		return $success;
	}

	/**
	 * Determine whether the plugin is enabled. Overrides parent so that
	 * the plugin will be displayed during install.
	 */
	function getEnabled() {
		if (!Config::getVar('general', 'installed')) return true;
		return parent::getEnabled();
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
		return 'NotificationBlockPlugin';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return Locale::translate('plugins.block.notification.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return Locale::translate('plugins.block.notification.description');
	}
	
	
	function getContents(&$templateMgr) {
		$user =& Request::getUser();
		$journal =& Request::getJournal();
		
		if ($user && $journal) {
			$userId = $user->getId();
			$notificationDao =& DAORegistry::getDAO('NotificationDAO');
			$templateMgr->assign('unreadNotifications',  $notificationDao->getUnreadNotificationCount($userId));
		}
		
		return parent::getContents($templateMgr);
	}
}

?>
