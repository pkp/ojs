<?php

/**
 * @file pages/sectionEditor/SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionCommentsHandler
 * @ingroup pages_sectionEditor
 *
 * @brief Handle requests for submission comments.
 */

import('pages.sectionEditor.SubmissionEditHandler');

class SubmissionCommentsHandler extends SectionEditorHandler {
	/** comment associated with the request **/
	var $comment;

	/**
	 * Constructor
	 **/
	function SubmissionCommentsHandler() {
		parent::SectionEditorHandler();
	}

	/**
	 * View peer review comments.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewPeerReviewComments($args, &$request) {
		$articleId = (int) array_shift($args);
		$reviewId = (int) array_shift($args);

		$this->validate($articleId);
		$this->setupTemplate(true);

		SectionEditorAction::viewPeerReviewComments($this->submission, $reviewId);
	}

	/**
	 * Post peer review comments.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function postPeerReviewComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$reviewId = (int) $request->getUserVar('reviewId');

		$this->validate($articleId);
		$this->setupTemplate(true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		if (SectionEditorAction::postPeerReviewComment($this->submission, $reviewId, $emailComment, $request)) {
			SectionEditorAction::viewPeerReviewComments($this->submission, $reviewId);
		}
	}

	/**
	 * View editor decision comments.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewEditorDecisionComments($args, $request) {
		$articleId = (int) array_shift($args);

		$this->validate($articleId);
		$this->setupTemplate(true);

		SectionEditorAction::viewEditorDecisionComments($this->submission);
	}

	/**
	 * Post peer review comments.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function postEditorDecisionComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId);

		$this->setupTemplate(true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		if (SectionEditorAction::postEditorDecisionComment($this->submission, $emailComment, $request)) {
			SectionEditorAction::viewEditorDecisionComments($this->submission);
		}
	}

	/**
	 * View copyedit comments.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewCopyeditComments($args, $request) {
		$articleId = (int) array_shift($args);

		$this->validate($articleId);
		$this->setupTemplate(true);

		SectionEditorAction::viewCopyeditComments($this->submission);
	}

	/**
	 * Post copyedit comment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function postCopyeditComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');

		$this->validate($articleId);
		$this->setupTemplate(true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		if (SectionEditorAction::postCopyeditComment($this->submission, $emailComment, $request)) {
			SectionEditorAction::viewCopyeditComments($this->submission);
		}
	}

	/**
	 * View layout comments.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewLayoutComments($args, $request) {
		$articleId = (int) array_shift($args);

		$this->validate($articleId);
		$this->setupTemplate(true);

		SectionEditorAction::viewLayoutComments($this->submission, $request);
	}

	/**
	 * Post layout comment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function postLayoutComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');

		$this->validate($articleId);
		$this->setupTemplate(true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		if (SectionEditorAction::postLayoutComment($this->submission, $emailComment, $request)) {
			SectionEditorAction::viewLayoutComments($this->submission);
		}
	}

	/**
	 * View proofread comments.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewProofreadComments($args, $request) {
		$articleId = (int) array_shift($args);

		$this->validate($articleId);
		$this->setupTemplate(true);

		SectionEditorAction::viewProofreadComments($this->submission);
	}

	/**
	 * Post proofread comment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function postProofreadComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');

		$this->validate($articleId);
		$this->setupTemplate(true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		if (SectionEditorAction::postProofreadComment($this->submission, $emailComment, $request)) {
			SectionEditorAction::viewProofreadComments($this->submission);
		}
	}

	/**
	 * Email an editor decision comment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function emailEditorDecisionComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId);

		$this->setupTemplate(true);
		if (SectionEditorAction::emailEditorDecisionComment($this->submission, $request->getUserVar('send'), $request)) {
			if ($request->getUserVar('blindCcReviewers')) {
				$request->redirect(null, null, 'bccEditorDecisionCommentToReviewers', null, array('articleId' => $articleId));
			} else {
				$request->redirect(null, null, 'submissionReview', array($articleId));
			}
		}
	}

	/**
	 * Blind CC the editor decision email to reviewers.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function bccEditorDecisionCommentToReviewers($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId);

		$this->setupTemplate(true);
		if (SectionEditorAction::bccEditorDecisionCommentToReviewers($this->submission, $request->getUserVar('send'), $request)) {
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
		$commentId = (int) array_shift($args);

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate($articleId);
		$comment =& $this->comment;

		$this->setupTemplate(true);

		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			$request->redirect(null, $request->getRequestedPage());
		}

		SectionEditorAction::editComment($this->submission, $comment);
	}

	/**
	 * Save comment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function saveComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$commentId = (int) $request->getUserVar('commentId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate($articleId);
		$comment =& $this->comment;

		$this->setupTemplate(true);

		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			$request->redirect(null, $request->getRequestedPage());
		}

		// Save the comment.
		SectionEditorAction::saveComment($this->submission, $comment, $emailComment, $request);

		$articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
		$comment =& $articleCommentDao->getArticleCommentById($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			$request->redirect(null, null, 'viewPeerReviewComments', array($articleId, $comment->getAssocId()));
		} else if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			$request->redirect(null, null, 'viewEditorDecisionComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
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
	function deleteComment($args, $request) {
		$articleId = (int) array_shift($args);
		$commentId = (int) array_shift($args);

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate($articleId);
		$comment =& $this->comment;

		$this->setupTemplate(true);

		SectionEditorAction::deleteComment($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			$request->redirect(null, null, 'viewPeerReviewComments', array($articleId, $comment->getAssocId()));
		} else if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			$request->redirect(null, null, 'viewEditorDecisionComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
			$request->redirect(null, null, 'viewCopyeditComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			$request->redirect(null, null, 'viewLayoutComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			$request->redirect(null, null, 'viewProofreadComments', $articleId);
		}
	}
}

?>
