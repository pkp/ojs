<?php

/**
 * @file pages/admin/AdminHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminHandler
 * @ingroup pages_admin
 *
 * @brief Handle requests for site administration functions. 
 */

import('classes.handler.Handler');

class AdminHandler extends Handler {
	/**
	 * Constructor
	 **/
	function AdminHandler() {
		parent::Handler();
		
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_SITE_ADMIN)));
		$this->addCheck(new HandlerValidatorCustom($this, true, null, null, create_function(null, 'return Request::getRequestedJournalPath() == \'index\';')));
	}

	/**
	 * Display site admin index page.
	 */
	function index() {
		$this->validate();
		$this->setupTemplate();

		$templateMgr =& TemplateManager::getManager();

		// Display a warning message if there is a new version of OJS available
		$newVersionAvailable = false;
		if (Config::getVar('general', 'show_upgrade_warning')) {
			import('lib.pkp.classes.site.VersionCheck');
			if($latestVersion = VersionCheck::checkIfNewVersionExists()) {
				$newVersionAvailable = true;
				$templateMgr->assign('latestVersion', $latestVersion);
				$currentVersion =& VersionCheck::getCurrentDBVersion();
				$templateMgr->assign('currentVersion', $currentVersion->getVersionString());
			}
		}

		$templateMgr->assign('newVersionAvailable', $newVersionAvailable);
		$templateMgr->assign('helpTopicId', 'site.index');
		$templateMgr->display('admin/index.tpl');
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		parent::setupTemplate();
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_ADMIN, LOCALE_COMPONENT_OJS_ADMIN, LOCALE_COMPONENT_OJS_MANAGER);
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'admin'), 'admin.siteAdmin'))
				: array(array(Request::url(null, 'user'), 'navigation.user'))
		);
	}
}

?>
