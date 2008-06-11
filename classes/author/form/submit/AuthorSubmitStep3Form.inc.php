<?php

/**
 * @file AuthorSubmitStep3Form.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package author.form.submit
 * @class AuthorSubmitStep3Form
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
	function AuthorSubmitStep3Form($article) {
		parent::AuthorSubmitForm($article, 3);

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
			$templateMgr->assign_by_ref('submissionFile', $articleFileDao->getArticleFile($this->article->getSubmissionFileId()));
		}
		parent::display();
	}

	/**
	 * Upload the submission file.
	 * @param $fileName string
	 * @return boolean
	 */
	function uploadSubmissionFile($fileName) {
		import("file.ArticleFileManager");

		$articleFileManager = &new ArticleFileManager($this->articleId);
		$articleDao = &DAORegistry::getDAO('ArticleDAO');

		if ($articleFileManager->uploadedFileExists($fileName)) {
			// upload new submission file, overwriting previous if necessary
			$submissionFileId = $articleFileManager->uploadSubmissionFile($fileName, $this->article->getSubmissionFileId(), true);
		}

		if (isset($submissionFileId)) {
			$this->article->setSubmissionFileId($submissionFileId);
			return $articleDao->updateArticle($this->article);

		} else {
			return false;
		}
	}

	/**
	 * Save changes to article.
	 * @return int the article ID
	 */
	function execute() {
		// Update article
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = &$this->article;

		if ($article->getSubmissionProgress() <= $this->step) {
			$article->stampStatusModified();
			$article->setSubmissionProgress($this->step + 1);
			$articleDao->updateArticle($article);
		}

		return $this->articleId;
	}

}

?>
