<?php

/**
 * @file pages/reviewer/SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionCommentsHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for submission comments.
 */

import('pages.reviewer.SubmissionReviewHandler');

class SubmissionCommentsHandler extends ReviewerHandler {
	/** comment associated with the request **/
	var $comment;

	/**
	 * Constructor
	 */
	function SubmissionCommentsHandler() {
		parent::ReviewerHandler();
	}

	/**
	 * View peer review comments.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewPeerReviewComments($args, $request) {
		$articleId = (int) array_shift($args);
		$reviewId = (int) array_shift($args);

		$this->validate($request, $reviewId);
		$this->setupTemplate(true);
		ReviewerAction::viewPeerReviewComments($this->user, $this->submission, $reviewId);
	}

	/**
	 * Post peer review comments.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function postPeerReviewComment($args, &$request) {
		$articleId = (int) $request->getUserVar('articleId');
		$reviewId = (int) $request->getUserVar('reviewId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$this->validate($request, $reviewId);
		$this->setupTemplate(true);

		if (ReviewerAction::postPeerReviewComment($this->user, $this->submission, $reviewId, $emailComment, $request)) {
			ReviewerAction::viewPeerReviewComments($this->user, $this->submission, $reviewId);
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
		if (!$commentId) $commentId = null;

		$reviewId = (int) $request->getUserVar('reviewId');

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate($request, $reviewId);
		$this->setupTemplate(true);

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getArticle($articleId);

		ReviewerAction::editComment($article, $this->comment, $reviewId);
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
		$this->validate($request, $reviewId);
		$this->setupTemplate(true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getArticle($articleId);

		ReviewerAction::saveComment($article, $this->comment, $emailComment, $request);

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
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function deleteComment($args, $request) {
		$articleId = (int) array_shift($args);
		$commentId = (int) array_shift($args);
		$reviewId = (int) $request->getUserVar('reviewId');

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate($request, $reviewId);
		$comment =& $this->comment;

		$this->setupTemplate($request, true);

		ReviewerAction::deleteComment($commentId, $this->user);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			$request->redirect(null, null, 'viewPeerReviewComments', array($articleId, $this->comment->getAssocId()));
		}
	}
}

?>
