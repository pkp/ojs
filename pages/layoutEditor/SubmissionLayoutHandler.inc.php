<?php

/**
 * @file SubmissionLayoutHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.layoutEditor
 * @class SubmissionLayoutHandler
 *
 * Handle requests related to submission layout editing. 
 *
 * $Id$
 */

class SubmissionLayoutHandler extends LayoutEditorHandler {

	//
	// Submission Management
	//

	/**
	 * View an assigned submission's layout editing page.
	 * @param $args array ($articleId)
	 */
	function submission($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);
		parent::setupTemplate(true, $articleId);

		import('submission.proofreader.ProofreaderAction');
		ProofreaderAction::layoutEditorProofreadingUnderway($submission);

		$layoutAssignment = &$submission->getLayoutAssignment();

		if ($layoutAssignment->getDateNotified() != null && $layoutAssignment->getDateUnderway() == null)
		{
			// Set underway date
			$layoutAssignment->setDateUnderway(Core::getCurrentDate());
			$layoutDao = &DAORegistry::getDAO('LayoutEditorSubmissionDAO');
			$layoutDao->updateSubmission($submission);
		}

		$disableEdit = !SubmissionLayoutHandler::layoutEditingEnabled($submission);

		$templateMgr = &TemplateManager::getManager();
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

	function viewMetadata($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'summary');

