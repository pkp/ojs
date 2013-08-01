<?php
/**
 * @defgroup pages_dashboard Editorial Dashboard Pages
 */

 /**
 * @file pages/dashboard/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_about
 * @brief Handle requests for dashboard functions.
 *
 */

switch($op) {
	case 'index':
	case 'tasks':
	case 'submissions':
	case 'archives':
		define('HANDLER_CLASS', 'DashboardHandler');
		import('pages.dashboard.DashboardHandler');
		break;
}

?>
