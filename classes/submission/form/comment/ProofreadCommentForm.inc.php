<?php

/**
 * ProofreadCommentForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.form
 *
 * ProofreadComment form.
 *
 * $Id$
 */
 
import("submission.form.comment.CommentForm");

class ProofreadCommentForm extends CommentForm {

	/**
	 * Constructor.
	 * @param $article object
	 */
	function ProofreadCommentForm($article, $roleId) {
		parent::CommentForm($article, COMMENT_TYPE_PROOFREAD, $roleId, $article->getArticleId());
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageTitle', 'submission.comments.corrections');
		$templateMgr->assign('commentAction', 'postProofreadComment');
		$templateMgr->assign('commentType', 'proofread');
		$templateMgr->assign('hiddenFormParams', 
			array(
				'articleId' => $this->article->getArticleId()
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
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();	

		// Create list of recipients:
		$recipients = array();
		
		// Proofread comments are to be sent to the editor, layout editor, proofreader, and author,
		// excluding whomever posted the comment.
		
		// Get editor
		$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignment = &$editAssignmentDao->getEditAssignmentByArticleId($this->article->getArticleId());
		if ($editAssignment != null && $editAssignment->getEditorId() != null) {
			$editor = &$userDao->getUser($editAssignment->getEditorId());
		} else {
			$editor = null;
		}
		
		// Get editors
		$editors = &$roleDao->getUsersByRoleId(ROLE_ID_EDITOR, $journal->getJournalId());
		
		// Get layout editor
		$layoutAssignmentDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
		$layoutAssignment = &$layoutAssignmentDao->getLayoutAssignmentByArticleId($this->article->getArticleId());
		if ($layoutAssignment != null && $layoutAssignment->getEditorId() > 0) {
			$layoutEditor = &$userDao->getUser($layoutAssignment->getEditorId());
		} else {
			$layoutEditor = null;
		}
		
		// Get proofreader
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = &$proofAssignmentDao->getProofAssignmentByArticleId($this->article->getArticleId());
		if ($proofAssignment != null && $proofAssignment->getProofreaderId() > 0) {
			$proofreader = &$userDao->getUser($proofAssignment->getProofreaderId());
		} else {
			$proofreader = null;
		}
		
		// Get author
		$author = &$userDao->getUser($this->article->getUserId());
		
		// Choose who receives this email
		if ($this->roleId == ROLE_ID_EDITOR) {
			// Then add layout editor, proofreader and author
			if ($layoutEditor != null) {
				$recipients = array_merge($recipients, array($layoutEditor->getEmail() => $layoutEditor->getFullName()));
			}
			
			if ($proofreader != null) {
				$recipients = array_merge($recipients, array($proofreader->getEmail() => $proofreader->getFullName()));
			}
			
			if (isset($author)) $recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));
		
		} else if ($this->roleId == ROLE_ID_LAYOUT_EDITOR) {
			// Then add editor, proofreader and author
			if ($editor != null) {
				$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
			} else {
				foreach ($editors as $editor) {
					$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
				}
			}
			
			if ($proofreader != null) {
				$recipients = array_merge($recipients, array($proofreader->getEmail() => $proofreader->getFullName()));
			}
		
			if (isset($author)) $recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));
		
		} else if ($this->roleId == ROLE_ID_PROOFREADER) {
			// Then add editor, layout editor, and author
			if ($editor != null) {
				$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
			} else {
				foreach ($editors as $editor) {
					$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
				}
			}
			
			if ($layoutEditor != null) {
				$recipients = array_merge($recipients, array($layoutEditor->getEmail() => $layoutEditor->getFullName()));
			}
			
			if (isset($author)) $recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));
		
		} else {
			// Then add editor, layout editor, and proofreader
			if ($editor != null) {
				$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
			} else {
				foreach ($editors as $editor) {
					$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
				}
			}
			
			if ($layoutEditor != null) {
				$recipients = array_merge($recipients, array($layoutEditor->getEmail() => $layoutEditor->getFullName()));
			}
			
			if ($proofreader != null) {
				$recipients = array_merge($recipients, array($proofreader->getEmail() => $proofreader->getFullName()));
			}
		}
		
		parent::email($recipients);
	}
}

?>
