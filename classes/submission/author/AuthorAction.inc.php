<?php

/**
 * @file classes/submission/author/AuthorAction.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorAction
 * @ingroup submission
 *
 * @brief AuthorAction class.
 */

import('classes.submission.common.Action');

class AuthorAction extends Action {

	/**
	 * Constructor.
	 */
	function AuthorAction() {
		parent::Action();
	}

	/**
	 * Actions.
	 */

	/**
	 * Designates the original file the review version.
	 * @param $authorSubmission object
	 * @param $designate boolean
	 */
	function designateReviewVersion($authorSubmission, $designate = false) {
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($authorSubmission->getId());
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');

		if ($designate && !HookRegistry::call('AuthorAction::designateReviewVersion', array(&$authorSubmission))) {
			$submissionFile =& $authorSubmission->getSubmissionFile();
			if ($submissionFile) {
				$reviewFileId = $articleFileManager->copyToReviewFile($submissionFile->getFileId());

				$authorSubmission->setReviewFileId($reviewFileId);

				$authorSubmissionDao->updateAuthorSubmission($authorSubmission);

				$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
				$sectionEditorSubmissionDao->createReviewRound($authorSubmission->getId(), 1, 1);
			}
		}
	}

	/**
	 * Delete an author file from a submission.
	 * @param $article object
	 * @param $fileId int
	 * @param $revisionId int
	 */
	function deleteArticleFile($article, $fileId, $revisionId) {
		import('classes.file.ArticleFileManager');

		$articleFileManager = new ArticleFileManager($article->getId());
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');

		$articleFile =& $articleFileDao->getArticleFile($fileId, $revisionId, $article->getId());
		$authorSubmission = $authorSubmissionDao->getAuthorSubmission($article->getId());
		$authorRevisions = $authorSubmission->getAuthorFileRevisions();

		// Ensure that this is actually an author file.
		if (isset($articleFile)) {
			HookRegistry::call('AuthorAction::deleteArticleFile', array(&$articleFile, &$authorRevisions));
			foreach ($authorRevisions as $round) {
				foreach ($round as $revision) {
					if ($revision->getFileId() == $articleFile->getFileId() &&
						$revision->getRevision() == $articleFile->getRevision()) {
						$articleFileManager->deleteFile($articleFile->getFileId(), $articleFile->getRevision());
					}
				}
			}
		}
	}

