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
}

?>
