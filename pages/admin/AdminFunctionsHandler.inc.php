<?php

/**
 * @file pages/admin/AdminFunctionsHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminFunctionsHandler
 * @ingroup pages_admin
 *
 * @brief Handle requests for site administrative/maintenance functions. 
 */

import('lib.pkp.classes.site.Version');
import('lib.pkp.classes.site.VersionDAO');
import('lib.pkp.classes.site.VersionCheck');
import('pages.admin.AdminHandler');

class AdminFunctionsHandler extends AdminHandler {
	/**
	 * Constructor
	 **/
	function AdminFunctionsHandler() {
		parent::AdminHandler();
	}

	/**
	 * Show system information summary.
	 */
	function systemInfo() {
		$this->validate();
		$this->setupTemplate(true);

		$configData =& Config::getData();

		$dbconn =& DBConnection::getConn();
		$dbServerInfo = $dbconn->ServerInfo();

		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$currentVersion =& $versionDao->getCurrentVersion();
		$versionHistory =& $versionDao->getVersionHistory();

		$serverInfo = array(
			'admin.server.platform' => Core::serverPHPOS(),
			'admin.server.phpVersion' => Core::serverPHPVersion(),
			'admin.server.apacheVersion' => (function_exists('apache_get_version') ? apache_get_version() : __('common.notAvailable')),
			'admin.server.dbDriver' => Config::getVar('database', 'driver'),
			'admin.server.dbVersion' => (empty($dbServerInfo['description']) ? $dbServerInfo['version'] : $dbServerInfo['description'])
		);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('currentVersion', $currentVersion);
		$templateMgr->assign_by_ref('versionHistory', $versionHistory);
		$templateMgr->assign_by_ref('configData', $configData);
		$templateMgr->assign_by_ref('serverInfo', $serverInfo);
		if (Request::getUserVar('versionCheck')) {
			$latestVersionInfo =& VersionCheck::getLatestVersion();
			$latestVersionInfo['patch'] = VersionCheck::getPatch($latestVersionInfo);
			$templateMgr->assign_by_ref('latestVersionInfo', $latestVersionInfo);
		}
		$templateMgr->assign('helpTopicId', 'site.administrativeFunctions');
		$templateMgr->display('admin/systemInfo.tpl');
	}

	/**
	 * Show full PHP configuration information.
	 */
	function phpinfo() {
		$this->validate();
		phpinfo();
	}

	/**
	 * Expire all user sessions (will log out all users currently logged in).
	 */
	function expireSessions() {
		$this->validate();
		$sessionDao =& DAORegistry::getDAO('SessionDAO');
		$sessionDao->deleteAllSessions();
		Request::redirect(null, 'admin');
	}

	/**
	 * Clear compiled templates.
	 */
	function clearTemplateCache() {
		$this->validate();
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->clearTemplateCache();
		Request::redirect(null, 'admin');
	}

	/**
	 * Clear the data cache.
	 */
	function clearDataCache() {
		$this->validate();

		// Clear the CacheManager's caches
		$cacheManager =& CacheManager::getManager();
		$cacheManager->flush(null, CACHE_TYPE_FILE);
		$cacheManager->flush(null, CACHE_TYPE_OBJECT);

		// Clear ADODB's cache
		$userDao =& DAORegistry::getDAO('UserDAO'); // As good as any
		$userDao->flushCache();

		Request::redirect(null, 'admin');
	}

	/**
	 * Download scheduled task execution log file.
	 */
	function downloadScheduledTaskLogFile() {
		$this->validate();
		$application =& Application::getApplication();
		$request =& $application->getRequest();

		$file = basename($request->getUserVar('file'));
		import('lib.pkp.classes.scheduledTask.ScheduledTaskHelper');
		ScheduledTaskHelper::downloadExecutionLog($file);
	}
	
	/**
	 * Clear scheduled tasks execution logs.
	 */
	function clearScheduledTaskLogFiles() {
		$this->validate();
		import('lib.pkp.classes.scheduledTask.ScheduledTaskHelper');
		ScheduledTaskHelper::clearExecutionLogs();	

		Request::redirect(null, 'admin');
	}
}

?>
