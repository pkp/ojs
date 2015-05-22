<?php

/**
 * @file classes/submission/form/comment/LayoutCommentForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LayoutCommentForm
 * @ingroup submission_form
 *
 * @brief LayoutComment form.
 */

import('classes.submission.form.comment.CommentForm');

class LayoutCommentForm extends CommentForm {

	/**
	 * Constructor.
	 * @param $article object
	 */
	function LayoutCommentForm($article, $roleId) {
		parent::CommentForm($article, COMMENT_TYPE_LAYOUT, $roleId, $article->getId());
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageTitle', 'submission.comments.comments');
		$templateMgr->assign('commentAction', 'postLayoutComment');
		$templateMgr->assign('commentType', 'layout');
		$templateMgr->assign('hiddenFormParams', 
			array(
				'articleId' => $this->article->getId()
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
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$journal =& Request::getJournal();

		// Create list of recipients:

		// Layout comments are to be sent to the editor or layout editor;
		// the opposite of whomever posted the comment.
		$recipients = array();

		if ($this->roleId == ROLE_ID_EDITOR || $this->roleId == ROLE_ID_SECTION_EDITOR) {
			// Then add layout editor
			$signoffDao =& DAORegistry::getDAO('SignoffDAO');
			$layoutSignoff = $signoffDao->getBySymbolic('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $this->article->getId());

			// Check to ensure that there is a layout editor assigned to this article.
			if ($layoutSignoff != null && $layoutSignoff->getUserId() > 0) {
				$user =& $userDao->getUser($layoutSignoff->getUserId());

				if ($user) $recipients = array_merge($recipients, array($user->getEmail() => $user->getFullName()));
			}
		} else {
			// Then add editor
			$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments =& $editAssignmentDao->getEditAssignmentsByArticleId($this->article->getId());
			$editorAddresses = array();
			while (!$editAssignments->eof()) {
				$editAssignment =& $editAssignments->next();
				if ($editAssignment->getCanEdit()) $editorAddresses[$editAssignment->getEditorEmail()] = $editAssignment->getEditorFullName();
				unset($editAssignment);
			}

			// If no editors are currently assigned to this article,
			// send the email to all editors for the journal
			if (empty($editorAddresses)) {
				$editors =& $roleDao->getUsersByRoleId(ROLE_ID_EDITOR, $journal->getId());
				while (!$editors->eof()) {
					$editor =& $editors->next();
					$editorAddresses[$editor->getEmail()] = $editor->getFullName();
				}
			}
			$recipients = array_merge($recipients, $editorAddresses);
		}

		parent::email($recipients);
	}
}

?>
