<?php

/**
 * SubmitHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.author
 *
 * Handle requests for author article submission. 
 *
 * $Id$
 */

class SubmitHandler extends AuthorHandler {
	
	/**
	 * Display journal author article submission.
	 * Displays author index page if a valid step is not specified.
	 * @param $args array optional, if set the first parameter is the step to display
	 */
	function submit($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$step = isset($args[0]) ? (int) $args[0] : 0;
		$articleId = Request::getUserVar('articleId');
		
		if (SubmitHandler::validate($articleId, $step)) {
			$formClass = "AuthorSubmitStep{$step}Form";
			import("author.form.submit.$formClass");
			
			$submitForm = &new $formClass($articleId);
			$submitForm->initData();
			$submitForm->display();
		
		} else {
			Request::redirect('author/submit/1');
		}
	}
	
	/**
	 * Save a submission step.
	 * @param $args array first parameter is the step being saved
	 */
	function saveSubmit($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$step = isset($args[0]) ? (int) $args[0] : 0;
		$articleId = Request::getUserVar('articleId');
		
		if (!SubmitHandler::validate($articleId, $step)) {
			// Invalid step
			Request::redirect('author/submit');
			return;
		}
			
		$formClass = "AuthorSubmitStep{$step}Form";
		import("author.form.submit.$formClass");
		
		$submitForm = &new $formClass($articleId);
		$submitForm->readInputData();
			
		// Check for any special cases before trying to save
		switch ($step) {
			case 2:
				if (Request::getUserVar('addAuthor')) {
					// Add a sponsor
					$editData = true;
					$authors = $submitForm->getData('authors');
					array_push($authors, array());
					$submitForm->setData('authors', $authors);
					
				} else if (($delAuthor = Request::getUserVar('delAuthor')) && count($delAuthor) == 1) {
					// Delete an author
					$editData = true;
					list($delAuthor) = array_keys($delAuthor);
					$delAuthor = (int) $delAuthor;
					$authors = $submitForm->getData('authors');
					if (isset($authors[$delAuthor]['authorId']) && !empty($authors[$delAuthor]['authorId'])) {
						$deletedAuthors = explode(':', $submitForm->getData('deletedAuthors'));
						array_push($deletedAuthors, $authors[$delAuthor]['authorId']);
						$submitForm->setData('deletedAuthors', join(':', $deletedAuthors));
					}
					array_splice($authors, $delAuthor, 1);
					$submitForm->setData('authors', $authors);
					
					if ($submitForm->getData('primaryContact') == $delAuthor) {
						$submitForm->setData('primaryContact', 0);
					}
					
				} else if (Request::getUserVar('moveAuthor')) {
					// Move an author up/down
					$editData = true;
					$moveAuthorDir = Request::getUserVar('moveAuthorDir');
					$moveAuthorDir = $moveAuthorDir == 'u' ? 'u' : 'd';
					$moveAuthorIndex = (int) Request::getUserVar('moveAuthorIndex');
					$authors = $submitForm->getData('authors');
					
					if (!(($moveAuthorDir == 'u' && $moveAuthorIndex <= 0) || ($moveAuthorDir == 'd' && $moveAuthorIndex >= count($authors) - 1))) {
						$tmpAuthor = $authors[$moveAuthorIndex];
						if ($moveAuthorDir == 'u') {
							$authors[$moveAuthorIndex] = $authors[$moveAuthorIndex - 1];
							$authors[$moveAuthorIndex - 1] = $tmpAuthor;
						} else {
							$authors[$moveAuthorIndex] = $authors[$moveAuthorIndex + 1];
							$authors[$moveAuthorIndex + 1] = $tmpAuthor;
						}
					}
					$submitForm->setData('authors', $authors);
				}
				break;
				
			case 3:
				if (Request::getUserVar('uploadSubmissionFile')) {
					$submitForm->uploadSubmissionFile('submissionFile');
					$editData = true;
				}
				break;
		}
		
		if (!isset($editData) && $submitForm->validate()) {
			$articleId = $submitForm->execute();
			
			if ($step == 5) {
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('message', 'author.submit.submissionComplete');
				$templateMgr->assign('backLink', Request::getPageUrl() . '/author/track');
				$templateMgr->assign('backLinkLabel', 'author.track');
				$templateMgr->display('common/message.tpl');
				
			} else {
				Request::redirect(sprintf('author/submit/%d?articleId=%d', $step+1, $articleId));
			}
		
		} else {
			$submitForm->display();
		}
	}
	
	/**
	 * Display supplementary file submission form.
	 * @param $args array optional, if set the first parameter is the supplementary file to edit
	 */
	function submitSuppFile($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;
		
		if (!SubmitHandler::validate($articleId, 4)) {
			// Invalid submission
			Request::redirect('author/submit');
			return;
		}
		
		$formClass = "AuthorSubmitSuppFileForm";
		import("author.form.submit.$formClass");
		
		$submitForm = &new $formClass($articleId, $suppFileId);
		
		$submitForm->initData();
		$submitForm->display();
	}
	
	/**
	 * Save a supplementary file.
	 * @param $args array optional, if set the first parameter is the supplementary file to update
	 */
	function saveSubmitSuppFile($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;
		
		if (!SubmitHandler::validate($articleId, 4)) {
			// Invalid submission
			Request::redirect('author/submit');
			return;
		}
		
		$formClass = "AuthorSubmitSuppFileForm";
		import("author.form.submit.$formClass");
		
		$submitForm = &new $formClass($articleId, $suppFileId);
		$submitForm->readInputData();
		
		if ($submitForm->validate()) {
			$submitForm->execute();
			Request::redirect(sprintf('author/submit/%d?articleId=%d', 4, $articleId));
		
		} else {
			$submitForm->display();
		}
	}
	
	/**
	 * Delete a supplementary file.
	 * @param $args array, the first parameter is the supplementary file to delete
	 */
	function deleteSubmitSuppFile($args) {
		import("file.ArticleFileManager");

		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;

		if (!SubmitHandler::validate($articleId, 4)) {
			// Invalid submission
			Request::redirect('author/submit');
			return;
		}
		
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$suppFile = $suppFileDao->getSuppFile($suppFileId, $articleId);
		$suppFileDao->deleteSuppFileById($suppFileId, $articleId);
		
		$articleFileManager = new ArticleFileManager($articleId);
		$articleFileManager->removeSuppFile($suppFile->getFileName());

		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$articleFileDao->deleteArticleFileById($suppFile->getFileId(),1);
		
		Request::redirect(sprintf('author/submit/%d?articleId=%d', 4, $articleId));
	}

	/**
	 * Validation check for submission.
	 * Checks that article ID is valid, if specified.
	 * @param $articleId int
	 * @param $step int
	 */
	function validate($articleId, $step) {
		$articleId = Request::getUserVar('articleId');
		if ($step < 1 || $step > 5 || (!isset($articleId) && $step != 1)) {
			return false;
			
		} else if (!isset($articleId)) {
			return true;
		}
		
		// Check that article exists for this journal and user and that submission is incomplete
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$sessionManager = &SessionManager::getManager();
		$session = &$sessionManager->getUserSession();
		$user = &$session->getUser();
		$journal = &Request::getJournal();
		
		$submissionProgress = $articleDao->incompleteSubmissionExists($articleId, $user->getUserId(), $journal->getJournalId());
		return $step === false || $step > $submissionProgress ? false : true;
	}
	
}
?>
