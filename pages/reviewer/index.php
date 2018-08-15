<?php

/**
 * @defgroup pages_reviewer Reviewer Pages
 */

/**
 * @file pages/reviewer/index.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_reviewer
 * @brief Handle requests for reviewer functions.
 *
 */


switch ($op) {
	//
	// Submission Tracking
	//
	case 'submission':
	case 'step':
	case 'saveStep':
	case 'showDeclineReview':
	case 'saveDeclineReview':
		define('HANDLER_CLASS', 'ReviewerHandler');
		import('pages.reviewer.ReviewerHandler');
		break;
}


