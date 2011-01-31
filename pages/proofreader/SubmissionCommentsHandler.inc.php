<?php

/**
 * @file SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionCommentsHandler
 * @ingroup pages_proofreader
 *
 * @brief Handle requests for submission comments.
 */

// $Id$


import('pages.proofreader.SubmissionProofreadHandler');

class SubmissionCommentsHandler extends ProofreaderHandler {
	/** comment associated with the request **/
	var $comment;

	/**
	 * Constructor
	 **/
	function SubmissionCommentsHandler() {
		parent::ProofreaderHandler();
	}

	/**
	 * View proofread comments.
	 */
	function viewProofreadComments($args) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = $args[0];

		$submissionProofreadHandler = new SubmissionProofreadHandler();
		$submissionProofreadHandler->validate($articleId);
		$submission =& $submissionProofreadHandler->submission;
		ProofreaderAction::viewProofreadComments($submission);
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

		$submissionProofreadHandler = new SubmissionProofreadHandler();
		$submissionProofreadHandler->validate($articleId);
		$submission =& $submissionProofreadHandler->submission;

		if (ProofreaderAction::postProofreadComment($submission, $emailComment)) {
			ProofreaderAction::viewProofreadComments($submission);
		}
	}

	/**
	 * View layout comments.
	 */
	function viewLayoutComments($args) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = $args[0];

		$submissionProofreadHandler = new SubmissionProofreadHandler();
		$submissionProofreadHandler->validate($articleId);
		$submission =& $submissionProofreadHandler->submission;
		ProofreaderAction::viewLayoutComments($submission);

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

		$submissionProofreadHandler = new SubmissionProofreadHandler();
		$submissionProofreadHandler->validate($articleId);
		$submission =& $submissionProofreadHandler->submission;
		if (ProofreaderAction::postLayoutComment($submission, $emailComment)) {
			ProofreaderAction::viewLayoutComments($submission);
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

		$submissionProofreadHandler = new SubmissionProofreadHandler();
		$submissionProofreadHandler->validate($articleId);
		$submission =& $submissionProofreadHandler->submission;
		ProofreaderAction::editComment($submission, $comment);

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

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		$submissionProofreadHandler = new SubmissionProofreadHandler();
		$submissionProofreadHandler->validate($articleId);
		$submission =& $submissionProofreadHandler->submission;

		ProofreaderAction::saveComment($submission, $comment, $emailComment);

		// Determine which page to redirect back to.
		$commentPageMap = array(
			COMMENT_TYPE_PROOFREAD => 'viewProofreadComments',
			COMMENT_TYPE_LAYOUT => 'viewLayoutComments'
		);

		// Redirect back to initial comments page
		Request::redirect(null, null, $commentPageMap[$comment->getCommentType()], $articleId);
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
		
		$submissionProofreadHandler = new SubmissionProofreadHandler();
		$submissionProofreadHandler->validate($articleId);
		$submission =& $submissionProofreadHandler->submission;

		ProofreaderAction::deleteComment($commentId);

		// Determine which page to redirect back to.
		$commentPageMap = array(
			COMMENT_TYPE_PROOFREAD => 'viewProofreadComments',
			COMMENT_TYPE_LAYOUT => 'viewLayoutComments'
		);

		// Redirect back to initial comments page
		Request::redirect(null, null, $commentPageMap[$comment->getCommentType()], $articleId);
	}
}

?>
