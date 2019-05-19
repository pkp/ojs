<?php

/**
 * @defgroup pages_about About page
 */

/**
 * @file pages/about/index.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_about
 * @brief Handle requests for about the journal functions.
 *
 */

switch ($op) {
	case 'subscriptions':
		define('HANDLER_CLASS', 'AboutHandler');
		import('pages.about.AboutHandler');
		break;
	default:
		// Fall back on pkp-lib implementation
		require_once('lib/pkp/pages/about/index.php');
}


