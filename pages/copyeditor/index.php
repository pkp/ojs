<?php

/**
 * @defgroup pages_copyeditor
 */

/**
 * @file pages/copyeditor/index.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_copyeditor
 * @brief Handle requests for copyeditor functions.
 *
 */

switch ($op) {
	//
	// Assignment Tracking
	//
	case 'submission':
	case 'completeCopyedit':
	case 'completeFinalCopyedit':
	case 'uploadCopyeditVersion':
	//
	// Misc.
	//
	case 'downloadFile':
	case 'viewFile':
	//
	// Proofreading Actions
	//
	case 'authorProofreadingComplete':
	case 'proofGalley':
	case 'proofGalleyTop':
	case 'proofGalleyFile':
	//
	// Metadata Actions
	//
	case 'viewMetadata':
	case 'saveMetadata':
	case 'removeArticleCoverPage':
	//
	// Citation Editing
	//
	case 'submissionCitations':
		define('HANDLER_CLASS', 'SubmissionCopyeditHandler');
		import('pages.copyeditor.SubmissionCopyeditHandler');
		break;
	//
	// Submission Comments
	//
	case 'viewLayoutComments':
	case 'postLayoutComment':
	case 'viewCopyeditComments':
	case 'postCopyeditComment':
	case 'editComment':
	case 'saveComment':
	case 'deleteComment':
		define('HANDLER_CLASS', 'SubmissionCommentsHandler');
		import('pages.copyeditor.SubmissionCommentsHandler');
		break;
	case 'index':
	case 'instructions':
		define('HANDLER_CLASS', 'CopyeditorHandler');
		import('pages.copyeditor.CopyeditorHandler');
}

?>
