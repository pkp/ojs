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
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::postPeerReviewComment($articleId, $reviewId, $emailComment);

		
		
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
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		//$blindCcReviewers = Request::getUserVar('blindCcReviewers') != null ? true : false;
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::postEditorDecisionComment($articleId, $emailComment);
		
		//if (!$blindCcReviewers) {
		//	SectionEditorAction::viewEditorDecisionComments($articleId);
		//}
		
		SectionEditorAction::viewEditorDecisionComments($articleId);
	
	}
	
	/**
	 * Blind CC the reviews to reviewers.
	 */
	function blindCcReviewsToReviewers($args = array()) {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		
		if (isset($args[0]) && $args[0] == 'send') {
			$send = true;
			SectionEditorAction::blindCcReviewsToReviewers($articleId, $send);
			Request::redirect(sprintf('%s/viewEditorDecisionComments/%d', Request::getRequestedPage(), $articleId));
			
		} else {
			parent::setupTemplate(true, $articleId, 'editing');
			SectionEditorAction::blindCcReviewsToReviewers($articleId);
		}
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
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::postCopyeditComment($articleId, $emailComment);
		
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
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::postLayoutComment($articleId, $emailComment);
		
		SectionEditorAction::viewLayoutComments($articleId);
	
	}
	
	/**
	 * View proofread comments.
	 */
	function viewProofreadComments($args) {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::viewProofreadComments($articleId);

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
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::postProofreadComment($articleId, $emailComment);
		
		SectionEditorAction::viewProofreadComments($articleId);
	
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
		list($comment) = SubmissionCommentsHandler::validate($commentId);
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
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		TrackSubmissionHandler::validate($articleId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		
		// Save the comment.
		SectionEditorAction::saveComment($commentId, $emailComment);

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
		
		TrackSubmissionHandler::validate($articleId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
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
	
	/**
	 * Import Peer Review comments.
	 */
	function importPeerReviews() {
		SectionEditorHandler::validate();
		SectionEditorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::importPeerReviews($articleId);
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
