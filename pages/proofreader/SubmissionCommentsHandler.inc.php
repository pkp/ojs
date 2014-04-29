<?php

/**
 * @file pages/proofreader/SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionCommentsHandler
 * @ingroup pages_proofreader
 *
 * @brief Handle requests for submission comments.
 */

import('pages.proofreader.SubmissionProofreadHandler');

class SubmissionCommentsHandler extends ProofreaderHandler {
	/** comment associated with the request **/
	var $comment;

	/**
	 * Constructor
	 */
	function SubmissionCommentsHandler() {
		parent::ProofreaderHandler();
	}

	/**
	 * View proofread comments.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewProofreadComments($args, &$request) {
		$articleId = (int) array_shift($args);
		$this->validate($request, $articleId);
		$this->setupTemplate(true);

		ProofreaderAction::viewProofreadComments($this->submission);
	}

	/**
	 * Post proofread comment.
	 * @param $args array
	 * @param $request object
	 */
	function postProofreadComment($args, &$request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$this->setupTemplate(true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		if (ProofreaderAction::postProofreadComment($this->submission, $emailComment, $request)) {
			ProofreaderAction::viewProofreadComments($this->submission);
		}
	}

	/**
	 * View layout comments.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewLayoutComments($args, &$request) {
		$articleId = (int) array_shift($args);
		$this->validate($request, $articleId);
		$this->setupTemplate(true);

		ProofreaderAction::viewLayoutComments($this->submission);
	}

	/**
	 * Post layout comment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function postLayoutComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$this->setupTemplate(true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		if (ProofreaderAction::postLayoutComment($this->submission, $emailComment, $request)) {
			ProofreaderAction::viewLayoutComments($this->submission);
		}
	}

	/**
	 * Edit comment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editComment($args, &$request) {
		$articleId = (int) array_shift($args);
		$commentId = (int) array_shift($args);

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate($request, $articleId);
		$this->setupTemplate(true);

		ProofreaderAction::editComment($this->submission, $this->comment);
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
		$this->validate($request, $articleId);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		ProofreaderAction::saveComment($this->submission, $this->comment, $emailComment, $request);

		// Determine which page to redirect back to.
		$commentPageMap = array(
			COMMENT_TYPE_PROOFREAD => 'viewProofreadComments',
			COMMENT_TYPE_LAYOUT => 'viewLayoutComments'
		);

		// Redirect back to initial comments page
		$request->redirect(null, null, $commentPageMap[$comment->getCommentType()], $articleId);
	}

	/**
	 * Delete comment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function deleteComment($args, &$request) {
		$articleId = (int) array_shift($args);
		$commentId = (int) array_shift($args);

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate($request, $articleId);
		ProofreaderAction::deleteComment($commentId);

		// Determine which page to redirect back to.
		$commentPageMap = array(
			COMMENT_TYPE_PROOFREAD => 'viewProofreadComments',
			COMMENT_TYPE_LAYOUT => 'viewLayoutComments'
		);

		// Redirect back to initial comments page
		$request->redirect(null, null, $commentPageMap[$this->comment->getCommentType()], $articleId);
	}
}

?>
