<?php

/**
 * SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.sectionEditor
 *
 * Handle requests for submission comments. 
 *
 * $Id$
 */

class SubmissionCommentsHandler extends SectionEditorHandler {
	
	/**
	 * View peer review comments.
	 */
	function viewPeerReviewComments($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		$reviewId = $args[1];
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::viewPeerReviewComments($articleId, $reviewId);
	
	}
	
	/**
	 * Post peer review comments.
	 */
	function postPeerReviewComment() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$reviewId = Request::getUserVar('reviewId');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::postPeerReviewComment($articleId, $reviewId);
		
		SectionEditorAction::viewPeerReviewComments($articleId, $reviewId);

	}
	
	/**
	 * View editor decision comments.
	 */
	function viewEditorDecisionComments($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::viewEditorDecisionComments($articleId);
	
	}
	
	/**
	 * Post peer review comments.
	 */
	function postEditorDecisionComment() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::postEditorDecisionComment($articleId);
		
		SectionEditorAction::viewEditorDecisionComments($articleId);
	
	}
	
	/**
	 * View copyedit comments.
	 */
	function viewCopyeditComments($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::viewCopyeditComments($articleId);
	
	}
	
	/**
	 * Post copyedit comment.
	 */
	function postCopyeditComment() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::postCopyeditComment($articleId);
		
		SectionEditorAction::viewCopyeditComments($articleId);
	
	}
	
	/**
	 * View layout comments.
	 */
	function viewLayoutComments($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::viewLayoutComments($articleId);

	}
	
	/**
	 * Post layout comment.
	 */
	function postLayoutComment() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::postLayoutComment($articleId);
		
		SectionEditorAction::viewLayoutComments($articleId);
	
	}
	
	/**
	 * Edit comment.
	 */
	function editComment($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		$commentId = $args[1];
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::editComment($commentId);

	}
	
	/**
	 * Save comment.
	 */
	function saveComment() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$commentId = Request::getUserVar('commentId');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::saveComment($commentId);

		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$comment = &$articleCommentDao->getArticleCommentById($commentId);
		
		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			Request::redirect(sprintf('%s/viewPeerReviewComments/%d/%d', Request::getRequestedPage(), $articleId, $comment->getAssocId()));
		} else if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
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
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		$commentId = $args[1];
		
		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$comment = &$articleCommentDao->getArticleCommentById($commentId);
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::deleteComment($commentId);
		
		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			Request::redirect(sprintf('%s/viewPeerReviewComments/%d/%d', Request::getRequestedPage(), $articleId, $comment->getAssocId()));
		} else if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
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
	
}
?>