	/**
	 * Upload the revised version of an article.
	 * @param $authorSubmission object
	 * @param $request object
	 */
	function uploadRevisedVersion($authorSubmission, $request) {
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($authorSubmission->getId());
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');

		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName)) {
			HookRegistry::call('AuthorAction::uploadRevisedVersion', array(&$authorSubmission));
			if ($authorSubmission->getRevisedFileId() != null) {
				$fileId = $articleFileManager->uploadEditorDecisionFile($fileName, $authorSubmission->getRevisedFileId());
			} else {
				$fileId = $articleFileManager->uploadEditorDecisionFile($fileName);
			}
		}

		if (isset($fileId) && $fileId != 0) {
			$authorSubmission->setRevisedFileId($fileId);

			$authorSubmissionDao->updateAuthorSubmission($authorSubmission);

			$user =& $request->getUser();
			$journal =& $request->getJournal();
			import('classes.mail.ArticleMailTemplate');
			$email = new ArticleMailTemplate($authorSubmission, 'REVISED_VERSION_NOTIFY');			
			if ($email->isEnabled()) {
				$isEditor = false;
				$assignedSectionEditors = $email->toAssignedEditingSectionEditors($authorSubmission->getId());
				$editor = array_shift($assignedSectionEditors);
				if (!$editor) {
					$isEditor = true;
					$assignedEditors = $email->toAssignedEditors($authorSubmission->getId());
					$editor = array_shift($assignedEditors);
				}
				if (!$editor) {
					$email->addRecipient($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
					$editorName = $journal->getSetting('contactName');
				} else {
					$editorName = $editor->getEditorFullName();
				}
				
				$paramArray = array(
					'editorialContactName' => $editorName,
					'articleTitle' => $authorSubmission->getLocalizedTitle(),
					'authorName' => $user->getFullName(),
					'submissionUrl' => $request->url(null, $isEditor?'editor':'sectionEditor', 'submissionReview', $authorSubmission->getId()),
					'editorialContactSignature' => $journal->getSetting('contactName') . "\n" . $journal->getLocalizedTitle()
				);
				$email->assignParams($paramArray);
				$email->send($request);
			}
			// Add log entry
			import('classes.article.log.ArticleLog');
			ArticleLog::logEvent($request, $authorSubmission, ARTICLE_LOG_AUTHOR_REVISION, 'log.author.documentRevised', array('authorName' => $user->getFullName(), 'fileId' => $fileId));
		}
	}

	/**
	 * Author completes editor / author review.
	 * @param $authorSubmission object
	 * @param $send boolean
	 * @param $request object
	 */
	function completeAuthorCopyedit($authorSubmission, $send, $request) {
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$journal =& $request->getJournal();

		$authorSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_AUTHOR', ASSOC_TYPE_ARTICLE, $authorSubmission->getId());
		if ($authorSignoff->getDateCompleted() != null) {
			return true;
		}

		$user =& $request->getUser();
		import('classes.mail.ArticleMailTemplate');
		$email = new ArticleMailTemplate($authorSubmission, 'COPYEDIT_AUTHOR_COMPLETE');

		$editAssignments = $authorSubmission->getEditAssignments();

		$copyeditor = $authorSubmission->getUserBySignoffType('SIGNOFF_COPYEDITING_INITIAL');

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('AuthorAction::completeAuthorCopyedit', array(&$authorSubmission, &$email));
			if ($email->isEnabled()) {
				$email->send($request);
			}

			$authorSignoff->setDateCompleted(Core::getCurrentDate());

			$finalSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_FINAL', ASSOC_TYPE_ARTICLE, $authorSubmission->getId());
			if ($copyeditor) $finalSignoff->setUserId($copyeditor->getId());
			$finalSignoff->setDateNotified(Core::getCurrentDate());

			$signoffDao->updateObject($authorSignoff);
			$signoffDao->updateObject($finalSignoff);

			// Add log entry
			import('classes.article.log.ArticleLog');
			ArticleLog::logEvent($request, $authorSubmission, ARTICLE_LOG_COPYEDIT_REVISION, 'log.copyedit.authorFile');

			return true;
		} else {
			if (!$request->getUserVar('continued')) {
				if (isset($copyeditor)) {
					$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
					$assignedSectionEditors = $email->ccAssignedEditingSectionEditors($authorSubmission->getId());
					$assignedEditors = $email->ccAssignedEditors($authorSubmission->getId());
					if (empty($assignedSectionEditors) && empty($assignedEditors)) {
						$email->addCc($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
						$editorName = $journal->getSetting('contactName');
					} else {
						$editor = array_shift($assignedSectionEditors);
						if (!$editor) $editor = array_shift($assignedEditors);
						$editorName = $editor->getEditorFullName();
					}
				} else {
					$assignedSectionEditors = $email->toAssignedEditingSectionEditors($authorSubmission->getId());
					$assignedEditors = $email->ccAssignedEditors($authorSubmission->getId());
					if (empty($assignedSectionEditors) && empty($assignedEditors)) {
						$email->addRecipient($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
						$editorName = $journal->getSetting('contactName');
					} else {
						$editor = array_shift($assignedSectionEditors);
						if (!$editor) $editor = array_shift($assignedEditors);
						$editorName = $editor->getEditorFullName();
					}
				}

				$paramArray = array(
					'editorialContactName' => isset($copyeditor)?$copyeditor->getFullName():$editorName,
					'authorName' => $user->getFullName()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm($request->url(null, 'author', 'completeAuthorCopyedit', 'send'), array('articleId' => $authorSubmission->getId()));

			return false;
		}
	}

	/**
	 * Set that the copyedit is underway.
	 */
	function copyeditUnderway($authorSubmission) {
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');

		$authorSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_AUTHOR', ASSOC_TYPE_ARTICLE, $authorSubmission->getId());
		if ($authorSignoff->getDateNotified() != null && $authorSignoff->getDateUnderway() == null) {
			HookRegistry::call('AuthorAction::copyeditUnderway', array(&$authorSubmission));
			$authorSignoff->setDateUnderway(Core::getCurrentDate());
			$signoffDao->updateObject($authorSignoff);
		}
	}

	/**
	 * Upload the revised version of a copyedit file.
	 * @param $authorSubmission object
	 * @param $copyeditStage string
	 */
	function uploadCopyeditVersion($authorSubmission, $copyeditStage) {
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($authorSubmission->getId());
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');

		// Authors cannot upload if the assignment is not active, i.e.
		// they haven't been notified or the assignment is already complete.
		$authorSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_AUTHOR', ASSOC_TYPE_ARTICLE, $authorSubmission->getId());
		if (!$authorSignoff->getDateNotified() || $authorSignoff->getDateCompleted()) return;

		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName)) {
			HookRegistry::call('AuthorAction::uploadCopyeditVersion', array(&$authorSubmission, &$copyeditStage));
			if ($authorSignoff->getFileId() != null) {
				$fileId = $articleFileManager->uploadCopyeditFile($fileName, $authorSignoff->getFileId());
			} else {
				$fileId = $articleFileManager->uploadCopyeditFile($fileName);
			}
		}

		$authorSignoff->setFileId($fileId);

		if ($copyeditStage == 'author') {
			$authorSignoff->setFileRevision($articleFileDao->getRevisionNumber($fileId));
		}

		$signoffDao->updateObject($authorSignoff);
	}

	//
	// Comments
	//

	/**
	 * View layout comments.
	 * @param $article object
	 */
	function viewLayoutComments($article) {
		if (!HookRegistry::call('AuthorAction::viewLayoutComments', array(&$article))) {
			import('classes.submission.form.comment.LayoutCommentForm');
			$commentForm = new LayoutCommentForm($article, ROLE_ID_EDITOR);
			$commentForm->initData();
			$commentForm->display();
		}
	}

	/**
	 * Post layout comment.
	 * @param $article object
	 * @param $emailComment boolean
	 */
	function postLayoutComment($article, $emailComment, $request) {
		if (!HookRegistry::call('AuthorAction::postLayoutComment', array(&$article, &$emailComment))) {
			import('classes.submission.form.comment.LayoutCommentForm');

			$commentForm = new LayoutCommentForm($article, ROLE_ID_AUTHOR);
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
	 * View editor decision comments.
	 * @param $article object
	 */
	function viewEditorDecisionComments($article) {
		if (!HookRegistry::call('AuthorAction::viewEditorDecisionComments', array(&$article))) {
			import('classes.submission.form.comment.EditorDecisionCommentForm');

			$commentForm = new EditorDecisionCommentForm($article, ROLE_ID_AUTHOR);
			$commentForm->initData();
			$commentForm->display();
		}
	}

	/**
	 * Email editor decision comment.
	 * @param $authorSubmission object
	 * @param $send boolean
	 * @param $request object
	 */
	function emailEditorDecisionComment($authorSubmission, $send, $request) {
		$userDao =& DAORegistry::getDAO('UserDAO');

		$journal =& $request->getJournal();
		$user =& $request->getUser();

		import('classes.mail.ArticleMailTemplate');
		$email = new ArticleMailTemplate($authorSubmission);

		$editAssignments = $authorSubmission->getEditAssignments();
		$editors = array();
		foreach ($editAssignments as $editAssignment) {
			array_push($editors, $userDao->getUser($editAssignment->getEditorId()));
		}

		if ($send && !$email->hasErrors()) {
			HookRegistry::call('AuthorAction::emailEditorDecisionComment', array(&$authorSubmission, &$email));
			$email->send($request);

			$articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
			$articleComment = new ArticleComment();
			$articleComment->setCommentType(COMMENT_TYPE_EDITOR_DECISION);
			$articleComment->setRoleId(ROLE_ID_AUTHOR);
			$articleComment->setArticleId($authorSubmission->getId());
			$articleComment->setAuthorId($authorSubmission->getUserId());
			$articleComment->setCommentTitle($email->getSubject());
			$articleComment->setComments($email->getBody());
			$articleComment->setDatePosted(Core::getCurrentDate());
			$articleComment->setViewable(true);
			$articleComment->setAssocId($authorSubmission->getId());
			$articleCommentDao->insertArticleComment($articleComment);

			return true;
		} else {
			if (!$request->getUserVar('continued')) {
				$email->setSubject($authorSubmission->getLocalizedTitle());
				if (!empty($editors)) {
					foreach ($editors as $editor) {
						$email->addRecipient($editor->getEmail(), $editor->getFullName());
					}
				} else {
					$email->addRecipient($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
				}
			}

			$email->displayEditForm(Request::url(null, null, 'emailEditorDecisionComment', 'send'), array('articleId' => $authorSubmission->getId()), 'submission/comment/editorDecisionEmail.tpl');

			return false;
		}
	}

	/**
	 * View copyedit comments.
	 * @param $article object
	 */
	function viewCopyeditComments($article) {
		if (!HookRegistry::call('AuthorAction::viewCopyeditComments', array(&$article))) {
			import('classes.submission.form.comment.CopyeditCommentForm');

			$commentForm = new CopyeditCommentForm($article, ROLE_ID_AUTHOR);
			$commentForm->initData();
			$commentForm->display();
		}
	}

	/**
	 * Post copyedit comment.
	 * @param $article object
	 * @param $emailComment boolean
	 * @param $request object
	 */
	function postCopyeditComment($article, $emailComment, $request) {
		if (!HookRegistry::call('AuthorAction::postCopyeditComment', array(&$article, &$emailComment))) {
			import('classes.submission.form.comment.CopyeditCommentForm');

			$commentForm = new CopyeditCommentForm($article, ROLE_ID_AUTHOR);
			$commentForm->readInputData();

			if ($commentForm->validate()) {
				$commentForm->execute();

				// Send a notification to associated users
				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationUsers = $article->getAssociatedUserIds(true, false);
				foreach ($notificationUsers as $userRole) {
					$notificationManager->createNotification(
						$request, $userRole['id'], NOTIFICATION_TYPE_COPYEDIT_COMMENT,
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
		if (!HookRegistry::call('AuthorAction::viewProofreadComments', array(&$article))) {
			import('classes.submission.form.comment.ProofreadCommentForm');

			$commentForm = new ProofreadCommentForm($article, ROLE_ID_AUTHOR);
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
		if (!HookRegistry::call('AuthorAction::postProofreadComment', array(&$article, &$emailComment))) {
			import('classes.submission.form.comment.ProofreadCommentForm');

			$commentForm = new ProofreadCommentForm($article, ROLE_ID_AUTHOR);
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
	 * Download a file an author has access to.
	 * @param $article object
	 * @param $fileId int
	 * @param $revision int
	 * @return boolean
	 * TODO: Complete list of files author has access to
	 */
	function downloadAuthorFile($article, $fileId, $revision = null) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');

		$authorSubmission =& $authorSubmissionDao->getAuthorSubmission($article->getId());
		$layoutSignoff = $signoffDao->getBySymbolic('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $authorSubmission->getId());

		$canDownload = false;

		// Authors have access to:
		// 1) The original submission file.
		// 2) Any files uploaded by the reviewers that are "viewable",
		//    although only after a decision has been made by the editor.
		// 3) The initial and final copyedit files, after initial copyedit is complete.
		// 4) Any of the author-revised files.
		// 5) The layout version of the file.
		// 6) Any supplementary file
		// 7) Any galley file
		// 8) All review versions of the file
		// 9) Current editor versions of the file
		// THIS LIST SHOULD NOW BE COMPLETE.
		if ($authorSubmission->getSubmissionFileId() == $fileId) {
			$canDownload = true;
		} else if ($authorSubmission->getFileBySignoffType('SIGNOFF_COPYEDITING_INITIAL', true) == $fileId) {
			if ($revision != null) {
				$initialSignoff = $signoffDao->getBySymbolic('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $authorSubmission->getId());
				$authorSignoff = $signoffDao->getBySymbolic('SIGNOFF_COPYEDITING_AUTHOR', ASSOC_TYPE_ARTICLE, $authorSubmission->getId());
				$finalSignoff = $signoffDao->getBySymbolic('SIGNOFF_COPYEDITING_FINAL', ASSOC_TYPE_ARTICLE, $authorSubmission->getId());

				if ($initialSignoff && $initialSignoff->getFileRevision()==$revision && $initialSignoff->getDateCompleted()!=null) $canDownload = true;
				else if ($finalSignoff && $finalSignoff->getFileRevision()==$revision && $finalSignoff->getDateCompleted()!=null) $canDownload = true;
				else if ($authorSignoff && $authorSignoff->getFileRevision()==$revision) $canDownload = true;
			} else {
				$canDownload = false;
			}
		} else if ($authorSubmission->getFileBySignoffType('SIGNOFF_COPYEDITING_AUTHOR', true) == $fileId){
			$canDownload = true;
		} else if ($authorSubmission->getRevisedFileId() == $fileId) {
			$canDownload = true;
		} else if ($layoutSignoff->getFileId() == $fileId) {
			$canDownload = true;
		} else {
			// Check reviewer files
			foreach ($authorSubmission->getReviewAssignments() as $roundReviewAssignments) {
				foreach ($roundReviewAssignments as $reviewAssignment) {
					if ($reviewAssignment->getReviewerFileId() == $fileId) {
						$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');

						$articleFile =& $articleFileDao->getArticleFile($fileId, $revision);

						if ($articleFile != null && $articleFile->getViewable()) {
							$canDownload = true;
						}
					}
				}
			}

			// Check supplementary files
			foreach ($authorSubmission->getSuppFiles() as $suppFile) {
				if ($suppFile->getFileId() == $fileId) {
					$canDownload = true;
				}
			}

			// Check galley files
			foreach ($authorSubmission->getGalleys() as $galleyFile) {
				if ($galleyFile->getFileId() == $fileId) {
					$canDownload = true;
				}
			}

			// Check current review version
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewFilesByRound =& $reviewAssignmentDao->getReviewFilesByRound($article->getId());
			$reviewFile = @$reviewFilesByRound[$article->getCurrentRound()];
			if ($reviewFile && $fileId == $reviewFile->getFileId()) {
				$canDownload = true;
			}

			// Check editor version
			$editorFiles = $authorSubmission->getEditorFileRevisions($article->getCurrentRound());
			if (is_array($editorFiles)) foreach ($editorFiles as $editorFile) {
				if ($editorFile->getFileId() == $fileId) {
					$canDownload = true;
				}
			}
		}

		$result = false;
		if (!HookRegistry::call('AuthorAction::downloadAuthorFile', array(&$article, &$fileId, &$revision, &$canDownload, &$result))) {
			if ($canDownload) {
				return Action::downloadFile($article->getId(), $fileId, $revision);
			} else {
				return false;
			}
		}
		return $result;
	}
}

?>
