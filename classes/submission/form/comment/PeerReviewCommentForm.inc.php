<?php

/**
 * CommentForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.form
 *
 * Comment form.
 *
 * $Id$
 */
 
import("submission.form.comment.CommentForm");

class PeerReviewCommentForm extends CommentForm {

	/** @var int the ID of the review assignment */
	var $reviewId;
	
	/**
	 * Constructor.
	 * @param $articleId int
	 */
	function PeerReviewCommentForm($articleId, $reviewId, $roleId) {
		parent::CommentForm($articleId, COMMENT_TYPE_PEER_REVIEW, $roleId, $reviewId);
		$this->reviewId = $reviewId;
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($this->reviewId);
		$reviewLetters = &$reviewAssignmentDao->getReviewIndexesForRound($this->articleId, $this->article->getCurrentRound());

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('commentType', 'peerReview');
		$templateMgr->assign('pageTitle', 'submission.comments.review');
		$templateMgr->assign('commentAction', 'postPeerReviewComment');
		$templateMgr->assign('commentTitle', $this->article->getArticleTitle());
		$templateMgr->assign('isLocked', isset($reviewAssignment) && $reviewAssignment->getDateCompleted() != null);
		$templateMgr->assign('canEmail', $this->roleId == ROLE_ID_EDITOR ? true : false);
		$templateMgr->assign('showReviewLetters', $this->roleId == ROLE_ID_EDITOR ? true : false);
		$templateMgr->assign('reviewLetters', $reviewLetters);
		$templateMgr->assign('reviewer', ROLE_ID_REVIEWER);
		$templateMgr->assign('hiddenFormParams', 
			array(
				'articleId' => $this->articleId,
				'reviewId' => $this->reviewId
			)
		);
		
		parent::display();
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'commentTitle',
				'authorComments',
				'comments'
			)
		);
	}
	
	/**
	 * Add the comment.
	 */
	function execute() {
		// Personalized execute() method since now there are possibly two comments contained within each form submission.
	
		$commentDao = &DAORegistry::getDAO('ArticleCommentDAO');
	
		// Assign all common information	
		$comment = &new ArticleComment();
		$comment->setCommentType($this->commentType);
		$comment->setRoleId($this->roleId);
		$comment->setArticleId($this->articleId);
		$comment->setAssocId($this->assocId);
		$comment->setAuthorId($this->user->getUserId());
		$comment->setCommentTitle($this->getData('commentTitle'));
		$comment->setDatePosted(Core::getCurrentDate());
		
		// If comments "For authors and editor" submitted
		if ($this->getData('authorComments') != null) {
			$comment->setComments($this->getData('authorComments'));
			$comment->setViewable(1);
			$commentDao->insertArticleComment($comment);
		}		
		
		// If comments "For editor" submitted
		if ($this->getData('comments') != null) {
			$comment->setComments($this->getData('comments'));
			$comment->setViewable(null);
			$commentDao->insertArticleComment($comment);
		}
	}
	
	/**
	 * Email the comment.
	 */
	function email() {
		// Create list of recipients:
		
		// Peer Review comments are to be sent to the editor or reviewer;
		// the opposite of whomever posted the comment.
		$recipients = array();
		
		if ($this->roleId == ROLE_ID_EDITOR) {
			// Then add reviewer
			$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
			$userDao = &DAORegistry::getDAO('UserDAO');
			
			$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($this->reviewId);
			$user = &$userDao->getUser($reviewAssignment->getReviewerId());
			
			$recipients = array_merge($recipients, array($user->getEmail() => $user->getFullName()));
		} else {
			// Then add editor
			$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
			$userDao = &DAORegistry::getDAO('UserDAO');
			
			$editAssignment = &$editAssignmentDao->getEditAssignmentByArticleId($this->articleId);
			
			// Check to ensure that there is a section editor assigned to this article.
			// If there isn't, I guess all editors should be emailed, but this is not coded
			// as of yet.
			if ($editAssignment != null && $editAssignment->getEditorId() != null) {
				$user = &$userDao->getUser($editAssignment->getEditorId());
				
				$recipients = array_merge($recipients, array($user->getEmail() => $user->getFullName()));
			}
		}
		
		parent::email($recipients);
	}
}

?>
