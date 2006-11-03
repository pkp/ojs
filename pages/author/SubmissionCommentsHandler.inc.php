<?php

/**
 * SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.author
 *
 * Handle requests for submission comments. 
 *
 * $Id$
 */

import('pages.author.TrackSubmissionHandler');

class SubmissionCommentsHandler extends AuthorHandler {
	
	/**
	 * View editor decision comments.
	 */
	function viewEditorDecisionComments($args) {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		
		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);
		AuthorAction::viewEditorDecisionComments($authorSubmission);
	}
	
	/**
	 * View copyedit comments.
	 */
	function viewCopyeditComments($args) {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		
		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);
		AuthorAction::viewCopyeditComments($authorSubmission);
	
	}
	
	/**
	 * Post copyedit comment.
	 */
	function postCopyeditComment() {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);
		if (AuthorAction::postCopyeditComment($authorSubmission, $emailComment)) {
			AuthorAction::viewCopyeditComments($authorSubmission);
		}
	
	}
	
	/**
	 * View proofread comments.
	 */
	function viewProofreadComments($args) {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		
		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);
		AuthorAction::viewProofreadComments($authorSubmission);
	
	}
	
	/**
	 * Post proofread comment.
	 */
	function postProofreadComment() {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);
		if (AuthorAction::postProofreadComment($authorSubmission, $emailComment)) {
			AuthorAction::viewProofreadComments($authorSubmission);
		}
	
	}

	/**
	 * View layout comments.
	 */
	function viewLayoutComments($args) {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);

		$articleId = $args[0];

		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);
		AuthorAction::viewLayoutComments($authorSubmission);

	}

	/**
	 * Post layout comment.
	 */
	function postLayoutComment() {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);
		if (AuthorAction::postLayoutComment($authorSubmission, $emailComment)) {
			AuthorAction::viewLayoutComments($authorSubmission);
		}
	
	}

	/**
	 * Email an editor decision comment.
	 */
	function emailEditorDecisionComment() {
		$articleId = (int) Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);

		parent::setupTemplate(true);		
		if (AuthorAction::emailEditorDecisionComment($submission, Request::getUserVar('send'))) {
			Request::redirect(null, null, 'submissionReview', array($articleId));
		}
	}
	
	/**
	 * Edit comment.
	 */
	function editComment($args) {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		$commentId = $args[1];
		
		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);

		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			Request::redirect(null, Request::getRequestedPage());
		}

		AuthorAction::editComment($authorSubmission, $comment);

	}
	
	/**
	 * Save comment.
	 */
	function saveComment() {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$commentId = Request::getUserVar('commentId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);

		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			Request::redirect(null, Request::getRequestedPage());
		}

		AuthorAction::saveComment($authorSubmission, $comment, $emailComment);

		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$comment = &$articleCommentDao->getArticleCommentById($commentId);
		
		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
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
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		$commentId = $args[1];
		
		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$comment = &$articleCommentDao->getArticleCommentById($commentId);
		
		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		AuthorAction::deleteComment($commentId);
		
		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
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
