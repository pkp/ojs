<?php

/**
 * @file pages/copyeditor/SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionCommentsHandler
 * @ingroup pages_copyeditor
 *
 * @brief Handle requests for submission comments. 
 */

import('pages.copyeditor.SubmissionCopyeditHandler');

class SubmissionCommentsHandler extends CopyeditorHandler {
	/** comment associated with this request **/
	var $comment;

	/**
	 * Constructor
	 */
	function SubmissionCommentsHandler() {
		parent::CopyeditorHandler();
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
		CopyeditorAction::viewLayoutComments($this->submission);
	}

	/**
	 * Post layout comment.
	 * @param $args array
	 * @param $request object
	 */
	function postLayoutComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$this->setupTemplate(true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		if (CopyeditorAction::postLayoutComment($this->submission, $emailComment, $request)) {
			CopyeditorAction::viewLayoutComments($this->submission);
		}
	}

	/**
	 * View copyedit comments.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewCopyeditComments($args, &$request) {
		$articleId = (int) array_shift($args);
		$this->validate($request, $articleId);
		$this->setupTemplate(true);
		CopyeditorAction::viewCopyeditComments($this->submission);
	}

	/**
	 * Post copyedit comment.
	 * @param $args array
	 * @param $request object
	 */
	function postCopyeditComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$this->setupTemplate(true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;
		if (CopyeditorAction::postCopyeditComment($this->submission, $emailComment, $request)) {
			CopyeditorAction::viewCopyeditComments($this->submission);
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
		CopyeditorAction::editComment($this->submission, $this->comment);
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
		$comment =& $this->comment;

		$this->setupTemplate(true);
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		CopyeditorAction::saveComment($this->submission, $comment, $emailComment, $request);

		// refresh the comment
		$articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
		$comment =& $articleCommentDao->getArticleCommentById($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
			$request->redirect(null, null, 'viewCopyeditComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			$request->redirect(null, null, 'viewLayoutComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			$request->redirect(null, null, 'viewProofreadComments', $articleId);
		}
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
		$comment =& $this->comment;

		$this->setupTemplate(true);

		CopyeditorAction::deleteComment($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
			$request->redirect(null, null, 'viewCopyeditComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			$request->redirect(null, null, 'viewLayoutComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			$request->redirect(null, null, 'viewProofreadComments', $articleId);
		}
	}
}

?>
