<?php

/**
 * CopyeditCommentForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.form
 *
 * CopyeditComment form.
 *
 * $Id$
 */
 
import("submission.form.comment.CommentForm");

class CopyeditCommentForm extends CommentForm {

	/**
	 * Constructor.
	 * @param $articleId int
	 */
	function CopyeditCommentForm($articleId, $roleId) {
		parent::CommentForm($articleId, COMMENT_TYPE_COPYEDIT, $roleId, $articleId);
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageTitle', 'submission.comments.copyeditComments');
		$templateMgr->assign('commentAction', 'postCopyeditComment');
		$templateMgr->assign('commentType', 'copyedit');
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
		$recipients = array();
		
		// Copyedit comments are to be sent to the editor, author, and copyeditor,
		// excluding whomever posted the comment.
		
		// Get editor
		$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignment = &$editAssignmentDao->getEditAssignmentByArticleId($this->articleId);
		if ($editAssignment != null && $editAssignment->getEditorId() != null) {
			$editor = &$userDao->getUser($editAssignment->getEditorId());
		} else {
			$editor = null;
		}
		
		// Get editors
		$editors = &$roleDao->getUsersByRoleId(ROLE_ID_EDITOR, $journal->getJournalId());
		
		// Get copyeditor
		$copyAssignmentDao = &DAORegistry::getDAO('CopyAssignmentDAO');
		$copyAssignment = &$copyAssignmentDao->getCopyAssignmentByArticleId($this->articleId);
		if ($copyAssignment != null && $copyAssignment->getCopyeditorId() != null) {
			$copyeditor = &$userDao->getUser($copyAssignment->getCopyeditorId());
		} else {
			$copyeditor = null;
		}
		
		// Get author
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = &$articleDao->getArticle($this->articleId);
		$author = &$userDao->getUser($article->getUserId());
		
		// Choose who receives this email
		if ($this->roleId == ROLE_ID_EDITOR) {
			// Then add copyeditor and author
			if ($copyeditor != null) {
				$recipients = array_merge($recipients, array($copyeditor->getEmail() => $copyeditor->getFullName()));
			}
			
			$recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));
		
		} else if ($this->roleId == ROLE_ID_COPYEDITOR) {
			// Then add editor and author
			// Check to ensure that there is a section editor assigned to this article.
			// If there isn't, add all editors.
			if ($editor != null) {
				$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
			} else {
				foreach ($editors as $editor) {
					$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
				}
			}
		
			$recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));
		
		} else {
			// Then add editor and copyeditor
			// Check to ensure that there is a section editor assigned to this article.
			// If there isn't, add all editors.
			if ($editor != null) {
				$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
			} else {
				foreach ($editors as $editor) {
					$recipients = array_merge($recipients, array($editor->getEmail() => $editor->getFullName()));
				}
			}
			
			if ($copyeditor != null) {
				$recipients = array_merge($recipients, array($copyeditor->getEmail() => $copyeditor->getFullName()));
			}
		}
		
		parent::email($recipients);
	}
}

?>
