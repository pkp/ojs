<?php

/**
 * SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.copyeditor
 *
 * Handle requests for submission comments. 
 *
 * $Id$
 */

class SubmissionCommentsHandler extends CopyeditorHandler {
	
	/**
	 * View copyedit comments.
	 */
	function viewCopyeditComments($args) {
		CopyeditorHandler::validate();
		CopyeditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		
		TrackSubmissionHandler::validate($articleId);
		CopyeditorAction::viewCopyeditComments($articleId);
	
	}
	
	/**
	 * Post copyedit comment.
	 */
	function postCopyeditComment() {
		CopyeditorHandler::validate();
		CopyeditorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		CopyeditorAction::postCopyeditComment($articleId);
		
		CopyeditorAction::viewCopyeditComments($articleId);
	
	}

	/**
	 * Edit comment.
	 */
	function editComment($args) {
		CopyeditorHandler::validate();
		CopyeditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		$commentId = $args[1];
		
		TrackSubmissionHandler::validate($articleId);
		CopyeditorAction::editComment($commentId);

	}
	
	/**
	 * Save comment.
	 */
	function saveComment() {
		CopyeditorHandler::validate();
		CopyeditorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$commentId = Request::getUserVar('commentId');
		
		TrackSubmissionHandler::validate($articleId);
		CopyeditorAction::saveComment($commentId);

		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$comment = &$articleCommentDao->getArticleCommentById($commentId);
		
		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
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
		CopyeditorHandler::validate();
		CopyeditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		$commentId = $args[1];
		
		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$comment = &$articleCommentDao->getArticleCommentById($commentId);
		
		TrackSubmissionHandler::validate($articleId);
		CopyeditorAction::deleteComment($commentId);
		
		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
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
	
}
?>
