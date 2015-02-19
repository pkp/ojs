<?php

/**
 * @defgroup pages_rt Reading Tools Pages
 */
 
/**
 * @file pages/rt/index.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_rt
 * @brief Handle Reading Tools requests. 
 *
 */

switch ($op) {
	case 'bio':
	case 'metadata':
	case 'context':
	case 'captureCite':
	case 'printerFriendly':
	case 'emailColleague':
	case 'emailAuthor':
	case 'findingReferences':
		define('HANDLER_CLASS', 'RTHandler');
		import('pages.rt.RTHandler');
		break;
}

?>
