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

import('submission.common.Action');

class LayoutEditorAction extends Action {
	
	//
	// Actions
	//

	/**
	 * Change the sequence order of a galley.
	 * @param $article object
	 * @param $galleyId int
	 * @param $direction char u = up, d = down
	 */
	function orderGalley($article, $galleyId, $direction) {
		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$galleyDao->getGalley($galleyId, $article->getArticleId());
		
		if (isset($galley)) {
			$galley->setSequence($galley->getSequence() + ($direction == 'u' ? -1.5 : 1.5));
			$galleyDao->updateGalley($galley);
			$galleyDao->resequenceGalleys($article->getArticleId());
		}
	}
	
	/**
	 * Delete a galley.
	 * @param $article object
	 * @param $galleyId int
	 */
	function deleteGalley($article, $galleyId) {
		import('file.ArticleFileManager');
		
		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$galleyDao->getGalley($galleyId, $article->getArticleId());
		
		if (isset($galley)) {
			$articleFileManager = &new ArticleFileManager($article->getArticleId());
			
			if ($galley->getFileId()) {
				$articleFileManager->deleteFile($galley->getFileId());
				import('search.ArticleSearchIndex');
				ArticleSearchIndex::deleteTextIndex($article->getArticleId(), ARTICLE_SEARCH_GALLEY_FILE, $galley->getFileId());
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
	 * @param $article object
	 * @param $suppFileId int
	 * @param $direction char u = up, d = down
	 */
	function orderSuppFile($article, $suppFileId, $direction) {
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$suppFile = &$suppFileDao->getSuppFile($suppFileId, $article->getArticleId());
		
		if (isset($suppFile)) {
			$suppFile->setSequence($suppFile->getSequence() + ($direction == 'u' ? -1.5 : 1.5));
			$suppFileDao->updateSuppFile($suppFile);
			$suppFileDao->resequenceSuppFiles($article->getArticleId());
		}
	}
	
	/**
	 * Delete a supplementary file.
	 * @param $article object
	 * @param $suppFileId int
	 */
	function deleteSuppFile($article, $suppFileId) {
		import('file.ArticleFileManager');
		
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		
		$suppFile = &$suppFileDao->getSuppFile($suppFileId, $article->getArticleId());
		if (isset($suppFile)) {
			if ($suppFile->getFileId()) {
				$articleFileManager = &new ArticleFileManager($article->getArticleId());
				$articleFileManager->deleteFile($suppFile->getFileId());
				import('search.ArticleSearchIndex');
				ArticleSearchIndex::deleteTextIndex($article->getArticleId(), ARTICLE_SEARCH_SUPPLEMENTARY_FILE, $suppFile->getFileId());
			}
			$suppFileDao->deleteSuppFile($suppFile);
		}
	}
	
	/**
	 * Marks layout assignment as completed.
	 * @param $submission object
	 * @param $send boolean
	 */
	function completeLayoutEditing($submission, $send = false) {
		$submissionDao = &DAORegistry::getDAO('LayoutEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		
		$layoutAssignment = &$submission->getLayoutAssignment();
		if ($layoutAssignment->getDateCompleted() != null) {
			return true;
		}
		
		$user = &Request::getUser();
		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($submission, 'LAYOUT_COMPLETE');
		$email->setFrom($user->getEmail(), $user->getFullName());

		$editAssignment = &$submission->getEditor();
		$editor = &$userDao->getUser($editAssignment->getEditorId());
		if (!isset($editor)) return;
		
		if ($send && !$email->hasErrors()) {
			$email->setAssoc(ARTICLE_EMAIL_LAYOUT_NOTIFY_COMPLETE, ARTICLE_EMAIL_TYPE_LAYOUT, $layoutAssignment->getLayoutId());
			$email->send();
				
			$layoutAssignment->setDateCompleted(Core::getCurrentDate());
			$submissionDao->updateSubmission($submission);

			// Add log entry
			$user = &Request::getUser();
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($submission->getArticleId(), ARTICLE_LOG_LAYOUT_COMPLETE, ARTICLE_LOG_TYPE_LAYOUT, $user->getUserId(), 'log.layout.layoutEditComplete', Array('editorName' => $user->getFullName(), 'articleId' => $submission->getArticleId()));
			
			return true;
			
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($editor->getEmail(), $editor->getFullName());
				$paramArray = array(
					'editorialContactName' => $editor->getFullName(),
					'layoutEditorName' => $user->getFullName()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/layoutEditor/completeAssignment/send', array('articleId' => $submission->getArticleId()));

			return false;
		}
	}
	
	//
	// Comments
	//
	
	/**
	 * View layout comments.
	 * @param $article object
	 */
	function viewLayoutComments($article) {
		import("submission.form.comment.LayoutCommentForm");
		
		$commentForm = new LayoutCommentForm($article, ROLE_ID_LAYOUT_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post layout comment.
	 * @param $article object
	 */
	function postLayoutComment($article, $emailComment) {
		import("submission.form.comment.LayoutCommentForm");
		
		$commentForm = new LayoutCommentForm($article, ROLE_ID_LAYOUT_EDITOR);
		$commentForm->readInputData();
		
		if ($commentForm->validate()) {
			$commentForm->execute();
			
			if ($emailComment) {
				$commentForm->email();
			}
			
		} else {
			parent::setupTemplate(true);
			$commentForm->display();
		}
	}
	
	/**
	 * View proofread comments.
	 * @param $article object
	 */
	function viewProofreadComments($article) {
		import("submission.form.comment.ProofreadCommentForm");
		
		$commentForm = new ProofreadCommentForm($article, ROLE_ID_LAYOUT_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post proofread comment.
	 * @param $article object
	 */
	function postProofreadComment($article, $emailComment) {
		import("submission.form.comment.ProofreadCommentForm");
		
		$commentForm = new ProofreadCommentForm($article, ROLE_ID_LAYOUT_EDITOR);
		$commentForm->readInputData();
		
		if ($commentForm->validate()) {
			$commentForm->execute();
			
			if ($emailComment) {
				$commentForm->email();
			}
			
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
	 * @param $article object
	 * @parma $fileId int
	 * @param $revision int optional
	 * @return boolean
	 */
	function downloadFile($article, $fileId, $revision = null) {
		$canDownload = false;
		
		$layoutDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$suppDao = &DAORegistry::getDAO('SuppFileDAO');
		
		$layoutAssignment = &$layoutDao->getLayoutAssignmentByArticleId($article->getArticleId());
		
		if ($layoutAssignment->getLayoutFileId() == $fileId) {
			$canDownload = true;
			
		} else if($galleyDao->galleyExistsByFileId($article->getArticleId(), $fileId)) {
			$canDownload = true;
			
		} else if($suppDao->suppFileExistsByFileId($article->getArticleId(), $fileId)) {
			$canDownload = true;
		}
		
		if ($canDownload) {
			return parent::downloadFile($article->getArticleId(), $fileId, $revision);
		} else {
			return false;
		}
	}
}

?>
