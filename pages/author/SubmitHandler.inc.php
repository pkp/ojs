<?php

/**
 * @file pages/author/SubmitHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmitHandler
 * @ingroup pages_author
 *
 * @brief Handle requests for author article submission.
 */

import('pages.author.AuthorHandler');

class SubmitHandler extends AuthorHandler {
	/** article associated with the request **/
	var $article;

	/**
	 * Constructor
	 */
	function SubmitHandler() {
		parent::AuthorHandler();
	}

	/**
	 * Display journal author article submission.
	 * Displays author index page if a valid step is not specified.
	 * @param $args array optional, if set the first parameter is the step to display
	 * @param $request PKPRequest
	 */
	function submit($args, $request) {
		$step = (int) array_shift($args);
		$articleId = (int) $request->getUserVar('articleId');
		$journal =& $request->getJournal();

		$this->validate($request, $articleId, $step, 'author.submit.authorSubmitLoginMessage');
		$article =& $this->article;
		$this->setupTemplate($request, true);

		$formClass = "AuthorSubmitStep{$step}Form";
		import("classes.author.form.submit.$formClass");

		$submitForm = new $formClass($article, $journal, $request);
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
	 * @param $request Request
	 */
	function saveSubmit($args, $request) {
		$step = (int) array_shift($args);
		$articleId = (int) $request->getUserVar('articleId');
		$journal =& $request->getJournal();

		$this->validate($request, $articleId, $step);
		$this->setupTemplate($request, true);
		$article =& $this->article;

		$formClass = "AuthorSubmitStep{$step}Form";
		import("classes.author.form.submit.$formClass");

		$submitForm = new $formClass($article, $journal, $request);
		$submitForm->readInputData();

		if (!HookRegistry::call('SubmitHandler::saveSubmit', array($step, &$article, &$submitForm))) {

			// Check for any special cases before trying to save
			switch ($step) {
				case 2:
					if ($request->getUserVar('uploadSubmissionFile')) {
						$submitForm->uploadSubmissionFile('submissionFile');
						$editData = true;
					}
					break;

				case 3:
					if ($request->getUserVar('addAuthor')) {
						// Add a sponsor
						$editData = true;
						$authors = $submitForm->getData('authors');
						array_push($authors, array());
						$submitForm->setData('authors', $authors);

					} else if (($delAuthor = $request->getUserVar('delAuthor')) && count($delAuthor) == 1) {
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

					} else if ($request->getUserVar('moveAuthor')) {
						// Move an author up/down
						$editData = true;
						$moveAuthorDir = $request->getUserVar('moveAuthorDir');
						$moveAuthorDir = $moveAuthorDir == 'u' ? 'u' : 'd';
						$moveAuthorIndex = (int) $request->getUserVar('moveAuthorIndex');
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

				case 4:
					if ($request->getUserVar('submitUploadSuppFile')) {
						$this->submitUploadSuppFile(array(), $request);
						return;
					}
					break;
			}

			if (!isset($editData) && $submitForm->validate()) {
				$articleId = $submitForm->execute();
				HookRegistry::call('Author::SubmitHandler::saveSubmit', array(&$step, &$article, &$submitForm));

				if ($step == 5) {
					// Send a notification to associated users
					import('classes.notification.NotificationManager');
					$notificationManager = new NotificationManager();
					$articleDao =& DAORegistry::getDAO('ArticleDAO');
					$article =& $articleDao->getArticle($articleId);
					$roleDao =& DAORegistry::getDAO('RoleDAO');
					$notificationUsers = array();
					$editors = $roleDao->getUsersByRoleId(ROLE_ID_EDITOR, $journal->getId());
					while ($editor =& $editors->next()) {
						$notificationManager->createNotification(
							$request, $editor->getId(), NOTIFICATION_TYPE_ARTICLE_SUBMITTED,
							$article->getJournalId(), ASSOC_TYPE_ARTICLE, $article->getId()
						);
						unset($editor);
					}

					$journal =& $request->getJournal();
					$templateMgr =& TemplateManager::getManager();
					$templateMgr->assign_by_ref('journal', $journal);
					// If this is an editor and there is a
					// submission file, article can be expedited.
					if (Validation::isEditor($journal->getId()) && $article->getSubmissionFileId()) {
						$templateMgr->assign('canExpedite', true);
					}
					$templateMgr->assign('articleId', $articleId);
					$templateMgr->assign('helpTopicId','submission.index');
					$templateMgr->display('author/submit/complete.tpl');

				} else {
					$request->redirect(null, null, 'submit', $step+1, array('articleId' => $articleId));
				}
			} else {
				$submitForm->display();
			}
		}
	}

	/**
	 * Create new supplementary file with a uploaded file.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submitUploadSuppFile($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$journal =& $request->getJournal();

		$this->validate($request, $articleId, 4);
		$article =& $this->article;
		$this->setupTemplate($request, true);

		import('classes.author.form.submit.AuthorSubmitSuppFileForm');
		$submitForm = new AuthorSubmitSuppFileForm($article, $journal);
		$submitForm->setData('title', array($article->getLocale() => __('common.untitled')));
		$suppFileId = $submitForm->execute();

		$request->redirect(null, null, 'submitSuppFile', $suppFileId, array('articleId' => $articleId));
	}

	/**
	 * Display supplementary file submission form.
	 * @param $args array optional, if set the first parameter is the supplementary file to edit
	 * @param $request PKPRequest
	 */
	function submitSuppFile($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;
		$journal =& $request->getJournal();

		$this->validate($request, $articleId, 4);
		$article =& $this->article;
		$this->setupTemplate($request, true);

		import('classes.author.form.submit.AuthorSubmitSuppFileForm');
		$submitForm = new AuthorSubmitSuppFileForm($article, $journal, $suppFileId);

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
	 * @param $request PKPRequest
	 */
	function saveSubmitSuppFile($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;
		$journal =& $request->getJournal();

		$this->validate($request, $articleId, 4);
		$article =& $this->article;
		$this->setupTemplate($request, true);

		import('classes.author.form.submit.AuthorSubmitSuppFileForm');
		$submitForm = new AuthorSubmitSuppFileForm($article, $journal, $suppFileId);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();
			$request->redirect(null, null, 'submit', '4', array('articleId' => $articleId));
		} else {
			$submitForm->display();
		}
	}

	/**
	 * Delete a supplementary file.
	 * @param $args array, the first parameter is the supplementary file to delete
	 * @param $request PKPRequest
	 */
	function deleteSubmitSuppFile($args, $request) {
		import('classes.file.ArticleFileManager');

		$articleId = (int) $request->getUserVar('articleId');
		$suppFileId =(int) array_shift($args);

		$this->validate($request, $articleId, 4);
		$article =& $this->article;
		$this->setupTemplate($request, true);

		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$suppFile = $suppFileDao->getSuppFile($suppFileId, $articleId);
		$suppFileDao->deleteSuppFileById($suppFileId, $articleId);

		if ($suppFile->getFileId()) {
			$articleFileManager = new ArticleFileManager($articleId);
			$articleFileManager->deleteFile($suppFile->getFileId());
		}

		$request->redirect(null, null, 'submit', '4', array('articleId' => $articleId));
	}

	/**
	 * Expedite a submission -- rush it through the editorial process, for
	 * users who are both authors and editors.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function expediteSubmission($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$journal =& $request->getJournal();
		$article =& $this->article;

		// The author must also be an editor to perform this task.
		if (Validation::isEditor($journal->getId()) && $article->getSubmissionFileId()) {
			import('classes.submission.editor.EditorAction');
			EditorAction::expediteSubmission($article, $request);
			$request->redirect(null, 'editor', 'submissionEditing', array($article->getId()));
		}

		$request->redirect(null, null, 'track');
	}

	/**
	 * Validation check for submission.
	 * Checks that article ID is valid, if specified.
	 * @param $articleId int
	 * @param $step int
	 */
	function validate($request, $articleId = null, $step = false, $reason = null) {
		parent::validate($reason);
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$user =& $request->getUser();
		$journal =& $request->getJournal();

		if ($step !== false && ($step < 1 || $step > 5 || (!$articleId && $step != 1))) {
			$request->redirect(null, null, 'submit', array(1));
		}

		$article = null;

		// Check that article exists for this journal and user and that submission is incomplete
		if ($articleId) {
			$article =& $articleDao->getArticle((int) $articleId);
			if (!$article || $article->getUserId() !== $user->getId() || $article->getJournalId() !== $journal->getId() || ($step !== false && $step > $article->getSubmissionProgress())) {
				$request->redirect(null, null, 'submit');
			}
		}

		$this->article =& $article;
		return true;
	}
}

?>
