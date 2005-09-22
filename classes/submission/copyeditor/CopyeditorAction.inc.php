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

import('submission.common.Action');

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
	 * @param $copyeditorSubmission object
	 */
	function completeCopyedit($copyeditorSubmission, $send = false) {
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();

		if ($copyeditorSubmission->getDateCompleted() != null) {
			return true;
		}

		$user = &Request::getUser();
		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($copyeditorSubmission, 'COPYEDIT_COMPLETE');
		
		$editAssignment = $copyeditorSubmission->getEditor();
		$editor = &$userDao->getUser($editAssignment->getEditorId());

		$authors = $copyeditorSubmission->getAuthors();
		$author = $authors[0];	// assumed at least one author always
		
		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_COMPLETE, ARTICLE_EMAIL_TYPE_COPYEDIT, $copyeditorSubmission->getArticleId());
				$email->send();
			}
				
			$copyeditorSubmission->setDateCompleted(Core::getCurrentDate());
			$copyeditorSubmission->setDateAuthorNotified(Core::getCurrentDate());
			$copyeditorSubmissionDao->updateCopyeditorSubmission($copyeditorSubmission);
	
			// Add log entry
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($copyeditorSubmission->getArticleId(), ARTICLE_LOG_COPYEDIT_INITIAL, ARTICLE_LOG_TYPE_COPYEDIT, $user->getUserId(), 'log.copyedit.initialEditComplete', Array('copyeditorName' => $user->getFullName(), 'articleId' => $copyeditorSubmission->getArticleId()));

			return true;
			
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($author->getEmail(), $author->getFullName());
				if (isset($editor)) $email->addCc($editor->getEmail(), $editor->getFullName());
				$paramArray = array(
					'editorialContactName' => $author->getFullName(),
					'copyeditorName' => $user->getFullName()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/copyeditor/completeCopyedit/send', array('articleId' => $copyeditorSubmission->getArticleId()));

			return false;
		}
	}
	
	/**
	 * Copyeditor completes final copyedit.
	 * @param $copyeditorSubmission object
	 */
	function completeFinalCopyedit($copyeditorSubmission, $send = false) {
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();

		if ($copyeditorSubmission->getDateFinalCompleted() != null) {
			return true;
		}

		$user = &Request::getUser();
		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($copyeditorSubmission, 'COPYEDIT_FINAL_COMPLETE');
		
		$editAssignment = $copyeditorSubmission->getEditor();
		$editor = &$userDao->getUser($editAssignment->getEditorId());
		
		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_FINAL_COMPLETE, ARTICLE_EMAIL_TYPE_COPYEDIT, $copyeditorSubmission->getArticleId());
				$email->send();
			}
				
			$copyeditorSubmission->setDateFinalCompleted(Core::getCurrentDate());
			$copyeditorSubmissionDao->updateCopyeditorSubmission($copyeditorSubmission);
			
			if ($copyEdFile = $copyeditorSubmission->getFinalCopyeditFile()) {
				// Set initial layout version to final copyedit version
				$layoutDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
				$layoutAssignment = &$layoutDao->getLayoutAssignmentByArticleId($copyeditorSubmission->getArticleId());

				if (isset($layoutAssignment) && !$layoutAssignment->getLayoutFileId()) {
					import('file.ArticleFileManager');
					$articleFileManager = &new ArticleFileManager($copyeditorSubmission->getArticleId());
					if ($layoutFileId = $articleFileManager->copyToLayoutFile($copyEdFile->getFileId(), $copyEdFile->getRevision())) {
						$layoutAssignment->setLayoutFileId($layoutFileId);
						$layoutDao->updateLayoutAssignment($layoutAssignment);
					}
				}
			}

			// Add log entry
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($copyeditorSubmission->getArticleId(), ARTICLE_LOG_COPYEDIT_FINAL, ARTICLE_LOG_TYPE_COPYEDIT, $user->getUserId(), 'log.copyedit.finalEditComplete', Array('copyeditorName' => $user->getFullName(), 'articleId' => $copyeditorSubmission->getArticleId()));

			return true;

		} else {
			if (!Request::getUserVar('continued')) {
				if (isset($editor)) {
					$email->addRecipient($editor->getEmail(), $editor->getFullName());
					$paramArray = array(
						'editorialContactName' => $editor->getFullName(),
						'copyeditorName' => $user->getFullName()
					);
				} else {
					$email->addRecipient($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
					$paramArray = array(
						'editorialContactName' => $journal->getSetting('contactName'),
						'copyeditorName' => $journal->getSetting('contactEmail')
					);
				}
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/copyeditor/completeFinalCopyedit/send', array('articleId' => $copyeditorSubmission->getArticleId()));

			return false;
		}
	}
	
	/**
	 * Set that the copyedit is underway.
	 */
	function copyeditUnderway(&$copyeditorSubmission) {
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');		
		
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
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($copyeditorSubmission->getArticleId(), ARTICLE_LOG_COPYEDIT_INITIATE, ARTICLE_LOG_TYPE_COPYEDIT, $user->getUserId(), 'log.copyedit.initiate', Array('copyeditorName' => $user->getFullName(), 'articleId' => $copyeditorSubmission->getArticleId()));
		}
	}	

	/**
	 * Upload the copyedited version of an article.
	 * @param $copyeditorSubmission object
	 */
	function uploadCopyeditVersion($copyeditorSubmission, $copyeditStage) {
		import("file.ArticleFileManager");
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');		

		// Only allow an upload if they're in the initial or final copyediting
		// stages.
		if ($copyeditStage == 'initial' && ($copyeditorSubmission->getDateNotified() == null || $copyeditorSubmission->getDateCompleted() != null)) return;
		else if ($copyeditStage == 'final' && ($copyeditorSubmission->getDateFinalNotified() == null || $copyeditorSubmission->getDateFinalCompleted() != null)) return;
		else if ($copyeditStage != 'initial' && $copyeditStage != 'final') return;

		$articleFileManager = &new ArticleFileManager($copyeditorSubmission->getArticleId());
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
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
	
			$entry = &new ArticleEventLogEntry();
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
	 * @param $article object
	 */
	function viewLayoutComments($article) {
		import("submission.form.comment.LayoutCommentForm");
		
		$commentForm = &new LayoutCommentForm($article, ROLE_ID_COPYEDITOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post layout comment.
	 * @param $article object
	 */
	function postLayoutComment($article, $emailComment) {
		import("submission.form.comment.LayoutCommentForm");
		
		$commentForm = &new LayoutCommentForm($article, ROLE_ID_COPYEDITOR);
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
	 * @param $article object
	 */
	function viewCopyeditComments($article) {
		import("submission.form.comment.CopyeditCommentForm");
		
		$commentForm = &new CopyeditCommentForm($article, ROLE_ID_COPYEDITOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post copyedit comment. 
	 * @param $article object
	 */
	function postCopyeditComment($article, $emailComment) {
		import("submission.form.comment.CopyeditCommentForm");
		
		$commentForm = &new CopyeditCommentForm($article, ROLE_ID_COPYEDITOR);
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
	 * @param $submission object
	 * @param $fileId int
	 * @param $revision int
	 */
	function downloadCopyeditorFile($submission, $fileId, $revision = null) {
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');		

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
		else {
			// Check galley files
			foreach ($submission->getGalleys() as $galleyFile) {
				if ($galleyFile->getFileId() == $fileId) {
					$canDownload = true;
				}
			}
			// Check supp files
			foreach ($submission->getSuppFiles() as $suppFile) {
				if ($suppFile->getFileId() == $fileId) {
					$canDownload = true;
				}
			}
		}
		
		if ($canDownload) {
			return Action::downloadFile($submission->getArticleId(), $fileId, $revision);
		} else {
			return false;
		}
	}
}

?>
