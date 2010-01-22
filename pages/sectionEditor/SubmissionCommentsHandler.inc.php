<?php

/**
 * @file SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionCommentsHandler
 * @ingroup pages_sectionEditor
 *
 * @brief Handle requests for submission comments. 
 */

// $Id$


import('pages.sectionEditor.SubmissionEditHandler');

class SubmissionCommentsHandler extends SectionEditorHandler {

	/**
	 * View peer review comments.
	 */
	function viewPeerReviewComments($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);

		$articleId = $args[0];
		$reviewId = $args[1];

		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		SectionEditorAction::viewPeerReviewComments($submission, $reviewId);

	}

	/**
	 * Post peer review comments.
	 */
	function postPeerReviewComment() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		$reviewId = Request::getUserVar('reviewId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		if (SectionEditorAction::postPeerReviewComment($submission, $reviewId, $emailComment)) {
			SectionEditorAction::viewPeerReviewComments($submission, $reviewId);
		}

	}

	/**
	 * View editor decision comments.
	 */
	function viewEditorDecisionComments($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);

		$articleId = $args[0];

		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		SectionEditorAction::viewEditorDecisionComments($submission);

	}

	/**
	 * Post peer review comments.
	 */
	function postEditorDecisionComment() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		if (SectionEditorAction::postEditorDecisionComment($submission, $emailComment)) {
			SectionEditorAction::viewEditorDecisionComments($submission);
		}

	}

	/**
	 * Blind CC the reviews to reviewers.
	 */
	function blindCcReviewsToReviewers($args = array()) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);

		$send = Request::getUserVar('send')?true:false;
		$inhibitExistingEmail = Request::getUserVar('blindCcReviewers')?true:false;

		if (!$send) parent::setupTemplate(true, $articleId, 'editing');
		if (SectionEditorAction::blindCcReviewsToReviewers($submission, $send, $inhibitExistingEmail)) {
			Request::redirect(null, null, 'submissionReview', $articleId);
		}
	}

	/**
	 * View copyedit comments.
	 */
	function viewCopyeditComments($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);

		$articleId = $args[0];

		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		SectionEditorAction::viewCopyeditComments($submission);

	}

	/**
	 * Post copyedit comment.
	 */
	function postCopyeditComment() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		if (SectionEditorAction::postCopyeditComment($submission, $emailComment)) {
			SectionEditorAction::viewCopyeditComments($submission);
		}

	}

	/**
	 * View layout comments.
	 */
	function viewLayoutComments($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);

		$articleId = $args[0];

		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		SectionEditorAction::viewLayoutComments($submission);

	}

	/**
	 * Post layout comment.
	 */
	function postLayoutComment() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		if (SectionEditorAction::postLayoutComment($submission, $emailComment)) {
			SectionEditorAction::viewLayoutComments($submission);
		}

	}

	/**
	 * View proofread comments.
	 */
	function viewProofreadComments($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);

		$articleId = $args[0];

		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		SectionEditorAction::viewProofreadComments($submission);

	}

	/**
	 * Post proofread comment.
	 */
	function postProofreadComment() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		if (SectionEditorAction::postProofreadComment($submission, $emailComment)) {
			SectionEditorAction::viewProofreadComments($submission);
		}

	}

	/**
	 * Email an editor decision comment.
	 */
	function emailEditorDecisionComment() {
		$articleId = (int) Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);

		parent::setupTemplate(true);		
		if (SectionEditorAction::emailEditorDecisionComment($submission, Request::getUserVar('send'))) {
			if (Request::getUserVar('blindCcReviewers')) {
				SubmissionCommentsHandler::blindCcReviewsToReviewers();
			} else {
				Request::redirect(null, null, 'submissionReview', array($articleId));
			}
		}
	}

	/**
	 * Edit comment.
	 */
	function editComment($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);

		$articleId = $args[0];
		$commentId = $args[1];

		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);

		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			Request::redirect(null, Request::getRequestedPage());
		}

		SectionEditorAction::editComment($submission, $comment);

	}

	/**
	 * Save comment.
	 */
	function saveComment() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		$commentId = Request::getUserVar('commentId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);

		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			Request::redirect(null, Request::getRequestedPage());
		}

		// Save the comment.
		SectionEditorAction::saveComment($submission, $comment, $emailComment);

		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$comment = &$articleCommentDao->getArticleCommentById($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			Request::redirect(null, null, 'viewPeerReviewComments', array($articleId, $comment->getAssocId()));
		} else if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			Request::redirect(null, null, 'viewEditorDecisionComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
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
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);

		$articleId = $args[0];
		$commentId = $args[1];

		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		SectionEditorAction::deleteComment($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			Request::redirect(null, null, 'viewPeerReviewComments', array($articleId, $comment->getAssocId()));
		} else if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			Request::redirect(null, null, 'viewEditorDecisionComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
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

		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$user = &Request::getUser();

		$comment = &$articleCommentDao->getArticleCommentById($commentId);

		if (
			$comment == null ||
			$comment->getAuthorId() != $user->getUserId()
		) {
			Request::redirect(null, Request::getRequestedPage());
		}

		return array($comment);
	}
}
?>
