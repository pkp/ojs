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
		parent::validate();
		parent::setupTemplate(true);
			
		$articleId = $args[0];

		TrackSubmissionHandler::validate($articleId);

		$journal = &Request::getJournal();
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$articleDao->deleteArticleById($args[0]);
		
		Request::redirect('author/index');
	}
	
	/**
	 * Display a summary of the status of an author's submission.
	 */
	function submission($args) {
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$articleId = $args[0];
		
		parent::validate();
		parent::setupTemplate(true, $articleId);
		
		TrackSubmissionHandler::validate($articleId);
		
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		$submission = $authorSubmissionDao->getAuthorSubmission($articleId);
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$journalSettings = $journalSettingsDao->getJournalSettings($journal->getJournalId());
			
		// Setting the round.
		$round = isset($args[1]) ? $args[1] : $submission->getCurrentRound();
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('journalSettings', $journalSettings);
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('reviewAssignments', $submission->getReviewAssignments($round));
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('round', $round);
		$templateMgr->assign('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign('revisedFile', $submission->getRevisedFile());
		$templateMgr->assign('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('editorDecisionOptions',
			array(
				'' => 'editor.article.decision.chooseOne',
				SUBMISSION_EDITOR_DECISION_ACCEPT => 'editor.article.decision.accept',
				SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => 'editor.article.decision.pendingRevisions',
				SUBMISSION_EDITOR_DECISION_RESUBMIT => 'editor.article.decision.resubmit',
				SUBMISSION_EDITOR_DECISION_DECLINE => 'editor.article.decision.decline'
			)
		);

		$templateMgr->display('author/submission.tpl');
	}

	/**
	 * Display specific details of an author's submission.
	 */
	function submissionReview($args) {
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$articleId = $args[0];
		
		parent::validate();
		parent::setupTemplate(true, $articleId);
		
		TrackSubmissionHandler::validate($articleId);
		
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		$submission = $authorSubmissionDao->getAuthorSubmission($articleId);
			
		// Setting the round.
		$round = isset($args[1]) ? $args[1] : $submission->getCurrentRound();
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('reviewAssignments', $submission->getReviewAssignments($round));
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('round', $round);
		$templateMgr->assign('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign('revisedFile', $submission->getRevisedFile());
		$templateMgr->assign('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('editorDecisionOptions',
			array(
				'' => 'editor.article.decision.chooseOne',
				SUBMISSION_EDITOR_DECISION_ACCEPT => 'editor.article.decision.accept',
				SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => 'editor.article.decision.pendingRevisions',
				SUBMISSION_EDITOR_DECISION_RESUBMIT => 'editor.article.decision.resubmit',
				SUBMISSION_EDITOR_DECISION_DECLINE => 'editor.article.decision.decline'
			)
		);

		$templateMgr->display('author/submissionReview.tpl');
	}

	/**
	 * Add a supplementary file.
	 * @param $args array ($articleId)
	 */
	function addSuppFile($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'submission');

		import('submission.form.SuppFileForm');

		$submitForm = &new SuppFileForm($articleId);

		$submitForm->initData();
		$submitForm->display();
	}

	/**
	 * Save a supplementary file.
	 * @param $args array ($suppFileId)
	 */
	function saveSuppFile($args) {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);

		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;

		import('submission.form.SuppFileForm');

		$submitForm = &new SuppFileForm($articleId, $suppFileId);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();
			Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $articleId));

		} else {
			parent::setupTemplate(true, $articleId, 'submission');
			$submitForm->display();
		}
	}

	/**
	 * Display the status and other details of an author's submission.
	 */
	function submissionEditing($args) {
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$articleId = $args[0];
		
		parent::validate();
		parent::setupTemplate(true, $articleId);

		TrackSubmissionHandler::validate($articleId);
		
		AuthorAction::copyeditUnderway($articleId);
		ProofreaderAction::authorProofreadingUnderway($articleId);
	
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		$submission = $authorSubmissionDao->getAuthorSubmission($articleId);
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('copyeditor', $submission->getCopyeditor());
		$templateMgr->assign('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign('initialCopyeditFile', $submission->getInitialCopyeditFile());
		$templateMgr->assign('editorAuthorCopyeditFile', $submission->getEditorAuthorCopyeditFile());
		$templateMgr->assign('finalCopyeditFile', $submission->getFinalCopyeditFile());
		$templateMgr->assign('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('proofAssignment', $submission->getProofAssignment());
	
		$templateMgr->display('author/submissionEditing.tpl');
	}
	
	/**
	 * Upload the author's revised version of an article.
	 */
	function uploadRevisedVersion() {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);		
		AuthorAction::uploadRevisedVersion($articleId);
		
		Request::redirect(sprintf('author/submission/%d', $articleId));	
	}
	
	function viewMetadata($args) {
		$articleId = $args[0];

		parent::validate();
		parent::setupTemplate(true, $articleId);
	
		TrackSubmissionHandler::validate($articleId);
		AuthorAction::viewMetadata($articleId, ROLE_ID_AUTHOR);
	}
	
	function saveMetadata() {
		$articleId = Request::getUserVar('articleId');
		
		parent::validate();
		parent::setupTemplate(true, $articleId);
		
		TrackSubmissionHandler::validate($articleId);
		AuthorAction::saveMetadata($articleId);
		Request::redirect(Request::getRequestedPage() . "/submission/$articleId");
	}

	function uploadCopyeditVersion() {
		$copyeditStage = Request::getUserVar('copyeditStage');
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		parent::validate();
		parent::setupTemplate(true, $articleId);
		
		AuthorAction::uploadCopyeditVersion($articleId, $copyeditStage);
		
		Request::redirect(sprintf('author/submissionEditing/%d', $articleId));	
	}
	
	function completeAuthorCopyedit($args) {
		parent::validate();
		parent::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		
		if (isset($args[0]) && $args[0] == 'send') {
			$send = true;
			AuthorAction::completeAuthorCopyedit($articleId, $send);
			Request::redirect(sprintf('author/submissionEditing/%d', $articleId));
		} else {
			AuthorAction::completeAuthorCopyedit($articleId);
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

		TrackSubmissionHandler::validate($articleId);
		if (!AuthorAction::downloadAuthorFile($articleId, $fileId, $revision)) {
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

		TrackSubmissionHandler::validate($articleId);
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
	}

	//
	// Proofreading
	//

	/**
	 * Set the author proofreading date completion
	 */
	function authorProofreadingComplete($args) {
		parent::validate();
		parent::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		$send = false;
		if (isset($args[0])) {
			$send = ($args[0] == 'send') ? true : false;
		}

		TrackSubmissionHandler::validate($articleId);

		if ($send) {
			ProofreaderAction::proofreadEmail($articleId,'PROOFREAD_AUTHOR_COMP');
			Request::redirect(sprintf('author/submissionEditing/%d', $articleId));	
		} else {
			ProofreaderAction::proofreadEmail($articleId,'PROOFREAD_AUTHOR_COMP','/author/authorProofreadingComplete/send');
		}
	}
}
?>
