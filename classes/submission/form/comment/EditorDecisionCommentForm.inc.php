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
 *
 *	TODO: blindCcReviewer stuff needs to be removed, after OK from John.
 */
 
import("submission.form.comment.CommentForm");

class EditorDecisionCommentForm extends CommentForm {

	/** @var boolean import peer review comments */
	var $importPeerReviews;

	/** @var peer reviews to import into new comment */
	var $peerReviews;
	
	/** @var boolean blind cc reviewers */
	var $blindCcReviewers;

	/**
	 * Constructor.
	 * @param $article object
	 */
	function EditorDecisionCommentForm($article, $roleId) {
		parent::CommentForm($article, COMMENT_TYPE_EDITOR_DECISION, $roleId, $article->getArticleId());
		
		$this->importPeerReviews = false;
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageTitle', 'submission.comments.editorAuthorCorrespondence');
		$templateMgr->assign('articleId', $this->article->getArticleId());
		$templateMgr->assign('commentAction', 'postEditorDecisionComment');
		$templateMgr->assign('hiddenFormParams', 
			array(
				'articleId' => $this->article->getArticleId()
			)
		);
		
		$isEditor = $this->roleId == ROLE_ID_EDITOR || $this->roleId == ROLE_ID_SECTION_EDITOR ? true : false;
		$templateMgr->assign('isEditor', $isEditor);
		
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
		$this->readUserVars(
			array(
				'commentTitle',
				'comments',
				'blindCcReviewers'
			)
		);
	}
	
	/**
	 * Add the comment.
	 */
	function execute() {
		parent::execute();
		
		if ($this->getData('blindCcReviewers') != null) {
			$this->blindCcReviewers = true;
		}
	}
	
	/**
	 * Email the comment.
	 */
	function email() {
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		
		// Create list of recipients:
		
		// Editor Decision comments are to be sent to the editor or author,
		// the opposite of whomever wrote the comment.
		$recipients = array();
		
		if ($this->roleId == ROLE_ID_EDITOR) {
			// Then add author
			$user = &$userDao->getUser($this->article->getUserId());
			
			if ($user) $recipients = array_merge($recipients, array($user->getEmail() => $user->getFullName()));
		} else {
			// Then add editor
			$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignment = &$editAssignmentDao->getEditAssignmentByArticleId($this->article->getArticleId());
			
			// Check to ensure that there is a section editor assigned to this article.
			// If there isn't, add all editors.
			if ($editAssignment != null && $editAssignment->getEditorId() != null) {
				$user = &$userDao->getUser($editAssignment->getEditorId());
				
				if ($user) $recipients = array_merge($recipients, array($user->getEmail() => $user->getFullName()));
			} else {
				// Get editors
				$editors = &$roleDao->getUsersByRoleId(ROLE_ID_EDITOR, $journal->getJournalId());
				
				while (!$editors->eof()) {
					$editor = &$editors->next();
					$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
				}
			}
		}
		
		parent::email($recipients);	
	}
	
	/**
	 * Imports Peer Review comments.
	 * FIXME: Need to apply localization to these strings.
	 */
	function importPeerReviews() {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignments = &$reviewAssignmentDao->getReviewAssignmentsByArticleId($this->article->getArticleId(), $this->article->getCurrentRound());
		$reviewIndexes = &$reviewAssignmentDao->getReviewIndexesForRound($this->article->getArticleId(), $this->article->getCurrentRound());	
		
		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
				
		$this->importPeerReviews = true;
		$this->peerReviews = "The editor should replace this text with the editorial decision and explanation for this submission.\n\n";
		
		foreach ($reviewAssignments as $reviewAssignment) {
			// If the reviewer has completed the assignment, then import the review.
			if ($reviewAssignment->getDateCompleted() != null && !$reviewAssignment->getCancelled()) {
				// Get the comments associated with this review assignment
				$articleComments = &$articleCommentDao->getArticleComments($this->article->getArticleId(), COMMENT_TYPE_PEER_REVIEW, $reviewAssignment->getReviewId());
			
				$this->peerReviews .= "------------------------------------------------------\n";
				$this->peerReviews .= "Reviewer " . chr(ord('A') + $reviewIndexes[$reviewAssignment->getReviewId()]) . ":\n";
				
				if (is_array($articleComments)) {
					foreach ($articleComments as $comment) {
						// If the comment is viewable by the author, then add the comment.
						if ($comment->getViewable()) {
							$this->peerReviews .= $comment->getComments() . "\n";
						}
					}
				}
				
				$this->peerReviews .= "------------------------------------------------------\n\n";
			}
		}			
	}
}

?>
