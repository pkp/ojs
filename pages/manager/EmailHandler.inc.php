<?php

/**
 * @file pages/manager/EmailHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmailHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for email management functions. 
 */

import('pages.manager.ManagerHandler');

class EmailHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function EmailHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display a list of the emails within the current journal.
	 */
	function emails($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('pageHierarchy', array(array($request->url(null, 'manager'), 'manager.journalManagement')));
		$templateMgr->assign('helpTopicId','journal.managementPages.emails');
		$templateMgr->display('manager/emails/emails.tpl');
	}
}

?>
