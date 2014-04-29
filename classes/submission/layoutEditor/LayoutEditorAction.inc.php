<?php

/**
 * @defgroup submission_layoutEditor_LayoutEditorAction
 */

/**
 * @file classes/submission/layoutEditor/LayoutEditorAction.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LayoutEditorAction
 * @ingroup submission_layoutEditor_LayoutEditorAction
 *
 * @brief LayoutEditorAction class.
 */

import('classes.submission.common.Action');

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
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId, $article->getId());

		if (isset($galley)) {
			$galley->setSequence($galley->getSequence() + ($direction == 'u' ? -1.5 : 1.5));
			$galleyDao->updateGalley($galley);
			$galleyDao->resequenceGalleys($article->getId());
		}
	}

	/**
	 * Delete a galley.
	 * @param $article object
	 * @param $galleyId int
	 */
	function deleteGalley($article, $galleyId) {
		import('classes.file.ArticleFileManager');

		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId, $article->getId());

		if (isset($galley) && !HookRegistry::call('LayoutEditorAction::deleteGalley', array(&$article, &$galley))) {
			$articleFileManager = new ArticleFileManager($article->getId());

			if ($galley->getFileId()) {
				$articleFileManager->deleteFile($galley->getFileId());
				import('classes.search.ArticleSearchIndex');
				$articleSearchIndex = new ArticleSearchIndex();
				$articleSearchIndex->articleFileDeleted($article->getId(), ARTICLE_SEARCH_GALLEY_FILE, $galley->getFileId());
				$articleSearchIndex->articleChangesFinished();
			}
			if ($galley->isHTMLGalley()) {
				if ($galley->getStyleFileId()) {
					$articleFileManager->deleteFile($galley->getStyleFileId());
				}
				foreach ((array) $galley->getImageFiles() as $image) {
					$articleFileManager->deleteFile($image->getFileId());
				}
			}
			$galleyDao->deleteGalley($galley);

			// Stamp the article modification (for OAI)
			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$articleDao->updateArticle($article);
		}
	}

	/**
	 * Delete an image from an article galley.
	 * @param $submission object
	 * @param $fileId int
	 * @param $revision int (optional)
	 */
	function deleteArticleImage($submission, $fileId, $revision) {
		import('classes.file.ArticleFileManager');
		$articleGalleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		if (HookRegistry::call('LayoutEditorAction::deleteArticleImage', array(&$submission, &$fileId, &$revision))) return;
		foreach ($submission->getGalleys() as $galley) {
			$images =& $articleGalleyDao->getGalleyImages($galley->getId());
			foreach ($images as $imageFile) {
				if ($imageFile->getArticleId() == $submission->getId() && $fileId == $imageFile->getFileId() && $imageFile->getRevision() == $revision) {
					$articleFileManager = new ArticleFileManager($submission->getId());
					$articleFileManager->deleteFile($imageFile->getFileId(), $imageFile->getRevision());
				}
			}
			unset($images);
		}
	}

	/**
	 * Change the sequence order of a supplementary file.
	 * @param $article object
	 * @param $suppFileId int
	 * @param $direction char u = up, d = down
	 */
	function orderSuppFile($article, $suppFileId, $direction) {
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$suppFile =& $suppFileDao->getSuppFile($suppFileId, $article->getId());

		if (isset($suppFile)) {
			$suppFile->setSequence($suppFile->getSequence() + ($direction == 'u' ? -1.5 : 1.5));
			$suppFileDao->updateSuppFile($suppFile);
			$suppFileDao->resequenceSuppFiles($article->getId());
		}
	}

	/**
	 * Delete a supplementary file.
	 * @param $article object
	 * @param $suppFileId int
	 */
	function deleteSuppFile($article, $suppFileId) {
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$suppFile =& $suppFileDao->getSuppFile($suppFileId, $article->getId());
		if (isset($suppFile) && !HookRegistry::call('LayoutEditorAction::deleteSuppFile', array(&$article, &$suppFile))) {
			if ($suppFile->getFileId()) {
				import('classes.file.ArticleFileManager');
				$articleFileManager = new ArticleFileManager($article->getId());
				$articleFileManager->deleteFile($suppFile->getFileId());
			}
			$suppFileDao->deleteSuppFile($suppFile);

			// Update the search index after deleting the
			// supp file so that idempotent search plug-ins
			// correctly update supp file meta-data.
			if ($suppFile->getFileId()) {
				import('classes.search.ArticleSearchIndex');
				$articleSearchIndex = new ArticleSearchIndex();
				$articleSearchIndex->articleFileDeleted($article->getId(), ARTICLE_SEARCH_SUPPLEMENTARY_FILE, $suppFile->getFileId());
				$articleSearchIndex->articleChangesFinished();
			}

			// Stamp the article modification (for OAI)
			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$articleDao->updateArticle($article);
		}
	}

	/**
	 * Marks layout assignment as completed.
	 * @param $submission object
	 * @param $send boolean
	 * @param $request object
	 */
	function completeLayoutEditing($submission, $send, $request) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$journal =& $request->getJournal();

		$layoutSignoff = $signoffDao->build('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $submission->getId());
		if ($layoutSignoff->getDateCompleted() != null) {
			return true;
		}

		import('classes.mail.ArticleMailTemplate');
		$email = new ArticleMailTemplate($submission, 'LAYOUT_COMPLETE');

		$editAssignments =& $submission->getEditAssignments();
		if (empty($editAssignments)) return;

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('LayoutEditorAction::completeLayoutEditing', array(&$submission, &$editAssignments, &$email));
			if ($email->isEnabled()) {
				$email->send($request);
			}

			$layoutSignoff->setDateCompleted(Core::getCurrentDate());
			$signoffDao->updateObject($layoutSignoff);

			// Add log entry
			$user =& $request->getUser();
			import('classes.article.log.ArticleLog');
			ArticleLog::logEvent($request, $submission, ARTICLE_LOG_LAYOUT_COMPLETE, 'log.layout.layoutEditComplete', array('editorName' => $user->getFullName()));

			return true;
		} else {
			$user =& $request->getUser();
			if (!$request->getUserVar('continued')) {
				$assignedSectionEditors = $email->toAssignedEditingSectionEditors($submission->getId());
				$assignedEditors = $email->ccAssignedEditors($submission->getId());
				if (empty($assignedSectionEditors) && empty($assignedEditors)) {
					$email->addRecipient($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
					$editorialContactName = $journal->getSetting('contactName');
				} else {
					$editorialContact = array_shift($assignedSectionEditors);
					if (!$editorialContact) $editorialContact = array_shift($assignedEditors);
					$editorialContactName = $editorialContact->getEditorFullName();
				}
				$paramArray = array(
					'editorialContactName' => $editorialContactName,
					'layoutEditorName' => $user->getFullName()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm($request->url(null, 'layoutEditor', 'completeAssignment', 'send'), array('articleId' => $submission->getId()));

			return false;
		}
	}

	/**
	 * Upload the layout version of an article.
	 * @param $submission object
	 */
	function uploadLayoutVersion($submission) {
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($submission->getId());
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$layoutEditorSubmissionDao =& DAORegistry::getDAO('LayoutEditorSubmissionDAO');

		$layoutSignoff = $signoffDao->build('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $submission->getId());

		$fileName = 'layoutFile';
		if ($articleFileManager->uploadedFileExists($fileName) && !HookRegistry::call('LayoutEditorAction::uploadLayoutVersion', array(&$submission))) {
			if ($layoutSignoff->getFileId() != null) {
				$layoutFileId = $articleFileManager->uploadLayoutFile($fileName, $layoutSignoff->getFileId());
			} else {
				$layoutFileId = $articleFileManager->uploadLayoutFile($fileName);
			}
			$layoutSignoff->setFileId($layoutFileId);
			$signoffDao->updateObject($layoutSignoff);
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
		if (!HookRegistry::call('LayoutEditorAction::viewLayoutComments', array(&$article))) {
			import('classes.submission.form.comment.LayoutCommentForm');

			$commentForm = new LayoutCommentForm($article, ROLE_ID_LAYOUT_EDITOR);
			$commentForm->initData();
			$commentForm->display();
		}
	}

	/**
	 * Post layout comment.
	 * @param $article object
	 * @param $emailComment boolean
	 * @param $request object
	 */
	function postLayoutComment($article, $emailComment, $request) {
		if (!HookRegistry::call('LayoutEditorAction::postLayoutComment', array(&$article, &$emailComment))) {
			import('classes.submission.form.comment.LayoutCommentForm');

			$commentForm = new LayoutCommentForm($article, ROLE_ID_LAYOUT_EDITOR);
			$commentForm->readInputData();

			if ($commentForm->validate()) {
				$commentForm->execute();

				// Send a notification to associated users
				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationUsers = $article->getAssociatedUserIds(true, false);
				foreach ($notificationUsers as $userRole) {
					$notificationManager->createNotification(
						$request, $userRole['id'], NOTIFICATION_TYPE_LAYOUT_COMMENT,
						$article->getJournalId(), ASSOC_TYPE_ARTICLE, $article->getId()
					);
				}

				if ($emailComment) {
					$commentForm->email($request);
				}
			} else {
				$commentForm->display();
				return false;
			}
			return true;
		}
	}

	/**
	 * View proofread comments.
	 * @param $article object
	 */
	function viewProofreadComments($article) {
		if (!HookRegistry::call('LayoutEditorAction::viewProofreadComments', array(&$article))) {
			import('classes.submission.form.comment.ProofreadCommentForm');

			$commentForm = new ProofreadCommentForm($article, ROLE_ID_LAYOUT_EDITOR);
			$commentForm->initData();
			$commentForm->display();
		}
	}

	/**
	 * Post proofread comment.
	 * @param $article object
	 * @param $emailComment boolean
	 * @param $request object
	 */
	function postProofreadComment($article, $emailComment, $request) {
		if (!HookRegistry::call('LayoutEditorAction::postProofreadComment', array(&$article, &$emailComment))) {
			import('classes.submission.form.comment.ProofreadCommentForm');

			$commentForm = new ProofreadCommentForm($article, ROLE_ID_LAYOUT_EDITOR);
			$commentForm->readInputData();

			if ($commentForm->validate()) {
				$commentForm->execute();

				// Send a notification to associated users
				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationUsers = $article->getAssociatedUserIds(true, false);
				foreach ($notificationUsers as $userRole) {
					$notificationManager->createNotification(
						$request, $userRole['id'], NOTIFICATION_TYPE_PROOFREAD_COMMENT,
						$article->getJournalId(), ASSOC_TYPE_ARTICLE, $article->getId()
					);
				}

				if ($emailComment) {
					$commentForm->email($request);
				}

			} else {
				$commentForm->display();
				return false;
			}
			return true;
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

		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$suppDao =& DAORegistry::getDAO('SuppFileDAO');

		$layoutSignoff = $signoffDao->build('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $article->getId());
		if ($layoutSignoff->getFileId() == $fileId) {
			$canDownload = true;
		} else if($galleyDao->galleyExistsByFileId($article->getId(), $fileId)) {
			$canDownload = true;
		} else if($suppDao->suppFileExistsByFileId($article->getId(), $fileId)) {
			$canDownload = true;
		}

		$result = false;
		if (!HookRegistry::call('LayoutEditorAction::downloadFile', array(&$article, &$fileId, &$revision, &$canDownload, &$result))) {
			if ($canDownload) {
				return parent::downloadFile($article->getId(), $fileId, $revision);
			} else {
				return false;
			}
		}
		return $result;
	}
}

?>
