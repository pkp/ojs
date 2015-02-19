<?php

/**
 * @file pages/layoutEditor/SubmissionLayoutHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionLayoutHandler
 * @ingroup pages_layoutEditor
 *
 * @brief Handle requests related to submission layout editing.
 */

import('pages.layoutEditor.LayoutEditorHandler');

class SubmissionLayoutHandler extends LayoutEditorHandler {
	/**
	 * Constructor
	 */
	function SubmissionLayoutHandler() {
		parent::LayoutEditorHandler();
	}

	//
	// Submission Management
	//

	/**
	 * View an assigned submission's layout editing page.
	 * @param $args array ($articleId)
	 * @param $request PKPRequest
	 */
	function submission($args, &$request) {
		$articleId = (int) array_shift($args);
		$journal =& $request->getJournal();

		$this->validate($request, $articleId);
		$submission =& $this->submission;
		$this->setupTemplate(true, $articleId);
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');

		import('classes.submission.proofreader.ProofreaderAction');
		ProofreaderAction::proofreadingUnderway($submission, 'SIGNOFF_PROOFREADING_LAYOUT');

		$layoutSignoff = $signoffDao->build('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $articleId);

		if ($layoutSignoff->getDateNotified() != null && $layoutSignoff->getDateUnderway() == null) {
			// Set underway date
			$layoutSignoff->setDateUnderway(Core::getCurrentDate());
			$signoffDao->updateObject($layoutSignoff);
		}

		$disableEdit = !$this->_layoutEditingEnabled($submission);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign('disableEdit', $disableEdit);
		$templateMgr->assign('useProofreaders', $journal->getSetting('useProofreaders'));
		$templateMgr->assign('templates', $journal->getSetting('templates'));
		$templateMgr->assign('helpTopicId', 'editorial.layoutEditorsRole.layout');

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($submission->getId());
		if ($publishedArticle) {
			$issueDao =& DAORegistry::getDAO('IssueDAO');
			$issue =& $issueDao->getIssueById($publishedArticle->getIssueId());
			$templateMgr->assign_by_ref('publishedArticle', $publishedArticle);
			$templateMgr->assign_by_ref('issue', $issue);
		}

		$templateMgr->display('layoutEditor/submission.tpl');
	}

	/**
	 * View submission metadata.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewMetadata($args, $request) {
		$articleId = (int) array_shift($args);
		$journal =& $request->getJournal();
		$this->validate($request, $articleId);
		$this->setupTemplate(true, $articleId, 'summary');
		LayoutEditorAction::viewMetadata($this->submission, $journal);
	}

	/**
	 * Mark assignment as complete.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function completeAssignment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->setupTemplate(true, $articleId, 'editing');
		$this->validate($request, $articleId);
		if (LayoutEditorAction::completeLayoutEditing($this->submission, $request->getUserVar('send'), $request)) {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}


	//
	// Galley Management
	//

	/**
	 * Create a new layout file (layout version, galley, or supp file) with the uploaded file.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function uploadLayoutFile($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$submission =& $this->submission;

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
				$suppFileForm->setData('title', array($submission->getLocale() => __('common.untitled')));
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
	 * @param $request PKPRequest
	 */
	function editGalley($args, &$request) {
		$articleId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($request, $articleId);
		$submission =& $this->submission;

		$this->setupTemplate(true, $articleId, 'editing');

		if ($this->_layoutEditingEnabled($submission)) {
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
				$request->redirect(null, null, 'submission', $articleId);
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
	 * @param $request Request
	 */
	function saveGalley($args, $request) {
		$articleId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($request, $articleId);
		$this->setupTemplate(true, $articleId, 'editing');

		import('classes.submission.form.ArticleGalleyForm');

		$submitForm = new ArticleGalleyForm($articleId, $galleyId);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();

			// Send a notification to associated users
			import('classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$article =& $articleDao->getArticle($articleId);
			$notificationUsers = $article->getAssociatedUserIds(true, false);
			foreach ($notificationUsers as $userRole) {
				$notificationManager->createNotification(
					$request, $userRole['id'], NOTIFICATION_TYPE_GALLEY_MODIFIED,
					$article->getJournalId(), ASSOC_TYPE_ARTICLE, $article->getId()
				);
			}

			if ($request->getUserVar('uploadImage')) {
				$submitForm->uploadImage();
				$request->redirect(null, null, 'editGalley', array($articleId, $galleyId));
			} else if(($deleteImage = $request->getUserVar('deleteImage')) && count($deleteImage) == 1) {
				list($imageId) = array_keys($deleteImage);
				$submitForm->deleteImage($imageId);
				$request->redirect(null, null, 'editGalley', array($articleId, $galleyId));
			}
			$request->redirect(null, null, 'submission', $articleId);
		} else {
			$submitForm->display();
		}
	}

	/**
	 * Delete a galley file.
	 * @param $args array ($articleId, $galleyId)
	 * @param $request PKPRequest
	 */
	function deleteGalley($args, &$request) {
		$articleId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($request, $articleId);
		$submission =& $this->submission;

		LayoutEditorAction::deleteGalley($submission, $galleyId);

		$request->redirect(null, null, 'submission', $articleId);
	}

	/**
	 * Change the sequence order of a galley.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function orderGalley($args, &$request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$submission =& $this->submission;

		LayoutEditorAction::orderGalley($submission, $request->getUserVar('galleyId'), $request->getUserVar('d'));

		$request->redirect(null, null, 'submission', $articleId);
	}

	/**
	 * Proof / "preview" a galley.
	 * @param $args array ($articleId, $galleyId)
	 * @param $request PKPRequest
	 */
	function proofGalley($args, &$request) {
		$articleId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($request, $articleId);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->display('submission/layout/proofGalley.tpl');
	}

	/**
	 * Proof galley (shows frame header).
	 * @param $args array ($articleId, $galleyId)
	 * @param $request PKPRequest
	 */
	function proofGalleyTop($args, &$request) {
		$articleId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($request, $articleId);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('backHandler', 'submissionEditing');
		$templateMgr->display('submission/layout/proofGalleyTop.tpl');
	}

	/**
	 * Proof galley (outputs file contents).
	 * @param $args array ($articleId, $galleyId)
	 * @param $request PKPRequest
	 */
	function proofGalleyFile($args, &$request) {
		$articleId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($request, $articleId);

		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId, $articleId);

		import('classes.file.ArticleFileManager'); // FIXME

		if (isset($galley)) {
			if ($galley->isHTMLGalley()) {
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign_by_ref('galley', $galley);
				if ($galley->isHTMLGalley() && $styleFile =& $galley->getStyleFile()) {
					$templateMgr->addStyleSheet($request->url(null, 'article', 'viewFile', array(
						$articleId, $galleyId, $styleFile->getFileId()
					)));
				}
				$templateMgr->display('submission/layout/proofGalleyHTML.tpl');
			} else {
				// View non-HTML file inline
				$this->viewFile(array($articleId, $galley->getFileId()), $request);
			}
		}
	}

	/**
	 * Delete an article image.
	 * @param $args array ($articleId, $fileId)
	 * @param $request PKPRequest
	 */
	function deleteArticleImage($args, &$request) {
		$articleId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revisionId = (int) array_shift($args);
		$this->validate($request, $articleId);
		LayoutEditorAction::deleteArticleImage($this->submission, $fileId, $revisionId);

		$request->redirect(null, null, 'editGalley', array($articleId, $galleyId));
	}


	//
	// Supplementary File Management
	//


	/**
	 * Edit a supplementary file.
	 * @param $args array ($articleId, $suppFileId)
	 * @param $request PKPRequest
	 */
	function editSuppFile($args, $request) {
		$articleId = (int) array_shift($args);
		$suppFileId = (int) array_shift($args);
		$journal =& $request->getJournal();

		$this->validate($request, $articleId);
		$submission =& $this->submission;

		$this->setupTemplate(true, $articleId, 'editing');

		if ($this->_layoutEditingEnabled($submission)) {
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
	 * @param $request Request
	 */
	function saveSuppFile($args, $request) {
		$articleId = $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		$submission =& $this->submission;
		$this->setupTemplate(true, $articleId, 'editing');

		$suppFileId = (int) array_shift($args);
		$journal =& $request->getJournal();

		import('classes.submission.form.SuppFileForm');

		$submitForm = new SuppFileForm($submission, $journal, $suppFileId);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();

			// Send a notification to associated users
			import('classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$article =& $articleDao->getArticle($articleId);
			$notificationUsers = $article->getAssociatedUserIds(true, false);
			foreach ($notificationUsers as $userRole) {
				$notificationManager->createNotification(
					$request, $userRole['id'], NOTIFICATION_TYPE_SUPP_FILE_MODIFIED,
					$article->getJournalId(), ASSOC_TYPE_ARTICLE, $article->getId()
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
	 * @param $request PKPRequest
	 */
	function deleteSuppFile($args, &$request) {
		$articleId = (int) array_shift($args);
		$suppFileId = (int) array_shift($args);
		$this->validate($request, $articleId);
		LayoutEditorAction::deleteSuppFile($this->submission, $suppFileId);
		$request->redirect(null, null, 'submission', $articleId);
	}

	/**
	 * Change the sequence order of a supplementary file.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function orderSuppFile($args, &$request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($request, $articleId);
		LayoutEditorAction::orderSuppFile($this->submission, $request->getUserVar('suppFileId'), $request->getUserVar('d'));
		$request->redirect(null, null, 'submission', $articleId);
	}


	//
	// File Access
	//

	/**
	 * Download a file.
	 * @param $args array ($articleId, $fileId, [$revision])
	 * @param $request PKPRequest
	 */
	function downloadFile($args, &$request) {
		$articleId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = array_shift($args); // Can be null

		if($this->validate($request, $articleId)) {
			$journal =& $request->getJournal();
			$submission =& $this->submission;
		}
		if (!LayoutEditorAction::downloadFile($submission, $fileId, $revision)) {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}

	/**
	 * View a file (inlines file).
	 * @param $args array ($articleId, $fileId, [$revision])
	 * @param $request PKPRequest
	 */
	function viewFile($args, &$request) {
		$articleId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = array_shift($args); // Can be null

		if($this->validate($request, $articleId)) {
			$journal =& $request->getJournal();
			$submission =& $this->submission;
		}
		if (!LayoutEditorAction::viewFile($articleId, $fileId, $revision)) {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}

	//
	// Proofreading
	//

	/**
	 * Sets the date of layout editor proofreading completion
	 * @param $args array
	 * @param $request Request
	 */
	function layoutEditorProofreadingComplete($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');

		list($journal, $submission) = $this->validate($request, $articleId);
		$this->setupTemplate(true, $articleId);

		$send = false;
		if (isset($args[0])) {
			$send = $request->getUserVar('send') ? true : false;
		}

		import('classes.submission.proofreader.ProofreaderAction');
		if (ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_LAYOUT_COMPLETE', $request, $send?'':$request->url(null, 'layoutEditor', 'layoutEditorProofreadingComplete', 'send'))) {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}


	/**
	 * Download a layout template.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function downloadLayoutTemplate($args, &$request) {
		parent::validate($request);
		$journal =& $request->getJournal();
		$templates = $journal->getSetting('templates');
		import('classes.file.JournalFileManager');
		$journalFileManager = new JournalFileManager($journal);
		$templateId = (int) array_shift($args);
		if ($templateId >= count($templates) || $templateId < 0) $request->redirect(null, 'layoutEditor');
		$template =& $templates[$templateId];

		$filename = "template-$templateId." . $journalFileManager->parseFileExtension($template['originalFilename']);
		$journalFileManager->downloadFile($filename, $template['fileType']);
	}
}

?>
