<?php

/**
 * EditorAction.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * EditorAction class.
 *
 * $Id$
 */

import('submission.sectionEditor.SectionEditorAction');

class EditorAction extends SectionEditorAction {

	/**
	 * Constructor.
	 */
	function EditorAction() {

	}
	
	/**
	 * Actions.
	 */
	 
	/**
	 * Assigns a section editor to a submission.
	 * @param $articleId int
	 * @return boolean true iff ready for redirect
	 */
	function assignEditor($articleId, $sectionEditorId, $send = false) {
		$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
		$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');

		$user = &Request::getUser();
		$journal = &Request::getJournal();

		$editorSubmission = &$editorSubmissionDao->getEditorSubmission($articleId);
		$sectionEditor = &$userDao->getUser($sectionEditorId);
		$editor = $editorSubmission->getEditor();
		if (!isset($sectionEditor)) return true;

		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($editorSubmission, 'EDITOR_ASSIGN');

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_EDITOR_ASSIGN, ARTICLE_EMAIL_TYPE_EDITOR, $sectionEditor->getUserId());
				$email->send();
			}

			if (!isset($editor)) {
				$editor = new EditAssignment();
				$editor->setArticleId($articleId);
			}
		
			// Make the selected editor the new editor
			$editor->setEditorId($sectionEditorId);
			$editor->setDateNotified(Core::getCurrentDate());
			$editor->setDateUnderway(null);
		
			$editorSubmission->setEditor($editor);
		
			$editorSubmissionDao->updateEditorSubmission($editorSubmission);
		
			// Add log
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($articleId, ARTICLE_LOG_EDITOR_ASSIGN, ARTICLE_LOG_TYPE_EDITOR, $sectionEditorId, 'log.editor.editorAssigned', array('editorName' => $sectionEditor->getFullName(), 'articleId' => $articleId));
			return true;
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($sectionEditor->getEmail(), $sectionEditor->getFullName());
				$paramArray = array(
					'editorialContactName' => $sectionEditor->getFullName(),
					'editorUsername' => $sectionEditor->getUsername(),
					'editorPassword' => $sectionEditor->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionUrl' => Request::getPageUrl() . '/sectionEditor/submissionReview/' . $articleId,
					'submissionEditingUrl' => Request::getPageUrl() . '/sectionEditor/submissionReview/' . $articleId // FIXME For backwards compatibility
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/assignEditor/send', array('articleId' => $articleId, 'editorId' => $sectionEditorId));
			return false;
		}
	}
}

?>
