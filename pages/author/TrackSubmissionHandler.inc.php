<?php

/**
 * TrackSubmissionHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
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
			import('file.ArticleFileManager');
			$articleFileManager = &new ArticleFileManager($articleId);
			$articleFileManager->deleteArticleTree();
			
			$articleDao = &DAORegistry::getDAO('ArticleDAO');
			$articleDao->deleteArticleById($args[0]);
		}
		
		Request::redirect(null, null, 'index');
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
		if ($authorSubmission->getStatus() != STATUS_PUBLISHED && $authorSubmission->getStatus() != STATUS_ARCHIVED) {
			AuthorAction::deleteArticleFile($authorSubmission, $fileId, $revisionId);
		}
		
		Request::redirect(null, null, 'submissionReview', $articleId);
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

		$templateMgr = &TemplateManager::getManager();

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = &$publishedArticleDao->getPublishedArticleByArticleId($submission->getArticleId());
		if ($publishedArticle) {
			$issueDao = &DAORegistry::getDAO('IssueDAO');
			$issue = &$issueDao->getIssueById($publishedArticle->getIssueId());
			$templateMgr->assign_by_ref('issue', $issue);
		}

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$section = &$sectionDao->getSection($submission->getSectionId());
		$templateMgr->assign_by_ref('section', $section);

		$templateMgr->assign_by_ref('journalSettings', $journalSettings);
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('reviewAssignments', $submission->getReviewAssignments($round));
		$templateMgr->assign('round', $round);
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('revisedFile', $submission->getRevisedFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());

		import('submission.sectionEditor.SectionEditorSubmission');
		$templateMgr->assign_by_ref('editorDecisionOptions', SectionEditorSubmission::getEditorDecisionOptions());

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
		$reviewFilesByRound =& $reviewAssignmentDao->getReviewFilesByRound($articleId);
		$authorViewableFilesByRound = &$reviewAssignmentDao->getAuthorViewableFilesByRound($articleId);

		$editorDecisions = $authorSubmission->getDecisions($authorSubmission->getCurrentRound());
		$lastDecision = count($editorDecisions) >= 1 ? $editorDecisions[count($editorDecisions) - 1] : null;
		
		$templateMgr = &TemplateManager::getManager();

		$reviewAssignments =& $authorSubmission->getReviewAssignments();
		$templateMgr->assign_by_ref('reviewAssignments', $reviewAssignments);
		$templateMgr->assign_by_ref('submission', $authorSubmission);
		$templateMgr->assign_by_ref('reviewFilesByRound', $reviewFilesByRound);
		$templateMgr->assign_by_ref('reviewFilesByRound', $reviewFilesByRound);
		$templateMgr->assign_by_ref('authorViewableFilesByRound', $authorViewableFilesByRound);
		$templateMgr->assign_by_ref('reviewModifiedByRound', $reviewModifiedByRound);

		$reviewIndexesByRound = array();
		for ($round = 1; $round <= $authorSubmission->getCurrentRound(); $round++) {
			$reviewIndexesByRound[$round] = $reviewAssignmentDao->getReviewIndexesForRound($articleId, $round);
		}
		$templateMgr->assign_by_ref('reviewIndexesByRound', $reviewIndexesByRound);

		$templateMgr->assign('reviewEarliestNotificationByRound', $reviewEarliestNotificationByRound);
		$templateMgr->assign_by_ref('submissionFile', $authorSubmission->getSubmissionFile());
		$templateMgr->assign_by_ref('revisedFile', $authorSubmission->getRevisedFile());
		$templateMgr->assign_by_ref('suppFiles', $authorSubmission->getSuppFiles());
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
		if ($authorSubmission->getStatus() != STATUS_PUBLISHED && $authorSubmission->getStatus() != STATUS_ARCHIVED) {
			parent::setupTemplate(true, $articleId, 'summary');

			import('submission.form.SuppFileForm');

			$submitForm = &new SuppFileForm($authorSubmission);

			$submitForm->initData();
			$submitForm->display();
		} else {
			Request::redirect(null, null, 'submission', $articleId);
		}
	}

	/**
	 * Edit a supplementary file.
	 * @param $args array ($articleId, $suppFileId)
	 */
	function editSuppFile($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$suppFileId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);
		if ($authorSubmission->getStatus() != STATUS_PUBLISHED && $authorSubmission->getStatus() != STATUS_ARCHIVED) {
			parent::setupTemplate(true, $articleId, 'summary');
		
			import('submission.form.SuppFileForm');
		
			$submitForm = &new SuppFileForm($authorSubmission, $suppFileId);
		
			$submitForm->initData();
			$submitForm->display();
		} else {
			Request::redirect(null, null, 'submission', $articleId);
		}
	}
	
	/**
	 * Set reviewer visibility for a supplementary file.
	 * @param $args array ($suppFileId)
	 */
	function setSuppFileVisibility($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);
		
		if ($authorSubmission->getStatus() != STATUS_PUBLISHED && $authorSubmission->getStatus() != STATUS_ARCHIVED) {
			$suppFileId = Request::getUserVar('fileId');
			$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
			$suppFile = $suppFileDao->getSuppFile($suppFileId, $articleId);

			if (isset($suppFile) && $suppFile != null) {
				$suppFile->setShowReviewers(Request::getUserVar('hide')==1?0:1);
				$suppFileDao->updateSuppFile($suppFile);
			}
		}
		Request::redirect(null, null, 'submissionReview', $articleId);
	}
	
	/**
	 * Save a supplementary file.
	 * @param $args array ($suppFileId)
	 */
	function saveSuppFile($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $authorSubmission) = TrackSubmissionHandler::validate($articleId);

		if ($authorSubmission->getStatus() != STATUS_PUBLISHED && $authorSubmission->getStatus() != STATUS_ARCHIVED) {
			$suppFileId = isset($args[0]) ? (int) $args[0] : 0;

			import('submission.form.SuppFileForm');

			$submitForm = &new SuppFileForm($authorSubmission, $suppFileId);
			$submitForm->readInputData();

			if ($submitForm->validate()) {
				$submitForm->execute();
				Request::redirect(null, null, 'submission', $articleId);
			} else {
				parent::setupTemplate(true, $articleId, 'summary');
				$submitForm->display();
			}
		} else {
			Request::redirect(null, null, 'submission', $articleId);
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
		ProofreaderAction::authorProofreadingUnderway($submission);
	
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('copyeditor', $submission->getCopyeditor());
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('initialCopyeditFile', $submission->getInitialCopyeditFile());
		$templateMgr->assign_by_ref('editorAuthorCopyeditFile', $submission->getEditorAuthorCopyeditFile());
		$templateMgr->assign_by_ref('finalCopyeditFile', $submission->getFinalCopyeditFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('useCopyeditors', $journal->getSetting('useCopyeditors'));
		$templateMgr->assign('useLayoutEditors', $journal->getSetting('useLayoutEditors'));
		$templateMgr->assign('useProofreaders', $journal->getSetting('useProofreaders'));
		$templateMgr->assign_by_ref('proofAssignment', $submission->getProofAssignment());
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
		
		Request::redirect(null, null, 'submissionReview', $articleId);
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

		// If the copy editor has completed copyediting, disallow
		// the author from changing the metadata.
		if ($submission->getCopyeditorDateCompleted() != null || AuthorAction::saveMetadata($submission)) {
			Request::redirect(null, null, 'submission', $articleId);
		}
	}

	function uploadCopyeditVersion() {
		$copyeditStage = Request::getUserVar('copyeditStage');
		$articleId = Request::getUserVar('articleId');
		
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId);
		
		AuthorAction::uploadCopyeditVersion($submission, $copyeditStage);
		
		Request::redirect(null, null, 'submissionEditing', $articleId);
	}
	
	function completeAuthorCopyedit($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);		
		
		if (AuthorAction::completeAuthorCopyedit($submission, Request::getUserVar('send'))) {
			Request::redirect(null, null, 'submissionEditing', $articleId);
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
			Request::redirect(null, null, 'submission', $articleId);
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
			Request::redirect(null, Request::getRequestedPage());
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

		if (ProofreaderAction::proofreadEmail($articleId,'PROOFREAD_AUTHOR_COMPLETE', $send?'':Request::url(null, 'author', 'authorProofreadingComplete', 'send'))) {
			Request::redirect(null, null, 'submissionEditing', $articleId);
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
				$templateMgr->assign_by_ref('galley', $galley);
				if ($galley->isHTMLGalley() && $styleFile =& $galley->getStyleFile()) {
					$templateMgr->addStyleSheet(Request::url(null, 'article', 'viewFile', array(
						$articleId, $galleyId, $styleFile->getFileId()
					)));
				}
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
			Request::redirect(null, null, 'submission', $articleId);
		}
	}

}
?>
