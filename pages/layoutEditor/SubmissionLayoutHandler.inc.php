<?php

/**
 * @file SubmissionLayoutHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionLayoutHandler
 * @ingroup pages_layoutEditor
 *
 * @brief Handle requests related to submission layout editing.
 */

// $Id$

import('pages.layoutEditor.LayoutEditorHandler');

class SubmissionLayoutHandler extends LayoutEditorHandler {
	/** journal associated with the request **/
	var $journal;

	/** submission associated with the request **/
	var $submission;

	/**
	 * Constructor
	 **/
	function SubmissionLayoutHandler() {
		parent::LayoutEditorHandler();
	}

	//
	// Submission Management
	//

	/**
	 * View an assigned submission's layout editing page.
	 * @param $args array ($articleId)
	 */
	function submission($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$journal =& Request::getJournal();

		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);
		$submission =& $submissionLayoutHandler->submission;
		$this->setupTemplate(true, $articleId);
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');

		import('classes.submission.proofreader.ProofreaderAction');
		ProofreaderAction::proofreadingUnderway($submission, 'SIGNOFF_PROOFREADING_LAYOUT');

		$layoutSignoff = $signoffDao->build('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $articleId);

		if ($layoutSignoff->getDateNotified() != null && $layoutSignoff->getDateUnderway() == null)
		{
			// Set underway date
			$layoutSignoff->setDateUnderway(Core::getCurrentDate());
			$signoffDao->updateObject($layoutSignoff);
		}

		$disableEdit = !$this->layoutEditingEnabled($submission);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign('disableEdit', $disableEdit);
		$templateMgr->assign('useProofreaders', $journal->getSetting('useProofreaders'));
		$templateMgr->assign('templates', $journal->getSetting('templates'));
		$templateMgr->assign('helpTopicId', 'editorial.layoutEditorsRole.layout');

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($submission->getArticleId());
		if ($publishedArticle) {
			$issueDao =& DAORegistry::getDAO('IssueDAO');
			$issue =& $issueDao->getIssueById($publishedArticle->getIssueId());
			$templateMgr->assign_by_ref('publishedArticle', $publishedArticle);
			$templateMgr->assign_by_ref('issue', $issue);
		}

		$templateMgr->display('layoutEditor/submission.tpl');
	}

	function viewMetadata($args, $request) {
		$articleId = (int) array_shift($args);
		$journal =& $request->getJournal();
		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);
		$submission =& $submissionLayoutHandler->submission;
		$this->setupTemplate(true, $articleId, 'summary');

