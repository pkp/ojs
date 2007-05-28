<?php

/**
 * EditorDecisionCommentForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.form
 *
 * EditorDecisionComment form.
 *
 * $Id$
 *
 */
 
import("submission.form.comment.CommentForm");

class EditorDecisionCommentForm extends CommentForm {

	/**
	 * Constructor.
	 * @param $article object
	 */
	function EditorDecisionCommentForm($article, $roleId) {
		parent::CommentForm($article, COMMENT_TYPE_EDITOR_DECISION, $roleId, $article->getArticleId());
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
		
		parent::display();
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'commentTitle',
				'comments'
			)
		);
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
		
		// Editor Decision comments are to be sent to the editor or author,
		// the opposite of whomever wrote the comment.
		$recipients = array();
		
		if ($this->roleId == ROLE_ID_EDITOR || $this->roleId == ROLE_ID_SECTION_EDITOR) {
			// Then add author
			$user = &$userDao->getUser($this->article->getUserId());
			
			if ($user) $recipients = array_merge($recipients, array($user->getEmail() => $user->getFullName()));
		} else {
			// Then add editor
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
			$recipients = array_merge($recipients, $editorAddresses);
		}
		
		parent::email($recipients);	
	}
}

?>
