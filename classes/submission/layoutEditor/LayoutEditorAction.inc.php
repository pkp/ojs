<?php

/**
 * LayoutEditorAction.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.layoutEditor.LayoutEditorAction
 *
 * LayoutEditorAction class.
 *
 * $Id$
 */

class LayoutEditorAction extends Action {
	
	//
	// Actions
	//

	/**
	 * Change the sequence order of a galley.
	 * @param $articleId int
	 * @param $galleyId int
	 * @param $direction char u = up, d = down
	 */
	function orderGalley($articleId, $galleyId, $direction) {
		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$galleyDao->getGalley($galleyId, $articleId);
		
		if (isset($galley)) {
			$galley->setSequence($galley->getSequence() + ($direction == 'u' ? -1.5 : 1.5));
			$galleyDao->updateGalley($galley);
			$galleyDao->resequenceGalleys($articleId);
		}
	}
	
	/**
	 * Delete a galley.
	 * @param $articleId int
	 * @param $galleyId int
	 */
	function deleteGalley($articleId, $galleyId) {
		import('file.ArticleFileManager');
		
		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$galleyDao->getGalley($galleyId, $articleId);
		
		if (isset($galley)) {
			$articleFileManager = &new ArticleFileManager($articleId);
			
			if ($galley->getFileId()) {
				$articleFileManager->deleteFile($galley->getFileId());
			}
			if ($galley->isHTMLGalley()) {
				if ($galley->getStyleFileId()) {
					$articleFileManager->deleteFile($galley->getStyleFileId());
				}
				foreach ($galley->getImageFiles() as $image) {
					$articleFileManager->deleteFile($image->getFileId());
				}
			}
			$galleyDao->deleteGalley($galley);
		}
	}
	
	/**
	 * Change the sequence order of a supplementary file.
	 * @param $articleId int
	 * @param $suppFileId int
	 * @param $direction char u = up, d = down
	 */
	function orderSuppFile($articleId, $suppFileId, $direction) {
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$suppFile = &$suppFileDao->getSuppFile($suppFileId, $articleId);
		
		if (isset($suppFile)) {
			$suppFile->setSequence($suppFile->getSequence() + ($direction == 'u' ? -1.5 : 1.5));
			$suppFileDao->updateSuppFile($suppFile);
			$suppFileDao->resequenceSuppFiles($articleId);
		}
	}
	
	/**
	 * Delete a supplementary file.
	 * @param $articleId int
	 * @param $suppFileId int
	 */
	function deleteSuppFile($articleId, $suppFileId) {
		import('file.ArticleFileManager');
		
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		
		$suppFile = &$suppFileDao->getSuppFile($suppFileId, $articleId);
		if (isset($suppFile)) {
			if ($suppFile->getFileId()) {
				$articleFileManager = &new ArticleFileManager($articleId);
				$articleFileManager->deleteFile($suppFile->getFileId());
			}
			$suppFileDao->deleteSuppFile($suppFile);
		}
	}
	
	/**
	 * Marks layout assignment as completed.
	 * @param $articleId int
	 * @param $send boolean
	 */
	function completeLayoutEditing($articleId, $send = false) {
		$submissionDao = &DAORegistry::getDAO('LayoutEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$email = &new ArticleMailTemplate($articleId, 'LAYOUT_COMPLETE');
		$email->setFrom($user->getEmail(), $user->getFullName());

		$submission = &$submissionDao->getSubmission($articleId);
		$layoutAssignment = &$submission->getLayoutAssignment();
		$editAssignment = &$submission->getEditor();
		$editor = &$userDao->getUser($editAssignment->getEditorId());
		
		if ($send && !$email->hasErrors()) {
			$email->setAssoc(ARTICLE_EMAIL_LAYOUT_NOTIFY_COMPLETE, ARTICLE_EMAIL_TYPE_LAYOUT, $layoutAssignment->getLayoutId());
			$email->send();
				
			$layoutAssignment->setDateCompleted(Core::getCurrentDate());
			$submissionDao->updateSubmission($submission);
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($editor->getEmail(), $editor->getFullName());
				$paramArray = array(
					'editorialContactName' => $editor->getFullName(),
					'journalName' => $journal->getSetting('journalTitle'),
					'articleTitle' => $copyeditorSubmission->getArticleTitle(),
					'layoutEditorName' => $user->getFullName()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/copyeditor/completeCopyedit/send', array('articleId' => $articleId));
		}

		// Add log entry
		$user = &Request::getUser();
		ArticleLog::logEvent($articleId, ARTICLE_LOG_LAYOUT_COMPLETE, ARTICLE_LOG_TYPE_LAYOUT, $user->getUserId(), 'log.layout.layoutEditComplete', Array('editorName' => $user->getFullName(), 'articleId' => $articleId));
	}
	
	//
	// Comments
	//
	
	/**
	 * View layout comments.
	 * @param $articleId int
	 */
	function viewLayoutComments($articleId) {
		import("submission.form.comment.LayoutCommentForm");
		
		$commentForm = new LayoutCommentForm($articleId, ROLE_ID_LAYOUT_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post layout comment.
	 * @param $articleId int
	 */
	function postLayoutComment($articleId) {
		import("submission.form.comment.LayoutCommentForm");
		
		$commentForm = new LayoutCommentForm($articleId, ROLE_ID_LAYOUT_EDITOR);
		$commentForm->readInputData();
		
		if ($commentForm->validate()) {
			$commentForm->execute();
			
		} else {
			parent::setupTemplate(true);
			$commentForm->display();
		}
	}
	
	/**
	 * View proofread comments.
	 * @param $articleId int
	 */
	function viewProofreadComments($articleId) {
		import("submission.form.comment.ProofreadCommentForm");
		
		$commentForm = new ProofreadCommentForm($articleId, ROLE_ID_LAYOUT_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post proofread comment.
	 * @param $articleId int
	 */
	function postProofreadComment($articleId) {
		import("submission.form.comment.ProofreadCommentForm");
		
		$commentForm = new ProofreadCommentForm($articleId, ROLE_ID_LAYOUT_EDITOR);
		$commentForm->readInputData();
		
		if ($commentForm->validate()) {
			$commentForm->execute();
			
		} else {
			parent::setupTemplate(true);
			$commentForm->display();
		}
	}
	
	//
	// Misc
	//
	
	/**
	 * Download a file a layout editor has access to.
	 * This includes: The layout editor submission file, supplementary files, and galley files.
	 * @param $articleId int
	 * @parma $fileId int
	 * @param $revision int optional
	 * @return boolean
	 */
	function downloadFile($articleId, $fileId, $revision = null) {
		$canDownload = false;
		
		$layoutDao = &DAORegistry::getDAO('LayoutAssignmentDao');
		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$suppDao = &DAORegistry::getDAO('SuppFileDAO');
		
		$layoutAssignment = &$layoutDao->getLayoutAssignmentByArticleId($articleId);
		
		if ($layoutAssignment->getLayoutFileId() == $fileId) {
			$canDownload = true;
			
		} else if($galleyDao->galleyExistsByFileId($articleId, $fileId)) {
			$canDownload = true;
			
		} else if($suppDao->suppFileExistsByFileId($articleId, $fileId)) {
			$canDownload = true;
		}
		
		if ($canDownload) {
			return parent::downloadFile($articleId, $fileId, $revision);
		} else {
			return false;
		}
	}
}

?>
