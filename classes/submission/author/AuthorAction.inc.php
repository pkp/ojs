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
		if (isset($articleFile)) foreach ($authorRevisions as $round) {
			foreach ($round as $revision) {
				if ($revision->getFileId() == $articleFile->getFileId() &&
				    $revision->getRevision() == $articleFile->getRevision()) {
					$articleFileManager->deleteFile($articleFile->getFileId(), $articleFile->getRevision());
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
		
		$editAssignment = $authorSubmission->getEditor();
		if ($editAssignment->getEditorId() != null) {
			$editor = &$userDao->getUser($editAssignment->getEditorId());
		}

		$copyeditor = $authorSubmission->getCopyeditor();
		
		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
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
					if (isset($editor)) {
						$email->addCc($editor->getEmail(), $editor->getFullName());
					} else {
						$email->addCc($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
					}
				} else {
					if (isset($editor)) {
						$email->addRecipient($editor->getEmail(), $editor->getFullName());
					} else {
						$email->addRecipient($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
					}
				}

				$paramArray = array(
					'editorialContactName' => isset($copyeditor)?$copyeditor->getFullName():$editor->getFullName(),
					'authorName' => $user->getFullName()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::getPageUrl() . '/author/completeAuthorCopyedit/send', array('articleId' => $authorSubmission->getArticleId()));

			return false;
		}
	}
	
	/**
	 * Set that the copyedit is underway.
	 */
	function copyeditUnderway($authorSubmission) {
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');		
		
		if ($authorSubmission->getCopyeditorDateAuthorNotified() != null && $authorSubmission->getCopyeditorDateAuthorUnderway() == null) {
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
		import("submission.form.comment.LayoutCommentForm");

		$commentForm = &new LayoutCommentForm($article, ROLE_ID_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post layout comment.
	 * @param $article object
	 * @param $emailComment boolean
	 */
	function postLayoutComment($article, $emailComment) {
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
	
	/**
	 * View editor decision comments.
	 * @param $article object
	 */
	function viewEditorDecisionComments($article) {
		import("submission.form.comment.EditorDecisionCommentForm");
		
		$commentForm = &new EditorDecisionCommentForm($article, ROLE_ID_AUTHOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post editor decision comment.
	 * @param $article object
	 * @param $emailComment boolean
	 */
	function postEditorDecisionComment($article, $emailComment) {
		import("submission.form.comment.EditorDecisionCommentForm");
		
		$commentForm = &new EditorDecisionCommentForm($article, ROLE_ID_AUTHOR);
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
		
		$commentForm = &new CopyeditCommentForm($article, ROLE_ID_AUTHOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post copyedit comment.
	 * @param $article object
	 */
	function postCopyeditComment($article, $emailComment) {
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

	/**
	 * View proofread comments.
	 * @param $article object
	 */
	function viewProofreadComments($article) {
		import("submission.form.comment.ProofreadCommentForm");
		
		$commentForm = &new ProofreadCommentForm($article, ROLE_ID_AUTHOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post proofread comment.
	 * @param $article object
	 * @param $emailComment boolean
	 */
	function postProofreadComment($article, $emailComment) {
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
			$reviewFilesByRound = $reviewAssignmentDao->getReviewFilesByRound($article->getArticleId());
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
		
		if ($canDownload) {
			return Action::downloadFile($article->getArticleId(), $fileId, $revision);
		} else {
			return false;
		}
	}
}

?>
