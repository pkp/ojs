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

class AuthorAction {

	/**
	 * Constructor.
	 */
	function AuthorAction() {

	}
	
	/**
	 * Actions.
	 */
	 
	/**
	 * Upload the revised version of an article.
	 * @param $articleId int
	 */
	function uploadRevisedArticle($articleId) {
		import("file.ArticleFileManager");
		$articleFileManager = new ArticleFileManager($articleId);
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		
		$authorSubmission = $authorSubmissionDao->getAuthorSubmission($articleId);
		
		$fileName = 'upload';
		if ($articleFileManager->getUploadedFileExists($fileName)) {
			if (($submissionFile = $authorSubmission->getSubmissionFile()) != null) {
				$articleFileManager->uploadSubmissionFile($fileName, $submissionFile->getFileId());
			} else {
				$articleFileManager->uploadSubmissionFile($fileName);
			}
		}
	}
}

?>
