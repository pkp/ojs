<?php

/**
 * SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.layoutEditor
 *
 * Handle requests for submission comments. 
 *
 * $Id$
 */

import('pages.layoutEditor.SubmissionLayoutHandler');

class SubmissionCommentsHandler extends LayoutEditorHandler {
	
	/**
	 * View layout comments.
	 */
	function viewLayoutComments($args) {
		LayoutEditorHandler::validate();
		LayoutEditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);
		LayoutEditorAction::viewLayoutComments($submission);
	
	}
	
	/**
	 * Post layout comment.
	 */
	function postLayoutComment() {
		LayoutEditorHandler::validate();
		LayoutEditorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);
		LayoutEditorAction::postLayoutComment($submission, $emailComment);
		
		LayoutEditorAction::viewLayoutComments($submission);
	
	}

	/**
	 * View proofread comments.
	 */
	function viewProofreadComments($args) {
		LayoutEditorHandler::validate();
		LayoutEditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);
		LayoutEditorAction::viewProofreadComments($submission);
	
	}
	
	/**
	 * Post proofread comment.
	 */
	function postProofreadComment() {
		LayoutEditorHandler::validate();
		LayoutEditorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);
		LayoutEditorAction::postProofreadComment($submission, $emailComment);
		
		LayoutEditorAction::viewProofreadComments($submission);
	
	}

	/**
	 * Edit comment.
	 */
	function editComment($args) {
		LayoutEditorHandler::validate();
		LayoutEditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		$commentId = $args[1];
		
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		LayoutEditorAction::editComment($submission, $comment);

	}
	
	/**
	 * Save comment.
	 */
	function saveComment() {
		LayoutEditorHandler::validate();
		LayoutEditorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$commentId = Request::getUserVar('commentId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		LayoutEditorAction::saveComment($submission, $comment, $emailComment);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			Request::redirect(null, null, 'viewLayoutComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			Request::redirect(null, null, 'viewProofreadComments', $articleId);
		}
	}
	
	/**
	 * Delete comment.
	 */
	function deleteComment($args) {
		LayoutEditorHandler::validate();
		LayoutEditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		$commentId = $args[1];
		
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		LayoutEditorAction::deleteComment($commentId);
		
		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			Request::redirect(null, null, 'viewLayoutComments', $articleId));
		} else if ($comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			Request::redirect(null, null, 'viewProofreadComments', $articleId));
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
