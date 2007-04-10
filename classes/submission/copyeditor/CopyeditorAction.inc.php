<?php

/**
 * CopyeditorAction.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
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
		
		$editAssignments = $copyeditorSubmission->getEditAssignments();

		$authors = $copyeditorSubmission->getAuthors();
		$author = $authors[0];	// assumed at least one author always
		
		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('CopyeditorAction::completeCopyedit', array(&$copyeditorSubmission, &$editAssignments, &$author, &$email));
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
				$email->ccAssignedEditingSectionEditors($copyeditorSubmission->getArticleId());
				$email->ccAssignedEditors($copyeditorSubmission->getArticleId());

				$paramArray = array(
					'editorialContactName' => $author->getFullName(),
					'copyeditorName' => $user->getFullName()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::url(null, 'copyeditor', 'completeCopyedit', 'send'), array('articleId' => $copyeditorSubmission->getArticleId()));

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
		
		$editAssignments = $copyeditorSubmission->getEditAssignments();
		
		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('CopyeditorAction::completeFinalCopyedit', array(&$copyeditorSubmission, &$editAssignments, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_FINAL_COMPLETE, ARTICLE_EMAIL_TYPE_COPYEDIT, $copyeditorSubmission->getArticleId());
				$email->send();
			}
				
			$copyeditorSubmission->setDateFinalCompleted(Core::getCurrentDate());
			$copyeditorSubmissionDao->updateCopyeditorSubmission($copyeditorSubmission);
			
			if ($copyEdFile =& $copyeditorSubmission->getFinalCopyeditFile()) {
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
				$assignedSectionEditors = $email->toAssignedEditingSectionEditors($copyeditorSubmission->getArticleId());
				$assignedEditors = $email->ccAssignedEditors($copyeditorSubmission->getArticleId());
				if (empty($assignedSectionEditors) && empty($assignedEditors)) {
					$email->addRecipient($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
					$paramArray = array(
						'editorialContactName' => $journal->getSetting('contactName'),
						'copyeditorName' => $user->getFullName()
					);
				} else {
					$editorialContact = array_shift($assignedSectionEditors);
					if (!$editorialContact) $editorialContact = array_shift($assignedEditors);

					$paramArray = array(
						'editorialContactName' => $editorialContact->getEditorFullName(),
						'copyeditorName' => $user->getFullName()
					);
				}
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::url(null, 'copyeditor', 'completeFinalCopyedit', 'send'), array('articleId' => $copyeditorSubmission->getArticleId()));

			return false;
		}
	}
	
	/**
	 * Set that the copyedit is underway.
	 */
	function copyeditUnderway(&$copyeditorSubmission) {
		if (!HookRegistry::call('CopyeditorAction::copyeditUnderway', array(&$copyeditorSubmission))) {
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
			HookRegistry::call('CopyeditorAction::uploadCopyeditVersion', array(&$copyeditorSubmission));
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
		if (!HookRegistry::call('CopyeditorAction::viewLayoutComments', array(&$article))) {
			import("submission.form.comment.LayoutCommentForm");
		
			$commentForm = &new LayoutCommentForm($article, ROLE_ID_COPYEDITOR);
			$commentForm->initData();
			$commentForm->display();
		}
	}
	
	/**
	 * Post layout comment.
	 * @param $article object
	 */
	function postLayoutComment($article, $emailComment) {
		if (!HookRegistry::call('CopyeditorAction::postLayoutComment', array(&$article, &$emailComment))) {
			import("submission.form.comment.LayoutCommentForm");
		
			$commentForm = &new LayoutCommentForm($article, ROLE_ID_COPYEDITOR);
			$commentForm->readInputData();
		
			if ($commentForm->validate()) {
				$commentForm->execute();

				if ($emailComment) {
					$commentForm->email();
				}

			} else {
				$commentForm->display();
				return false;
			}
			return true;
		}
	}
	
	/**
	 * View copyedit comments.
	 * @param $article object
	 */
	function viewCopyeditComments($article) {
		if (!HookRegistry::call('CopyeditorAction::viewCopyeditComments', array(&$article))) {
			import("submission.form.comment.CopyeditCommentForm");
		
			$commentForm = &new CopyeditCommentForm($article, ROLE_ID_COPYEDITOR);
			$commentForm->initData();
			$commentForm->display();
		}
	}
	
	/**
	 * Post copyedit comment. 
	 * @param $article object
	 */
	function postCopyeditComment($article, $emailComment) {
		if (!HookRegistry::call('CopyeditorAction::postCopyeditComment', array(&$article, &$emailComment))) {
			import("submission.form.comment.CopyeditCommentForm");
		
			$commentForm = &new CopyeditCommentForm($article, ROLE_ID_COPYEDITOR);
			$commentForm->readInputData();
		
			if ($commentForm->validate()) {
				$commentForm->execute();
				
				if ($emailComment) {
					$commentForm->email();
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

		$result = false;
		if (!HookRegistry::call('CopyeditorAction::downloadCopyeditorFile', array(&$submission, &$fileId, &$revision, &$result))) {
			if ($canDownload) {
				return Action::downloadFile($submission->getArticleId(), $fileId, $revision);
			} else {
				return false;
			}
		}

		return $result;
	}
}

?>
