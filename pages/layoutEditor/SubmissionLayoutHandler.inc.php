<?php

/**
 * SubmissionLayoutHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.layoutEditor
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
	 * Show layout editing assignments.
	 * @param $args array optional ('completed')
	 */
	function assignments($args = array()) {
		parent::validate();
		parent::setupTemplate(true);
		
		$showActive = !(isset($args[0]) && $args[0] == 'completed');
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$layoutDao = &DAORegistry::getDAO('LayoutEditorSubmissionDAO');
		$submissions = &$layoutDao->getSubmissions($user->getUserId(), $journal->getJournalId(), $showActive);
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('showActive', $showActive);
		$templateMgr->assign('submissions', $submissions);
		$templateMgr->display('layoutEditor/submissions.tpl');
	}

	/**
	 * View an assigned submission's layout editing page.
	 * @param $args array ($articleId)
	 */
	function submission($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		SubmissionLayoutHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$layoutDao = &DAORegistry::getDAO('LayoutEditorSubmissionDAO');
		$submission = &$layoutDao->getSubmission($articleId);
		
		$layoutAssignment = &$submission->getLayoutAssignment();
		if ($layoutAssignment->getDateNotified() != null && $layoutAssignment->getDateUnderway() == null)
		{
			// Set underway date
			$layoutAssignment->setDateUnderway(Core::getCurrentDate());
			$layoutDao->updateSubmission($submission);
		}
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('disableEdit', ($layoutAssignment->getDateNotified() == null || $layoutAssignment->getDateCompleted() != null));
		$templateMgr->display('layoutEditor/submission.tpl');
	}
	
	/**
	 * Mark assignment as complete.
	 */
	function completeAssignment($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		SubmissionLayoutHandler::validate($articleId, true);
		
		$layoutDao = &DAORegistry::getDAO('LayoutEditorSubmissionDAO');
		$submission = &$layoutDao->getSubmission($articleId);
		$layoutAssignment = &$submission->getLayoutAssignment();
		$layoutAssignment->setDateCompleted(Core::getCurrentDate());
		$layoutDao->updateSubmission($submission);
		Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $articleId));
	}
	

	//
	// Galley Management
	//
	
	/**
	 * Create a new galley with the uploaded file.
	 */
	function uploadGalley() {
		$articleId = Request::getUserVar('articleId');
		SubmissionLayoutHandler::validate($articleId, true);
		
		import('submission.form.ArticleGalleyForm');
		
		$galleyForm = &new ArticleGalleyForm($articleId);
		$galleyId = $galleyForm->execute();
		
		Request::redirect(sprintf('%s/editGalley/%d/%d', Request::getRequestedPage(), $articleId, $galleyId));
	}
	
	/**
	 * Edit a galley.
	 * @param $args array ($articleId, $galleyId)
	 */
	function editGalley($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		SubmissionLayoutHandler::validate($articleId);
		
		parent::setupTemplate(true);
		
		$layoutDao = &DAORegistry::getDAO('LayoutEditorSubmissionDAO');
		$submission = &$layoutDao->getSubmission($articleId);
		$layoutAssignment = &$submission->getLayoutAssignment();
		
		if ($layoutAssignment->getDateNotified() == null || $layoutAssignment->getDateCompleted() != null) {
			// View galley only
			$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
			$galley = &$galleyDao->getGalley($galleyId, $articleId);
			
			if (!isset($galley)) {
				Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $articleId));
			}
			
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('galley', $galley);
			$templateMgr->display('submission/layout/galleyView.tpl');
			
		} else {
			import('submission.form.ArticleGalleyForm');
			
			$submitForm = &new ArticleGalleyForm($articleId, $galleyId);
			
			$submitForm->initData();
			$submitForm->display();
		}
	}
	
	/**
	 * Save changes to a galley.
	 * @param $args array ($articleId, $galleyId)
	 */
	function saveGalley($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		SubmissionLayoutHandler::validate($articleId, true);
		
		import('submission.form.ArticleGalleyForm');
		
		$submitForm = &new ArticleGalleyForm($articleId, $galleyId);
		$submitForm->readInputData();
		
		if (Request::getUserVar('uploadImage')) {
			// Attach galley image
			$submitForm->uploadImage();
			
			parent::setupTemplate(true);
			$submitForm->display();
		
		} else if(($deleteImage = Request::getUserVar('deleteImage')) && count($deleteImage) == 1) {
			// Delete galley image
			list($imageId) = array_keys($deleteImage);
			$submitForm->deleteImage($imageId);
			
			parent::setupTemplate(true);
			$submitForm->display();
			
		} else if ($submitForm->validate()) {
			$submitForm->execute();
			Request::redirect('layoutEditor/submission/' . $articleId);
		
		} else {
			parent::setupTemplate(true);
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
		SubmissionLayoutHandler::validate($articleId, true);
		
		SectionEditorAction::deleteGalley($articleId, $galleyId);
		
		Request::redirect('layoutEditor/submission/' . $articleId);
	}
	
	/**
	 * Change the sequence order of a galley.
	 */
	function orderGalley() {
		$articleId = Request::getUserVar('articleId');
		SubmissionLayoutHandler::validate($articleId, true);
		
		SectionEditorAction::orderGalley($articleId, Request::getUserVar('galleyId'), Request::getUserVar('d'));

		Request::redirect('layoutEditor/submission/' . $articleId);
	}
	
	/**
	 * Proof / "preview" a galley.
	 * @param $args array ($articleId, $galleyId)
	 */
	function proofGalley($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		SubmissionLayoutHandler::validate($articleId);
		
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
		SubmissionLayoutHandler::validate($articleId);
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->display('submission/layout/proofGalleyTop.tpl');
	}
	
	/**
	 * Proof galley (outputs file contents).
	 * @param $args array ($articleId, $galleyId)
	 */
	function proofGalleyFile($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		SubmissionLayoutHandler::validate($articleId);
		
		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$galleyDao->getGalley($galleyId, $articleId);
		
		import('file.ArticleFileManager'); // FIXME
		
		if (isset($galley)) {
			if ($galley->isHTMLGalley()) {
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('galley', $galley);
				$templateMgr->display('submission/layout/proofGalleyHTML.tpl');
				
			} else {
				// View non-HTML file inline
				SubmissionLayoutHandler::viewFile(array($articleId, $galley->getFileId()));
			}
		}
	}
	
	
	//
	// Supplementary File Management
	//
	
	/**
	 * Upload a new supplementary file.
	 */
	function uploadSuppFile() {
		$articleId = Request::getUserVar('articleId');
		SubmissionLayoutHandler::validate($articleId, true);
		
		import('submission.form.SuppFileForm');
		
		$suppFileForm = &new SuppFileForm($articleId);
		$suppFileForm->setData('title', Locale::translate('common.untitled'));
		$suppFileId = $suppFileForm->execute();
		
		Request::redirect(sprintf('%s/editSuppFile/%d/%d', Request::getRequestedPage(), $articleId, $suppFileId));
	}
	
	/**
	 * Edit a supplementary file.
	 * @param $args array ($articleId, $suppFileId)
	 */
	function editSuppFile($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$suppFileId = isset($args[1]) ? (int) $args[1] : 0;
		SubmissionLayoutHandler::validate($articleId);
		
		parent::setupTemplate(true);
		
		$layoutDao = &DAORegistry::getDAO('LayoutEditorSubmissionDAO');
		$submission = &$layoutDao->getSubmission($articleId);
		$layoutAssignment = &$submission->getLayoutAssignment();
		
		if ($layoutAssignment->getDateNotified() == null || $layoutAssignment->getDateCompleted() != null) {
			// View supplementary file only
			$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
			$suppFile = &$suppFileDao->getSuppFile($suppFileId, $articleId);
			
			if (!isset($suppFile)) {
				Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $articleId));
			}
			
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('suppFile', $suppFile);
			$templateMgr->display('submission/suppFile/suppFileView.tpl');
			
			
		} else {		
			import('submission.form.SuppFileForm');
			
			$submitForm = &new SuppFileForm($articleId, $suppFileId);
			
			$submitForm->initData();
			$submitForm->display();
		}
	}
	
	/**
	 * Save a supplementary file.
	 * @param $args array ($suppFileId)
	 */
	function saveSuppFile($args) {
		$articleId = Request::getUserVar('articleId');
		SubmissionLayoutHandler::validate($articleId);
		
		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;
		
		import('submission.form.SuppFileForm');
		
		$submitForm = &new SuppFileForm($articleId, $suppFileId);
		$submitForm->readInputData();
		
		if ($submitForm->validate()) {
			$submitForm->execute();
			Request::redirect('layoutEditor/submission/' . $articleId);
		
		} else {
			parent::setupTemplate(true);
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
		SubmissionLayoutHandler::validate($articleId, true);
		
		SectionEditorAction::deleteSuppFile($articleId, $suppFileId);
		
		Request::redirect('layoutEditor/submission/' . $articleId);
	}
	
	/**
	 * Change the sequence order of a supplementary file.
	 */
	function orderSuppFile() {
		$articleId = Request::getUserVar('articleId');
		SubmissionLayoutHandler::validate($articleId, true);
		
		SectionEditorAction::orderSuppFile($articleId, Request::getUserVar('suppFileId'), Request::getUserVar('d'));

		Request::redirect('layoutEditor/submission/' . $articleId);
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

		SubmissionLayoutHandler::validate($articleId);
		if (!SectionEditorAction::downloadFile($articleId, $fileId, $revision)) {
			Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $articleId));
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

		SubmissionLayoutHandler::validate($articleId);
		if (!SectionEditorAction::viewFile($articleId, $fileId, $revision)) {
			Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $articleId));
		}
	}
				

	//
	// Validation
	//
	
	/**
	 * Validate that the user is the assigned layout editor for the submission.
	 * Redirects to layoutEditor index page if validation fails.
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
			if ($layoutAssignment->getEditorId() == $user->getUserId()) {
				if ($checkEdit) {
					$isValid = ($layoutAssignment->getDateNotified() != null && $layoutAssignment->getDateCompleted() == null);
				} else {
					$isValid = true;
				}
			}			
		}
		
		if (!$isValid) {
			Request::redirect(Request::getRequestedPage());
		}
	}
	
}

?>
