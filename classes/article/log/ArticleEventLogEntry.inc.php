<?php

/**
 * @file classes/article/log/ArticleEventLogEntry.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleEventLogEntry
 * @ingroup article_log
 * @see ArticleEventLogDAO
 *
 * @brief Extension to EventLogEntry for article-specific log entries.
 */


import('lib.pkp.classes.log.EventLogEntry');

// Log entry associative types. All types must be defined here
define('ARTICLE_LOG_TYPE_DEFAULT', 			0);
define('ARTICLE_LOG_TYPE_AUTHOR', 			0x01);
define('ARTICLE_LOG_TYPE_EDITOR', 			0x02);
define('ARTICLE_LOG_TYPE_REVIEW', 			0x03);
define('ARTICLE_LOG_TYPE_COPYEDIT', 			0x04);
define('ARTICLE_LOG_TYPE_LAYOUT', 			0x05);
define('ARTICLE_LOG_TYPE_PROOFREAD', 			0x06);

// Log entry event types. All types must be defined here
define('ARTICLE_LOG_DEFAULT', 0);

// General events 					0x10000000
define('ARTICLE_LOG_ARTICLE_SUBMIT', 			0x10000001);
define('ARTICLE_LOG_METADATA_UPDATE', 			0x10000002);
define('ARTICLE_LOG_SUPPFILE_UPDATE', 			0x10000003);
define('ARTICLE_LOG_ISSUE_SCHEDULE', 			0x10000004);
define('ARTICLE_LOG_ISSUE_ASSIGN', 			0x10000005);
define('ARTICLE_LOG_ARTICLE_PUBLISH', 			0x10000006);
define('ARTICLE_LOG_ARTICLE_IMPORT',			0x10000007);

// Author events 					0x20000000
define('ARTICLE_LOG_AUTHOR_REVISION', 			0x20000001);

// Editor events 					0x30000000
define('ARTICLE_LOG_EDITOR_ASSIGN', 			0x30000001);
define('ARTICLE_LOG_EDITOR_UNASSIGN',		 	0x30000002);
define('ARTICLE_LOG_EDITOR_DECISION', 			0x30000003);
define('ARTICLE_LOG_EDITOR_FILE', 			0x30000004);
define('ARTICLE_LOG_EDITOR_ARCHIVE', 			0x30000005);
define('ARTICLE_LOG_EDITOR_RESTORE', 			0x30000006);
define('ARTICLE_LOG_EDITOR_EXPEDITE', 			0x30000007);

// Reviewer events 					0x40000000
define('ARTICLE_LOG_REVIEW_ASSIGN', 			0x40000001);
define('ARTICLE_LOG_REVIEW_UNASSIGN',		 	0x40000002);
define('ARTICLE_LOG_REVIEW_INITIATE', 			0x40000003);
define('ARTICLE_LOG_REVIEW_CANCEL', 			0x40000004);
define('ARTICLE_LOG_REVIEW_REINITIATE',	 		0x40000005);
define('ARTICLE_LOG_REVIEW_ACCEPT', 			0x40000006);
define('ARTICLE_LOG_REVIEW_DECLINE', 			0x40000007);
define('ARTICLE_LOG_REVIEW_REVISION', 			0x40000008);
define('ARTICLE_LOG_REVIEW_RECOMMENDATION', 		0x40000009);
define('ARTICLE_LOG_REVIEW_RATE', 			0x40000010);
define('ARTICLE_LOG_REVIEW_SET_DUE_DATE', 		0x40000011);
define('ARTICLE_LOG_REVIEW_RESUBMIT', 			0x40000012);
define('ARTICLE_LOG_REVIEW_FILE', 			0x40000013);
define('ARTICLE_LOG_REVIEW_CLEAR', 			0x40000014);
define('ARTICLE_LOG_REVIEW_CONFIRM_BY_PROXY', 		0x40000015);
define('ARTICLE_LOG_REVIEW_RECOMMENDATION_BY_PROXY', 	0x40000016);
define('ARTICLE_LOG_REVIEW_FILE_BY_PROXY', 		0x40000017);

