<?php

/**
 * @file SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionCommentsHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for submission comments.
 */

// $Id$


import('pages.reviewer.SubmissionReviewHandler');

class SubmissionCommentsHandler extends ReviewerHandler {
	/** comment associated with the request **/
	var $comment;

	/**
	 * Constructor
	 **/
	function SubmissionCommentsHandler() {
		parent::ReviewerHandler();
	}

	/**
	 * View peer review comments.
	 */
	function viewPeerReviewComments($args) {
		$articleId = $args[0];
		$reviewId = $args[1];

		$submissionReviewHandler =& new SubmissionReviewHandler();
		$submissionReviewHandler->validate($reviewId);
		$submission =& $submissionReviewHandler->submission;
		$user =& $submissionReviewHandler->user;

		$this->setupTemplate(true);
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

		$submissionReviewHandler =& new SubmissionReviewHandler();
		$submissionReviewHandler->validate($reviewId);
		$submission =& $submissionReviewHandler->submission;
		$user =& $submissionReviewHandler->user;

		$this->setupTemplate(true);
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

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getArticle($articleId);

		$submissionReviewHandler =& new SubmissionReviewHandler();
		$submissionReviewHandler->validate($reviewId);
		$submission =& $submissionReviewHandler->submission;
		$user =& $submissionReviewHandler->user;
		$this->validate($commentId);
		$comment =& $this->comment;

		$this->setupTemplate(true);

		ReviewerAction::editComment($article, $comment, $reviewId);
	}

	/**
	 * Save comment.
	 */
	function saveComment() {
		$articleId = Request::getUserVar('articleId');
		$commentId = Request::getUserVar('commentId');
		$reviewId = Request::getUserVar('reviewId');

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getArticle($articleId);

		$submissionReviewHandler =& new SubmissionReviewHandler();
		$submissionReviewHandler->validate($reviewId);
		$submission =& $submissionReviewHandler->submission;
		$user =& $submissionReviewHandler->user;
		$this->validate($commentId);
		$comment =& $this->comment;

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		$this->setupTemplate(true);

		ReviewerAction::saveComment($article, $comment, $emailComment);

		// Refresh the comment
		$articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
		$comment =& $articleCommentDao->getArticleCommentById($commentId);

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

		$submissionReviewHandler =& new SubmissionReviewHandler();
		$submissionReviewHandler->validate($reviewId);
		$submission =& $submissionReviewHandler->submission;
		$user =& $submissionReviewHandler->user;
		$this->validate($commentId);
		$comment =& $this->comment;

		$this->setupTemplate(true);

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

		$articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
		$comment =& $articleCommentDao->getArticleCommentById($commentId);

		if ($comment == null) {
			$isValid = false;

		} else if ($comment->getAuthorId() != $user->getId()) {
			$isValid = false;
		}

		if (!$isValid) {
			Request::redirect(null, Request::getRequestedPage());
		}

		$this->comment =& $comment;
		return true;
	}
}

?>
