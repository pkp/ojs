<?php

/**
 * @file pages/manager/JournalLanguagesHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalLanguagesHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for changing journal language settings.
 */

import('pages.manager.ManagerHandler');

class JournalLanguagesHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function JournalLanguagesHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display form to edit language settings.
	 */
	function languages($args, &$request) {
		$this->validate($request);
		$this->setupTemplate($request, true);

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->display('manager/languageSettings.tpl');
	}
}

?>
