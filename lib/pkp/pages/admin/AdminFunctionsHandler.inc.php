<?php

/**
 * @file pages/admin/AdminFunctionsHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
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
import('lib.pkp.pages.admin.AdminHandler');

class AdminFunctionsHandler extends AdminHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();

		$this->addRoleAssignment(
			array(ROLE_ID_SITE_ADMIN),
			array(
				'systemInfo', 'editSystemConfig', 'saveSystemConfig', 'phpinfo',
				'expireSessions', 'clearTemplateCache', 'clearDataCache', 
				'downloadScheduledTaskLogFile', 'clearScheduledTaskLogFiles'
			)
		);
	}

	/**
	 * Show system information summary.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function systemInfo($args, $request) {
		$this->setupTemplate($request, true);

		$versionDao = DAORegistry::getDAO('VersionDAO');
		$currentVersion = $versionDao->getCurrentVersion();

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('currentVersion', $currentVersion);
		if ($request->getUserVar('versionCheck')) {
			$latestVersionInfo = VersionCheck::getLatestVersion();
			$latestVersionInfo['patch'] = VersionCheck::getPatch($latestVersionInfo);
			$templateMgr->assign('latestVersionInfo', $latestVersionInfo);
		}
		$templateMgr->display('admin/systemInfo.tpl');
	}

	/**
	 * Show full PHP configuration information.
	 */
	function phpinfo() {
		phpinfo();
	}

	/**
	 * Expire all user sessions (will log out all users currently logged in).
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function expireSessions($args, $request) {
		$sessionDao = DAORegistry::getDAO('SessionDAO');
		$sessionDao->deleteAllSessions();
		$request->redirect(null, 'admin');
	}

	/**
	 * Clear compiled templates.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function clearTemplateCache($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->clearTemplateCache();
		$request->redirect(null, 'admin');
	}

	/**
	 * Clear the data cache.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function clearDataCache($args, $request) {
		// Clear the CacheManager's caches
		$cacheManager = CacheManager::getManager();
		$cacheManager->flush();

		// Clear ADODB's cache
		$userDao = DAORegistry::getDAO('UserDAO'); // As good as any
		$userDao->flushCache();

		$request->redirect(null, 'admin');
	}

	/**
	 * Download scheduled task execution log file.
	 */
	function downloadScheduledTaskLogFile() {
		$application = Application::getApplication();
		$request = $application->getRequest();

		$file = basename($request->getUserVar('file'));
		import('lib.pkp.classes.scheduledTask.ScheduledTaskHelper');
		ScheduledTaskHelper::downloadExecutionLog($file);
	}
	
	/**
	 * Clear scheduled tasks execution logs.
	 */
	function clearScheduledTaskLogFiles() {
		import('lib.pkp.classes.scheduledTask.ScheduledTaskHelper');
		ScheduledTaskHelper::clearExecutionLogs();	

		Request::redirect(null, 'admin');
	}
}

?>
