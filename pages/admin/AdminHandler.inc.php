<?php

/**
 * AdminHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.admin
 *
 * Handle requests for site administration functions. 
 *
 * $Id$
 */

import('pages.admin.AdminFunctionsHandler');
import('pages.admin.AdminJournalHandler');
import('pages.admin.AdminSettingsHandler');

class AdminHandler extends Handler {

	/**
	 * Display site admin index page.
	 */
	function index() {
		AdminHandler::validate();
		AdminHandler::setupTemplate();
			
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->display('admin/index.tpl');
	}
	
	/**
	 * Validate that user has admin privileges and is not trying to access the admin module with a journal selected.
	 * Redirects to the user index page if not properly authenticated.
	 */
	function validate() {
		parent::validate();
		if (!Validation::isLoggedIn('admin') || Request::getRequestedJournalPath() != 'index') {
			Request::redirect('user');
		}
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array('user', 'navigation.user'), array('admin', 'admin.siteAdmin'))
				: array(array('user', 'navigation.user'))
		);
	}
	
	
	//
	// Settings
	//
	
	function settings() {
		AdminSettingsHandler::settings();
	}
	
	function saveSettings() {
		AdminSettingsHandler::saveSettings();
	}
	
	
	//
	// Journal Management
	//

	function journals() {
		AdminJournalHandler::journals();
	}
	
	function createJournal() {
		AdminJournalHandler::createJournal();
	}
	
	function editJournal($args = array()) {
		AdminJournalHandler::editJournal($args);
	}
	
	function updateJournal() {
		AdminJournalHandler::updateJournal();
	}
	
	function deleteJournal($args) {
		AdminJournalHandler::deleteJournal($args);
	}
	
	function moveJournal() {
		AdminJournalHandler::moveJournal();
	}
	
	
	// Administrative functions
	
	function systemInfo() {
		AdminFunctionsHandler::systemInfo();
	}
	
	function editSystemConfig() {
		AdminFunctionsHandler::editSystemConfig();
	}
	
	function saveSystemConfig() {
		AdminFunctionsHandler::saveSystemConfig();
	}
	
	function phpinfo() {
		AdminFunctionsHandler::phpInfo();
	}
	
	function expireSessions() {
		AdminFunctionsHandler::expireSessions();
	}
	
	function clearTemplateCache() {
		AdminFunctionsHandler::clearTemplateCache();
	}
	
}

?>