		LayoutEditorAction::viewMetadata($submission, ROLE_ID_LAYOUT_EDITOR);
	}

	/**
	 * Mark assignment as complete.
	 */
	function completeAssignment($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId, true);

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
	function uploadLayoutFile() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId, true);

		switch (Request::getUserVar('layoutFileType')) {
			case 'submission':
				LayoutEditorAction::uploadLayoutVersion($submission);
				Request::redirect(null, null, 'submission', $articleId);
				break;
			case 'galley':
				import('submission.form.ArticleGalleyForm');

				$galleyForm = &new ArticleGalleyForm($articleId);
				$galleyId = $galleyForm->execute('layoutFile');

				Request::redirect(null, null, 'editGalley', array($articleId, $galleyId));
				break;
			case 'supp':
				import('submission.form.SuppFileForm');

				$suppFileForm = &new SuppFileForm($submission);
				$suppFileForm->setData('title', Locale::translate('common.untitled'));
				$suppFileId = $suppFileForm->execute('layoutFile');

				Request::redirect(null, null, 'editSuppFile', array($articleId, $suppFileId));
				break;
			default:
				// Invalid upload type.
				Request::redirect(null, 'layoutEditor');
		}
	}

	/**
	 * Edit a galley.
	 * @param $args array ($articleId, $galleyId)
	 */
	function editGalley($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);

		parent::setupTemplate(true, $articleId, 'editing');

		if (SubmissionLayoutHandler::layoutEditingEnabled($submission)) {
			import('submission.form.ArticleGalleyForm');

			$submitForm = &new ArticleGalleyForm($articleId, $galleyId);

			if ($submitForm->isLocaleResubmit()) {
				$submitForm->readInputData();
			} else {
				$submitForm->initData();
			}
			$submitForm->display();

		} else {
			// View galley only
			$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
			$galley = &$galleyDao->getGalley($galleyId, $articleId);

			if (!isset($galley)) {
				Request::redirect(null, null, 'submission', $articleId);
			}

			$templateMgr = &TemplateManager::getManager();
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
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId, true);

		import('submission.form.ArticleGalleyForm');

		$submitForm = &new ArticleGalleyForm($articleId, $galleyId);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();

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
			parent::setupTemplate(true, $articleId, 'editing');
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
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId, true);

		LayoutEditorAction::deleteGalley($submission, $galleyId);

		Request::redirect(null, null, 'submission', $articleId);
	}

	/**
	 * Change the sequence order of a galley.
	 */
	function orderGalley() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId, true);

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
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);

		$templateMgr = &TemplateManager::getManager();
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
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);

		$templateMgr = &TemplateManager::getManager();
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
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);

		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$galleyDao->getGalley($galleyId, $articleId);

		import('file.ArticleFileManager'); // FIXME

		if (isset($galley)) {
			if ($galley->isHTMLGalley()) {
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign_by_ref('galley', $galley);
				if ($galley->isHTMLGalley() && $styleFile =& $galley->getStyleFile()) {
					$templateMgr->addStyleSheet(Request::url(null, 'article', 'viewFile', array(
						$articleId, $galleyId, $styleFile->getFileId()
					)));
				}
				$templateMgr->display('submission/layout/proofGalleyHTML.tpl');

			} else {
				// View non-HTML file inline
				SubmissionLayoutHandler::viewFile(array($articleId, $galley->getFileId()));
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
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);
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
	function editSuppFile($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$suppFileId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);

		parent::setupTemplate(true, $articleId, 'editing');

		if (SubmissionLayoutHandler::layoutEditingEnabled($submission)) {
			import('submission.form.SuppFileForm');

			$submitForm = &new SuppFileForm($submission, $suppFileId);

			if ($submitForm->isLocaleResubmit()) {
				$submitForm->readInputData();
			} else {
				$submitForm->initData();
			}
			$submitForm->display();


		} else {
			// View supplementary file only
			$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
			$suppFile = &$suppFileDao->getSuppFile($suppFileId, $articleId);

			if (!isset($suppFile)) {
				Request::redirect(null, null, 'submission', $articleId);
			}

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign_by_ref('suppFile', $suppFile);
			$templateMgr->display('submission/suppFile/suppFileView.tpl');	
		}
	}

	/**
	 * Save a supplementary file.
	 * @param $args array ($suppFileId)
	 */
	function saveSuppFile($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);

		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;

		import('submission.form.SuppFileForm');

		$submitForm = &new SuppFileForm($submission, $suppFileId);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();
			Request::redirect(null, null, 'submission', $articleId);

		} else {
			parent::setupTemplate(true, $articleId, 'editing');
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
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId, true);

		LayoutEditorAction::deleteSuppFile($submission, $suppFileId);

		Request::redirect(null, null, 'submission', $articleId);
	}

	/**
	 * Change the sequence order of a supplementary file.
	 */
	function orderSuppFile() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId, true);

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

		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);
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

		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);
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

		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);
		parent::setupTemplate(true, $articleId);

		$send = false;
		if (isset($args[0])) {
			$send = Request::getUserVar('send') ? true : false;
		}

		import('submission.proofreader.ProofreaderAction');
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

		$journal = &Request::getJournal();
		$user = &Request::getUser();

		$layoutDao = &DAORegistry::getDAO('LayoutEditorSubmissionDAO');
		$submission = &$layoutDao->getSubmission($articleId, $journal->getJournalId());

		if (isset($submission)) {
			$layoutAssignment = &$submission->getLayoutAssignment();
			if (!isset($layoutAssignment)) $isValid = false;
			elseif ($layoutAssignment->getEditorId() == $user->getUserId()) {
				if ($checkEdit) {
					$isValid = SubmissionLayoutHandler::layoutEditingEnabled($submission);
				} else {
					$isValid = true;
				}
			}			
		}

		if (!$isValid) {
			Request::redirect(null, Request::getRequestedPage());
		}
		return array($journal, $submission);
	}

	/**
	 * Check if a layout editor is allowed to make changes to the submission.
	 * This is allowed if there is an outstanding galley creation or layout editor
	 * proofreading request.
	 * @param $submission LayoutEditorSubmission
	 * @return boolean true if layout editor can modify the submission
	 */
	function layoutEditingEnabled(&$submission) {
		$layoutAssignment = &$submission->getLayoutAssignment();
		$proofAssignment = &$submission->getProofAssignment();

		return(($layoutAssignment->getDateNotified() != null
			&& $layoutAssignment->getDateCompleted() == null)
		|| ($proofAssignment->getDateLayoutEditorNotified() != null
			&& $proofAssignment->getDateLayoutEditorCompleted() == null));
	}

	function downloadLayoutTemplate($args) {
		parent::validate();
		$journal =& Request::getJournal();
		$templates = $journal->getSetting('templates');
		import('file.JournalFileManager');
		$journalFileManager =& new JournalFileManager($journal);
		$templateId = (int) array_shift($args);
		if ($templateId >= count($templates) || $templateId < 0) Request::redirect(null, 'layoutEditor');
		$template =& $templates[$templateId];

		$filename = "template-$templateId." . $journalFileManager->parseFileExtension($template['originalFilename']);
		$journalFileManager->downloadFile($filename, $template['fileType']);
	}
}

?>
