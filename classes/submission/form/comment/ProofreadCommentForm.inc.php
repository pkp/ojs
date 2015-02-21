<?php

/**
 * @file classes/submission/form/comment/ProofreadCommentForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ProofreadCommentForm
 * @ingroup submission_form
 *
 * @brief ProofreadComment form.
 */

import('classes.submission.form.comment.CommentForm');

class ProofreadCommentForm extends CommentForm {

	/**
	 * Constructor.
	 * @param $article object
	 */
	function ProofreadCommentForm($article, $roleId) {
		parent::CommentForm($article, COMMENT_TYPE_PROOFREAD, $roleId, $article->getId());
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageTitle', 'submission.comments.corrections');
		$templateMgr->assign('commentAction', 'postProofreadComment');
		$templateMgr->assign('commentType', 'proofread');
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
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$journal =& Request::getJournal();	

		// Create list of recipients:
		$recipients = array();

		// Proofread comments are to be sent to the editors, layout editor, proofreader, and author,
		// excluding whomever posted the comment.

		// Get editors
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

		// Get layout editor
		$layoutSignoff = $signoffDao->getBySymbolic('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $this->article->getId());
		if ($layoutSignoff != null && $layoutSignoff->getUserId() > 0) {
			$layoutEditor =& $userDao->getUser($layoutSignoff->getUserId());
		} else {
			$layoutEditor = null;
		}

		// Get proofreader
		$proofSignoff = $signoffDao->getBySymbolic('SIGNOFF_PROOFREADING_PROOFREADER', ASSOC_TYPE_ARTICLE, $this->article->getId());
		if ($proofSignoff != null && $proofSignoff->getUserId() > 0) {
			$proofreader =& $userDao->getUser($proofSignoff->getUserId());
		} else {
			$proofreader = null;
		}

		// Get author
		$author =& $userDao->getUser($this->article->getUserId());

		// Choose who receives this email
		if ($this->roleId == ROLE_ID_EDITOR || $this->roleId == ROLE_ID_SECTION_EDITOR) {
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
