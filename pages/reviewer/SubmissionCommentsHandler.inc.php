<?php

/**
 * SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.reviewer
 *
 * Handle requests for submission comments. 
 *
 * $Id$
 */

class SubmissionCommentsHandler extends ReviewerHandler {
	
	/**
	 * View peer review comments.
	 */
	function viewPeerReviewComments($args) {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate(true);
		
		$articleId = $args[0];
		$reviewId = $args[1];

		list($journal, $submission) = SubmissionReviewHandler::validate($reviewId);
		ReviewerAction::viewPeerReviewComments($submission, $reviewId);
	
	}
	
	/**
	 * Post peer review comments.
	 */
	function postPeerReviewComment() {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$reviewId = Request::getUserVar('reviewId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($journal, $submission) = SubmissionReviewHandler::validate($reviewId);
		ReviewerAction::postPeerReviewComment($submission, $reviewId, $emailComment);
		
		ReviewerAction::viewPeerReviewComments($submission, $reviewId);
	}
	
	/**
	 * Edit comment.
	 */
	function editComment($args) {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate(true);
		
		$articleId = $args[0];
		$commentId = $args[1];

		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getArticle($articleId);

		// FIXME
		// list($journal, $submission) = SubmissionReviewHandler::validate($reviewId);

		list($comment) = SubmissionCommentsHandler::validate($commentId);
		ReviewerAction::editComment($article, $comment);

	}
	
	/**
	 * Save comment.
	 */
	function saveComment() {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$commentId = Request::getUserVar('commentId');
		
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getArticle($articleId);

		// FIXME
		// list($journal, $submission) = SubmissionReviewHandler::validate($reviewId);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		ReviewerAction::saveComment($article, $comment, $emailComment);

		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$comment = &$articleCommentDao->getArticleCommentById($commentId);
		
		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			Request::redirect(sprintf('%s/viewPeerReviewComments/%d/%d', Request::getRequestedPage(), $articleId, $comment->getAssocId()));
		}
	}
	
	/**
	 * Delete comment.
	 */
	function deleteComment($args) {
		ReviewerHandler::validate();
		ReviewerHandler::setupTemplate(true);
		
		$articleId = $args[0];
		$commentId = $args[1];
		
		// FIXME
		// list($journal, $submission) = SubmissionReviewHandler::validate($reviewId);

		list($comment) = SubmissionCommentsHandler::validate($commentId);
		ReviewerAction::deleteComment($commentId);
		
		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			Request::redirect(sprintf('%s/viewPeerReviewComments/%d/%d', Request::getRequestedPage(), $articleId, $comment->getAssocId()));
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
			Request::redirect(Request::getRequestedPage());
		}

		return array($comment);
	}
}
?>
