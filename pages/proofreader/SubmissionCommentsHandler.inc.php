<?php

/**
 * @file SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.proofreader
 * @class SubmissionCommentsHandler
 *
 * Handle requests for submission comments. 
 *
 * $Id$
 */

import('pages.proofreader.SubmissionProofreadHandler');

class SubmissionCommentsHandler extends ProofreaderHandler {

	/**
	 * View proofread comments.
	 */
	function viewProofreadComments($args) {
		ProofreaderHandler::validate();
		ProofreaderHandler::setupTemplate(true);

		$articleId = $args[0];

		list($journal, $submission) = SubmissionProofreadHandler::validate($articleId);
		ProofreaderAction::viewProofreadComments($submission);

	}

	/**
	 * Post proofread comment.
	 */
	function postProofreadComment() {
		ProofreaderHandler::validate();
		ProofreaderHandler::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		list($journal, $submission) = SubmissionProofreadHandler::validate($articleId);
		if (ProofreaderAction::postProofreadComment($submission, $emailComment)) {
			ProofreaderAction::viewProofreadComments($submission);
		}
	}

	/**
	 * View layout comments.
	 */
	function viewLayoutComments($args) {
		ProofreaderHandler::validate();
		ProofreaderHandler::setupTemplate(true);

		$articleId = $args[0];

		list($journal, $submission) = SubmissionProofreadHandler::validate($articleId);
		ProofreaderAction::viewLayoutComments($submission);

	}

	/**
	 * Post layout comment.
	 */
	function postLayoutComment() {
		ProofreaderHandler::validate();
		ProofreaderHandler::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		list($journal, $submission) = SubmissionProofreadHandler::validate($articleId);
		if (ProofreaderAction::postLayoutComment($submission, $emailComment)) {
			ProofreaderAction::viewLayoutComments($submission);
		}

	}

	/**
	 * Edit comment.
	 */
	function editComment($args) {
		ProofreaderHandler::validate();
		ProofreaderHandler::setupTemplate(true);

		$articleId = $args[0];
		$commentId = $args[1];

		list($journal, $submission) = SubmissionProofreadHandler::validate($articleId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		ProofreaderAction::editComment($submission, $comment);

	}

	/**
	 * Save comment.
	 */
	function saveComment() {
		ProofreaderHandler::validate();
		ProofreaderHandler::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		$commentId = Request::getUserVar('commentId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		list($journal, $submission) = SubmissionProofreadHandler::validate($articleId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
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
		ProofreaderHandler::validate();
		ProofreaderHandler::setupTemplate(true);

		$articleId = $args[0];
		$commentId = $args[1];

		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$comment = &$articleCommentDao->getArticleCommentById($commentId);

		list($journal, $submission) = SubmissionProofreadHandler::validate($articleId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		ProofreaderAction::deleteComment($commentId);

		// Determine which page to redirect back to.
		$commentPageMap = array(
			COMMENT_TYPE_PROOFREAD => 'viewProofreadComments',
			COMMENT_TYPE_LAYOUT => 'viewLayoutComments'
		);

		// Redirect back to initial comments page
		Request::redirect(null, null, $commentPageMap[$comment->getCommentType()], $articleId);
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

		return array(&$comment);
	}
}
?>
