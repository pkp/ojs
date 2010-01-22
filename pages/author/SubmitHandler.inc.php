<?php

/**
 * @file SubmitHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmitHandler
 * @ingroup pages_author
 *
 * @brief Handle requests for author article submission. 
 */

// $Id$


class SubmitHandler extends AuthorHandler {

	/**
	 * Display journal author article submission.
	 * Displays author index page if a valid step is not specified.
	 * @param $args array optional, if set the first parameter is the step to display
	 */
	function submit($args) {
		parent::validate('author.submit.authorSubmitLoginMessage');
		parent::setupTemplate(true);

		$step = isset($args[0]) ? (int) $args[0] : 0;
		$articleId = Request::getUserVar('articleId');

		list($journal, $article) = SubmitHandler::validate($articleId, $step);

		$formClass = "AuthorSubmitStep{$step}Form";
		import("author.form.submit.$formClass");

		$submitForm = &new $formClass($article);
		if ($submitForm->isLocaleResubmit()) {
			$submitForm->readInputData();
		} else {
			$submitForm->initData();
		}
		$submitForm->display();
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

		list($journal, $article) = SubmitHandler::validate($articleId, $step);

		$formClass = "AuthorSubmitStep{$step}Form";
		import("author.form.submit.$formClass");

		$submitForm = &new $formClass($article);
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
						$primaryContact = $submitForm->getData('primaryContact');
						if ($moveAuthorDir == 'u') {
							$authors[$moveAuthorIndex] = $authors[$moveAuthorIndex - 1];
							$authors[$moveAuthorIndex - 1] = $tmpAuthor;
							if ($primaryContact == $moveAuthorIndex) {
								$submitForm->setData('primaryContact', $moveAuthorIndex - 1);
							} else if ($primaryContact == ($moveAuthorIndex - 1)) {
								$submitForm->setData('primaryContact', $moveAuthorIndex);
							}
						} else {
							$authors[$moveAuthorIndex] = $authors[$moveAuthorIndex + 1];
							$authors[$moveAuthorIndex + 1] = $tmpAuthor;
							if ($primaryContact == $moveAuthorIndex) {
								$submitForm->setData('primaryContact', $moveAuthorIndex + 1);
							} else if ($primaryContact == ($moveAuthorIndex + 1)) {
								$submitForm->setData('primaryContact', $moveAuthorIndex);
							}
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

			case 4:
				if (Request::getUserVar('submitUploadSuppFile')) {
					SubmitHandler::submitUploadSuppFile();
					return;
				}
				break;
		}

		if (!isset($editData) && $submitForm->validate()) {
			$articleId = $submitForm->execute();

			if ($step == 5) {
				$journal = &Request::getJournal();
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign_by_ref('journal', $journal);
				// If this is an editor and there is a
				// submission file, article can be expedited.
				if (Validation::isEditor($journal->getJournalId()) && $article->getSubmissionFileId()) {
					$templateMgr->assign('canExpedite', true);
				}
				$templateMgr->assign('articleId', $articleId);
				$templateMgr->assign('helpTopicId','submission.index');
				$templateMgr->display('author/submit/complete.tpl');

			} else {
				Request::redirect(null, null, 'submit', $step+1, array('articleId' => $articleId));
			}

		} else {
			$submitForm->display();
		}
	}

	/**
	 * Create new supplementary file with a uploaded file.
	 */
	function submitUploadSuppFile() {
		parent::validate();
		parent::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');

		list($journal, $article) = SubmitHandler::validate($articleId, 4);

		import("author.form.submit.AuthorSubmitSuppFileForm");
		$submitForm = &new AuthorSubmitSuppFileForm($article);
		$submitForm->setData('title', Locale::translate('common.untitled'));
		$suppFileId = $submitForm->execute();

		Request::redirect(null, null, 'submitSuppFile', $suppFileId, array('articleId' => $articleId));
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

		list($journal, $article) = SubmitHandler::validate($articleId, 4);

		import("author.form.submit.AuthorSubmitSuppFileForm");
		$submitForm = &new AuthorSubmitSuppFileForm($article, $suppFileId);

		if ($submitForm->isLocaleResubmit()) {
			$submitForm->readInputData();
		} else {
			$submitForm->initData();
		}
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

		list($journal, $article) = SubmitHandler::validate($articleId, 4);

		import("author.form.submit.AuthorSubmitSuppFileForm");
		$submitForm = &new AuthorSubmitSuppFileForm($article, $suppFileId);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();
			Request::redirect(null, null, 'submit', '4', array('articleId' => $articleId));
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

		list($journal, $article) = SubmitHandler::validate($articleId, 4);

		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$suppFile = $suppFileDao->getSuppFile($suppFileId, $articleId);
		$suppFileDao->deleteSuppFileById($suppFileId, $articleId);

		if ($suppFile->getFileId()) {
			$articleFileManager = &new ArticleFileManager($articleId);
			$articleFileManager->deleteFile($suppFile->getFileId());
		}

		Request::redirect(null, null, 'submit', '4', array('articleId' => $articleId));
	}

	function expediteSubmission() {
		$articleId = (int) Request::getUserVar('articleId');
		list($journal, $article) = SubmitHandler::validate($articleId);

		// The author must also be an editor to perform this task.
		if (Validation::isEditor($journal->getJournalId()) && $article->getSubmissionFileId()) {
			import('submission.editor.EditorAction');
			EditorAction::expediteSubmission($article);
			Request::redirect(null, 'editor', 'submissionEditing', array($article->getArticleId()));
		}

		Request::redirect(null, null, 'track');
	}

	/**
	 * Validation check for submission.
	 * Checks that article ID is valid, if specified.
	 * @param $articleId int
	 * @param $step int
	 */
	function validate($articleId = null, $step = false) {
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$user = &Request::getUser();
		$journal = &Request::getJournal();

		if ($step !== false && ($step < 1 || $step > 5 || (!isset($articleId) && $step != 1))) {
			Request::redirect(null, null, 'submit', array(1));
		}

		$article = null;

		// Check that article exists for this journal and user and that submission is incomplete
		if (isset($articleId)) {
			$article =& $articleDao->getArticle((int) $articleId);
			if (!$article || $article->getUserId() !== $user->getUserId() || $article->getJournalId() !== $journal->getJournalId() || ($step !== false && $step > $article->getSubmissionProgress())) {
				Request::redirect(null, null, 'submit');
			}
		}
		return array(&$journal, &$article);
	}
}
?>
