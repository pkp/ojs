<?php

/**
 * @file classes/submission/copyeditor/CopyeditorAction.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CopyeditorAction
 * @ingroup submission
 * @see CopyeditorSubmissionDAO
 *
 * @brief CopyeditorAction class.
 */


import('classes.submission.common.Action');

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
		$copyeditorSubmissionDao =& DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$journal =& Request::getJournal();

		$initialSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $copyeditorSubmission->getArticleId());
		if ($initialSignoff->getDateCompleted() != null) {
			return true;
		}

		$user =& Request::getUser();
		import('classes.mail.ArticleMailTemplate');
		$email = new ArticleMailTemplate($copyeditorSubmission, 'COPYEDIT_COMPLETE');

		$editAssignments = $copyeditorSubmission->getEditAssignments();

		$author = $copyeditorSubmission->getUser();

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('CopyeditorAction::completeCopyedit', array(&$copyeditorSubmission, &$editAssignments, &$author, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_COMPLETE, ARTICLE_EMAIL_TYPE_COPYEDIT, $copyeditorSubmission->getArticleId());
				$email->send();
			}

			$initialSignoff->setDateCompleted(Core::getCurrentDate());

			$authorSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_AUTHOR', ASSOC_TYPE_ARTICLE, $copyeditorSubmission->getArticleId());
			$authorSignoff->setUserId($author->getId());
			$authorSignoff->setDateNotified(Core::getCurrentDate());
			$signoffDao->updateObject($initialSignoff);
			$signoffDao->updateObject($authorSignoff);


			// Add log entry
			import('classes.article.log.ArticleLog');
			import('classes.article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($copyeditorSubmission->getArticleId(), ARTICLE_LOG_COPYEDIT_INITIAL, ARTICLE_LOG_TYPE_COPYEDIT, $user->getId(), 'log.copyedit.initialEditComplete', Array('copyeditorName' => $user->getFullName(), 'articleId' => $copyeditorSubmission->getArticleId()));

			return true;

		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($author->getEmail(), $author->getFullName());
				$email->ccAssignedEditingSectionEditors($copyeditorSubmission->getArticleId());
				$email->ccAssignedEditors($copyeditorSubmission->getArticleId());

				$paramArray = array(
					'editorialContactName' => $author->getFullName(),
					'copyeditorName' => $user->getFullName(),
					'authorUsername' => $author->getUsername(),
					'submissionEditingUrl' => Request::url(null, 'author', 'submissionEditing', array($copyeditorSubmission->getArticleId()))
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
		$copyeditorSubmissionDao =& DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$journal =& Request::getJournal();

		$finalSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_FINAL', ASSOC_TYPE_ARTICLE, $copyeditorSubmission->getArticleId());
		if ($finalSignoff->getDateCompleted() != null) {
			return true;
		}

		$user =& Request::getUser();
		import('classes.mail.ArticleMailTemplate');
		$email = new ArticleMailTemplate($copyeditorSubmission, 'COPYEDIT_FINAL_COMPLETE');

		$editAssignments = $copyeditorSubmission->getEditAssignments();

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('CopyeditorAction::completeFinalCopyedit', array(&$copyeditorSubmission, &$editAssignments, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_FINAL_COMPLETE, ARTICLE_EMAIL_TYPE_COPYEDIT, $copyeditorSubmission->getArticleId());
				$email->send();
			}

			$finalSignoff->setDateCompleted(Core::getCurrentDate());
			$signoffDao->updateObject($finalSignoff);

			if ($copyEdFile = $copyeditorSubmission->getFileBySignoffType('SIGNOFF_COPYEDITING_FINAL')) {
				// Set initial layout version to final copyedit version
				$layoutSignoff = $signoffDao->build('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $copyeditorSubmission->getArticleId());

				if (!$layoutSignoff->getFileId()) {
					import('classes.file.ArticleFileManager');
					$articleFileManager = new ArticleFileManager($copyeditorSubmission->getArticleId());
					if ($layoutFileId = $articleFileManager->copyToLayoutFile($copyEdFile->getFileId(), $copyEdFile->getRevision())) {
						$layoutSignoff->setFileId($layoutFileId);
						$signoffDao->updateObject($layoutSignoff);
					}
				}
			}

			// Add log entry
			import('classes.article.log.ArticleLog');
			import('classes.article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($copyeditorSubmission->getArticleId(), ARTICLE_LOG_COPYEDIT_FINAL, ARTICLE_LOG_TYPE_COPYEDIT, $user->getId(), 'log.copyedit.finalEditComplete', Array('copyeditorName' => $user->getFullName(), 'articleId' => $copyeditorSubmission->getArticleId()));

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
			$copyeditorSubmissionDao =& DAORegistry::getDAO('CopyeditorSubmissionDAO');
			$signoffDao =& DAORegistry::getDAO('SignoffDAO');

			$initialSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $copyeditorSubmission->getId());
			$finalSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_FINAL', ASSOC_TYPE_ARTICLE, $copyeditorSubmission->getId());

			if ($initialSignoff->getDateNotified() != null && $initialSignoff->getDateUnderway() == null) {
				$initialSignoff->setDateUnderway(Core::getCurrentDate());
				$signoffDao->updateObject($initialSignoff);
				$update = true;

			} elseif ($finalSignoff->getDateNotified() != null && $finalSignoff->getDateUnderway() == null) {
				$finalSignoff->setDateUnderway(Core::getCurrentDate());
				$signoffDao->updateObject($finalSignoff);
				$update = true;
			}

			if (isset($update)) {
				// Add log entry
				$user =& Request::getUser();
				import('classes.article.log.ArticleLog');
				import('classes.article.log.ArticleEventLogEntry');
				ArticleLog::logEvent($copyeditorSubmission->getArticleId(), ARTICLE_LOG_COPYEDIT_INITIATE, ARTICLE_LOG_TYPE_COPYEDIT, $user->getId(), 'log.copyedit.initiate', Array('copyeditorName' => $user->getFullName(), 'articleId' => $copyeditorSubmission->getArticleId()));
			}
		}
	}

	/**
	 * Upload the copyedited version of an article.
	 * @param $copyeditorSubmission object
	 */
	function uploadCopyeditVersion($copyeditorSubmission, $copyeditStage) {
		import('classes.file.ArticleFileManager');
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$copyeditorSubmissionDao =& DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');

		if($copyeditStage == 'initial') {
			$signoff = $signoffDao->build('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $copyeditorSubmission->getArticleId());
		} else if($copyeditStage == 'final') {
			$signoff = $signoffDao->build('SIGNOFF_COPYEDITING_FINAL', ASSOC_TYPE_ARTICLE, $copyeditorSubmission->getArticleId());
		}

		// Only allow an upload if they're in the initial or final copyediting
		// stages.
		if ($copyeditStage == 'initial' && ($signoff->getDateNotified() == null || $signoff->getDateCompleted() != null)) return;
		else if ($copyeditStage == 'final' && ($signoff->getDateNotified() == null || $signoff->getDateCompleted() != null)) return;
		else if ($copyeditStage != 'initial' && $copyeditStage != 'final') return;

		$articleFileManager = new ArticleFileManager($copyeditorSubmission->getArticleId());
		$user =& Request::getUser();

		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName)) {
			HookRegistry::call('CopyeditorAction::uploadCopyeditVersion', array(&$copyeditorSubmission));
			if ($signoff->getFileId() != null) {
				$fileId = $articleFileManager->uploadCopyeditFile($fileName, $copyeditorSubmission->getFileBySignoffType('SIGNOFF_COPYEDITING_INITIAL', true));
			} else {
				$fileId = $articleFileManager->uploadCopyeditFile($fileName);
			}
		}

		if (isset($fileId) && $fileId != 0) {
			$signoff->setFileId($fileId);
			$signoff->setFileRevision($articleFileDao->getRevisionNumber($fileId));
			$signoffDao->updateObject($signoff);

			// Add log
			import('classes.article.log.ArticleLog');
			import('classes.article.log.ArticleEventLogEntry');

			$entry = new ArticleEventLogEntry();
			$entry->setArticleId($copyeditorSubmission->getArticleId());
			$entry->setUserId($user->getId());
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
			import('classes.submission.form.comment.LayoutCommentForm');

			$commentForm = new LayoutCommentForm($article, ROLE_ID_COPYEDITOR);
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
			import('classes.submission.form.comment.LayoutCommentForm');

			$commentForm = new LayoutCommentForm($article, ROLE_ID_COPYEDITOR);
			$commentForm->readInputData();

			if ($commentForm->validate()) {
				$commentForm->execute();

				// Send a notification to associated users
				import('lib.pkp.classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationUsers = $article->getAssociatedUserIds(true, false);
				foreach ($notificationUsers as $userRole) {
					$url = Request::url(null, $userRole['role'], 'submissionEditing', $article->getId(), null, 'layout');
					$notificationManager->createNotification(
						$userRole['id'], 'notification.type.layoutComment',
						$article->getLocalizedTitle(), $url, 1, NOTIFICATION_TYPE_LAYOUT_COMMENT
					);
				}

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
			import('classes.submission.form.comment.CopyeditCommentForm');

			$commentForm = new CopyeditCommentForm($article, ROLE_ID_COPYEDITOR);
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
			import('classes.submission.form.comment.CopyeditCommentForm');

			$commentForm = new CopyeditCommentForm($article, ROLE_ID_COPYEDITOR);
			$commentForm->readInputData();

			if ($commentForm->validate()) {
				$commentForm->execute();

				// Send a notification to associated users
				import('lib.pkp.classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationUsers = $article->getAssociatedUserIds(true, false);
				foreach ($notificationUsers as $userRole) {
					$url = Request::url(null, $userRole['role'], 'submissionEditing', $article->getId(), null, 'coypedit');
					$notificationManager->createNotification(
						$userRole['id'], 'notification.type.copyeditComment',
						$article->getLocalizedTitle(), $url, 1, NOTIFICATION_TYPE_COPYEDIT_COMMENT
					);
				}

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
	function downloadCopyeditorFile($copyeditorSubmission, $fileId, $revision = null) {
		$copyeditorSubmissionDao =& DAORegistry::getDAO('CopyeditorSubmissionDAO');

		$canDownload = false;

		// Copyeditors have access to:
		// 1) The first revision of the copyedit file
		// 2) The initial copyedit revision
		// 3) The author copyedit revision, after the author copyedit has been completed
		// 4) The final copyedit revision
		// 5) Layout galleys
		if ($copyeditorSubmission->getFileBySignoffType('SIGNOFF_COPYEDITING_INITIAL', true) == $fileId) {
			$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
			$signoffDao =& DAORegistry::getDAO('SignoffDAO');
			$currentRevision =& $articleFileDao->getRevisionNumber($fileId);

			if ($revision == null) {
				$revision = $currentRevision;
			}

			$initialSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $copyeditorSubmission->getArticleId());
			$authorSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_AUTHOR', ASSOC_TYPE_ARTICLE, $copyeditorSubmission->getArticleId());
			$finalSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_FINAL', ASSOC_TYPE_ARTICLE, $copyeditorSubmission->getArticleId());

			if ($revision == 1) {
				$canDownload = true;
			} else if ($initialSignoff->getFileRevision() == $revision) {
				$canDownload = true;
			} else if ($finalSignoff->getFileRevision() == $revision) {
				$canDownload = true;
			}
		} else if ($copyeditorSubmission->getFileBySignoffType('SIGNOFF_COPYEDITING_AUTHOR', true) == $fileId) {
			$signoffDao =& DAORegistry::getDAO('SignoffDAO');
			$authorSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_AUTHOR', ASSOC_TYPE_ARTICLE, $copyeditorSubmission->getArticleId());
		 	if($authorSignoff->getDateCompleted() != null) {
				$canDownload = true;
			}
		} else if ($copyeditorSubmission->getFileBySignoffType('SIGNOFF_COPYEDITING_FINAL', true) == $fileId) {
			$canDownload = true;
		}
		else {
			// Check galley files
			foreach ($copyeditorSubmission->getGalleys() as $galleyFile) {
				if ($galleyFile->getFileId() == $fileId) {
					$canDownload = true;
				}
			}
			// Check supp files
			foreach ($copyeditorSubmission->getSuppFiles() as $suppFile) {
				if ($suppFile->getFileId() == $fileId) {
					$canDownload = true;
				}
			}
		}

		$result = false;
		if (!HookRegistry::call('CopyeditorAction::downloadCopyeditorFile', array(&$copyeditorSubmission, &$fileId, &$revision, &$result))) {
			if ($canDownload) {
				return Action::downloadFile($copyeditorSubmission->getArticleId(), $fileId, $revision);
			} else {
				return false;
			}
		}

		return $result;
	}
}

?>
