<?php

/**
 * @file SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
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

	/**
	 * View layout comments.
	 */
	function viewLayoutComments($args) {
		CopyeditorHandler::validate();
		CopyeditorHandler::setupTemplate(true);

		$articleId = $args[0];

		list($journal, $submission) = SubmissionCopyeditHandler::validate($articleId);
		CopyeditorAction::viewLayoutComments($submission);

	}

	/**
	 * Post layout comment.
	 */
	function postLayoutComment() {
		CopyeditorHandler::validate();
		CopyeditorHandler::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		list($journal, $submission) = SubmissionCopyeditHandler::validate($articleId);
		if (CopyeditorAction::postLayoutComment($submission, $emailComment)) {
			CopyeditorAction::viewLayoutComments($submission);
		}

	}

	/**
	 * View copyedit comments.
	 */
	function viewCopyeditComments($args) {
		CopyeditorHandler::validate();
		CopyeditorHandler::setupTemplate(true);

		$articleId = $args[0];

		list($journal, $submission) = SubmissionCopyeditHandler::validate($articleId);
		CopyeditorAction::viewCopyeditComments($submission);

	}

	/**
	 * Post copyedit comment.
	 */
	function postCopyeditComment() {
		CopyeditorHandler::validate();
		CopyeditorHandler::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		list($journal, $submission) = SubmissionCopyeditHandler::validate($articleId);
		if (CopyeditorAction::postCopyeditComment($submission, $emailComment)) {
			CopyeditorAction::viewCopyeditComments($submission);
		}

	}

	/**
	 * Edit comment.
	 */
	function editComment($args) {
		CopyeditorHandler::validate();
		CopyeditorHandler::setupTemplate(true);

		$articleId = $args[0];
		$commentId = $args[1];

		list($journal, $submission) = SubmissionCopyeditHandler::validate($articleId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		CopyeditorAction::editComment($submission, $comment);

	}

	/**
	 * Save comment.
	 */
	function saveComment() {
		CopyeditorHandler::validate();
		CopyeditorHandler::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		$commentId = Request::getUserVar('commentId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		list($journal, $submission) = SubmissionCopyeditHandler::validate($articleId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		CopyeditorAction::saveComment($submission, $comment, $emailComment);

		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$comment = &$articleCommentDao->getArticleCommentById($commentId);

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
		CopyeditorHandler::validate();
		CopyeditorHandler::setupTemplate(true);

		$articleId = $args[0];
		$commentId = $args[1];

		list($journal, $submission) = SubmissionCopyeditHandler::validate($articleId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
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

	//
	// Validation
	//

	/**
	 * Validate that the user is the author of the comment.
	 */
	function validate($commentId) {
		parent::validate();

		$isValid = true;

		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$user = &Request::getUser();

		$comment = &$articleCommentDao->getArticleCommentById($commentId);

		if ($comment == null) {
			$isValid = false;

		} else if ($comment->getAuthorId() != $user->getUserId()) {
			$isValid = false;
		}

		if (!$isValid) {
			Request::redirect(null, Request::getRequestedPage());
		}

		return array($comment);
	}
}
?>
