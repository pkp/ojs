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

		$email = &new ArticleMailTemplate($articleId, 'EDITORIAL_ASSIGNMENT');
		$email->setFrom($user->getEmail(), $user->getFullName());

		if ($send && !$email->hasErrors()) {
			$email->setAssoc(ARTICLE_EMAIL_EDITOR_ASSIGN, ARTICLE_EMAIL_TYPE_EDITOR, $sectionEditor->getUserId());
			$email->send();

			if (!isset($editor)) {
				$editor = new EditAssignment();
				$editor->setArticleId($articleId);
			}
		
			// Make the selected editor the new editor
			$editor->setEditorId($sectionEditorId);
			$editor->setDateNotified(null);
			$editor->setDateCompleted(null);
			$editor->setDateAcknowledged(null);
		
			$editorSubmission->setEditor($editor);
		
			$editorSubmissionDao->updateEditorSubmission($editorSubmission);
		
			// Add log
			ArticleLog::logEvent($articleId, ARTICLE_LOG_EDITOR_ASSIGN, ARTICLE_LOG_TYPE_EDITOR, $sectionEditorId, 'log.editor.editorAssigned', array('editorName' => $sectionEditor->getFullName(), 'articleId' => $articleId));
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($sectionEditor->getEmail(), $sectionEditor->getFullName());
				$paramArray = array(
					'editorialContactName' => $sectionEditor->getFullName(),
					'articleTitle' => $editorSubmission->getArticleTitle(),
					'sectionName' => $editorSubmission->getSectionTitle(),
					'editorUsername' => $sectionEditor->getUsername(),
					'editorPassword' => $sectionEditor->getPassword(),
					'principalContactName' => $user->getFullName() . "\n" . $journal->getSetting('journalTitle') . "\n" . $user->getAffiliation()
				);

				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/assignEditor/send', array('articleId' => $articleId, 'editorId' => $sectionEditorId));
		}
	}
}

?>
