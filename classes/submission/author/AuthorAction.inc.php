<?php

/**
 * AuthorAction.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * AuthorAction class.
 *
 * $Id$
 */

class AuthorAction extends Action{

	/**
	 * Constructor.
	 */
	function AuthorAction() {
		parent::Action();
	}
	
	/**
	 * Actions.
	 */
	 
	/**
	 * Upload the revised version of an article.
	 * @param $articleId int
	 */
	function uploadRevisedVersion($articleId) {
		import("file.ArticleFileManager");
		$articleFileManager = new ArticleFileManager($articleId);
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		
		$authorSubmission = $authorSubmissionDao->getAuthorSubmission($articleId);
		
		
		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName)) {
			if ($authorSubmission->getRevisedFileId() != null) {
				$fileId = $articleFileManager->uploadSubmissionFile($fileName, $authorSubmission->getRevisedFileId());
			} else {
				$fileId = $articleFileManager->uploadSubmissionFile($fileName);
			}
		}
		
		$authorSubmission->setRevisedFileId($fileId);
		
		$authorSubmissionDao->updateAuthorSubmission($authorSubmission);
	}
	
	/**
	 * Author completes editor / author review.
	 * @param $articleId int
	 */
	function completeAuthorCopyedit($articleId) {
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		$email = &new ArticleMailTemplate($articleId, 'COPYEDIT_COMP');
		
		$authorSubmission = &$authorSubmissionDao->getAuthorSubmission($articleId);
		
		$editor = $authorSubmission->getEditor();
			
		$email->addRecipient($editor->getEmail(), $editor->getFullName());
				
		$paramArray = array(
			'reviewerName' => $editor->getFullName(),
			'journalName' => "Hansen",
			'journalUrl' => "Hansen",
			'articleTitle' => $authorSubmission->getTitle(),
			'sectionName' => $authorSubmission->getSectionTitle(),
			'reviewerUsername' => "http://www.roryscoolsite.com",
			'reviewerPassword' => "Hansen",
			'principalContactName' => "Hansen"	
		);
		$email->assignParams($paramArray);
		$email->setAssoc(ARTICLE_EMAIL_TYPE_AUTHOR, $authorSubmission->getUserId());
		$email->send();
		
		$authorSubmission->setCopyeditorDateAuthorCompleted(Core::getCurrentDate());
			
		$authorSubmissionDao->updateAuthorSubmission($authorSubmission);
	}
}

?>
