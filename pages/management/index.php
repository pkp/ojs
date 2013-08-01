<?php

/**
 * @defgroup pages_settings Settings Pages
 */

/**
 * @file pages/settings/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_settings
 * @brief Handle requests for settings pages.
 *
 */


switch ($op) {
	//
	// Settings
	//
	case 'index':
	case 'settings':
		import('pages.management.SettingsHandler');
		define('HANDLER_CLASS', 'SettingsHandler');
		break;
}
?>
