<?php

/**
 * SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.proofreader
 *
 * Handle requests for submission comments. 
 *
 * $Id$
 */

class SubmissionCommentsHandler extends ProofreaderHandler {
	
	/**
	 * View proofread comments.
	 */
	function viewProofreadComments($args) {
		ProofreaderHandler::validate();
		ProofreaderHandler::setupTemplate(true);
		
		$articleId = $args[0];
		
		SubmissionProofreaderHandler::validate($articleId);
		ProofreaderAction::viewProofreadComments($articleId);
	
	}
	
	/**
	 * Post proofread comment.
	 */
	function postProofreadComment() {
		ProofreaderHandler::validate();
		ProofreaderHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		SubmissionProofreaderHandler::validate($articleId);
		ProofreaderAction::postProofreadComment($articleId, $emailComment);
		
		ProofreaderAction::viewProofreadComments($articleId);
	
	}
	
	/**
	 * View layout comments.
	 */
	function viewLayoutComments($args) {
		ProofreaderHandler::validate();
		ProofreaderHandler::setupTemplate(true);
		
		$articleId = $args[0];
		
		SubmissionProofreaderHandler::validate($articleId);
		ProofreaderAction::viewLayoutComments($articleId);
	
	}
	
	/**
	 * Post layout comment.
	 */
	function postLayoutComment() {
		ProofreaderHandler::validate();
		ProofreaderHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		SubmissionProofreaderHandler::validate($articleId);
		ProofreaderAction::postLayoutComment($articleId, $emailComment);
		
		ProofreaderAction::viewLayoutComments($articleId);
	
	}
	
	/**
	 * Edit comment.
	 */
	function editComment($args) {
		ProofreaderHandler::validate();
		ProofreaderHandler::setupTemplate(true);
		
		$articleId = $args[0];
		$commentId = $args[1];
		
		SubmissionProofreaderHandler::validate($articleId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		ProofreaderAction::editComment($commentId);

	}
	
	/**
	 * Save comment.
	 */
	function saveComment() {
		ProofreaderHandler::validate();
		ProofreaderHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$commentId = Request::getUserVar('commentId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		SubmissionProofreaderHandler::validate($articleId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		ProofreaderAction::saveComment($commentId, $emailComment);

		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$comment = &$articleCommentDao->getArticleCommentById($commentId);
		
		// Redirect back to initial comments page
		Request::redirect(sprintf('%s/viewProofreadComments/%d', Request::getRequestedPage(), $articleId));
	}
	
	/**
	 * Delete comment.
	 */
	function deleteComment($args) {
		ProofreaderHandler::validate();
		ProofreaderHandler::setupTemplate(true);
		
		$articleId = $args[0];
		$commentId = $args[1];
		
		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$comment = &$articleCommentDao->getArticleCommentById($commentId);
		
		SubmissionProofreaderHandler::validate($articleId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		ProofreaderAction::deleteComment($commentId);
		
		// Redirect back to initial comments page
		Request::redirect(sprintf('%s/viewProofreadComments/%d', Request::getRequestedPage(), $articleId));
	}
	
	
	//
	// Validation
	//
	
	/**
	 * Validate that the user is the author of the comment.
	 */
	function &validate($commentId) {
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
