<?php

/**
 * @file SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.reviewer
 * @class SubmissionCommentsHandler
 *
 * Handle requests for submission comments. 
 *
 * $Id$
 */

import('pages.reviewer.SubmissionReviewHandler');

class SubmissionCommentsHandler extends ReviewerHandler {

	/**
	 * View peer review comments.
	 */
	function viewPeerReviewComments($args) {
		$articleId = $args[0];
		$reviewId = $args[1];

		list($journal, $submission, $user) = SubmissionReviewHandler::validate($reviewId);
		ReviewerHandler::setupTemplate(true);
		ReviewerAction::viewPeerReviewComments($user, $submission, $reviewId);

	}

	/**
	 * Post peer review comments.
	 */
	function postPeerReviewComment() {
		$articleId = Request::getUserVar('articleId');
		$reviewId = Request::getUserVar('reviewId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		list($journal, $submission, $user) = SubmissionReviewHandler::validate($reviewId);

		ReviewerHandler::setupTemplate(true);
		if (ReviewerAction::postPeerReviewComment($user, $submission, $reviewId, $emailComment)) {
			ReviewerAction::viewPeerReviewComments($user, $submission, $reviewId);
		}
	}

	/**
	 * Edit comment.
	 */
	function editComment($args) {
		$articleId = $args[0];
		$commentId = $args[1];
		$reviewId = Request::getUserVar('reviewId');

		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getArticle($articleId);

		list($journal, $submission, $user) = SubmissionReviewHandler::validate($reviewId);
		list($comment) = SubmissionCommentsHandler::validate($user, $commentId);

		ReviewerHandler::setupTemplate(true);

		ReviewerAction::editComment($article, $comment, $reviewId);
	}

	/**
	 * Save comment.
	 */
	function saveComment() {
		$articleId = Request::getUserVar('articleId');
		$commentId = Request::getUserVar('commentId');
		$reviewId = Request::getUserVar('reviewId');

		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getArticle($articleId);

		list($journal, $submission, $user) = SubmissionReviewHandler::validate($reviewId);
		list($comment) = SubmissionCommentsHandler::validate($user, $commentId);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		ReviewerHandler::setupTemplate(true);

		ReviewerAction::saveComment($article, $comment, $emailComment);

		// Refresh the comment
		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$comment = &$articleCommentDao->getArticleCommentById($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			Request::redirect(null, null, 'viewPeerReviewComments', array($articleId, $comment->getAssocId()));
		}
	}

	/**
	 * Delete comment.
	 */
	function deleteComment($args) {
		$articleId = $args[0];
		$commentId = $args[1];
		$reviewId = Request::getUserVar('reviewId');

		list($journal, $submission, $user) = SubmissionReviewHandler::validate($reviewId);
		list($comment) = SubmissionCommentsHandler::validate($user, $commentId);

		ReviewerHandler::setupTemplate(true);

		ReviewerAction::deleteComment($commentId, $user);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			Request::redirect(null, null, 'viewPeerReviewComments', array($articleId, $comment->getAssocId()));
		}
	}

	//
	// Validation
	//

	/**
	 * Validate that the user is the author of the comment.
	 */
	function validate($user, $commentId) {
		$isValid = true;

		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
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
