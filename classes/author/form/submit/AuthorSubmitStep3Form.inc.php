<?php

/**
 * AuthorSubmitStep3Form.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package author.form.submit
 *
 * Form for Step 3 of author article submission.
 *
 * $Id$
 */

import("author.form.submit.AuthorSubmitForm");

class AuthorSubmitStep3Form extends AuthorSubmitForm {
	
	/**
	 * Constructor.
	 */
	function AuthorSubmitStep3Form($articleId) {
		parent::AuthorSubmitForm($articleId, 3);

		// Validation checks for this form
	}
	
	/**
	 * Initialize form data from current article.
	 */
	function initData() {
		if (isset($this->article)) {
			$article = &$this->article;
			$this->_data = array(
			);
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
			)
		);
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		
		// Get supplementary files for this article
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		if ($this->article->getSubmissionFileId() != null) {
			$templateMgr->assign('submissionFile', $articleFileDao->getArticleFile($this->article->getSubmissionFileId()));
		}
		parent::display();
	}
	
	/**
	 * Save changes to article.
	 * @return int the article ID
	 */
	function execute() {
		import("file.ArticleFileManager");
		$articleFileManager = new ArticleFileManager($this->articleId);
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		
		$fileName = 'upload';
			
		if ($articleFileManager->uploadedFileExists($fileName)) {
			if ($this->article->getSubmissionFileId() != '') {
				$submissionFileId = $articleFileManager->uploadSubmissionFile($fileName, $this->article->getSubmissionFileId());
			} else {
				$submissionFileId = $articleFileManager->uploadSubmissionFile($fileName);
			}
		}
		
		// Update article
		$article = &$this->article;
		if ($article->getSubmissionProgress() <= $this->step) {
			$article->setSubmissionProgress($this->step + 1);
		}
		if (isset($submissionFileId)) {
			$article->setSubmissionFileId($submissionFileId);
		}
		$articleDao->updateArticle($article);
		
		return $this->articleId;
	}
	
}

?>
