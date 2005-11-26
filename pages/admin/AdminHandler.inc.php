<?php

/**
 * AdminHandler.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.admin
 *
 * Handle requests for site administration functions. 
 *
 * $Id$
 */

class AdminHandler extends Handler {

	/**
	 * Display site admin index page.
	 */
	function index() {
		AdminHandler::validate();
		AdminHandler::setupTemplate();
			
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'site.index');
		$templateMgr->display('admin/index.tpl');
	}
	
	/**
	 * Validate that user has admin privileges and is not trying to access the admin module with a journal selected.
	 * Redirects to the user index page if not properly authenticated.
	 */
	function validate() {
		parent::validate();
		if (!Validation::isLoggedIn('admin') || Request::getRequestedJournalPath() != 'index') {
			Validation::redirectLogin();
		}
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'admin'), 'admin.siteAdmin'))
				: array(array(Request::url(null, 'user'), 'navigation.user'))
		);
	}
	
	
	//
	// Settings
	//
	
	function settings() {
		import('pages.admin.AdminSettingsHandler');
		AdminSettingsHandler::settings();
	}
	
	function saveSettings() {
		import('pages.admin.AdminSettingsHandler');
		AdminSettingsHandler::saveSettings();
	}
	
	
	//
	// Journal Management
	//

	function journals() {
		import('pages.admin.AdminJournalHandler');
		AdminJournalHandler::journals();
	}
	
	function createJournal() {
		import('pages.admin.AdminJournalHandler');
		AdminJournalHandler::createJournal();
	}
	
	function editJournal($args = array()) {
		import('pages.admin.AdminJournalHandler');
		AdminJournalHandler::editJournal($args);
	}
	
	function updateJournal() {
		import('pages.admin.AdminJournalHandler');
		AdminJournalHandler::updateJournal();
	}
	
	function deleteJournal($args) {
		import('pages.admin.AdminJournalHandler');
		AdminJournalHandler::deleteJournal($args);
	}
	
	function moveJournal() {
		import('pages.admin.AdminJournalHandler');
		AdminJournalHandler::moveJournal();
	}
	
	function importOJS1() {
		import('pages.admin.AdminJournalHandler');
		AdminJournalHandler::importOJS1();
	}
	
	function doImportOJS1() {
		import('pages.admin.AdminJournalHandler');
		AdminJournalHandler::doImportOJS1();
	}
	
	
	//
	// Languages
	//
	
	function languages() {
		import('pages.admin.AdminLanguagesHandler');
		AdminLanguagesHandler::languages();
	}
	
	function saveLanguageSettings() {
		import('pages.admin.AdminLanguagesHandler');
		AdminLanguagesHandler::saveLanguageSettings();
	}
	
	function installLocale() {
		import('pages.admin.AdminLanguagesHandler');
		AdminLanguagesHandler::installLocale();
	}
	
	function uninstallLocale() {
		import('pages.admin.AdminLanguagesHandler');
		AdminLanguagesHandler::uninstallLocale();
	}
	
	function reloadLocale() {
		import('pages.admin.AdminLanguagesHandler');
		AdminLanguagesHandler::reloadLocale();
	}
	
	
	//
	// Authentication sources
	//
	
	function auth() {
		import('pages.admin.AuthSourcesHandler');
		AuthSourcesHandler::auth();
	}
	
	function updateAuthSources() {
		import('pages.admin.AuthSourcesHandler');
		AuthSourcesHandler::updateAuthSources();
	}
	
	function createAuthSource() {
		import('pages.admin.AuthSourcesHandler');
		AuthSourcesHandler::createAuthSource();
	}
	
	function editAuthSource($args) {
		import('pages.admin.AuthSourcesHandler');
		AuthSourcesHandler::editAuthSource($args);
	}
	
	function updateAuthSource($args) {
		import('pages.admin.AuthSourcesHandler');
		AuthSourcesHandler::updateAuthSource($args);
	}
	
	function deleteAuthSource($args) {
		import('pages.admin.AuthSourcesHandler');
		AuthSourcesHandler::deleteAuthSource($args);
	}
	
	
	//
	// Administrative functions
	//
	
	function systemInfo() {
		import('pages.admin.AdminFunctionsHandler');
		AdminFunctionsHandler::systemInfo();
	}
	
	function editSystemConfig() {
		import('pages.admin.AdminFunctionsHandler');
		AdminFunctionsHandler::editSystemConfig();
	}
	
	function saveSystemConfig() {
		import('pages.admin.AdminFunctionsHandler');
		AdminFunctionsHandler::saveSystemConfig();
	}
	
	function phpinfo() {
		import('pages.admin.AdminFunctionsHandler');
		AdminFunctionsHandler::phpInfo();
	}
	
	function expireSessions() {
		import('pages.admin.AdminFunctionsHandler');
		AdminFunctionsHandler::expireSessions();
	}
	
	function clearTemplateCache() {
		import('pages.admin.AdminFunctionsHandler');
		AdminFunctionsHandler::clearTemplateCache();
	}

	function clearDataCache() {
		import('pages.admin.AdminFunctionsHandler');
		AdminFunctionsHandler::clearDataCache();
	}
}

?>
