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
		$templateMgr->assign('submissionFile', $articleFileDao->getSubmissionArticleFile($this->articleId));

		parent::display();
	}
	
	/**
	 * Save changes to article.
	 * @return int the article ID
	 */
	function execute() {
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		
		// Update article
		$article = &$this->article;
		if ($article->getSubmissionProgress() <= $this->step) {
			$article->setSubmissionProgress($this->step + 1);
		}
		$articleDao->updateArticle($article);
		
		return $this->articleId;
	}
	
}

?>
