<?php

/**
 * @defgroup pages_editor
 */
 
/**
 * @file pages/editor/index.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_editor
 * @brief Handle requests for editor functions. 
 *
 */

// $Id$

switch ($op) {
	//
	// Issue
	//
	case 'futureIssues':
	case 'backIssues':
	case 'removeIssue':
	case 'createIssue':
	case 'saveIssue':
	case 'issueData':
	case 'editIssue':
	case 'removeIssueCoverPage':
	case 'removeStyleFile':
	case 'issueToc':
	case 'updateIssueToc':
	case 'setCurrentIssue':
	case 'moveIssue':
	case 'resetIssueOrder':
	case 'moveSectionToc':
	case 'resetSectionOrder':
	case 'moveArticleToc':
	case 'publishIssue':
	case 'notifyUsers':
		define('HANDLER_CLASS', 'IssueManagementHandler');
		import('pages.editor.IssueManagementHandler');
		break;
	default:
		define('HANDLER_CLASS', 'EditorHandler');
		import('pages.editor.EditorHandler');
}

?>
