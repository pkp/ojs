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
	 * Notifies a section editor of a submission assignment.
	 * @param $articleId int
	 */
	function notifySectionEditor($articleId) {
		$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$email = &new ArticleMailTemplate($articleId, 'EDITORIAL_ASSIGNMENT');
		
		$editorSubmission = &$editorSubmissionDao->getEditorSubmission($articleId);
		
		if ($editorSubmission->getEditorId() != null) {
			$sectionEditor = &$userDao->getUser($editorSubmission->getEditorId());
		
			$email->addRecipient($sectionEditor->getEmail(), $sectionEditor->getFullName());
			
			$paramArray = array(
				'editorialContactName' => $sectionEditor->getFullName(),
				'journalName' => "Hansen",
				'journalUrl' => "Hansen",
				'articleTitle' => $editorSubmission->getTitle(),
				'sectionName' => $editorSubmission->getSectionTitle(),
				'editorUsername' => "http://www.roryscoolsite.com",
				'editorPassword' => "Hansen",
				'principalContactName' => "Hansen"	
			);
			
			$email->assignParams($paramArray);
			$email->setAssoc(ARTICLE_EMAIL_TYPE_EDITOR, $editorSubmission->getEditId());
			$email->send();
		
		}
	}
	
	/**
	 * Assigns a section editor to a submission.
	 * @param $articleId int
	 */
	function assignEditor($articleId, $sectionEditorId) {
		$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
		$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		$editorSubmission = &$editorSubmissionDao->getEditorSubmission($articleId);
		$editor = $editorSubmission->getEditor();
		if (!isset($editor)) {
			$editor = new EditAssignment();
			$editor->setArticleId($articleId);
		}
		$sectionEditor = &$userDao->getUser($sectionEditorId);
		
		// Make the selected editor the new editor
		$editor->setEditorId($sectionEditorId);
		$editor->setDateNotified(null);
		$editor->setDateCompleted(null);
		$editor->setDateAcknowledged(null);
		
		$editorSubmission->setEditor($editor);
		
		$editorSubmissionDao->updateEditorSubmission($editorSubmission);
		
		// Add log
		ArticleLog::logEvent($articleId, ARTICLE_LOG_EDITOR_ASSIGN, ARTICLE_LOG_TYPE_EDITOR, $sectionEditorId, 'log.editor.editorAssigned', array('editorName' => $sectionEditor->getFullName(), 'articleId' => $articleId));
	}
}

?>
