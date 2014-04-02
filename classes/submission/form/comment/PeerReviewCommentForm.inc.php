<?php

/**
 * @file classes/submission/form/comment/PeerReviewCommentForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PeerReviewCommentForm
 * @ingroup submission_form
 *
 * @brief Comment form.
 */

import('classes.submission.form.comment.CommentForm');

class PeerReviewCommentForm extends CommentForm {

	/** @var int the ID of the review assignment */
	var $reviewId;

	/** @var array the IDs of the inserted comments */
	var $insertedComments;

	/**
	 * Constructor.
	 * @param $article object
	 */
	function PeerReviewCommentForm($article, $reviewId, $roleId) {
		parent::CommentForm($article, COMMENT_TYPE_PEER_REVIEW, $roleId, $reviewId);
		$this->reviewId = $reviewId;
	}

	/**
	 * Display the form.
	 */
	function display() {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment =& $reviewAssignmentDao->getById($this->reviewId);
		$reviewLetters =& $reviewAssignmentDao->getReviewIndexesForRound($this->article->getId(), $this->article->getCurrentRound());

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('commentType', 'peerReview');
		$templateMgr->assign('pageTitle', 'submission.comments.review');
		$templateMgr->assign('commentAction', 'postPeerReviewComment');
		$templateMgr->assign('commentTitle', strip_tags($this->article->getLocalizedTitle()));
		$templateMgr->assign('isLocked', isset($reviewAssignment) && $reviewAssignment->getDateCompleted() != null);
		$templateMgr->assign('canEmail', false); // Previously, editors could always email.
		$templateMgr->assign('showReviewLetters', ($this->roleId == ROLE_ID_EDITOR || $this->roleId == ROLE_ID_SECTION_EDITOR) ? true : false);
		$templateMgr->assign('reviewLetters', $reviewLetters);
		$templateMgr->assign('reviewer', ROLE_ID_REVIEWER);
		$templateMgr->assign('hiddenFormParams', 
			array(
				'articleId' => $this->article->getId(),
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

		$commentDao =& DAORegistry::getDAO('ArticleCommentDAO');
		$this->insertedComments = array();

		// Assign all common information	
		$comment = new ArticleComment();
		$comment->setCommentType($this->commentType);
		$comment->setRoleId($this->roleId);
		$comment->setArticleId($this->article->getId());
		$comment->setAssocId($this->assocId);
		$comment->setAuthorId($this->user->getId());
		$comment->setCommentTitle($this->getData('commentTitle'));
		$comment->setDatePosted(Core::getCurrentDate());

		// If comments "For authors and editor" submitted
		if ($this->getData('authorComments') != null) {
			$comment->setComments($this->getData('authorComments'));
			$comment->setViewable(1);
			array_push($this->insertedComments, $commentDao->insertArticleComment($comment));
		}		

		// If comments "For editor" submitted
		if ($this->getData('comments') != null) {
			$comment->setComments($this->getData('comments'));
			$comment->setViewable(null);
			array_push($this->insertedComments, $commentDao->insertArticleComment($comment));
		}
	}
}

?>
