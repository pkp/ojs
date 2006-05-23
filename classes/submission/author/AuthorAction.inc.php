<?php

/**
 * AuthorAction.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * AuthorAction class.
 *
 * $Id$
 */

import('submission.common.Action');

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
		import('file.ArticleFileManager');
		$articleFileManager = &new ArticleFileManager($authorSubmission->getArticleId());
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		
		if ($designate && !HookRegistry::call('AuthorAction::designateReviewVersion', array(&$authorSubmission))) {
			$submissionFile =& $authorSubmission->getSubmissionFile();
			if ($submissionFile) {
				$reviewFileId = $articleFileManager->copyToReviewFile($submissionFile->getFileId());
				$editorFileId = $articleFileManager->copyToEditorFile($reviewFileId, null, null);

				$authorSubmission->setReviewFileId($reviewFileId);
				$authorSubmission->setEditorFileId($editorFileId);
			
				$authorSubmissionDao->updateAuthorSubmission($authorSubmission);

				$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
				$sectionEditorSubmissionDao->createReviewRound($authorSubmission->getArticleId(), 1, 1);
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
		import('file.ArticleFileManager');

		$articleFileManager = &new ArticleFileManager($article->getArticleId());
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');

		$articleFile = &$articleFileDao->getArticleFile($fileId, $revisionId, $article->getArticleId());
		$authorSubmission = $authorSubmissionDao->getAuthorSubmission($article->getArticleId());
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
	 */
	function uploadRevisedVersion($authorSubmission) {
		import("file.ArticleFileManager");
		$articleFileManager = &new ArticleFileManager($authorSubmission->getArticleId());
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		
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

			// Add log entry
			$user = &Request::getUser();
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($authorSubmission->getArticleId(), ARTICLE_LOG_AUTHOR_REVISION, ARTICLE_LOG_TYPE_AUTHOR, $user->getUserId(), 'log.author.documentRevised', array('authorName' => $user->getFullName(), 'fileId' => $fileId, 'articleId' => $authorSubmission->getArticleId()));
		}
	}
	
	/**
	 * Author completes editor / author review.
	 * @param $authorSubmission object
	 */
	function completeAuthorCopyedit($authorSubmission, $send = false) {
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();

		if ($authorSubmission->getCopyeditorDateAuthorCompleted() != null) {
			return true;
		}
		
		$user = &Request::getUser();
		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($authorSubmission, 'COPYEDIT_AUTHOR_COMPLETE');
		
		$editAssignments = $authorSubmission->getEditAssignments();

		$copyeditor =& $authorSubmission->getCopyeditor();
		
		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('AuthorAction::completeAuthorCopyedit', array(&$authorSubmission, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_AUTHOR_COMPLETE, ARTICLE_EMAIL_TYPE_COPYEDIT, $authorSubmission->getArticleId());
				$email->send();
			}
				
			$authorSubmission->setCopyeditorDateAuthorCompleted(Core::getCurrentDate());
			$authorSubmission->setCopyeditorDateFinalNotified(Core::getCurrentDate());
			$authorSubmissionDao->updateAuthorSubmission($authorSubmission);
			
			// Add log entry
			import('article.log.ArticleLog');
			import('article.log.ArticleEventLogEntry');
			ArticleLog::logEvent($authorSubmission->getArticleId(), ARTICLE_LOG_COPYEDIT_REVISION, ARTICLE_LOG_TYPE_AUTHOR, $user->getUserId(), 'log.copyedit.authorFile');

			return true;

		} else {
			if (!Request::getUserVar('continued')) {
				if (isset($copyeditor)) {
					$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
					$assignedSectionEditors = $email->ccAssignedEditingSectionEditors($copyeditorSubmission->getArticleId());
					$assignedEditors = $email->ccAssignedEditors($copyeditorSubmission->getArticleId());
					if (empty($assignedSectionEditors) && empty($assignedEditors)) {
						$email->addCc($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
						$editorName = $journal->getSetting('contactName');
					} else {
						$editor = array_shift($assignedSectionEditors);
						if (!$editor) $editor = array_shift($assignedEditors);
						$editorName = $editor->getEditorFullName();
					}
				} else {
					$assignedSectionEditors = $email->toAssignedEditingSectionEditors($copyeditorSubmission->getArticleId());
					$assignedEditors = $email->ccAssignedEditors($copyeditorSubmission->getArticleId());
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
			$email->displayEditForm(Request::url(null, 'author', 'completeAuthorCopyedit', 'send'), array('articleId' => $authorSubmission->getArticleId()));

			return false;
		}
	}
	
	/**
	 * Set that the copyedit is underway.
	 */
	function copyeditUnderway($authorSubmission) {
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');		
		
		if ($authorSubmission->getCopyeditorDateAuthorNotified() != null && $authorSubmission->getCopyeditorDateAuthorUnderway() == null) {
			HookRegistry::call('AuthorAction::copyeditUnderway', array(&$authorSubmission));
			$authorSubmission->setCopyeditorDateAuthorUnderway(Core::getCurrentDate());
			$authorSubmissionDao->updateAuthorSubmission($authorSubmission);
		}
	}	
	
	/**
	 * Upload the revised version of a copyedit file.
	 * @param $authorSubmission object
	 * @param $copyeditStage string
	 */
	function uploadCopyeditVersion($authorSubmission, $copyeditStage) {
		import("file.ArticleFileManager");
		$articleFileManager = &new ArticleFileManager($authorSubmission->getArticleId());
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		
		// Authors cannot upload if the assignment is not active, i.e.
		// they haven't been notified or the assignment is already complete.
		if (!$authorSubmission->getCopyeditorDateAuthorNotified() || $authorSubmission->getCopyeditorDateAuthorCompleted()) return;

		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName)) {
			HookRegistry::call('AuthorAction::uploadCopyeditVersion', array(&$authorSubmission, &$copyeditStage));
			if ($authorSubmission->getCopyeditFileId() != null) {
				$fileId = $articleFileManager->uploadCopyeditFile($fileName, $authorSubmission->getCopyeditFileId());
			} else {
				$fileId = $articleFileManager->uploadCopyeditFile($fileName);
			}
		}
	
		$authorSubmission->setCopyeditFileId($fileId);
		
		if ($copyeditStage == 'author') {
			$authorSubmission->setCopyeditorEditorAuthorRevision($articleFileDao->getRevisionNumber($fileId));
		}
		
		$authorSubmissionDao->updateAuthorSubmission($authorSubmission);
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
			import("submission.form.comment.LayoutCommentForm");
			$commentForm = &new LayoutCommentForm($article, ROLE_ID_EDITOR);
			$commentForm->initData();
			$commentForm->display();
		}
	}
	
	/**
	 * Post layout comment.
	 * @param $article object
	 * @param $emailComment boolean
	 */
	function postLayoutComment($article, $emailComment) {
		if (!HookRegistry::call('AuthorAction::postLayoutComment', array(&$article, &$emailComment))) {
			import("submission.form.comment.LayoutCommentForm");

			$commentForm = &new LayoutCommentForm($article, ROLE_ID_AUTHOR);
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
	}
	
	/**
	 * View editor decision comments.
	 * @param $article object
	 */
	function viewEditorDecisionComments($article) {
		if (!HookRegistry::call('AuthorAction::viewEditorDecisionComments', array(&$article))) {
			import("submission.form.comment.EditorDecisionCommentForm");

			$commentForm = &new EditorDecisionCommentForm($article, ROLE_ID_AUTHOR);
			$commentForm->initData();
			$commentForm->display();
		}
	}
	
	/**
	 * Email editor decision comment.
	 * @param $authorSubmission object
	 * @param $send boolean
	 */
	function emailEditorDecisionComment($authorSubmission, $send) {
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();

		$user = &Request::getUser();
		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($authorSubmission);
	
		$editAssignments = $authorSubmission->getEditAssignments();
		$editors = array();
		foreach ($editAssignments as $editAssignment) {
			array_push($editors, $userDao->getUser($editAssignment->getEditorId()));
		}

		if ($send && !$email->hasErrors()) {
			HookRegistry::call('AuthorAction::emailEditorDecisionComment', array(&$authorSubmission, &$email));
			$email->send();

			$articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
			$articleComment =& new ArticleComment();
			$articleComment->setCommentType(COMMENT_TYPE_EDITOR_DECISION);
			$articleComment->setRoleId(ROLE_ID_AUTHOR);
			$articleComment->setArticleId($authorSubmission->getArticleId());
			$articleComment->setAuthorId($authorSubmission->getUserId());
			$articleComment->setCommentTitle($email->getSubject());
			$articleComment->setComments($email->getBody());
			$articleComment->setDatePosted(Core::getCurrentDate());
			$articleComment->setViewable(true);
			$articleComment->setAssocId($authorSubmission->getArticleId());
			$articleCommentDao->insertArticleComment($articleComment);

			return true;
		} else {
			if (!Request::getUserVar('continued')) {
				$email->setSubject($authorSubmission->getArticleTitle());
				if (!empty($editors)) {
					foreach ($editors as $editor) {
						$email->addRecipient($editor->getEmail(), $editor->getFullName());
					}
				} else {
					$email->addRecipient($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
				}
			}

			$email->displayEditForm(Request::url(null, null, 'emailEditorDecisionComment', 'send'), array('articleId' => $authorSubmission->getArticleId()), 'submission/comment/editorDecisionEmail.tpl');

			return false;
		}
	}
	
	/**
	 * View copyedit comments.
	 * @param $article object
	 */
	function viewCopyeditComments($article) {
		if (!HookRegistry::call('AuthorAction::viewCopyeditComments', array(&$article))) {
			import("submission.form.comment.CopyeditCommentForm");
		
			$commentForm = &new CopyeditCommentForm($article, ROLE_ID_AUTHOR);
			$commentForm->initData();
			$commentForm->display();
		}
	}
	
	/**
	 * Post copyedit comment.
	 * @param $article object
	 */
	function postCopyeditComment($article, $emailComment) {
		if (!HookRegistry::call('AuthorAction::postCopyeditComment', array(&$article, &$emailComment))) {
			import("submission.form.comment.CopyeditCommentForm");
		
			$commentForm = &new CopyeditCommentForm($article, ROLE_ID_AUTHOR);
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
	}

	/**
	 * View proofread comments.
	 * @param $article object
	 */
	function viewProofreadComments($article) {
		if (!HookRegistry::call('AuthorAction::viewProofreadComments', array(&$article))) {
			import("submission.form.comment.ProofreadCommentForm");
		
			$commentForm = &new ProofreadCommentForm($article, ROLE_ID_AUTHOR);
			$commentForm->initData();
			$commentForm->display();
		}
	}
	
	/**
	 * Post proofread comment.
	 * @param $article object
	 * @param $emailComment boolean
	 */
	function postProofreadComment($article, $emailComment) {
		if (!HookRegistry::call('AuthorAction::postProofreadComment', array(&$article, &$emailComment))) {
			import("submission.form.comment.ProofreadCommentForm");
		
			$commentForm = &new ProofreadCommentForm($article, ROLE_ID_AUTHOR);
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
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');		

		$submission = &$authorSubmissionDao->getAuthorSubmission($article->getArticleId());
		$layoutAssignment = &$submission->getLayoutAssignment();

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
		if ($submission->getSubmissionFileId() == $fileId) {
			$canDownload = true;
		} else if ($submission->getCopyeditFileId() == $fileId) {
			if ($revision != null) {
				$copyAssignmentDao = &DAORegistry::getDAO('CopyAssignmentDAO');
				$copyAssignment = &$copyAssignmentDao->getCopyAssignmentByArticleId($article->getArticleId());
				if ($copyAssignment && $copyAssignment->getInitialRevision()==$revision && $copyAssignment->getDateCompleted()!=null) $canDownload = true;
				else if ($copyAssignment && $copyAssignment->getFinalRevision()==$revision && $copyAssignment->getDateFinalCompleted()!=null) $canDownload = true;
				else if ($copyAssignment && $copyAssignment->getEditorAuthorRevision()==$revision) $canDownload = true; 
			} else {
				$canDownload = false;
			}
		} else if ($submission->getRevisedFileId() == $fileId) {
			$canDownload = true;
		} else if ($layoutAssignment->getLayoutFileId() == $fileId) {
			$canDownload = true;
		} else {
			// Check reviewer files
			foreach ($submission->getReviewAssignments() as $roundReviewAssignments) {
				foreach ($roundReviewAssignments as $reviewAssignment) {
					if ($reviewAssignment->getReviewerFileId() == $fileId) {
						$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
						
						$articleFile = &$articleFileDao->getArticleFile($fileId, $revision);
						
						if ($articleFile != null && $articleFile->getViewable()) {
							$canDownload = true;
						}
					}
				}
			}
			
			// Check supplementary files
			foreach ($submission->getSuppFiles() as $suppFile) {
				if ($suppFile->getFileId() == $fileId) {
					$canDownload = true;
				}
			}
			
			// Check galley files
			foreach ($submission->getGalleys() as $galleyFile) {
				if ($galleyFile->getFileId() == $fileId) {
					$canDownload = true;
				}
			}

			// Check current review version
			$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewFilesByRound =& $reviewAssignmentDao->getReviewFilesByRound($article->getArticleId());
			$reviewFile = @$reviewFilesByRound[$article->getCurrentRound()];
			if ($reviewFile && $fileId == $reviewFile->getFileId()) {
				$canDownload = true;
			}

			// Check editor version
			$editorFiles = $submission->getEditorFileRevisions($article->getCurrentRound());
			foreach ($editorFiles as $editorFile) {
				if ($editorFile->getFileId() == $fileId) {
					$canDownload = true;
				}
			}
		}
		
		$result = false;
		if (!HookRegistry::call('AuthorAction::downloadAuthorFile', array(&$article, &$fileId, &$revision, &$canDownload, &$result))) {
			if ($canDownload) {
				return Action::downloadFile($article->getArticleId(), $fileId, $revision);
			} else {
				return false;
			}
		}
		return $result;
	}
}

?>
