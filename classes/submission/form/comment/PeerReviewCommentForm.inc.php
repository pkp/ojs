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
		$templateMgr = &TemplateManager::getManager();		
		$templateMgr->assign('commentAction', 'postPeerReviewComment');
		$templateMgr->assign('commentType', 'peerReview');
		$templateMgr->assign('isLocked', $reviewAssignment->getDateCompleted() != null);
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
		parent::readInputData();
	}
	
	/**
	 * Add the comment.
	 */
	function execute() {
		parent::execute();
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
