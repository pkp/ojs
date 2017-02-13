<?php
/**
 * @defgroup pages_submissions Submissions editorial page
 */

 /**
 * @file pages/submissions/index.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_submissions
 * @brief Handle requests for submissions functions.
 *
 */

switch($op) {
	case 'index':
	case 'tasks':
	case 'myQueue':
	case 'active':
	case 'archives':
		define('HANDLER_CLASS', 'DashboardHandler');
		import('lib.pkp.pages.dashboard.DashboardHandler');
		break;
}

?>
