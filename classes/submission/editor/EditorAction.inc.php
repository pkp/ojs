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
		$sectionEditor = &$userDao->getUser($sectionEditorId);
		
		if ($editorSubmission->getEditor() != null) {
			// Add the current editor to the list of replaced editors			
			$replacedEditor = $editorSubmission->getEditor();
			$replacedEditor->setReplaced(1);
			
			$editorSubmission->addReplacedEditor($replacedEditor);
		}
		
		// Make the selected editor the new editor
		$editor = new EditAssignment();
		$editor->setArticleId($articleId);
		$editor->setEditorId($sectionEditorId);
		
		$editorSubmission->setEditor($editor);
		
		$editorSubmissionDao->updateEditorSubmission($editorSubmission);
		
		// Add log
		$entry = new ArticleEventLogEntry();
		$entry->setArticleId($articleId);
		$entry->setUserId($user->getUserId());
		$entry->setDateLogged(Core::getCurrentDate());
		$entry->setEventType(ARTICLE_LOG_EDITOR_ASSIGN);
		$entry->setAssocType(ARTICLE_LOG_TYPE_EDITOR);
		$entry->setAssocId($sectionEditorId);
		$entry->setLogMessage('log.editor.editorAssigned', array('editorName' => $sectionEditor->getFullName(), 'articleId' => $articleId));
	
		ArticleLog::logEventEntry($articleId, $entry);
	}
}

?>
