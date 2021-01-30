<?php

/**
 * @defgroup pages_management Management Pages
 */

/**
 * @file pages/management/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_management
 * @brief Handle requests for settings pages.
 *
 */

switch ($op) {
	//
	// Settings
	//
	case 'index':
	case 'settings':
	case 'access':
		import('pages.management.SettingsHandler');
		define('HANDLER_CLASS', 'SettingsHandler');
		break;
	case 'tools':
	case 'importexport':
	case 'statistics':
	case 'permissions':
	case 'resetPermissions':
		import('lib.pkp.pages.management.PKPToolsHandler');
		define('HANDLER_CLASS', 'PKPToolsHandler');
		break;
}


