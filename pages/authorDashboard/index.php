<?php

/**
 * @defgroup pages_authorDashboard Author Dashboard Pages
 */

/**
 * @file pages/authorDashboard/index.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_authorDashboard
 * @brief Handle requests for the author dashboard.
 *
 */


switch ($op) {
	//
	// Author Dashboard
	//
	case 'submission':
	case 'readSubmissionEmail':
	case 'reviewRoundInfo':
		import('pages.authorDashboard.AuthorDashboardHandler');
		define('HANDLER_CLASS', 'AuthorDashboardHandler');
}


