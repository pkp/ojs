<?php

/**
 * @file pages/manager/ManagerHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
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
		
		$this->addCheck(new HandlerValidatorJournal($this));
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER)));
	}
	/**
	 * Display journal management index page.
	 */
	function index($args, &$request) {
		$this->validate();
		$this->setupTemplate($request);
		$journal =& $request->getJournal();
		$templateMgr =& TemplateManager::getManager($request);

		// Display a warning message if there is a new version of OJS available
		$newVersionAvailable = false;
		if (Config::getVar('general', 'show_upgrade_warning')) {
			import('lib.pkp.classes.site.VersionCheck');
			if($latestVersion = VersionCheck::checkIfNewVersionExists()) {
				$newVersionAvailable = true;
				$templateMgr->assign('latestVersion', $latestVersion);
				$currentVersion =& VersionCheck::getCurrentDBVersion();
				$templateMgr->assign('currentVersion', $currentVersion->getVersionString());
				
				// Get contact information for site administrator
				$roleDao =& DAORegistry::getDAO('RoleDAO');
				$siteAdmins =& $roleDao->getUsersByRoleId(ROLE_ID_SITE_ADMIN);
				$templateMgr->assign_by_ref('siteAdmin', $siteAdmins->next());
			}
		}


		$templateMgr->assign('newVersionAvailable', $newVersionAvailable);
		$templateMgr->assign_by_ref('roleSettings', $this->retrieveRoleAssignmentPreferences($journal->getId()));
		$templateMgr->assign('publishingMode', $journal->getSetting('publishingMode'));
		$templateMgr->assign('announcementsEnabled', $journal->getSetting('enableAnnouncements'));
		$session =& $request->getSession();
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
	 
	/**
	 * Retrieves a list of special Journal Management settings related to the journal's inclusion of individual copyeditors, layout editors, and proofreaders.
	 * @param $journalId int Journal ID of the journal from which the settings will be obtained
	 * @return array
	 */	
	function &retrieveRoleAssignmentPreferences($journalId) {
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$journalSettings = $journalSettingsDao->getSettings($journalId);
  		$returner = array('useLayoutEditors'=>0,'useCopyeditors'=>0,'useProofreaders'=>0);

		foreach($returner as $specific=>$value) {
			if(isset($journalSettings[$specific])) {
				if($journalSettings[$specific]) {
					$returner[$specific]=1;
				}
			}
		}
		return $returner;
	}
}

?>
