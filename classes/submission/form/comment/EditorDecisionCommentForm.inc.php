<?php

/**
 * EditorDecisionCommentForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.form
 *
 * EditorDecisionComment form.
 *
 * $Id$
 */
 
import("submission.form.comment.CommentForm");

class EditorDecisionCommentForm extends CommentForm {

	/** @var boolean import peer review comments */
	var $importPeerReviews;

	/** @var peer reviews to import into new comment */
	var $peerReviews;

	/**
	 * Constructor.
	 * @param $articleId int
	 */
	function EditorDecisionCommentForm($articleId, $roleId) {
		parent::CommentForm($articleId, COMMENT_TYPE_EDITOR_DECISION, $roleId, $articleId);
		
		$this->importPeerReviews = false;
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageTitle', 'submission.comments.comments');
		$templateMgr->assign('commentAction', 'postEditorDecisionComment');
		$templateMgr->assign('commentType', 'editorDecision');
		$templateMgr->assign('hiddenFormParams', 
			array(
				'articleId' => $this->articleId
			)
		);
		
		$allowPeerReviewsImport = $this->roleId == ROLE_ID_EDITOR || $this->roleId == ROLE_ID_SECTION_EDITOR ? true : false;
		$templateMgr->assign('allowPeerReviewsImport', $allowPeerReviewsImport);
		
		// Populate comment title and comments with imported peer review comments.
		if ($this->importPeerReviews) {
			$templateMgr->assign('commentTitle', $this->article->getArticleTitle());
			$templateMgr->assign('comments', $this->peerReviews);
		}
		
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
		
		// Editor Decision comments are to be sent to the editor or author,
		// the opposite of whomever wrote the comment.
		$recipients = array();
		
		if ($this->roleId == ROLE_ID_EDITOR) {
			// Then add author
			$articleDao = &DAORegistry::getDAO('ArticleDAO');
			$userDao = &DAORegistry::getDAO('UserDAO');
			
			$article = &$articleDao->getArticle($this->articleId);
			$user = &$userDao->getUser($article->getUserId());
			
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
	
	/**
	 * Imports Peer Review comments.
	 */
	function importPeerReviews() {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignments = &$reviewAssignmentDao->getReviewAssignmentsByArticleId($this->articleId, $this->article->getCurrentRound());
		
		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
				
		$this->importPeerReviews = true;
		$this->peerReviews = "This is some text that introduces the review.\n\n";
		
		foreach ($reviewAssignments as $reviewAssignment) {
			// If the reviewer has completed the assignment, then import the review.
			if ($reviewAssignment->getDateCompleted() != null) {
				// Get the comments associated with this review assignment
				$articleComments = &$articleCommentDao->getArticleComments($this->articleId, COMMENT_TYPE_PEER_REVIEW, $reviewAssignment->getReviewId());
			
				$this->peerReviews .= "-----------------------------------------------\n";
				$this->peerReviews .= "Reviewer:\n";
				
				if (is_array($articleComments)) {
					foreach ($articleComments as $comment) {
						// If the comment is viewable by the author, then add the comment.
						if ($comment->getViewable()) {
							$this->peerReviews .= $comment->getComments() . "\n";
						}
					}
				}
				
				$this->peerReviews .= "-----------------------------------------------\n\n";
			}
		}			
	}
}

?>
