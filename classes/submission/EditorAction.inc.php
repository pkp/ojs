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
		$email = new MailTemplate('EDITORIAL_ASSIGNMENT');
		
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
			
			$email->send();
		
		}
	}
}

?>
