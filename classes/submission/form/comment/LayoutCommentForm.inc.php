<?php

/**
 * LayoutCommentForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.form
 *
 * LayoutComment form.
 *
 * $Id$
 */
 
import("submission.form.comment.CommentForm");

class LayoutCommentForm extends CommentForm {

	/**
	 * Constructor.
	 * @param $articleId int
	 */
	function LayoutCommentForm($articleId, $roleId) {
		parent::CommentForm($articleId, COMMENT_TYPE_LAYOUT, $roleId, $articleId);
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageTitle', 'submission.comments.comments');
		$templateMgr->assign('commentAction', 'postLayoutComment');
		$templateMgr->assign('commentType', 'layout');
		$templateMgr->assign('hiddenFormParams', 
			array(
				'articleId' => $this->articleId
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
		
		// Layout comments are to be sent to the editor or layout editor;
		// the opposite of whomever posted the comment.
		$recipients = array();
		
		if ($this->roleId == ROLE_ID_EDITOR) {
			// Then add layout editor
			$layoutAssignmentDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
			$layoutAssignment = &$layoutAssignmentDao->getLayoutAssignmentByArticleId($this->articleId);
			
			// Check to ensure that there is a layout editor assigned to this article.
			if ($layoutAssignment != null && $layoutAssignment->getEditorId() != null) {
				$user = &$userDao->getUser($layoutAssignment->getEditorId());
			
				$recipients = array_merge($recipients, array($user->getEmail() => $user->getFullName()));
			}
		} else {
			// Then add editor
			$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignment = &$editAssignmentDao->getEditAssignmentByArticleId($this->articleId);
			
			// Check to ensure that there is a section editor assigned to this article.
			// If there isn't, add all editors.
			if ($editAssignment != null && $editAssignment->getEditorId() != null) {
				$user = &$userDao->getUser($editAssignment->getEditorId());
				
				$recipients = array_merge($recipients, array($user->getEmail() => $user->getFullName()));
			} else {
				// Get editors
				$editors = &$roleDao->getUsersByRoleId(ROLE_ID_EDITOR, $journal->getJournalId());
				
				foreach ($editors as $editor) {
					$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
				}
			}
		}
		
		parent::email($recipients);
	}
}

?>
