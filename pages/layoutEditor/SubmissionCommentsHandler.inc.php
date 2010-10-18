<?php

/**
 * @file SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionCommentsHandler
 * @ingroup pages_layoutEditor
 *
 * @brief Handle requests for submission comments.
 */

// $Id$


import('pages.layoutEditor.SubmissionLayoutHandler');

class SubmissionCommentsHandler extends LayoutEditorHandler {
	/** comment associated with the request **/
	var $comment;
	
	/**
	 * Constructor
	 **/
	function SubmissionCommentsHandler() {
		parent::LayoutEditorHandler();
	}
	
	/**
	 * View layout comments.
	 */
	function viewLayoutComments($args) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = $args[0];

		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);
		$submission =& $submissionLayoutHandler->submission;
		LayoutEditorAction::viewLayoutComments($submission);

	}

	/**
	 * Post layout comment.
	 */
	function postLayoutComment($args, $request) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = (int) $request->getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);
		$submission =& $submissionLayoutHandler->submission;
		if (LayoutEditorAction::postLayoutComment($submission, $emailComment, $request)) {
			LayoutEditorAction::viewLayoutComments($submission);
		}
	}

	/**
	 * View proofread comments.
	 */
	function viewProofreadComments($args) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = $args[0];

		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);
		$submission =& $submissionLayoutHandler->submission;
		LayoutEditorAction::viewProofreadComments($submission);

	}

	/**
	 * Post proofread comment.
	 */
	function postProofreadComment($args, $request) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = (int) $request->getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);
		$submission =& $submissionLayoutHandler->submission;
		if (LayoutEditorAction::postProofreadComment($submission, $emailComment, $request)) {
			LayoutEditorAction::viewProofreadComments($submission);
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

		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);
		$submission =& $submissionLayoutHandler->submission;

		LayoutEditorAction::editComment($submission, $comment);

	}

	/**
	 * Save comment.
	 * @param $args array
	 * @param $request object
	 */
	function saveComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$commentId = (int) $request->getUserVar('commentId');

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate();
		$comment =& $this->comment;
		
		$this->setupTemplate(true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);
		$submission =& $submissionLayoutHandler->submission;

		LayoutEditorAction::saveComment($submission, $comment, $emailComment, $request);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			$request->redirect(null, null, 'viewLayoutComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			$request->redirect(null, null, 'viewProofreadComments', $articleId);
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

		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);
		$submission =& $submissionLayoutHandler->submission;

		LayoutEditorAction::deleteComment($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			Request::redirect(null, null, 'viewLayoutComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			Request::redirect(null, null, 'viewProofreadComments', $articleId);
		}
	}
}

?>
