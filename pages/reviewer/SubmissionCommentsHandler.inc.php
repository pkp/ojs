<?php

/**
 * @file SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
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

		$submissionReviewHandler = new SubmissionReviewHandler();
		$submissionReviewHandler->validate($reviewId);
		$submission =& $submissionReviewHandler->submission;
		$user =& $submissionReviewHandler->user;

		$this->setupTemplate(true);
		ReviewerAction::viewPeerReviewComments($user, $submission, $reviewId);

	}

	/**
	 * Post peer review comments.
	 */
	function postPeerReviewComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$reviewId = (int) $request->getUserVar('reviewId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$submissionReviewHandler = new SubmissionReviewHandler();
		$submissionReviewHandler->validate($request, $reviewId);
		$submission =& $submissionReviewHandler->submission;
		$user =& $submissionReviewHandler->user;

		$this->setupTemplate(true);
		if (ReviewerAction::postPeerReviewComment($user, $submission, $reviewId, $emailComment, $request)) {
			ReviewerAction::viewPeerReviewComments($user, $submission, $reviewId);
		}
	}

	/**
	 * Edit comment.
	 */
	function editComment($args, $request) {
		$articleId = (int) array_shift($args);
		$commentId = (int) array_shift($args);
		if (!$commentId) $commentId = null;

		$reviewId = (int) $request->getUserVar('reviewId');

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate();
		$comment =& $this->comment;
		
		$this->setupTemplate(true);

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getArticle($articleId);

		$submissionReviewHandler = new SubmissionReviewHandler();
		$submissionReviewHandler->validate($request, $reviewId);
		$submission =& $submissionReviewHandler->submission;
		$user =& $submissionReviewHandler->user;
		
		ReviewerAction::editComment($article, $comment, $reviewId);
	}

	/**
	 * Save comment.
	 * @param $args array
	 * @param $request object
	 */
	function saveComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$commentId = (int) $request->getUserVar('commentId');
		$reviewId = (int) $request->getUserVar('reviewId');

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate();
		$comment =& $this->comment;
		
		$this->setupTemplate(true);
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;		

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getArticle($articleId);

		$submissionReviewHandler = new SubmissionReviewHandler();
		$submissionReviewHandler->validate($request, $reviewId);
		$submission =& $submissionReviewHandler->submission;
		$user =& $submissionReviewHandler->user;

		ReviewerAction::saveComment($article, $comment, $emailComment, $request);

		// Refresh the comment
		$articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
		$comment =& $articleCommentDao->getArticleCommentById($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			$request->redirect(null, null, 'viewPeerReviewComments', array($articleId, $comment->getAssocId()));
		}
	}

	/**
	 * Delete comment.
	 */
	function deleteComment($args, $request) {
		$articleId = (int) array_shift($args);
		$commentId = (int) array_shift($args);
		$reviewId = (int) $request->getUserVar('reviewId');

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate();
		$comment =& $this->comment;
		
		$this->setupTemplate($request, true);
		
		$submissionReviewHandler = new SubmissionReviewHandler();
		$submissionReviewHandler->validate($reviewId);
		$submission =& $submissionReviewHandler->submission;
		$user =& $submissionReviewHandler->user;

		ReviewerAction::deleteComment($commentId, $user);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			$request->redirect(null, null, 'viewPeerReviewComments', array($articleId, $comment->getAssocId()));
		}
	}
}

?>
