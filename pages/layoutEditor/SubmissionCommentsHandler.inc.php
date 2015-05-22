<?php

/**
 * @file pages/layoutEditor/SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionCommentsHandler
 * @ingroup pages_layoutEditor
 *
 * @brief Handle requests for submission comments.
 */

import('pages.layoutEditor.SubmissionLayoutHandler');

class SubmissionCommentsHandler extends LayoutEditorHandler {
	/** comment associated with the request **/
	var $comment;
	
	/**
	 * Constructor
	 */
	function SubmissionCommentsHandler() {
		parent::LayoutEditorHandler();
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

		LayoutEditorAction::viewLayoutComments($this->submission);
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

		if (LayoutEditorAction::postLayoutComment($this->submission, $emailComment, $request)) {
			LayoutEditorAction::viewLayoutComments($this->submission);
		}
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

		LayoutEditorAction::viewProofreadComments($this->submission);
	}

	/**
	 * Post proofread comment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function postProofreadComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$this->setupTemplate(true);


		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		if (LayoutEditorAction::postProofreadComment($this->submission, $emailComment, $request)) {
			LayoutEditorAction::viewProofreadComments($this->submission);
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

		LayoutEditorAction::editComment($this->submission, $this->comment);
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
		$this->setupTemplate(true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		LayoutEditorAction::saveComment($this->submission, $this->comment, $emailComment, $request);

		// Redirect back to initial comments page
		if ($this->comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			$request->redirect(null, null, 'viewLayoutComments', $articleId);
		} else if ($this->comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			$request->redirect(null, null, 'viewProofreadComments', $articleId);
		}
	}

	/**
	 * Delete comment.
	 * @param $args array
	 * @param $request object
	 */
	function deleteComment($args, &$request) {
		$articleId = (int) array_shift($args);
		$commentId = (int) array_shift($args);

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate($request, $articleId);
		$this->setupTemplate(true);

		LayoutEditorAction::deleteComment($commentId);

		// Redirect back to initial comments page
		if ($this->comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			$request->redirect(null, null, 'viewLayoutComments', $articleId);
		} else if ($this->comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			$request->redirect(null, null, 'viewProofreadComments', $articleId);
		}
	}
}

?>
