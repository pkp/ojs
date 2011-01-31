<?php

/**
 * @file SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionCommentsHandler
 * @ingroup pages_copyeditor
 *
 * @brief Handle requests for submission comments. 
 */

// $Id$


import('pages.copyeditor.SubmissionCopyeditHandler');

class SubmissionCommentsHandler extends CopyeditorHandler {
	/** comment associated with this request **/
	var $comment;

	/**
	 * Constructor
	 **/
	function SubmissionCommentsHandler() {
		parent::CopyeditorHandler();
	}
	
	/**
	 * View layout comments.
	 */
	function viewLayoutComments($args) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = $args[0];

		$submissionCopyeditHandler = new SubmissionCopyeditHandler();
		$submissionCopyeditHandler->validate($articleId);
		$submission =& $submissionCopyeditHandler->submission;
		CopyeditorAction::viewLayoutComments($submission);

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

		$submissionCopyeditHandler = new SubmissionCopyeditHandler();
		$submissionCopyeditHandler->validate($articleId);
		$submission =& $submissionCopyeditHandler->submission;
		if (CopyeditorAction::postLayoutComment($submission, $emailComment)) {
			CopyeditorAction::viewLayoutComments($submission);
		}

	}

	/**
	 * View copyedit comments.
	 */
	function viewCopyeditComments($args) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = $args[0];

		$submissionCopyeditHandler = new SubmissionCopyeditHandler();
		$submissionCopyeditHandler->validate($articleId);
		$submission =& $submissionCopyeditHandler->submission;
		CopyeditorAction::viewCopyeditComments($submission);

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

		$submissionCopyeditHandler = new SubmissionCopyeditHandler();
		$submissionCopyeditHandler->validate($articleId);
		$submission =& $submissionCopyeditHandler->submission;
		if (CopyeditorAction::postCopyeditComment($submission, $emailComment)) {
			CopyeditorAction::viewCopyeditComments($submission);
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

		$submissionCopyeditHandler = new SubmissionCopyeditHandler();
		$submissionCopyeditHandler->validate($articleId);
		$submission =& $submissionCopyeditHandler->submission;

		CopyeditorAction::editComment($submission, $comment);

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

		$submissionCopyeditHandler = new SubmissionCopyeditHandler();
		$submissionCopyeditHandler->validate($articleId);
		$submission =& $submissionCopyeditHandler->submission;
		CopyeditorAction::saveComment($submission, $comment, $emailComment);

		// refresh the comment
		$articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
		$comment =& $articleCommentDao->getArticleCommentById($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
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

		$submissionCopyeditHandler = new SubmissionCopyeditHandler();
		$submissionCopyeditHandler->validate($articleId);
		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate();
		$comment =& $this->comment;

		$this->setupTemplate(true);

		CopyeditorAction::deleteComment($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
			Request::redirect(null, null, 'viewCopyeditComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			Request::redirect(null, null, 'viewLayoutComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			Request::redirect(null, null, 'viewProofreadComments', $articleId);
		}
	}
}
?>
