<?php

/**
 * @defgroup pages_admin Administration Pages
 */

/**
 * @file pages/admin/index.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_admin
 * @brief Handle requests for site administration functions.
 *
 */

switch ($op) {
	//
	// Journal Management
	//
	case 'contexts':
		define('HANDLER_CLASS', 'AdminContextHandler');
		import('lib.pkp.pages.admin.AdminContextHandler');
		break;
	//
	// Administrative functions
	//
	case 'systemInfo':
	case 'phpinfo':
	case 'expireSessions':
	case 'clearTemplateCache':
	case 'clearDataCache':
	case 'downloadScheduledTaskLogFile':
	case 'clearScheduledTaskLogFiles':
		define('HANDLER_CLASS', 'AdminFunctionsHandler');
		import('lib.pkp.pages.admin.AdminFunctionsHandler');
		break;
	// Main administration page
	case 'index':
	case 'settings':
	case 'saveSettings':
		define('HANDLER_CLASS', 'AdminHandler');
		import('lib.pkp.pages.admin.AdminHandler');
		break;
}

?>
