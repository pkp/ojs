<?php

/**
 * TrackSubmissionHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.reviewer
 *
 * Handle requests for submission tracking. 
 *
 * $Id$
 */


/** Submission Management Constants */
define('SUBMISSION_EDITOR_DECISION_ACCEPT', 1);
define('SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS', 2);
define('SUBMISSION_EDITOR_DECISION_RESUBMIT', 3);
define('SUBMISSION_EDITOR_DECISION_DECLINE', 4);
define('SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT', 1);
define('SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS', 2); 
define('SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT', 3);
define('SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE', 4);
define('SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS', 5); 


class TrackSubmissionHandler extends SectionEditorHandler {

	/**
	 * Show assignments list.
	 */
	function assignments($args = array()) {
		parent::validate();
		parent::setupTemplate(true);
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$templateMgr = &TemplateManager::getManager();
			
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$assignedArticles = &$sectionEditorSubmissionDao->getSectionEditorSubmissions($user->getUserId(), $journal->getJournalId());
		$templateMgr->assign('assignedArticles', $assignedArticles);
		
		if (isset($args[0]) && $args[0] == 'completed') {
			$templateMgr->assign('showCompleted', true);
		}
		$templateMgr->display('sectionEditor/assignments.tpl');
	}
	
	function summary($args) {
		parent::validate();
		parent::setupTemplate(true);

		$journal = &Request::getJournal();
		$articleId = $args[0];
		
		TrackSubmissionHandler::validate($articleId);
		
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$submission = $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('reviewFile', $submission->getReviewFile());

		$templateMgr->display('sectionEditor/summary.tpl');
	}
	
	function submission($args) {
		parent::validate();
		parent::setupTemplate(true);

		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$articleId = $args[0];
		
		TrackSubmissionHandler::validate($articleId);
		
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$submission = $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);

		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$journalSettings = $journalSettingsDao->getJournalSettings($journal->getJournalId());
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$isEditor = $roleDao->roleExists($journal->getJournalId(), $user->getUserId(), ROLE_ID_EDITOR);
		
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('authors', $submission->getAuthors());
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('reviewFile', $submission->getReviewFile());
		$templateMgr->assign('journalSettings', $journalSettings);
		$templateMgr->assign('isEditor', $isEditor);
		
