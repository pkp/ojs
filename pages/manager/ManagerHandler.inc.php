<?php

/**
 * @file pages/manager/ManagerHandler.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManagerHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for journal management functions. 
 */

import('classes.handler.Handler');
import('pages.manager.ManagerHandler');

class ManagerHandler extends Handler {
	/**
	 * Constructor
	 */
	function ManagerHandler() {
		parent::Handler();
		$this->addRoleAssignment(ROLE_ID_MANAGER, 'index');
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PkpContextAccessPolicy');
		$this->addPolicy(new PkpContextAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Display journal management index page.
	 */
	function index($args, $request) {
		$this->setupTemplate($request);
		$journal = $request->getJournal();
		$templateMgr = TemplateManager::getManager($request);

		// Display a warning message if there is a new version of OJS available
		$newVersionAvailable = false;
		if (Config::getVar('general', 'show_upgrade_warning')) {
			import('lib.pkp.classes.site.VersionCheck');
			if($latestVersion = VersionCheck::checkIfNewVersionExists()) {
				$newVersionAvailable = true;
				$templateMgr->assign('latestVersion', $latestVersion);
				$currentVersion = VersionCheck::getCurrentDBVersion();
				$templateMgr->assign('currentVersion', $currentVersion->getVersionString());

				// Get contact information for site administrator
				$roleDao = DAORegistry::getDAO('RoleDAO');
				$siteAdmins = $roleDao->getUsersByRoleId(ROLE_ID_SITE_ADMIN);
				$templateMgr->assign('siteAdmin', $siteAdmins->next());
			}
		}


		$templateMgr->assign('newVersionAvailable', $newVersionAvailable);
		$templateMgr->assign('publishingMode', $journal->getSetting('publishingMode'));
		$templateMgr->assign('announcementsEnabled', $journal->getSetting('enableAnnouncements'));
		$session = $request->getSession();
		$session->unsetSessionVar('enrolmentReferrer');

		$templateMgr->display('manager/index.tpl');
	}

	/**
	 * Setup common template variables.
	 * @param $request PKPRequest
	 */
	function setupTemplate($request) {
		parent::setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_APP_MANAGER, LOCALE_COMPONENT_PKP_ADMIN);
	}
}

?>
