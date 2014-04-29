<?php

/**
 * @defgroup pages_editor
 */

/**
 * @file pages/editor/index.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_editor
 * @brief Handle requests for editor functions.
 *
 */

switch ($op) {
	//
	// Submission Tracking
	//
	case 'enrollSearch':
	case 'createReviewer':
	case 'suggestUsername':
	case 'enroll':
	case 'submission':
	case 'submissionRegrets':
	case 'submissionReview':
	case 'submissionEditing':
	case 'submissionHistory':
	case 'submissionCitations':
	case 'changeSection':
	case 'recordDecision':
	case 'selectReviewer':
	case 'notifyReviewer':
	case 'notifyAllReviewers':
	case 'userProfile':
	case 'clearReview':
	case 'cancelReview':
	case 'remindReviewer':
	case 'thankReviewer':
	case 'rateReviewer':
	case 'reassignReviewer':
	case 'confirmReviewForReviewer':
	case 'uploadReviewForReviewer':
	case 'enterReviewerRecommendation':
	case 'makeReviewerFileViewable':
	case 'setDueDate':
	case 'viewMetadata':
	case 'saveMetadata':
	case 'removeArticleCoverPage':
	case 'editorReview':
	case 'selectCopyeditor':
	case 'notifyCopyeditor':
	case 'initiateCopyedit':
	case 'thankCopyeditor':
	case 'notifyAuthorCopyedit':
	case 'thankAuthorCopyedit':
	case 'notifyFinalCopyedit':
	case 'thankFinalCopyedit':
	case 'selectCopyeditRevisions':
	case 'uploadReviewVersion':
	case 'uploadCopyeditVersion':
	case 'completeCopyedit':
	case 'completeFinalCopyedit':
	case 'addSuppFile':
	case 'setSuppFileVisibility':
	case 'editSuppFile':
	case 'saveSuppFile':
	case 'deleteSuppFile':
	case 'deleteArticleFile':
	case 'archiveSubmission':
	case 'unsuitableSubmission':
	case 'restoreToQueue':
	case 'updateSection':
	case 'updateCommentsStatus':
	//
	// Layout Editing
	//
	case 'deleteArticleImage':
	case 'uploadLayoutFile':
	case 'uploadLayoutVersion':
	case 'assignLayoutEditor':
	case 'notifyLayoutEditor':
	case 'thankLayoutEditor':
	case 'uploadGalley':
	case 'editGalley':
	case 'saveGalley':
	case 'orderGalley':
	case 'deleteGalley':
	case 'proofGalley':
	case 'proofGalleyTop':
	case 'proofGalleyFile':
	case 'uploadSuppFile':
	case 'orderSuppFile':
	//
	// Submission History
	//
	case 'submissionEventLog':
	case 'clearSubmissionEventLog':
	case 'submissionEmailLog':
	case 'clearSubmissionEmailLog':
	case 'addSubmissionNote':
	case 'removeSubmissionNote':
	case 'updateSubmissionNote':
	case 'clearAllSubmissionNotes':
	case 'submissionNotes':
	//
	// Misc.
	//
	case 'downloadFile':
	case 'viewFile':
	// Submission Review Form
	case 'clearReviewForm':
	case 'selectReviewForm':
	case 'previewReviewForm':
	case 'viewReviewFormResponse':
	// Proof Assignment Functions
	case 'selectProofreader':
	case 'notifyAuthorProofreader':
	case 'thankAuthorProofreader':
	case 'editorInitiateProofreader':
	case 'editorCompleteProofreader':
	case 'notifyProofreader':
	case 'thankProofreader':
	case 'editorInitiateLayoutEditor':
	case 'editorCompleteLayoutEditor':
	case 'notifyLayoutEditorProofreader':
	case 'thankLayoutEditorProofreader':
	//
	// Scheduling functions
	//
	case 'scheduleForPublication':
	case 'setDatePublished':
	//
	// Payments
	//
	case 'waiveSubmissionFee':
	case 'waiveFastTrackFee':
	case 'waivePublicationFee':
	case 'downloadLayoutTemplate':
		define('HANDLER_CLASS', 'SubmissionEditHandler');
		import('pages.sectionEditor.SubmissionEditHandler');
		break;
	//
	// Submission Comments
	//
	case 'viewPeerReviewComments':
	case 'postPeerReviewComment':
	case 'viewEditorDecisionComments':
	case 'bccEditorDecisionCommentToReviewers':
	case 'postEditorDecisionComment':
	case 'viewCopyeditComments':
	case 'postCopyeditComment':
	case 'emailEditorDecisionComment':
	case 'viewLayoutComments':
	case 'postLayoutComment':
	case 'viewProofreadComments':
	case 'postProofreadComment':
	case 'editComment':
	case 'saveComment':
	case 'deleteComment':
		define('HANDLER_CLASS', 'SubmissionCommentsHandler');
		import('pages.sectionEditor.SubmissionCommentsHandler');
		break;
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
	case 'issueGalleys':
	case 'uploadIssueGalley':
	case 'editIssueGalley':
	case 'saveIssueGalley':
	case 'orderIssueGalley':
	case 'deleteIssueGalley':
	case 'proofIssueGalley':
	case 'proofIssueGalleyTop':
	case 'proofIssueGalleyFile':
	case 'downloadIssueFile':
	case 'issueToc':
	case 'updateIssueToc':
	case 'setCurrentIssue':
	case 'moveIssue':
	case 'resetIssueOrder':
	case 'moveSectionToc':
	case 'resetSectionOrder':
	case 'moveArticleToc':
	case 'publishIssue':
	case 'unpublishIssue':
	case 'notifyUsers':
		define('HANDLER_CLASS', 'IssueManagementHandler');
		import('pages.editor.IssueManagementHandler');
		break;
	case 'index':
	case 'submissions':
	case 'setEditorFlags':
	case 'deleteEditAssignment':
	case 'assignEditor':
	case 'deleteSubmission':
	case 'instructions':
		define('HANDLER_CLASS', 'EditorHandler');
		import('pages.editor.EditorHandler');
}

?>
