<?php

/**
 * CopyeditorAction.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * CopyeditorAction class.
 *
 * $Id$
 */

class CopyeditorAction extends Action {

	/**
	 * Constructor.
	 */
	function CopyeditorAction() {

	}
	
	/**
	 * Actions.
	 */
	
	/**
	 * Copyeditor completes initial copyedit.
	 * @param $articleId int
	 */
	function completeCopyedit($articleId, $send = false) {
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();

		$copyeditorSubmission = &$copyeditorSubmissionDao->getCopyeditorSubmission($articleId);

		if ($copyeditorSubmission->getDateCompleted() != null) {
			return true;
		}
		
		$user = &Request::getUser();
		$email = &new ArticleMailTemplate($articleId, 'COPYEDIT_COMPLETE');
		$email->setFrom($user->getEmail(), $user->getFullName());
		
		$editAssignment = $copyeditorSubmission->getEditor();
		$editor = &$userDao->getUser($editAssignment->getEditorId());

		$authors = $copyeditorSubmission->getAuthors();
		$author = $authors[0];	// assumed at least one author always
		
		if ($send && !$email->hasErrors()) {
			$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_COMPLETE, ARTICLE_EMAIL_TYPE_COPYEDIT, $articleId);
			$email->send();
				
			$copyeditorSubmission->setDateCompleted(Core::getCurrentDate());
			$copyeditorSubmission->setDateAuthorNotified(Core::getCurrentDate());
			$copyeditorSubmissionDao->updateCopyeditorSubmission($copyeditorSubmission);
	
			// Add log entry
			ArticleLog::logEvent($articleId, ARTICLE_LOG_COPYEDIT_INITIAL, ARTICLE_LOG_TYPE_COPYEDIT, $user->getUserId(), 'log.copyedit.initialEditComplete', Array('copyEditorName' => $user->getFullName(), 'articleId' => $articleId));

			return true;
			
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($author->getEmail(), $author->getFullName());
				$email->addCc($editor->getEmail(), $editor->getFullName());
				$paramArray = array(
					'editorialContactName' => $author->getFullName(),
					'articleTitle' => $copyeditorSubmission->getArticleTitle(),
					'copyeditorName' => $user->getFullName()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/copyeditor/completeCopyedit/send', array('articleId' => $articleId));

			return false;
		}
	}
	
	/**
	 * Copyeditor completes final copyedit.
	 * @param $articleId int
	 */
	function completeFinalCopyedit($articleId, $send = false) {
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();

		$copyeditorSubmission = &$copyeditorSubmissionDao->getCopyeditorSubmission($articleId);

		if ($copyeditorSubmission->getDateFinalCompleted() != null) {
			return true;
		}
		
		$user = &Request::getUser();
		$email = &new ArticleMailTemplate($articleId, 'COPYEDIT_FINAL_COMPLETE');
		$email->setFrom($user->getEmail(), $user->getFullName());
		
		$editAssignment = $copyeditorSubmission->getEditor();
		$editor = &$userDao->getUser($editAssignment->getEditorId());
		
		if ($send && !$email->hasErrors()) {
			$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_FINAL_COMPLETE, ARTICLE_EMAIL_TYPE_COPYEDIT, $articleId);
			$email->send();
				
			$copyeditorSubmission->setDateFinalCompleted(Core::getCurrentDate());
			$copyeditorSubmissionDao->updateCopyeditorSubmission($copyeditorSubmission);
			
			if ($copyEdFile = $copyeditorSubmission->getFinalCopyeditFile()) {
				// Set initial layout version to final copyedit version
				$layoutDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
				$layoutAssignment = &$layoutDao->getLayoutAssignmentByArticleId($articleId);

				if (isset($layoutAssignment) && !$layoutAssignment->getLayoutFileId()) {
					import('file.ArticleFileManager');
					$articleFileManager = new ArticleFileManager($articleId);
					if ($layoutFileId = $articleFileManager->copyToLayoutFile($copyEdFile->getFileId(), $copyEdFile->getRevision())) {
						$layoutAssignment->setLayoutFileId($layoutFileId);
						$layoutDao->updateLayoutAssignment($layoutAssignment);
					}
				}
			}

			// Add log entry
			ArticleLog::logEvent($articleId, ARTICLE_LOG_COPYEDIT_FINAL, ARTICLE_LOG_TYPE_COPYEDIT, $user->getUserId(), 'log.copyedit.finalEditComplete', Array('copyEditorName' => $user->getFullName(), 'articleId' => $articleId));

			return true;

		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($editor->getEmail(), $editor->getFullName());
				$paramArray = array(
					'editorialContactName' => $editor->getFullName(),
					'articleTitle' => $copyeditorSubmission->getArticleTitle(),
					'copyeditorName' => $user->getFullName()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/copyeditor/completeFinalCopyedit/send', array('articleId' => $articleId));

			return false;
		}
	}
	