// Copyeditor events 					0x50000000
define('ARTICLE_LOG_COPYEDIT_ASSIGN', 			0x50000001);
define('ARTICLE_LOG_COPYEDIT_UNASSIGN',	 		0x50000002);
define('ARTICLE_LOG_COPYEDIT_INITIATE', 		0x50000003);
define('ARTICLE_LOG_COPYEDIT_REVISION', 		0x50000004);
define('ARTICLE_LOG_COPYEDIT_INITIAL', 			0x50000005);
define('ARTICLE_LOG_COPYEDIT_FINAL', 			0x50000006);
define('ARTICLE_LOG_COPYEDIT_SET_FILE',			0x50000007);
define('ARTICLE_LOG_COPYEDIT_COPYEDIT_FILE',		0x50000008);
define('ARTICLE_LOG_COPYEDIT_COPYEDITOR_FILE',		0x50000009);

// Proofreader events 					0x60000000
define('ARTICLE_LOG_PROOFREAD_ASSIGN', 			0x60000001);
define('ARTICLE_LOG_PROOFREAD_UNASSIGN', 		0x60000002);
define('ARTICLE_LOG_PROOFREAD_INITIATE', 		0x60000003);
define('ARTICLE_LOG_PROOFREAD_REVISION', 		0x60000004);
define('ARTICLE_LOG_PROOFREAD_COMPLETE', 		0x60000005);

// Layout events 					0x70000000
define('ARTICLE_LOG_LAYOUT_ASSIGN', 			0x70000001);
define('ARTICLE_LOG_LAYOUT_UNASSIGN', 			0x70000002);
define('ARTICLE_LOG_LAYOUT_INITIATE', 			0x70000003);
define('ARTICLE_LOG_LAYOUT_GALLEY', 			0x70000004);
define('ARTICLE_LOG_LAYOUT_COMPLETE', 			0x70000005);


class ArticleEventLogEntry extends EventLogEntry {
	/**
	 * Constructor
	 */
	function ArticleEventLogEntry() {
		parent::EventLogEntry();
	}

