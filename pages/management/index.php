<?php

/**
 * @defgroup pages_management Management Pages
 */

/**
 * @file pages/management/index.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
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
		import('pages.management.ToolsHandler');
		define('HANDLER_CLASS', 'ToolsHandler');
		break;
}

?>
