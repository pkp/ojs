<?php

/**
 * @file pages/author/SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionCommentsHandler
 * @ingroup pages_author
 *
 * @brief Handle requests for submission comments.
 */

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
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewEditorDecisionComments($args, $request) {
		$articleId = (int) array_shift($args);
		$this->validate($request, $articleId);
		$this->setupTemplate($request, true);
		AuthorAction::viewEditorDecisionComments($this->submission);
	}

	/**
	 * View copyedit comments.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewCopyeditComments($args, $request) {
		$articleId = (int) array_shift($args);
		$this->validate($request, $articleId);
		$this->setupTemplate($request, true);
		AuthorAction::viewCopyeditComments($this->submission);
	}

	/**
	 * Post copyedit comment.
	 * @param $args array
	 * @param $request object
	 */
	function postCopyeditComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$this->setupTemplate($request, true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		if (AuthorAction::postCopyeditComment($this->submission, $emailComment, $request)) {
			AuthorAction::viewCopyeditComments($this->submission);
		}
	}

	/**
	 * View proofread comments.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewProofreadComments($args, $request) {
		$articleId = (int) array_shift($args);
		$this->validate($request, $articleId);
		$this->setupTemplate($request, true);
		AuthorAction::viewProofreadComments($this->submission);
	}

	/**
	 * Post proofread comment.
	 * @param $args array
	 * @param $request object
	 */
	function postProofreadComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$this->setupTemplate($request, true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		if (AuthorAction::postProofreadComment($this->submission, $emailComment, $request)) {
			AuthorAction::viewProofreadComments($this->submission);
		}
	}

	/**
	 * View layout comments.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewLayoutComments($args, $request) {
		$articleId = (int) array_shift($args);
		$this->validate($request, $articleId);
		$this->setupTemplate($request, true);
		AuthorAction::viewLayoutComments($this->submission);

	}

	/**
	 * Post layout comment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function postLayoutComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$this->setupTemplate($request, true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		if (AuthorAction::postLayoutComment($this->submission, $emailComment, $request)) {
			AuthorAction::viewLayoutComments($this->submission);
		}
	}

	/**
	 * Email an editor decision comment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function emailEditorDecisionComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->setupTemplate($request, true);
		$this->validate($request, $articleId);
		if (AuthorAction::emailEditorDecisionComment($this->submission, $request->getUserVar('send'), $request)) {
			$request->redirect(null, null, 'submissionReview', array($articleId));
		}
	}

	/**
	 * Edit comment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editComment($args, $request) {
		$articleId = (int) array_shift($args);
		$commentId = array_shift($args);

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate($request, $articleId);
		$this->setupTemplate($request, true);
		if ($this->comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			$request->redirect(null, $request->getRequestedPage());
		}
		AuthorAction::editComment($this->submission, $this->comment);
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
		$this->setupTemplate($request, true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		if ($this->comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			$request->redirect(null, $request->getRequestedPage());
		}

		AuthorAction::saveComment($this->submission, $this->comment, $emailComment, $request);

		// refresh the comment
		$articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
		$comment =& $articleCommentDao->getArticleCommentById($commentId);

		// Redirect back to initial comments page
		if ($this->comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			$request->redirect(null, null, 'viewEditorDecisionComments', $articleId);
		} else if ($this->comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
			$request->redirect(null, null, 'viewCopyeditComments', $articleId);
		} else if ($this->comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			$request->redirect(null, null, 'viewLayoutComments', $articleId);
		} else if ($this->comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			$request->redirect(null, null, 'viewProofreadComments', $articleId);
		}
	}

	/**
	 * Delete comment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function deleteComment($args, $request) {
		$articleId = (int) array_shift($args);
		$commentId = (int) array_shift($args);

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate($request, $articleId);
		$this->setupTemplate($request, true);

		AuthorAction::deleteComment($commentId);

		// Redirect back to initial comments page
		if ($this->comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			$request->redirect(null, null, 'viewEditorDecisionComments', $articleId);
		} else if ($this->comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
			$request->redirect(null, null, 'viewCopyeditComments', $articleId);
		} else if ($this->comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			$request->redirect(null, null, 'viewLayoutComments', $articleId);
		} else if ($this->comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			$request->redirect(null, null, 'viewProofreadComments', $articleId);
		}
	}
}

?>