	/**
	 * Return locale message key describing event type.
	 * @return string
	 */
	function getEventTitle() {
		switch ($this->getData('eventType')) {
			// General events
			case ARTICLE_LOG_ARTICLE_SUBMIT:
				return 'submission.event.general.articleSubmitted';
			case ARTICLE_LOG_METADATA_UPDATE:
				return 'submission.event.general.metadataUpdated';
			case ARTICLE_LOG_SUPPFILE_UPDATE:
				return 'submission.event.general.suppFileUpdated';
			case ARTICLE_LOG_ISSUE_SCHEDULE:
				return 'submission.event.general.issueScheduled';
			case ARTICLE_LOG_ISSUE_ASSIGN:
				return 'submission.event.general.issueAssigned';
			case ARTICLE_LOG_ARTICLE_PUBLISH:
				return 'submission.event.general.articlePublished';

			// Author events
			case ARTICLE_LOG_AUTHOR_REVISION:
				return 'submission.event.author.authorRevision';

			// Editor events
			case ARTICLE_LOG_EDITOR_ASSIGN:
				return 'submission.event.editor.editorAssigned';
			case ARTICLE_LOG_EDITOR_UNASSIGN:
				return 'submission.event.editor.editorUnassigned';
			case ARTICLE_LOG_EDITOR_DECISION:
				return 'submission.event.editor.editorDecision';
			case ARTICLE_LOG_EDITOR_FILE:
				return 'submission.event.editor.editorFile';
			case ARTICLE_LOG_EDITOR_ARCHIVE:
				return 'submission.event.editor.submissionArchived';
			case ARTICLE_LOG_EDITOR_RESTORE:
				return 'submission.event.editor.submissionRestored';

			// Reviewer events
			case ARTICLE_LOG_REVIEW_ASSIGN:
				return 'submission.event.reviewer.reviewerAssigned';
			case ARTICLE_LOG_REVIEW_UNASSIGN:
				return 'submission.event.reviewer.reviewerUnassigned';
			case ARTICLE_LOG_REVIEW_INITIATE:
				return 'submission.event.reviewer.reviewInitiated';
			case ARTICLE_LOG_REVIEW_CANCEL:
				return 'submission.event.reviewer.reviewCancelled';
			case ARTICLE_LOG_REVIEW_REINITIATE:
				return 'submission.event.reviewer.reviewReinitiated';
			case ARTICLE_LOG_REVIEW_CONFIRM_BY_PROXY:
				return 'submission.event.reviewer.reviewAcceptedByProxy';
			case ARTICLE_LOG_REVIEW_ACCEPT:
				return 'submission.event.reviewer.reviewAccepted';
			case ARTICLE_LOG_REVIEW_DECLINE:
				return 'submission.event.reviewer.reviewDeclined';
			case ARTICLE_LOG_REVIEW_REVISION:
				return 'submission.event.reviewer.reviewRevision';
			case ARTICLE_LOG_REVIEW_RECOMMENDATION:
				return 'submission.event.reviewer.reviewRecommendation';
			case ARTICLE_LOG_REVIEW_RATE:
				return 'submission.event.reviewer.reviewerRated';
			case ARTICLE_LOG_REVIEW_SET_DUE_DATE:
				return 'submission.event.reviewer.reviewDueDate';
			case ARTICLE_LOG_REVIEW_RESUBMIT:
				return 'submission.event.reviewer.reviewResubmitted';
			case ARTICLE_LOG_REVIEW_FILE:
				return 'submission.event.reviewer.reviewFile';

			// Copyeditor events
			case ARTICLE_LOG_COPYEDIT_ASSIGN:
				return 'submission.event.copyedit.copyeditorAssigned';
			case ARTICLE_LOG_COPYEDIT_UNASSIGN:
				return 'submission.event.copyedit.copyeditorUnassigned';
			case ARTICLE_LOG_COPYEDIT_INITIATE:
				return 'submission.event.copyedit.copyeditInitiated';
			case ARTICLE_LOG_COPYEDIT_REVISION:
				return 'submission.event.copyedit.copyeditRevision';
			case ARTICLE_LOG_COPYEDIT_INITIAL:
				return 'submission.event.copyedit.copyeditInitialCompleted';
			case ARTICLE_LOG_COPYEDIT_FINAL:
				return 'submission.event.copyedit.copyeditFinalCompleted';
			case ARTICLE_LOG_COPYEDIT_SET_FILE:
				return 'submission.event.copyedit.copyeditSetFile';

			// Proofreader events
			case ARTICLE_LOG_PROOFREAD_ASSIGN:
				return 'submission.event.proofread.proofreaderAssigned';
			case ARTICLE_LOG_PROOFREAD_UNASSIGN:
				return 'submission.event.proofread.proofreaderUnassigned';
			case ARTICLE_LOG_PROOFREAD_INITIATE:
				return 'submission.event.proofread.proofreadInitiated';
			case ARTICLE_LOG_PROOFREAD_REVISION:
				return 'submission.event.proofread.proofreadRevision';
			case ARTICLE_LOG_PROOFREAD_COMPLETE:
				return 'submission.event.proofread.proofreadCompleted';

			// Layout events
			case ARTICLE_LOG_LAYOUT_ASSIGN:
				return 'submission.event.layout.layoutEditorAssigned';
			case ARTICLE_LOG_LAYOUT_UNASSIGN:
				return 'submission.event.layout.layoutEditorUnassigned';
			case ARTICLE_LOG_LAYOUT_INITIATE:
				return 'submission.event.layout.layoutInitiated';
			case ARTICLE_LOG_LAYOUT_GALLEY:
				return 'submission.event.layout.layoutGalleyCreated';
			case ARTICLE_LOG_LAYOUT_COMPLETE:
				return 'submission.event.layout.layoutComplete';

			default:
				return 'submission.event.general.defaultEvent';
		}
	}
}
