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
	 */
	function viewPeerReviewComments($args) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = $args[0];
		$reviewId = $args[1];

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($articleId);
		$submission =& $submissionEditHandler->submission;

		SectionEditorAction::viewPeerReviewComments($submission, $reviewId);
	}

	/**
	 * Post peer review comments.
	 */
	function postPeerReviewComment($args, $request) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = $request->getUserVar('articleId');
		$reviewId = $request->getUserVar('reviewId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($articleId);
		$submission =& $submissionEditHandler->submission;
		if (SectionEditorAction::postPeerReviewComment($submission, $reviewId, $emailComment, $request)) {
			SectionEditorAction::viewPeerReviewComments($submission, $reviewId);
		}
	}

	/**
	 * View editor decision comments.
	 */
	function viewEditorDecisionComments($args, $request) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = (int) array_shift($args);

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($articleId);
		$submission =& $submissionEditHandler->submission;
		SectionEditorAction::viewEditorDecisionComments($submission);
	}

	/**
	 * Post peer review comments.
	 */
	function postEditorDecisionComment($args, $request) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = $request->getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($articleId);
		$submission =& $submissionEditHandler->submission;
		if (SectionEditorAction::postEditorDecisionComment($submission, $emailComment, $request)) {
			SectionEditorAction::viewEditorDecisionComments($submission);
		}
	}

	/**
	 * Blind CC the reviews to reviewers.
	 */
	function blindCcReviewsToReviewers($args, $request) {
		$articleId = $request->getUserVar('articleId');
		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($articleId);
		$submission =& $submissionEditHandler->submission;

		$send = $request->getUserVar('send')?true:false;
		$inhibitExistingEmail = $request->getUserVar('blindCcReviewers')?true:false;

		if (!$send) $this->setupTemplate(true, $articleId, 'editing');
		if (SectionEditorAction::blindCcReviewsToReviewers($submission, $send, $inhibitExistingEmail, $request)) {
			$request->redirect(null, null, 'submissionReview', $articleId);
		}
	}

	/**
	 * View copyedit comments.
	 */
	function viewCopyeditComments($args, $request) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = (int) array_shift($args);

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($articleId);
		$submission =& $submissionEditHandler->submission;
		SectionEditorAction::viewCopyeditComments($submission);
	}

	/**
	 * Post copyedit comment.
	 */
	function postCopyeditComment($args, $request) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = (int) $request->getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($articleId);
		$submission =& $submissionEditHandler->submission;
		if (SectionEditorAction::postCopyeditComment($submission, $emailComment, $request)) {
			SectionEditorAction::viewCopyeditComments($submission);
		}
	}

	/**
	 * View layout comments.
	 */
	function viewLayoutComments($args, $request) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = (int) array_shift($args);

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($articleId);
		$submission =& $submissionEditHandler->submission;
		SectionEditorAction::viewLayoutComments($submission, $request);
	}

	/**
	 * Post layout comment.
	 */
	function postLayoutComment($args, $request) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = $request->getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($articleId);
		$submission =& $submissionEditHandler->submission;
		if (SectionEditorAction::postLayoutComment($submission, $emailComment, $request)) {
			SectionEditorAction::viewLayoutComments($submission);
		}
	}

	/**
	 * View proofread comments.
	 */
	function viewProofreadComments($args, $request) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = (int) array_shift($args);

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($articleId);
		$submission =& $submissionEditHandler->submission;
		SectionEditorAction::viewProofreadComments($submission);
	}

	/**
	 * Post proofread comment.
	 */
	function postProofreadComment($args, $request) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = (int) $request->getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($articleId);
		$submission =& $submissionEditHandler->submission;
		if (SectionEditorAction::postProofreadComment($submission, $emailComment, $request)) {
			SectionEditorAction::viewProofreadComments($submission);
		}
	}

	/**
	 * Email an editor decision comment.
	 */
	function emailEditorDecisionComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($articleId);
		$submission =& $submissionEditHandler->submission;

		$this->setupTemplate(true);
		if (SectionEditorAction::emailEditorDecisionComment($submission, $request->getUserVar('send'), $request)) {
			if ($request->getUserVar('blindCcReviewers')) {
				SubmissionCommentsHandler::blindCcReviewsToReviewers($args, $request);
			} else {
				$request->redirect(null, null, 'submissionReview', array($articleId));
			}
		}
	}

	/**
	 * Edit comment.
	 */
	function editComment($args, $request) {
		$articleId = (int) array_shift($args);
		$commentId = (int) array_shift($args);

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate();
		$comment =& $this->comment;
		
		$this->setupTemplate(true);
		
		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($articleId);
		$submission =& $submissionEditHandler->submission;

		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			$request->redirect(null, $request->getRequestedPage());
		}

		SectionEditorAction::editComment($submission, $comment);
	}

	/**
	 * Save comment.
	 */
	function saveComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$commentId = (int) $request->getUserVar('commentId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate();
		$comment =& $this->comment;
		
		$this->setupTemplate(true);

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($articleId);
		$submission =& $submissionEditHandler->submission;

		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			$request->redirect(null, $request->getRequestedPage());
		}

		// Save the comment.
		SectionEditorAction::saveComment($submission, $comment, $emailComment, $request);

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
	 */
	function deleteComment($args, $request) {
		$articleId = (int) array_shift($args);
		$commentId = (int) array_shift($args);

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate();
		$comment =& $this->comment;
		
		$this->setupTemplate(true);
		
		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($articleId);
		$submission =& $submissionEditHandler->submission;

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
