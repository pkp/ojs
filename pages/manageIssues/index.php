<?php

/**
 * @defgroup pages_manageIssues Issue Management Pages
 */

/**
 * @file pages/manageIssues/index.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_editor
 * @brief Handle requests for issue management functions.
 *
 */

switch ($op) {
	//
	// Issue
	//
	case 'index':
		define('HANDLER_CLASS', 'ManageIssuesHandler');
		import('pages.manageIssues.ManageIssuesHandler');
		break;
}

?>
