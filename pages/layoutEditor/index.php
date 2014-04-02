<?php

/**
 * @defgroup pages_layoutEditor
 */
 
/**
 * @file pages/layoutEditor/index.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_layoutEditor
 * @brief Handle requests for layout editor functions. 
 *
 */

switch ($op) {
	//
	// issue 
	//
	case 'issueData':
	case 'issueToc':
	case 'resetSectionOrder':
	case 'updateIssueToc':
	case 'moveSectionToc':
	case 'moveArticleToc':
	case 'editIssue':
	case 'removeCoverPage':
	case 'removeStyleFile':
		define('HANDLER_CLASS', 'IssueManagementHandler');
		import('pages.editor.IssueManagementHandler');
		break;
	case 'viewMetadata':
	//
	// Submission Layout Editing
	//
	case 'submission':
	case 'submissionEditing':
	case 'completeAssignment':
	case 'uploadLayoutFile':
	case 'editGalley':
	case 'saveGalley':
	case 'deleteGalley':
	case 'orderGalley':
	case 'proofGalley':
	case 'proofGalleyTop':
	case 'proofGalleyFile':
	case 'editSuppFile':
	case 'saveSuppFile':
	case 'deleteSuppFile':
	case 'orderSuppFile':
	case 'downloadFile':
	case 'viewFile':
	case 'downloadLayoutTemplate':
	case 'deleteArticleImage':
	//
	// Proofreading Actions
	//
	case 'layoutEditorProofreadingComplete':
		define('HANDLER_CLASS', 'SubmissionLayoutHandler');
		import('pages.layoutEditor.SubmissionLayoutHandler');
		break;
	//
	// Submission Comments
	//
	case 'viewLayoutComments':
	case 'postLayoutComment':
	case 'viewProofreadComments':
	case 'postProofreadComment':
	case 'editComment':
	case 'saveComment':
	case 'deleteComment':
		define('HANDLER_CLASS', 'SubmissionCommentsHandler');
		import('pages.layoutEditor.SubmissionCommentsHandler');
		break;
	case 'index':
	case 'submissions':
	case 'futureIssues':
	case 'backIssues':
	case 'instructions':
	case 'completeProofreader':
		define('HANDLER_CLASS', 'LayoutEditorHandler');
		import('pages.layoutEditor.LayoutEditorHandler');
		break;
}

?>