		$templateMgr->display('sectionEditor/submission.tpl');
	}
	
	function submissionReview($args) {
		parent::validate();
		parent::setupTemplate(true);

		$journal = &Request::getJournal();
		$articleId = $args[0];
		
		TrackSubmissionHandler::validate($articleId);
		
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$submission = $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		// Setting the round.
		$round = isset($args[1]) ? $args[1] : $submission->getCurrentRound();

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sections = $sectionDao->getJournalSections($journal->getJournalId());

		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$numReviewers = $journalSettingsDao->getSetting($journal->getJournalId(), 'numReviewersPerSubmission');
		
		if ($round == $submission->getCurrentRound() && count($submission->getReviewAssignments()) < $numReviewers) {
			$numSelectReviewers = $numReviewers - count($submission->getReviewAssignments());
		} else {
			$numSelectReviewers = 0;
		}
		
		$showPeerReviewOptions = $round == $submission->getCurrentRound() && $submission->getReviewFile() != null ? true : false;

		$editorDecisions = $submission->getDecisions($round);
		$lastDecision = count($editorDecisions) > 1 ? $editorDecisions[count($editorDecisions) - 1]['decision'] : null;				

		$allowRecommendation = $submission->getCurrentRound() == $round && $submission->getCopyeditFileId() == null ? true : false;
		$allowResubmit = $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT && $sectionEditorSubmissionDao->getMaxReviewRound($articleId) == $round ? true : false;
		$allowCopyedit = $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT && $submission->getCopyeditFileId() == null ? true : false;
		
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('round', $round);
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('reviewAssignments', $submission->getReviewAssignments($round));
		$templateMgr->assign('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('reviewFile', $submission->getReviewFile());
		$templateMgr->assign('revisedFile', $submission->getRevisedFile());
		$templateMgr->assign('editorFile', $submission->getEditorFile());
		$templateMgr->assign('numSelectReviewers', $numSelectReviewers);
		$templateMgr->assign('showPeerReviewOptions', $showPeerReviewOptions);
		$templateMgr->assign('sections', $sections);
		$templateMgr->assign('editorDecisionOptions',
			array(
				'' => 'editor.article.decision.chooseOne',
				SUBMISSION_EDITOR_DECISION_ACCEPT => 'editor.article.decision.accept',
				SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => 'editor.article.decision.pendingRevisions',
				SUBMISSION_EDITOR_DECISION_RESUBMIT => 'editor.article.decision.resubmit',
				SUBMISSION_EDITOR_DECISION_DECLINE => 'editor.article.decision.decline'
			)
		);
		$templateMgr->assign('lastDecision', $lastDecision);
		$templateMgr->assign('reviewerRecommendationOptions',
			array(
				'' => 'reviewer.article.decision.chooseOne',
				SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT => 'reviewer.article.decision.accept',
				SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS => 'reviewer.article.decision.pendingRevisions',
				SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT => 'reviewer.article.decision.resubmit',
				SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE => 'reviewer.article.decision.decline',
				SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS => 'reviewer.article.decision.seeComments'
			)
		);
		$templateMgr->assign('allowRecommendation', $allowRecommendation);
		$templateMgr->assign('allowResubmit', $allowResubmit);
		$templateMgr->assign('allowCopyedit', $allowCopyedit);
	
		$templateMgr->display('sectionEditor/submissionReview.tpl');
	}
	
	function submissionEditing($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$journal = &Request::getJournal();
		$articleId = $args[0];
		
		TrackSubmissionHandler::validate($articleId);
		
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$submission = $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sections = $sectionDao->getJournalSections($journal->getJournalId());
		
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$useCopyeditors = $journalSettingsDao->getSetting($journal->getJournalId(), 'useCopyeditors');
		$useProofreaders = $journalSettingsDao->getSetting($journal->getJournalId(), 'useProofreaders');

		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('reviewFile', $submission->getReviewFile());
		$templateMgr->assign('copyeditor', $submission->getCopyeditor());
		$templateMgr->assign('sections', $sections);
		$templateMgr->assign('useCopyeditors', $useCopyeditors);
		$templateMgr->assign('useProofreaders', $useProofreaders);
		
		$templateMgr->display('sectionEditor/submissionEditing.tpl');
	}
	
	function submissionHistory($args) {
		parent::validate();
		parent::setupTemplate(true);

		$journal = &Request::getJournal();
		$articleId = $args[0];
		
		TrackSubmissionHandler::validate($articleId);
		
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$submission = $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sections = $sectionDao->getJournalSections($journal->getJournalId());

		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$numReviewers = $journalSettingsDao->getSetting($journal->getJournalId(), 'numReviewersPerSubmission');
		
		if (count($submission->getReviewAssignments()) < $numReviewers) {
			$numSelectReviewers = $numReviewers - count($submission->getReviewAssignments());
		} else {
			$numSelectReviewers = 1;
		}
		
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('reviewAssignments', $submission->getReviewAssignments());
		$templateMgr->assign('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('reviewFile', $submission->getReviewFile());
		$templateMgr->assign('revisedFile', $submission->getRevisedFile());
		$templateMgr->assign('editorFile', $submission->getEditorFile());
		$templateMgr->assign('numSelectReviewers', $numSelectReviewers);
		$templateMgr->assign('sections', $sections);
		
		$templateMgr->display('sectionEditor/submissionHistory.tpl');
	}
	
	function designateReviewVersion() {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$designate = Request::getUserVar('designate');

		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::designateReviewVersion($articleId, $designate);
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function changeSection() {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$sectionId = Request::getUserVar('sectionId');

		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::changeSection($articleId, $sectionId);
		
		Request::redirect(sprintf('sectionEditor/submission/%d', $articleId));
	}
	
	function recordDecision() {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$decision = Request::getUserVar('decision');

		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::recordDecision($articleId, $decision);
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function selectReviewer($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$journal = &Request::getJournal();
		$articleId = $args[0];
		
		TrackSubmissionHandler::validate($articleId);
				
		if (isset($args[1]) && $args[1] != null) {
			// Assign reviewer to article			
			SectionEditorAction::addReviewer($articleId, $args[1]);
			Request::redirect('sectionEditor/submissionReview/'.$articleId);
			
			// FIXME: Prompt for due date.
		} else {
			$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
			$reviewers = $sectionEditorSubmissionDao->getReviewersNotAssignedToArticle($journal->getJournalId(), $articleId);
		
			$templateMgr = &TemplateManager::getManager();
		
			$templateMgr->assign('reviewers', $reviewers);
			$templateMgr->assign('articleId', $articleId);
	
			$templateMgr->display('sectionEditor/selectReviewer.tpl');
		}
	}
	
	function removeReview() {
		parent::validate();
		parent::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		$articleId = Request::getUserVar('articleId');

		TrackSubmissionHandler::validate($articleId);		
		SectionEditorAction::removeReview($articleId, $reviewId);
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function notifyReviewer() {
		parent::validate();
		parent::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::notifyReviewer($articleId, $reviewId);
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function initiateReview() {
		parent::validate();
		parent::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::initiateReview($articleId, $reviewId);
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function reinitiateReview() {
		parent::validate();
		parent::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::reinitiateReview($articleId, $reviewId);
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function initiateAllReviews() {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::initiateAllReviews($articleId);
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function cancelReview() {
		parent::validate();
		parent::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::cancelReview($articleId, $reviewId);
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function remindReviewer($args = null) {
		parent::validate();
		parent::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		
		if (isset($args[0]) && $args[0] == 'send') {
			SectionEditorAction::remindReviewer($articleId, $reviewId, true);
		} else {
			SectionEditorAction::remindReviewer($articleId, $reviewId);
		}
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function replaceReviewer($args) {
		parent::validate();
		parent::setupTemplate(true);
		$journal = &Request::getJournal();
		
		$articleId = $args[0];
		$reviewId = $args[1];
		
		TrackSubmissionHandler::validate($articleId);
		
		if (isset($args[2]) && $args[2] != '') {
			$reviewerId = $args[2];
			SectionEditorAction::clearReviewer($articleId, $reviewId);
			SectionEditorAction::addReviewer($articleId, $reviewerId);
		
			Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
		} else {
			$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
			$reviewers = $sectionEditorSubmissionDao->getReviewersNotAssignedToArticle($journal->getJournalId(), $articleId);
		
			$templateMgr = &TemplateManager::getManager();
		
			$templateMgr->assign('reviewers', $reviewers);
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('reviewId', $reviewId);
	
			$templateMgr->display('sectionEditor/replaceReviewer.tpl');
		}
	}
	
	function rateReviewer() {
		parent::validate();
		parent::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		$articleId = Request::getUserVar('articleId');
		$timeliness = Request::getUserVar('timeliness');
		$quality = Request::getUserVar('quality');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::rateReviewer($articleId, $reviewId, $timeliness, $quality);
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function makeReviewerFileViewable() {
		parent::validate();
		parent::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		$articleId = Request::getUserVar('articleId');
		$viewable = Request::getUserVar('viewable');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::makeReviewerFileViewable($articleId, $reviewId, $viewable);
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function setDueDate($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = $args[0];
		$reviewId = $args[1];
		$dueDate = Request::getUserVar('dueDate');
		$numWeeks = Request::getUserVar('numWeeks');
		
		TrackSubmissionHandler::validate($articleId);
		
		if ($dueDate != null || $numWeeks != null) {
			SectionEditorAction::setDueDate($articleId, $reviewId, $dueDate, $numWeeks);
		
			Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
		} else {
			$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
			$submission = $sectionEditorSubmissionDao->getSectionEditorSubmission($args[0]);
			foreach ($submission->getReviewAssignments($submission->getCurrentRound()) as $reviewAssignment) {
				if ($reviewAssignment->getReviewId() == $reviewId) {
					$existingDueDate = $reviewAssignment->getDateDue();
				}
			}

			$templateMgr = &TemplateManager::getManager();
		
			if (isset($existingDueDate) && $existingDueDate) {
				$templateMgr->assign('dueDate', $existingDueDate);
			}
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('reviewId', $reviewId);
			$templateMgr->assign('todaysDate', date('Y-m-d'));
	
			$templateMgr->display('sectionEditor/setDueDate.tpl');
		}
	}
	
	function enterReviewerRecommendation($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$reviewId = Request::getUserVar('reviewId');
		
		TrackSubmissionHandler::validate($articleId);
		
		$recommendation = Request::getUserVar('recommendation');
		
		if ($recommendation != null) {
			SectionEditorAction::setReviewerRecommendation($articleId, $reviewId, $recommendation);
		
			Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));	
		} else {
			$templateMgr = &TemplateManager::getManager();
			
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('reviewId', $reviewId);
			$templateMgr->assign('reviewerRecommendationOptions',
				array(
					'' => 'reviewer.article.decision.chooseOne',
					SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT => 'reviewer.article.decision.accept',
					SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS => 'reviewer.article.decision.pendingRevisions',
					SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT => 'reviewer.article.decision.resubmit',
					SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE => 'reviewer.article.decision.decline',
					SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS => 'reviewer.article.decision.seeComments'
				)
			);
			$templateMgr->display('sectionEditor/reviewerRecommendation.tpl');
		}
	}
	
	function viewMetadata($args) {
		parent::validate();
		parent::setupTemplate(true);
	
		$articleId = $args[0];
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::viewMetadata($articleId, ROLE_ID_SECTION_EDITOR);
	}
	
	function saveMetadata() {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::saveMetadata($articleId);
	}
	
	function editorReview() {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		
		// If the Send To Copyedit button was pressed.
		$setCopyeditFile = Request::getUserVar('setCopyeditFile');
		if ($setCopyeditFile != null) {
			$file = explode(',', Request::getUserVar('copyeditFile'));
			SectionEditorAction::setCopyeditFile($articleId, $file[0], $file[1]);
		}
		
		// If the Resubmit button was pressed.
		$resubmit = Request::getUserVar('resubmit');
		if ($resubmit != null) {
			$file = explode(',', Request::getUserVar('resubmitFile'));
			SectionEditorAction::resubmitFile($articleId, $file[0], $file[1]);
		}
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function selectCopyeditor($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$journal = &Request::getJournal();
		$articleId = $args[0];
		
		TrackSubmissionHandler::validate($articleId);
		
		if (isset($args[1]) && $args[1] != null) {
			SectionEditorAction::AddCopyeditor($args[0], $args[1]);
		
			Request::redirect('sectionEditor/submissionEditing/'.$args[0]);
			
		} else {
			$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
			$copyeditors = $sectionEditorSubmissionDao->getCopyeditorsNotAssignedToArticle($journal->getJournalId(), $articleId);
		
			$templateMgr = &TemplateManager::getManager();
		
			$templateMgr->assign('copyeditors', $copyeditors);
			$templateMgr->assign('articleId', $args[0]);
	
			$templateMgr->display('sectionEditor/selectCopyeditor.tpl');
		}
	}
	
	function notifyCopyeditor() {
		parent::validate();
		parent::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::notifyCopyeditor($articleId);
		
		Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));
	}

	function thankCopyeditor() {
		parent::validate();
		parent::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::thankCopyeditor($articleId);
		
		Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));
	}
	
	function notifyAuthorCopyedit() {
		parent::validate();
		parent::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::notifyAuthorCopyedit($articleId);
		
		Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));
	}
	
	function thankAuthorCopyedit() {
		parent::validate();
		parent::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::thankAuthorCopyedit($articleId);
		
		Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));
	}
	
	function initiateFinalCopyedit() {
		parent::validate();
		parent::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::initiateFinalCopyedit($articleId);
		
		Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));
	}

	function thankFinalCopyedit() {
		parent::validate();
		parent::setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::thankFinalCopyedit($articleId);
		
		Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));
	}
	
	function uploadReviewVersion() {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::uploadReviewVersion($articleId);
		
		Request::redirect(sprintf('sectionEditor/submission/%d', $articleId));	
	}
	
	function uploadPostReviewArticle() {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::uploadPostReviewArticle($articleId);
		
		Request::redirect(sprintf('sectionEditor/submission/%d', $articleId));	
	}
	
	function addSuppFile($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = $args[0];
		
		TrackSubmissionHandler::validate($articleId);
		
		import("submission.form.SuppFileForm");
		
		$submitForm = &new SuppFileForm($articleId);
		
		$submitForm->initData();
		$submitForm->display();
	}
	
	/**
	 * Save a supplementary file.
	 * @param $args array optional, if set the first parameter is the supplementary file to update
	 */
	function saveSuppFile($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;
		
		TrackSubmissionHandler::validate($articleId);
		
		import("author.form.submit.AuthorSubmitSuppFileForm");
		
		$submitForm = &new AuthorSubmitSuppFileForm($articleId, $suppFileId);
		$submitForm->readInputData();
		
		if ($submitForm->validate()) {
			$submitForm->execute();
			Request::redirect(sprintf('sectionEditor/submission/%d', $articleId));
		
		} else {
			$submitForm->display();
		}
	}
	
	//
	// Validation
	//
	
	/**
	 * Validate that the user is the assigned section editor for
	 * the article, or is a managing editor.
	 * Redirects to sectionEditor index page if validation fails.
	 */
	function validate($articleId) {
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$editor = $sectionEditorSubmission == null ? null : $sectionEditorSubmission->getEditor();
		
		if ($editor->getEditorId() != $user->getUserId() && !$roleDao->roleExists($journal->getJournalId(), $user->getUserId(), ROLE_ID_EDITOR)) {
			Request::redirect('sectionEditor');
		}
	}
}
?>
