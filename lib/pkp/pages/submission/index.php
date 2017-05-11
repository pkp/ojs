<?php

/**
 * @defgroup pages_submission Submission Pages
 */

/**
 * @file lib/pkp/pages/submission/index.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_submission
 * @brief Handle requests for the submission wizard.
 *
 */

switch ($op) {
	//
	// PKP Submission
	//
	case 'fetchChoices':
		import('lib.pkp.pages.submission.PKPSubmissionHandler');
		define('HANDLER_CLASS', 'PKPSubmissionHandler');
		break;
}

?>
