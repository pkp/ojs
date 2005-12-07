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
		
		// Proofread comments are to be sent to the editors, layout editor, proofreader, and author,
		// excluding whomever posted the comment.
		
		// Get editors
		$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments = &$editAssignmentDao->getEditAssignmentsByArticleId($this->article->getArticleId());
		$editorAddresses = array();
		while (!$editAssignments->eof()) {
			$editAssignment =& $editAssignments->next();
			$editorAddresses[$editAssignment->getEditorEmail()] = $editAssignment->getEditorFullName();
		}

		// If no editors are currently assigned to this article,
		// send the email to all editors for the journal
		if (empty($editorAddresses)) {
			$editors = &$roleDao->getUsersByRoleId(ROLE_ID_EDITOR, $journal->getJournalId());
			while (!$editors->eof()) {
				$editor = &$editors->next();
				$editorAddresses[$editor->getEmail()] = $editor->getFullName();
			}
		}

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
			// Then add editors, proofreader and author
			$recipients = array_merge($recipients, $editorAddresses);
			
			if ($proofreader != null) {
				$recipients = array_merge($recipients, array($proofreader->getEmail() => $proofreader->getFullName()));
			}
		
			if (isset($author)) $recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));
		
		} else if ($this->roleId == ROLE_ID_PROOFREADER) {
			// Then add editors, layout editor, and author
			$recipients = array_merge($recipients, $editorAddresses);
			
			if ($layoutEditor != null) {
				$recipients = array_merge($recipients, array($layoutEditor->getEmail() => $layoutEditor->getFullName()));
			}
			
			if (isset($author)) $recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));
		
		} else {
			// Then add editors, layout editor, and proofreader
			$recipients = array_merge($recipients, $editorAddresses);
			
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
