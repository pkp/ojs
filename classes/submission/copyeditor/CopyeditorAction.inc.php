<?php

/**
 * CopyeditorAction.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * CopyeditorAction class.
 *
 * $Id$
 */

class CopyeditorAction extends Action {

	/**
	 * Constructor.
	 */
	function CopyeditorAction() {

	}
	
	/**
	 * Actions.
	 */
	
	/**
	 * Copyeditor completes initial copyedit.
	 * @param $articleId int
	 */
	function completeCopyedit($articleId) {
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$email = &new ArticleMailTemplate($articleId, 'COPYEDIT_COMP');
		
		$copyeditorSubmission = &$copyeditorSubmissionDao->getCopyeditorSubmission($articleId);
		
		$editor = $copyeditorSubmission->getEditor();
			
		$email->addRecipient($editor->getEmail(), $editor->getFullName());
				
		$paramArray = array(
			'reviewerName' => $editor->getFullName(),
			'journalName' => "Hansen",
			'journalUrl' => "Hansen",
			'articleTitle' => $copyeditorSubmission->getTitle(),
			'sectionName' => $copyeditorSubmission->getSectionTitle(),
			'reviewerUsername' => "http://www.roryscoolsite.com",
			'reviewerPassword' => "Hansen",
			'principalContactName' => "Hansen"	
		);
		$email->assignParams($paramArray);
		$email->setAssoc(ARTICLE_EMAIL_TYPE_COPYEDIT, $copyeditorSubmission->getCopyedId());
		$email->send();
		
		$copyeditorSubmission->setDateCompleted(Core::getCurrentDate());
			
		$copyeditorSubmissionDao->updateCopyeditorSubmission($copyeditorSubmission);
	}
	
	/**
	 * Copyeditor completes final copyedit.
	 * @param $articleId int
	 */
	function completeFinalCopyedit($articleId) {
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$email = &new ArticleMailTemplate($articleId, 'COPYEDIT_FINAL_REVIEW_COMP');
		
		$copyeditorSubmission = &$copyeditorSubmissionDao->getCopyeditorSubmission($articleId);
		
		$editor = $copyeditorSubmission->getEditor();
			
		$email->addRecipient($editor->getEmail(), $editor->getFullName());
				
		$paramArray = array(
			'reviewerName' => $editor->getFullName(),
			'journalName' => "Hansen",
			'journalUrl' => "Hansen",
			'articleTitle' => $copyeditorSubmission->getTitle(),
			'sectionName' => $copyeditorSubmission->getSectionTitle(),
			'reviewerUsername' => "http://www.roryscoolsite.com",
			'reviewerPassword' => "Hansen",
			'principalContactName' => "Hansen"	
		);
		$email->assignParams($paramArray);
		$email->setAssoc(ARTICLE_EMAIL_TYPE_COPYEDIT, $copyeditorSubmission->getCopyedId());
		$email->send();
		
		$copyeditorSubmission->setDateFinalCompleted(Core::getCurrentDate());
			
		$copyeditorSubmissionDao->updateCopyeditorSubmission($copyeditorSubmission);
	}
}

?>
