<?php

/**
 * SubmissionLayoutHandler.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
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
	 * View an assigned submission's layout editing page.
	 * @param $args array ($articleId)
	 */
	function submission($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);
		parent::setupTemplate(true, $articleId);

		import('submission.proofreader.ProofreaderAction');
		ProofreaderAction::layoutEditorProofreadingUnderway($articleId);
		
		$layoutAssignment = &$submission->getLayoutAssignment();
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
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('disableEdit', $disableEdit);
		$templateMgr->assign('useProofreaders', $journal->getSetting('useProofreaders'));
		$templateMgr->assign('helpTopicId', 'editorial.layoutEditorsRole.layout');
		$templateMgr->display('layoutEditor/submission.tpl');
	}
	
	/**
	 * Mark assignment as complete.
	 */
	function completeAssignment($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId, true);
		
		if (LayoutEditorAction::completeLayoutEditing($submission, Request::getUserVar('send'))) {
			Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $articleId));
		}		
	}
	

	//
	// Galley Management
	//
	
	/**
	 * Create a new galley with the uploaded file.
	 */
	function uploadGalley() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId, true);
		
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
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);
		
		parent::setupTemplate(true, $articleId, 'editing');
		
		if (SubmissionLayoutHandler::layoutEditingEnabled($submission)) {
			import('submission.form.ArticleGalleyForm');
			
			$submitForm = &new ArticleGalleyForm($articleId, $galleyId);
			
			$submitForm->initData();
			$submitForm->display();
			
		} else {
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
		
		if (Request::getUserVar('uploadImage')) {
			// Attach galley image
			$submitForm->uploadImage();
			
			parent::setupTemplate(true, $articleId);
			$submitForm->display();
		
		} else if(($deleteImage = Request::getUserVar('deleteImage')) && count($deleteImage) == 1) {
			// Delete galley image
			list($imageId) = array_keys($deleteImage);
			$submitForm->deleteImage($imageId);
			
			parent::setupTemplate(true, $articleId);
			$submitForm->display();
			
		} else if ($submitForm->validate()) {
			$submitForm->execute();
			Request::redirect('layoutEditor/submission/' . $articleId);
		
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
		
		Request::redirect('layoutEditor/submission/' . $articleId);
	}
	
	/**
	 * Change the sequence order of a galley.
	 */
	function orderGalley() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId, true);
		
		LayoutEditorAction::orderGalley($submission, Request::getUserVar('galleyId'), Request::getUserVar('d'));

		Request::redirect('layoutEditor/submission/' . $articleId);
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
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId, true);
		
		import('submission.form.SuppFileForm');
		
		$suppFileForm = &new SuppFileForm($submission);
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
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);
		
		parent::setupTemplate(true, $articleId, 'editing');
		
		if (SubmissionLayoutHandler::layoutEditingEnabled($submission)) {
			import('submission.form.SuppFileForm');
			
			$submitForm = &new SuppFileForm($submission, $suppFileId);
			
			$submitForm->initData();
			$submitForm->display();
			
			
		} else {
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
			Request::redirect('layoutEditor/submission/' . $articleId);
		
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
		
		Request::redirect('layoutEditor/submission/' . $articleId);
	}
	
	/**
	 * Change the sequence order of a supplementary file.
	 */
	function orderSuppFile() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId, true);
		
		LayoutEditorAction::orderSuppFile($submission, Request::getUserVar('suppFileId'), Request::getUserVar('d'));

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

		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);
		if (!LayoutEditorAction::downloadFile($submission, $fileId, $revision)) {
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

		list($journal, $submission) = SubmissionLayoutHandler::validate($articleId);
		if (!LayoutEditorAction::viewFile($articleId, $fileId, $revision)) {
			Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $articleId));
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
		if ($send) {
			ProofreaderAction::proofreadEmail($articleId,'PROOFREAD_LAYOUT_COMPLETE');
			Request::redirect(sprintf('layoutEditor/submission/%d', $articleId));	
		} else {
			ProofreaderAction::proofreadEmail($articleId,'PROOFREAD_LAYOUT_COMPLETE','/layoutEditor/layoutEditorProofreadingComplete/send');
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
	function &validate($articleId, $checkEdit = false) {
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
			Request::redirect(Request::getRequestedPage());
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
}

?>
