<?php

/**
 * @file SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionCommentsHandler
 * @ingroup pages_author
 *
 * @brief Handle requests for submission comments. 
 */

// $Id$


import('pages.author.TrackSubmissionHandler');

class SubmissionCommentsHandler extends AuthorHandler {
	/** comment associated with the request **/
	var $comment;
	
	/**
	 * Constructor
	 **/
	function SubmissionCommentsHandler() {
		parent::AuthorHandler();
	}

	/**
	 * View editor decision comments.
	 */
	function viewEditorDecisionComments($args) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = $args[0];

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;

		AuthorAction::viewEditorDecisionComments($authorSubmission);
	}

	/**
	 * View copyedit comments.
	 */
	function viewCopyeditComments($args) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = $args[0];

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;

		AuthorAction::viewCopyeditComments($authorSubmission);

	}

	/**
	 * Post copyedit comment.
	 */
	function postCopyeditComment() {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = Request::getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;

		if (AuthorAction::postCopyeditComment($authorSubmission, $emailComment)) {
			AuthorAction::viewCopyeditComments($authorSubmission);
		}

	}

	/**
	 * View proofread comments.
	 */
	function viewProofreadComments($args) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = $args[0];

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;
		AuthorAction::viewProofreadComments($authorSubmission);
	}

	/**
	 * Post proofread comment.
	 */
	function postProofreadComment() {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = Request::getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;

		if (AuthorAction::postProofreadComment($authorSubmission, $emailComment)) {
			AuthorAction::viewProofreadComments($authorSubmission);
		}
	}

	/**
	 * View layout comments.
	 */
	function viewLayoutComments($args) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = $args[0];

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;
		AuthorAction::viewLayoutComments($authorSubmission);

	}

	/**
	 * Post layout comment.
	 */
	function postLayoutComment() {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = Request::getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;
		if (AuthorAction::postLayoutComment($authorSubmission, $emailComment)) {
			AuthorAction::viewLayoutComments($authorSubmission);
		}

	}

	/**
	 * Email an editor decision comment.
	 */
	function emailEditorDecisionComment() {
		$this->setupTemplate(true);
				
		$articleId = (int) Request::getUserVar('articleId');
		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;

		if (AuthorAction::emailEditorDecisionComment($authorSubmission, Request::getUserVar('send'))) {
			Request::redirect(null, null, 'submissionReview', array($articleId));
		}
	}

	/**
	 * Edit comment.
	 */
	function editComment($args) {
		$articleId = $args[0];
		$commentId = $args[1];

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate();
		$comment =& $this->comment;

		$this->setupTemplate(true);
		
		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;
		
		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			Request::redirect(null, Request::getRequestedPage());
		}

		AuthorAction::editComment($authorSubmission, $comment);

	}

	/**
	 * Save comment.
	 */
	function saveComment() {
		$articleId = Request::getUserVar('articleId');
		$commentId = Request::getUserVar('commentId');

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate();
		$comment =& $this->comment;
		
		$this->setupTemplate(true);
				
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;

		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			Request::redirect(null, Request::getRequestedPage());
		}

		AuthorAction::saveComment($authorSubmission, $comment, $emailComment);

		// refresh the comment
		$articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
		$comment =& $articleCommentDao->getArticleCommentById($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			Request::redirect(null, null, 'viewEditorDecisionComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
			Request::redirect(null, null, 'viewCopyeditComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			Request::redirect(null, null, 'viewLayoutComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			Request::redirect(null, null, 'viewProofreadComments', $articleId);
		}
	}

	/**
	 * Delete comment.
	 */
	function deleteComment($args) {
		$articleId = $args[0];
		$commentId = $args[1];

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate();
		$comment =& $this->comment;
		
		$this->setupTemplate(true);

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($articleId);
		$authorSubmission =& $trackSubmissionHandler->submission;
		AuthorAction::deleteComment($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			Request::redirect(null, null, 'viewEditorDecisionComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
			Request::redirect(null, null, 'viewCopyeditComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			Request::redirect(null, null, 'viewLayoutComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			Request::redirect(null, null, 'viewProofreadComments', $articleId);
		}
	}
}
?>
