<?php

/**
 * TrackSubmissionHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.author
 *
 * Handle requests for submission tracking. 
 *
 * $Id$
 */

class TrackSubmissionHandler extends AuthorHandler {
	
	/**
	 * Delete a submission.
	 */
	function deleteSubmission($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);

		// If the submission is incomplete, allow the author to delete it.
		if ($authorSubmission->getSubmissionProgress()!=0) {
			$articleDao = &DAORegistry::getDAO('ArticleDAO');
			$articleDao->deleteArticleById($args[0]);
		}
		
		Request::redirect('author/index');
	}
	
	/**
	 * Delete an author version file.
	 * @param $args array ($articleId, $fileId)
	 */
	function deleteArticleFile($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$fileId = isset($args[1]) ? (int) $args[1] : 0;
		$revisionId = isset($args[2]) ? (int) $args[2] : 0;

		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);
		AuthorAction::deleteArticleFile($authorSubmission, $fileId, $revisionId);
		
		Request::redirect(sprintf('%s/submissionReview/%d', Request::getRequestedPage(), $articleId));
	}
	
	/**
	 * Display a summary of the status of an author's submission.
	 */
	function submission($args) {
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId);
		
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$journalSettings = $journalSettingsDao->getJournalSettings($journal->getJournalId());
			
		// Setting the round.
		$round = isset($args[1]) ? $args[1] : $submission->getCurrentRound();
		
		$editorDecisions = $submission->getDecisions($submission->getCurrentRound());
		$lastDecision = count($editorDecisions) >= 1 ? $editorDecisions[count($editorDecisions) - 1] : null;	
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('journalSettings', $journalSettings);
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('reviewAssignments', $submission->getReviewAssignments($round));
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('round', $round);
		$templateMgr->assign('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign('revisedFile', $submission->getRevisedFile());
		$templateMgr->assign('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('lastEditorDecision', $lastDecision);
		$templateMgr->assign('editorDecisionOptions',
			array(
				'' => 'common.chooseOne',
				SUBMISSION_EDITOR_DECISION_ACCEPT => 'editor.article.decision.accept',
				SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => 'editor.article.decision.pendingRevisions',
				SUBMISSION_EDITOR_DECISION_RESUBMIT => 'editor.article.decision.resubmit',
				SUBMISSION_EDITOR_DECISION_DECLINE => 'editor.article.decision.decline'
			)
		);
		$templateMgr->assign('helpTopicId','editorial.authorsRole');
		$templateMgr->display('author/submission.tpl');
	}

	/**
	 * Display specific details of an author's submission.
	 */
	function submissionReview($args) {
		$user = &Request::getUser();
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		
		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId);
		
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewModifiedByRound = $reviewAssignmentDao->getLastModifiedByRound($articleId);
		$reviewEarliestNotificationByRound = $reviewAssignmentDao->getEarliestNotificationByRound($articleId);
		$reviewFilesByRound = $reviewAssignmentDao->getReviewFilesByRound($articleId);
		$authorViewableFilesByRound = &$reviewAssignmentDao->getAuthorViewableFilesByRound($articleId);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('submission', $authorSubmission);
		$templateMgr->assign('reviewAssignments', $authorSubmission->getReviewAssignments());
		$templateMgr->assign('reviewFilesByRound', $reviewFilesByRound);
		$templateMgr->assign('editor', $authorSubmission->getEditor());
		$templateMgr->assign('reviewFilesByRound', $reviewFilesByRound);
		$templateMgr->assign('authorViewableFilesByRound', &$authorViewableFilesByRound);
		$templateMgr->assign('reviewModifiedByRound', $reviewModifiedByRound);
		$templateMgr->assign('reviewEarliestNotificationByRound', $reviewEarliestNotificationByRound);
		$templateMgr->assign('submissionFile', $authorSubmission->getSubmissionFile());
		$templateMgr->assign('revisedFile', $authorSubmission->getRevisedFile());
		$templateMgr->assign('suppFiles', $authorSubmission->getSuppFiles());
		$templateMgr->assign('editorDecisionOptions',
			array(
				'' => 'common.chooseOne',
				SUBMISSION_EDITOR_DECISION_ACCEPT => 'editor.article.decision.accept',
				SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => 'editor.article.decision.pendingRevisions',
				SUBMISSION_EDITOR_DECISION_RESUBMIT => 'editor.article.decision.resubmit',
				SUBMISSION_EDITOR_DECISION_DECLINE => 'editor.article.decision.decline'
			)
		);
		$templateMgr->assign('helpTopicId', 'editorial.authorsRole.review');
		$templateMgr->display('author/submissionReview.tpl');
	}

	/**
	 * Add a supplementary file.
	 * @param $args array ($articleId)
	 */
	function addSuppFile($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'summary');

		import('submission.form.SuppFileForm');

		$submitForm = &new SuppFileForm($authorSubmission);

		$submitForm->initData();
		$submitForm->display();
	}

	/**
	 * Edit a supplementary file.
	 * @param $args array ($articleId, $suppFileId)
	 */
	function editSuppFile($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$suppFileId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'summary');
		
		import('submission.form.SuppFileForm');
		
		$submitForm = &new SuppFileForm($authorSubmission, $suppFileId);
		
		$submitForm->initData();
		$submitForm->display();
	}
	
	/**
	 * Set reviewer visibility for a supplementary file.
	 * @param $args array ($suppFileId)
	 */
	function setSuppFileVisibility($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);
		
		$suppFileId = Request::getUserVar('fileId');
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$suppFile = $suppFileDao->getSuppFile($suppFileId, $articleId);

		if (isset($suppFile) && $suppFile != null) {
			$suppFile->setShowReviewers(Request::getUserVar('hide')==1?0:1);
			$suppFileDao->updateSuppFile($suppFile);
		}
		Request::redirect(sprintf('%s/submissionReview/%d', Request::getRequestedPage(), $articleId));
	}
	
	/**
	 * Save a supplementary file.
	 * @param $args array ($suppFileId)
	 */
	function saveSuppFile($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);

		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;

		import('submission.form.SuppFileForm');

		$submitForm = &new SuppFileForm($authorSubmission, $suppFileId);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();
			Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $articleId));

		} else {
			parent::setupTemplate(true, $articleId, 'summary');
			$submitForm->display();
		}
	}

	/**
	 * Display the status and other details of an author's submission.
	 */
	function submissionEditing($args) {
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId);
		
		AuthorAction::copyeditUnderway($submission);
		import('submission.proofreader.ProofreaderAction');
		ProofreaderAction::authorProofreadingUnderway($articleId);
	
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('copyeditor', $submission->getCopyeditor());
		$templateMgr->assign('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign('initialCopyeditFile', $submission->getInitialCopyeditFile());
		$templateMgr->assign('editorAuthorCopyeditFile', $submission->getEditorAuthorCopyeditFile());
		$templateMgr->assign('finalCopyeditFile', $submission->getFinalCopyeditFile());
		$templateMgr->assign('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('useCopyeditors', $journal->getSetting('useCopyeditors'));
		$templateMgr->assign('useLayoutEditors', $journal->getSetting('useLayoutEditors'));
		$templateMgr->assign('useProofreaders', $journal->getSetting('useProofreaders'));
		$templateMgr->assign('proofAssignment', $submission->getProofAssignment());
		$templateMgr->assign('helpTopicId', 'editorial.authorsRole.editing');	
		$templateMgr->display('author/submissionEditing.tpl');
	}
	
	/**
	 * Upload the author's revised version of an article.
	 */
	function uploadRevisedVersion() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);	
		parent::setupTemplate(true);
			
		AuthorAction::uploadRevisedVersion($submission);
		
		Request::redirect(sprintf('author/submissionReview/%d', $articleId));	
	}
	
	function viewMetadata($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'summary');
		
		AuthorAction::viewMetadata($submission, ROLE_ID_AUTHOR);
	}
	
	function saveMetadata() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId);
		
		if (AuthorAction::saveMetadata($submission)) {
			Request::redirect(Request::getRequestedPage() . "/submission/$articleId");
		}
	}

	function uploadCopyeditVersion() {
		$copyeditStage = Request::getUserVar('copyeditStage');
		$articleId = Request::getUserVar('articleId');
		
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId);
		
		AuthorAction::uploadCopyeditVersion($submission, $copyeditStage);
		
		Request::redirect(sprintf('author/submissionEditing/%d', $articleId));	
	}
	
	function completeAuthorCopyedit($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);		
		
		if (AuthorAction::completeAuthorCopyedit($submission, Request::getUserVar('send'))) {
			Request::redirect(sprintf('author/submissionEditing/%d', $articleId));
		}
	}
	
	//
	// Misc
	//
	
	/**
	 * Download a file.
	 * @param $args array ($articleId, $fileId, [$revision])
	 */
	function downloadFile($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$fileId = isset($args[1]) ? $args[1] : 0;
		$revision = isset($args[2]) ? $args[2] : null;

		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		if (!AuthorAction::downloadAuthorFile($submission, $fileId, $revision)) {
			Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $articleId));
		}
	}

	/**
	 * Download a file.
	 * @param $args array ($articleId, $fileId, [$revision])
	 */
	function download($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$fileId = isset($args[1]) ? $args[1] : 0;
		$revision = isset($args[2]) ? $args[2] : null;

		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		Action::downloadFile($articleId, $fileId, $revision);
	}
	
	//
	// Validation
	//
	
	/**
	 * Validate that the user is the author for the article.
	 * Redirects to author index page if validation fails.
	 */
	function validate($articleId) {
		parent::validate();
		
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$isValid = true;
		
		$authorSubmission = &$authorSubmissionDao->getAuthorSubmission($articleId);
	
		if ($authorSubmission == null) {
			$isValid = false;
		} else if ($authorSubmission->getJournalId() != $journal->getJournalId()) {
			$isValid = false;
		} else {
			if ($authorSubmission->getUserId() != $user->getUserId()) {
				$isValid = false;
			}
		}
		
		if (!$isValid) {
			Request::redirect(Request::getRequestedPage());
		}

		return array($journal, $authorSubmission);
	}

	//
	// Proofreading
	//

	/**
	 * Set the author proofreading date completion
	 */
	function authorProofreadingComplete($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);

		$send = isset($args[0]) && $args[0] == 'send' ? true : false;

		import('submission.proofreader.ProofreaderAction');

		if ($send) {
			ProofreaderAction::proofreadEmail($articleId,'PROOFREAD_AUTHOR_COMPLETE');
			Request::redirect(sprintf('author/submissionEditing/%d', $articleId));	
		} else {
			ProofreaderAction::proofreadEmail($articleId,'PROOFREAD_AUTHOR_COMPLETE','/author/authorProofreadingComplete/send');
		}
	}

	/**
	 * Proof / "preview" a galley.
	 * @param $args array ($articleId, $galleyId)
	 */
	function proofGalley($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->display('submission/layout/proofGalley.tpl');
	}
	
	/**
	 * Proof galley (shows frame header).
	 * @param $args array ($articleId, $galleyId)
	 */
	function proofGalleyTop($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('backHandler', 'submissionEditing');
		$templateMgr->display('submission/layout/proofGalleyTop.tpl');
	}
	
	/**
	 * Proof galley (outputs file contents).
	 * @param $args array ($articleId, $galleyId)
	 */
	function proofGalleyFile($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$galleyDao->getGalley($galleyId, $articleId);
		
		import('file.ArticleFileManager'); // FIXME
		
		if (isset($galley)) {
			if ($galley->isHTMLGalley()) {
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('galley', $galley);
				$templateMgr->display('submission/layout/proofGalleyHTML.tpl');
				
			} else {
				// View non-HTML file inline
				TrackSubmissionHandler::viewFile(array($articleId, $galley->getFileId()));
			}
		}
	}
	
	/**
	 * View a file (inlines file).
	 * @param $args array ($articleId, $fileId, [$revision])
	 */
	function viewFile($args) {
		$articleId = isset($args[0]) ? $args[0] : 0;
		$fileId = isset($args[1]) ? $args[1] : 0;
		$revision = isset($args[2]) ? $args[2] : null;

		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		if (!AuthorAction::viewFile($articleId, $fileId, $revision)) {
			Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $articleId));
		}
	}

}
?>