	/**
	 * Set that the copyedit is underway.
	 */
	function copyeditUnderway($articleId) {
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');		
		$copyeditorSubmission = &$copyeditorSubmissionDao->getCopyeditorSubmission($articleId);
		
		if ($copyeditorSubmission->getDateNotified() != null && $copyeditorSubmission->getDateUnderway() == null) {
			$copyeditorSubmission->setDateUnderway(Core::getCurrentDate());
			$update = true;
			
		} elseif ($copyeditorSubmission->getDateFinalNotified() != null && $copyeditorSubmission->getDateFinalUnderway() == null) {
			$copyeditorSubmission->setDateFinalUnderway(Core::getCurrentDate());
			$update = true;
		}
		
		if (isset($update)) {
			$copyeditorSubmissionDao->updateCopyeditorSubmission($copyeditorSubmission);
		
			// Add log entry
			$user = &Request::getUser();
			ArticleLog::logEvent($articleId, ARTICLE_LOG_COPYEDIT_INITIATE, ARTICLE_LOG_TYPE_COPYEDIT, $user->getUserId(), 'log.copyedit.initiate', Array('copyEditorName' => $user->getFullName(), 'articleId' => $articleId));
		}
	}	

	/**
	 * Upload the copyeditted version of an article.
	 * @param $articleId int
	 */
	function uploadCopyeditVersion($articleId, $copyeditStage) {
		import("file.ArticleFileManager");
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');		
		$copyeditorSubmission = &$copyeditorSubmissionDao->getCopyeditorSubmission($articleId);

		// Only allow an upload if they're in the initial or final copyediting
		// stages.
		if ($copyeditStage == 'initial' && ($copyeditorSubmission->getDateNotified() == null || $copyeditorSubmission->getDateCompleted() != null)) return;
		else if ($copyeditorSubmission->getDateFinalNotified() == null || $copyeditorSubmission->getDateFinalCompleted() != null) return;

		$articleFileManager = new ArticleFileManager($copyeditorSubmission->getArticleId());
		$user = &Request::getUser();
		
		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName)) {
			if ($copyeditorSubmission->getCopyeditFileId() != null) {
				$fileId = $articleFileManager->uploadCopyeditFile($fileName, $copyeditorSubmission->getCopyeditFileId());
			} else {
				$fileId = $articleFileManager->uploadCopyeditFile($fileName);
			}
		}
		
		if (isset($fileId) && $fileId != 0) {
		$copyeditorSubmission->setCopyeditFileId($fileId);
		
			if ($copyeditStage == 'initial') {
				$copyeditorSubmission->setInitialRevision($articleFileDao->getRevisionNumber($fileId));
			} elseif ($copyeditStage == 'final') {
				$copyeditorSubmission->setFinalRevision($articleFileDao->getRevisionNumber($fileId));
			}
	
			$copyeditorSubmissionDao->updateCopyeditorSubmission($copyeditorSubmission);
	
			// Add log
			$entry = new ArticleEventLogEntry();
			$entry->setArticleId($copyeditorSubmission->getArticleId());
			$entry->setUserId($user->getUserId());
			$entry->setDateLogged(Core::getCurrentDate());
			$entry->setEventType(ARTICLE_LOG_COPYEDIT_COPYEDITOR_FILE);
			$entry->setLogMessage('log.copyedit.copyeditorFile');
			$entry->setAssocType(ARTICLE_LOG_TYPE_COPYEDIT);
			$entry->setAssocId($fileId);
				
			ArticleLog::logEventEntry($copyeditorSubmission->getArticleId(), $entry);
		}
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
		
		$commentForm = new LayoutCommentForm($articleId, ROLE_ID_COPYEDITOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post layout comment.
	 * @param $articleId int
	 */
	function postLayoutComment($articleId, $emailComment) {
		import("submission.form.comment.LayoutCommentForm");
		
		$commentForm = new LayoutCommentForm($articleId, ROLE_ID_COPYEDITOR);
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
	 * View copyedit comments.
	 * @param $articleId int
	 */
	function viewCopyeditComments($articleId) {
		import("submission.form.comment.CopyeditCommentForm");
		
		$commentForm = new CopyeditCommentForm($articleId, ROLE_ID_COPYEDITOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post copyedit comment.
	 * @param $articleId int
	 */
	function postCopyeditComment($articleId, $emailComment) {
		import("submission.form.comment.CopyeditCommentForm");
		
		$commentForm = new CopyeditCommentForm($articleId, ROLE_ID_COPYEDITOR);
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
	 * Download a file a copyeditor has access to.
	 * @param $articleId int
	 * @param $fileId int
	 * @param $revision int
	 */
	function downloadCopyeditorFile($articleId, $fileId, $revision = null) {
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');		
		$submission = &$copyeditorSubmissionDao->getCopyeditorSubmission($articleId);

		$canDownload = false;
		
		// Copyeditors have access to:
		// 1) The first revision of the copyedit file
		// 2) The initial copyedit revision
		// 3) The author copyedit revision, after the author copyedit has been completed
		// 4) The final copyedit revision
		// 5) Layout galleys
		if ($submission->getCopyeditFileId() == $fileId) {
			$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');		
			$currentRevision = &$articleFileDao->getRevisionNumber($fileId);
			
			if ($revision == null) {
				$revision = $currentRevision;
			}
			
			if ($revision == 1) {
				$canDownload = true;
			} else if ($submission->getInitialRevision() == $revision) {
				$canDownload = true;
			} else if ($submission->getEditorAuthorRevision() == $revision && $submission->getDateAuthorCompleted() != null) {
				$canDownload = true;
			} else if ($submission->getFinalRevision() == $revision) {
				$canDownload = true;
			}
		}
		// Check galley files
		else foreach ($submission->getGalleys() as $galleyFile) {
			if ($galleyFile->getFileId() == $fileId) {
				$canDownload = true;
			}
		}
		
		if ($canDownload) {
			return Action::downloadFile($articleId, $fileId, $revision);
		} else {
			return false;
		}
	}
}

?>
