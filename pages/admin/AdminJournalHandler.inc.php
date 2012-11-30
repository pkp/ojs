<?php

/**
 * @file pages/admin/AdminJournalHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminJournalHandler
 * @ingroup pages_admin
 *
 * @brief Handle requests for journal management in site administration.
 */

import('pages.admin.AdminHandler');

class AdminJournalHandler extends AdminHandler {
	/**
	 * Constructor
	 */
	function AdminJournalHandler() {
		parent::AdminHandler();
	}

	/**
	 * Display a list of the journals hosted on the site.
	 */
	function journals($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->display('admin/journals.tpl');
	}
}

?>
