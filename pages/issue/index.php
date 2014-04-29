<?php

/**
 * @defgroup pages_issue
 */
 
/**
 * @file pages/issue/index.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_issue
 * @brief Handle requests for issue functions. 
 *
 */

switch ($op) {
	case 'index':
	case 'current':
	case 'view':
	case 'archive':
	case 'viewIssue':
	case 'viewDownloadInterstitial':
	case 'viewFile':
	case 'download':
		define('HANDLER_CLASS', 'IssueHandler');
		import('pages.issue.IssueHandler');
		break;
}

?>
