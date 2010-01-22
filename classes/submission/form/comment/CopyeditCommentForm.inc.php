<?php

/**
 * @file classes/submission/form/comment/CopyeditCommentForm.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CopyeditCommentForm
 * @ingroup submission_form
 * @see Form
 *
 * @brief CopyeditComment form.
 */

// $Id$


import("submission.form.comment.CommentForm");

class CopyeditCommentForm extends CommentForm {

	/**
	 * Constructor.
	 * @param $article object
	 */
	function CopyeditCommentForm($article, $roleId) {
		parent::CommentForm($article, COMMENT_TYPE_COPYEDIT, $roleId, $article->getArticleId());
	}

	/**
	 * Display the form.
	 */
	function display() {
		$article = $this->article;

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageTitle', 'submission.comments.copyeditComments');
		$templateMgr->assign('commentAction', 'postCopyeditComment');
		$templateMgr->assign('commentType', 'copyedit');
		$templateMgr->assign('hiddenFormParams', 
			array(
				'articleId' => $article->getArticleId()
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
		$article = $this->article;
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();

		// Create list of recipients:
		$recipients = array();

		// Copyedit comments are to be sent to the editor, author, and copyeditor,
		// excluding whomever posted the comment.

		// Get editors
		$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments = &$editAssignmentDao->getEditAssignmentsByArticleId($article->getArticleId());
		$editAssignments =& $editAssignments->toArray();
		$editorAddresses = array();
		foreach ($editAssignments as $editAssignment) {
			if ($editAssignment->getCanEdit()) $editorAddresses[$editAssignment->getEditorEmail()] = $editAssignment->getEditorFullName();
		}

		// If no editors are currently assigned, send this message to
		// all of the journal's editors.
		if (empty($editorAddresses)) {
			$editors = &$roleDao->getUsersByRoleId(ROLE_ID_EDITOR, $journal->getJournalId());
			while (!$editors->eof()) {
				$editor = &$editors->next();
				$editorAddresses[$editor->getEmail()] = $editor->getFullName();
			}
		}

		// Get copyeditor
		$copyAssignmentDao = &DAORegistry::getDAO('CopyAssignmentDAO');
		$copyAssignment = &$copyAssignmentDao->getCopyAssignmentByArticleId($article->getArticleId());
		if ($copyAssignment != null && $copyAssignment->getCopyeditorId() > 0) {
			$copyeditor = &$userDao->getUser($copyAssignment->getCopyeditorId());
		} else {
			$copyeditor = null;
		}

		// Get author
		$author = &$userDao->getUser($article->getUserId());

		// Choose who receives this email
		if ($this->roleId == ROLE_ID_EDITOR || $this->roleId == ROLE_ID_SECTION_EDITOR) {
			// Then add copyeditor and author
			if ($copyeditor != null) {
				$recipients = array_merge($recipients, array($copyeditor->getEmail() => $copyeditor->getFullName()));
			}

			$recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));

		} else if ($this->roleId == ROLE_ID_COPYEDITOR) {
			// Then add editors and author
			$recipients = array_merge($recipients, $editorAddresses);

			if (isset($author)) $recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));

		} else {
			// Then add editors and copyeditor
			$recipients = array_merge($recipients, $editorAddresses);

			if ($copyeditor != null) {
				$recipients = array_merge($recipients, array($copyeditor->getEmail() => $copyeditor->getFullName()));
			}
		}

		parent::email($recipients);
	}
}

?>
