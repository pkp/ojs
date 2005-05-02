<?php

/**
 * SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.author
 *
 * Handle requests for submission comments. 
 *
 * $Id$
 */

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
	 * Post editor decision comments.
	 */
	function postEditorDecisionComment() {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;		
		
		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);
		AuthorAction::postEditorDecisionComment($authorSubmission, $emailComment);
		
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
		AuthorAction::postCopyeditComment($authorSubmission, $emailComment);
		
		AuthorAction::viewCopyeditComments($authorSubmission);
	
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
		AuthorAction::postProofreadComment($authorSubmission, $emailComment);
		
		AuthorAction::viewProofreadComments($authorSubmission);
	
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
		AuthorAction::postLayoutComment($authorSubmission, $emailComment);
		
		AuthorAction::viewLayoutComments($authorSubmission);
	
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
		AuthorAction::saveComment($authorSubmission, $comment, $emailComment);

		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$comment = &$articleCommentDao->getArticleCommentById($commentId);
		
		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			Request::redirect(sprintf('%s/viewEditorDecisionComments/%d', Request::getRequestedPage(), $articleId));
		} else if ($comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
			Request::redirect(sprintf('%s/viewCopyeditComments/%d', Request::getRequestedPage(), $articleId));
		} else if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			Request::redirect(sprintf('%s/viewLayoutComments/%d', Request::getRequestedPage(), $articleId));
		} else if ($comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			Request::redirect(sprintf('%s/viewProofreadComments/%d', Request::getRequestedPage(), $articleId));
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
			Request::redirect(sprintf('%s/viewEditorDecisionComments/%d', Request::getRequestedPage(), $articleId));
		} else if ($comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
			Request::redirect(sprintf('%s/viewCopyeditComments/%d', Request::getRequestedPage(), $articleId));
		} else if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			Request::redirect(sprintf('%s/viewLayoutComments/%d', Request::getRequestedPage(), $articleId));
		} else if ($comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			Request::redirect(sprintf('%s/viewProofreadComments/%d', Request::getRequestedPage(), $articleId));
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