		LayoutEditorAction::viewMetadata($submission, $journal);
	}

	/**
	 * Mark assignment as complete.
	 */
	function completeAssignment($args) {
		$articleId = Request::getUserVar('articleId');
		$this->setupTemplate(true, $articleId, 'editing');
		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);
		$submission =& $submissionLayoutHandler->submission;

		if (LayoutEditorAction::completeLayoutEditing($submission, Request::getUserVar('send'))) {
			Request::redirect(null, null, 'submission', $articleId);
		}
	}


	//
	// Galley Management
	//

	/**
	 * Create a new layout file (layout version, galley, or supp file) with the uploaded file.
	 */
	function uploadLayoutFile($args, $request) {
		$articleId = $request->getUserVar('articleId');
		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);
		$submission =& $submissionLayoutHandler->submission;

		switch ($request->getUserVar('layoutFileType')) {
			case 'submission':
				LayoutEditorAction::uploadLayoutVersion($submission);
				$request->redirect(null, null, 'submission', $articleId);
				break;
			case 'galley':
				import('classes.submission.form.ArticleGalleyForm');

				$galleyForm = new ArticleGalleyForm($articleId);
				$galleyId = $galleyForm->execute('layoutFile');

				$request->redirect(null, null, 'editGalley', array($articleId, $galleyId));
				break;
			case 'supp':
				import('classes.submission.form.SuppFileForm');
				$journal =& $request->getJournal();
				$suppFileForm = new SuppFileForm($submission, $journal);
				$suppFileForm->setData('title', array($submission->getLocale() => Locale::translate('common.untitled')));
				$suppFileId = $suppFileForm->execute('layoutFile');

				$request->redirect(null, null, 'editSuppFile', array($articleId, $suppFileId));
				break;
			default:
				// Invalid upload type.
				$request->redirect(null, 'layoutEditor');
		}
	}

	/**
	 * Edit a galley.
	 * @param $args array ($articleId, $galleyId)
	 */
	function editGalley($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);
		$submission =& $submissionLayoutHandler->submission;

		$this->setupTemplate(true, $articleId, 'editing');

		if ($this->layoutEditingEnabled($submission)) {
			import('classes.submission.form.ArticleGalleyForm');

			$submitForm = new ArticleGalleyForm($articleId, $galleyId);

			if ($submitForm->isLocaleResubmit()) {
				$submitForm->readInputData();
			} else {
				$submitForm->initData();
			}
			$submitForm->display();

		} else {
			// View galley only
			$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
			$galley =& $galleyDao->getGalley($galleyId, $articleId);

			if (!isset($galley)) {
				Request::redirect(null, null, 'submission', $articleId);
			}

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign_by_ref('galley', $galley);
			$templateMgr->display('submission/layout/galleyView.tpl');
		}
	}

	/**
	 * Save changes to a galley.
	 * @param $args array ($articleId, $galleyId)
	 */
	function saveGalley($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);
		$this->setupTemplate(true, $articleId, 'editing');

		import('classes.submission.form.ArticleGalleyForm');

		$submitForm = new ArticleGalleyForm($articleId, $galleyId);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();

			// Send a notification to associated users
			import('lib.pkp.classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$article =& $articleDao->getArticle($articleId);
			$notificationUsers = $article->getAssociatedUserIds(true, false);
			foreach ($notificationUsers as $userRole) {
				$url = Request::url(null, $userRole['role'], 'submissionEditing', $article->getId(), null, 'layout');
				$notificationManager->createNotification(
					$userRole['id'], 'notification.type.galleyModified',
					$article->getLocalizedTitle(), $url, 1, NOTIFICATION_TYPE_GALLEY_MODIFIED
				);
			}

			if (Request::getUserVar('uploadImage')) {
				$submitForm->uploadImage();
				Request::redirect(null, null, 'editGalley', array($articleId, $galleyId));
			} else if(($deleteImage = Request::getUserVar('deleteImage')) && count($deleteImage) == 1) {
				list($imageId) = array_keys($deleteImage);
				$submitForm->deleteImage($imageId);
				Request::redirect(null, null, 'editGalley', array($articleId, $galleyId));
			}
			Request::redirect(null, null, 'submission', $articleId);
		} else {
			$submitForm->display();
		}
	}

	/**
	 * Delete a galley file.
	 * @param $args array ($articleId, $galleyId)
	 */
	function deleteGalley($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);
		$submission =& $submissionLayoutHandler->submission;

		LayoutEditorAction::deleteGalley($submission, $galleyId);

		Request::redirect(null, null, 'submission', $articleId);
	}

	/**
	 * Change the sequence order of a galley.
	 */
	function orderGalley() {
		$articleId = Request::getUserVar('articleId');
		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);
		$submission =& $submissionLayoutHandler->submission;

		LayoutEditorAction::orderGalley($submission, Request::getUserVar('galleyId'), Request::getUserVar('d'));

		Request::redirect(null, null, 'submission', $articleId);
	}

	/**
	 * Proof / "preview" a galley.
	 * @param $args array ($articleId, $galleyId)
	 */
	function proofGalley($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->display('submission/layout/proofGalley.tpl');
	}

	/**
	 * Proof galley (shows frame header).
	 * @param $args array ($articleId, $galleyId)
	 */
	function proofGalleyTop($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('backHandler', 'submissionEditing');
		$templateMgr->display('submission/layout/proofGalleyTop.tpl');
	}

	/**
	 * Proof galley (outputs file contents).
	 * @param $args array ($articleId, $galleyId)
	 */
	function proofGalleyFile($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);

		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId, $articleId);

		import('classes.file.ArticleFileManager'); // FIXME

		if (isset($galley)) {
			if ($galley->isHTMLGalley()) {
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign_by_ref('galley', $galley);
				if ($galley->isHTMLGalley() && $styleFile =& $galley->getStyleFile()) {
					$templateMgr->addStyleSheet(Request::url(null, 'article', 'viewFile', array(
						$articleId, $galleyId, $styleFile->getFileId()
					)));
				}
				$templateMgr->display('submission/layout/proofGalleyHTML.tpl');

			} else {
				// View non-HTML file inline
				$this->viewFile(array($articleId, $galley->getFileId()));
			}
		}
	}

	/**
	 * Delete an article image.
	 * @param $args array ($articleId, $fileId)
	 */
	function deleteArticleImage($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$fileId = isset($args[2]) ? (int) $args[2] : 0;
		$revisionId = isset($args[3]) ? (int) $args[3] : 0;
		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);
		$submission =& $submissionLayoutHandler->submission;
		LayoutEditorAction::deleteArticleImage($submission, $fileId, $revisionId);

		Request::redirect(null, null, 'editGalley', array($articleId, $galleyId));
	}


	//
	// Supplementary File Management
	//


	/**
	 * Edit a supplementary file.
	 * @param $args array ($articleId, $suppFileId)
	 */
	function editSuppFile($args, $request) {
		$articleId = (int) array_shift($args);
		$suppFileId = (int) array_shift($args);
		$journal =& $request->getJournal();

		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);
		$submission =& $submissionLayoutHandler->submission;

		$this->setupTemplate(true, $articleId, 'editing');

		if ($this->layoutEditingEnabled($submission)) {
			import('classes.submission.form.SuppFileForm');

			$submitForm = new SuppFileForm($submission, $journal, $suppFileId);

			if ($submitForm->isLocaleResubmit()) {
				$submitForm->readInputData();
			} else {
				$submitForm->initData();
			}
			$submitForm->display();
		} else {
			// View supplementary file only
			$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
			$suppFile =& $suppFileDao->getSuppFile($suppFileId, $articleId);

			if (!isset($suppFile)) {
				$request->redirect(null, null, 'submission', $articleId);
			}

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign_by_ref('suppFile', $suppFile);
			$templateMgr->display('submission/suppFile/suppFileView.tpl');
		}
	}

	/**
	 * Save a supplementary file.
	 * @param $args array ($suppFileId)
	 */
	function saveSuppFile($args, $request) {
		$articleId = $request->getUserVar('articleId');
		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);
		$submission =& $submissionLayoutHandler->submission;
		$this->setupTemplate(true, $articleId, 'editing');

		$suppFileId = (int) array_shift($args);
		$journal =& $request->getJournal();

		import('classes.submission.form.SuppFileForm');

		$submitForm = new SuppFileForm($submission, $journal, $suppFileId);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();

			// Send a notification to associated users
			import('lib.pkp.classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$article =& $articleDao->getArticle($articleId);
			$notificationUsers = $article->getAssociatedUserIds(true, false);
			foreach ($notificationUsers as $userRole) {
				$url = $request->url(null, $userRole['role'], 'submissionEditing', $article->getId(), null, 'layout');
				$notificationManager->createNotification(
					$userRole['id'], 'notification.type.suppFileModified',
					$article->getLocalizedTitle(), $url, 1, NOTIFICATION_TYPE_SUPP_FILE_MODIFIED
				);
			}

			$request->redirect(null, null, 'submission', $articleId);
		} else {
			$submitForm->display();
		}
	}

	/**
	 * Delete a supplementary file.
	 * @param $args array ($articleId, $suppFileId)
	 */
	function deleteSuppFile($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$suppFileId = isset($args[1]) ? (int) $args[1] : 0;
		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);
		$submission =& $submissionLayoutHandler->submission;

		LayoutEditorAction::deleteSuppFile($submission, $suppFileId);

		Request::redirect(null, null, 'submission', $articleId);
	}

	/**
	 * Change the sequence order of a supplementary file.
	 */
	function orderSuppFile() {
		$articleId = Request::getUserVar('articleId');
		$submissionLayoutHandler = new SubmissionLayoutHandler();
		$submissionLayoutHandler->validate($articleId);
		$submission =& $submissionLayoutHandler->submission;

		LayoutEditorAction::orderSuppFile($submission, Request::getUserVar('suppFileId'), Request::getUserVar('d'));

		Request::redirect(null, null, 'submission', $articleId);
	}


	//
	// File Access
	//

	/**
	 * Download a file.
	 * @param $args array ($articleId, $fileId, [$revision])
	 */
	function downloadFile($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$fileId = isset($args[1]) ? $args[1] : 0;
		$revision = isset($args[2]) ? $args[2] : null;

		if($this->validate($articleId)) {
			$journal =& $this->journal;
			$submission =& $this->submission;
		}
		if (!LayoutEditorAction::downloadFile($submission, $fileId, $revision)) {
			Request::redirect(null, null, 'submission', $articleId);
		}
	}

	/**
	 * View a file (inlines file).
	 * @param $args array ($articleId, $fileId, [$revision])
	 */
	function viewFile($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$fileId = isset($args[1]) ? $args[1] : 0;
		$revision = isset($args[2]) ? $args[2] : null;

		if($this->validate($articleId)) {
			$journal =& $this->journal;
			$submission =& $this->submission;
		}
		if (!LayoutEditorAction::viewFile($articleId, $fileId, $revision)) {
			Request::redirect(null, null, 'submission', $articleId);
		}
	}

	//
	// Proofreading
	//

	/**
	 * Sets the date of layout editor proofreading completion
	 */
	function layoutEditorProofreadingComplete($args) {
		$articleId = Request::getUserVar('articleId');

		list($journal, $submission) = $this->validate($articleId);
		$this->setupTemplate(true, $articleId);

		$send = false;
		if (isset($args[0])) {
			$send = Request::getUserVar('send') ? true : false;
		}

		import('classes.submission.proofreader.ProofreaderAction');
		if (ProofreaderAction::proofreadEmail($articleId,'PROOFREAD_LAYOUT_COMPLETE', $send?'':Request::url(null, 'layoutEditor', 'layoutEditorProofreadingComplete', 'send'))) {
			Request::redirect(null, null, 'submission', $articleId);
		}
	}


	//
	// Validation
	//

	/**
	 * Validate that the user is the assigned layout editor for the submission.
	 * Redirects to layoutEditor index page if validation fails.
	 * @param $articleId int the submission being edited
	 * @param $checkEdit boolean check if editor has editing permissions
	 */
	function validate($articleId, $checkEdit = false) {
		parent::validate();

		$isValid = false;

		$journal =& Request::getJournal();
		$user =& Request::getUser();

		$layoutDao =& DAORegistry::getDAO('LayoutEditorSubmissionDAO');
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$submission =& $layoutDao->getSubmission($articleId, $journal->getId());

		if (isset($submission)) {
			$layoutSignoff = $signoffDao->getBySymbolic('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $articleId);
			if (!isset($layoutSignoff)) $isValid = false;
			elseif ($layoutSignoff->getUserId() == $user->getId()) {
				if ($checkEdit) {
					$isValid = $this->layoutEditingEnabled($submission);
				} else {
					$isValid = true;
				}
			}
		}

		if (!$isValid) {
			Request::redirect(null, Request::getRequestedPage());
		}

		$this->journal =& $journal;
		$this->submission =& $submission;
		return true;
	}

	/**
	 * Check if a layout editor is allowed to make changes to the submission.
	 * This is allowed if there is an outstanding galley creation or layout editor
	 * proofreading request.
	 * @param $submission LayoutEditorSubmission
	 * @return boolean true if layout editor can modify the submission
	 */
	function layoutEditingEnabled(&$submission) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$layoutEditorProofreadSignoff = $signoffDao->build('SIGNOFF_PROOFREADING_LAYOUT', ASSOC_TYPE_ARTICLE, $submission->getArticleId());
		$layoutSignoff = $signoffDao->build('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $submission->getArticleId());

		return(($layoutSignoff->getDateNotified() != null
			&& $layoutSignoff->getDateCompleted() == null)
		|| ($layoutEditorProofreadSignoff->getDateNotified() != null
			&& $layoutEditorProofreadSignoff->getDateCompleted() == null));
	}

	function downloadLayoutTemplate($args) {
		parent::validate();
		$journal =& Request::getJournal();
		$templates = $journal->getSetting('templates');
		import('classes.file.JournalFileManager');
		$journalFileManager = new JournalFileManager($journal);
		$templateId = (int) array_shift($args);
		if ($templateId >= count($templates) || $templateId < 0) Request::redirect(null, 'layoutEditor');
		$template =& $templates[$templateId];

		$filename = "template-$templateId." . $journalFileManager->parseFileExtension($template['originalFilename']);
		$journalFileManager->downloadFile($filename, $template['fileType']);
	}
}

?>
