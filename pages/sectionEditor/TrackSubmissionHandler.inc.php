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
		$templateMgr->assign('acceptEditorDecisionValue', SUBMISSION_EDITOR_DECISION_ACCEPT);
		
		if (isset($args[0]) && $args[0] == 'completed') {
			$templateMgr->assign('showCompleted', true);
		}
		$templateMgr->display('sectionEditor/assignments.tpl');
	}
	
	function summary($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);

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
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);

		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
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
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);

		$journal = &Request::getJournal();
		
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
		$lastDecision = count($editorDecisions) >= 1 ? $editorDecisions[count($editorDecisions) - 1]['decision'] : null;				

		$allowRecommendation = $submission->getCurrentRound() == $round && $submission->getCopyeditFileId() == null ? true : false;
		$allowResubmit = $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT && $sectionEditorSubmissionDao->getMaxReviewRound($articleId) == $round ? true : false;
		$allowCopyedit = $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT && $submission->getCopyeditFileId() == null ? true : false;
		
		
		// Prepare an array to store the 'Notify Reviewer' email logs
		$notifyReviewerLogs = array();
		foreach ($submission->getReviewAssignments($round) as $reviewAssignment) {
			$notifyReviewerLogs[$reviewAssignment->getReviewId()] = array();
		}
		
		// Parse the list of email logs and populate the array.
		foreach ($submission->getEmailLogs() as $emailLog) {
			if ($emailLog->getEventType() == ARTICLE_EMAIL_REVIEW_NOTIFY_REVIEWER) {
				if (isset($notifyReviewerLogs[$emailLog->getAssocId()]) && is_array($notifyReviewerLogs[$emailLog->getAssocId()])) {
					array_push($notifyReviewerLogs[$emailLog->getAssocId()], $emailLog);
				}
			}
		}
		
		// Prepare an array to store the 'Notify Author' email logs
		$notifyAuthorLogs = array();
		foreach ($submission->getEmailLogs() as $emailLog) {
			if ($emailLog->getEventType() == ARTICLE_EMAIL_EDITOR_NOTIFY_AUTHOR) {
				array_push($notifyAuthorLogs, $emailLog);
			}
		}
		
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('round', $round);
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('reviewAssignments', $submission->getReviewAssignments($round));
		$templateMgr->assign('notifyReviewerLogs', $notifyReviewerLogs);
		$templateMgr->assign('notifyAuthorLogs', $notifyAuthorLogs);
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
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$journal = &Request::getJournal();
		
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
		$templateMgr->assign('copyeditFile', $submission->getCopyeditFile());
		$templateMgr->assign('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('reviewFile', $submission->getReviewFile());
		$templateMgr->assign('copyeditor', $submission->getCopyeditor());
		$templateMgr->assign('sections', $sections);
		$templateMgr->assign('useCopyeditors', $useCopyeditors);
		$templateMgr->assign('useProofreaders', $useProofreaders);
		
		$templateMgr->display('sectionEditor/submissionEditing.tpl');
	}
	
	/**
	 * View submission history
	 */
	function submissionHistory($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$submission = $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$eventLogEntries = &ArticleLog::getEventLogEntries($articleId, 5);
		$emailLogEntries = &ArticleLog::getEmailLogEntries($articleId, 5);
		
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('isEditor', Validation::isEditor());
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('eventLogEntries', $eventLogEntries);
		$templateMgr->assign('emailLogEntries', $emailLogEntries);
		
		$templateMgr->display('sectionEditor/submissionHistory.tpl');
	}
	
	function designateReviewVersion() {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$designate = Request::getUserVar('designate');

		SectionEditorAction::designateReviewVersion($articleId, $designate);
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function changeSection() {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$sectionId = Request::getUserVar('sectionId');

		SectionEditorAction::changeSection($articleId, $sectionId);
		
		Request::redirect(sprintf('sectionEditor/submission/%d', $articleId));
	}
	
	function recordDecision() {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$decision = Request::getUserVar('decision');

		SectionEditorAction::recordDecision($articleId, $decision);
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function selectReviewer($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$journal = &Request::getJournal();
				
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
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');

		SectionEditorAction::removeReview($articleId, $reviewId);
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function notifyReviewer($args = array()) {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		
		if (isset($args[0]) && $args[0] == 'send') {
			$send = true;
			SectionEditorAction::notifyReviewer($articleId, $reviewId, $send);
			Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
		} else {
			SectionEditorAction::notifyReviewer($articleId, $reviewId);
		}
	}
	
	function initiateReview() {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		
		SectionEditorAction::initiateReview($articleId, $reviewId);
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function reinitiateReview() {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		
		SectionEditorAction::reinitiateReview($articleId, $reviewId);
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function initiateAllReviews() {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		SectionEditorAction::initiateAllReviews($articleId);
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function cancelReview() {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		
		SectionEditorAction::cancelReview($articleId, $reviewId);
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function remindReviewer($args = null) {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		
		if (isset($args[0]) && $args[0] == 'send') {
			SectionEditorAction::remindReviewer($articleId, $reviewId, true);
		} else {
			SectionEditorAction::remindReviewer($articleId, $reviewId);
		}
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function replaceReviewer($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$journal = &Request::getJournal();
		
		$reviewId = isset($args[1]) ? $args[1] : 0;
		
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
	
	function thankReviewer($args = array()) {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		
		if (isset($args[0]) && $args[0] == 'send') {
			$send = true;
			SectionEditorAction::thankReviewer($articleId, $reviewId, $send);
			Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
		} else {
			SectionEditorAction::thankReviewer($articleId, $reviewId);
		}
	}
	
	function rateReviewer() {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		$timeliness = Request::getUserVar('timeliness');
		$quality = Request::getUserVar('quality');
		
		SectionEditorAction::rateReviewer($articleId, $reviewId, $timeliness, $quality);
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function makeReviewerFileViewable() {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		$fileId = Request::getUserVar('fileId');
		$revision = Request::getUserVar('revision');
		$viewable = Request::getUserVar('viewable');
		
		SectionEditorAction::makeReviewerFileViewable($articleId, $reviewId, $fileId, $revision, $viewable);
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
	}
	
	function setDueDate($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$reviewId = isset($args[1]) ? $args[1] : 0;
		$dueDate = Request::getUserVar('dueDate');
		$numWeeks = Request::getUserVar('numWeeks');
		
		if ($dueDate != null || $numWeeks != null) {
			SectionEditorAction::setDueDate($articleId, $reviewId, $dueDate, $numWeeks);
		
			Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
		} else {
			$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignment = $reviewAssignmentDao->getReviewAssignmentById($reviewId);
			
			$templateMgr = &TemplateManager::getManager();
		
			if ($reviewAssignment->getDateDue() != null) {
				$templateMgr->assign('dueDate', $reviewAssignment->getDateDue());
			}
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('reviewId', $reviewId);
			$templateMgr->assign('todaysDate', date('Y-m-d'));
	
			$templateMgr->display('sectionEditor/setDueDate.tpl');
		}
	}
	
	function enterReviewerRecommendation($args) {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$reviewId = Request::getUserVar('reviewId');
		
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
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		SectionEditorAction::viewMetadata($articleId, ROLE_ID_SECTION_EDITOR);
	}
	
	function saveMetadata() {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		SectionEditorAction::saveMetadata($articleId);
	}
	
	function editorReview() {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		// If the Upload button was pressed.
		$submit = Request::getUserVar('submit');
		if ($submit != null) {
			SectionEditorAction::uploadEditorVersion($articleId);
		}		
		
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
	
	function notifyAuthor($args = array()) {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		if (isset($args[0]) && $args[0] == 'send') {
			$send = true;
			SectionEditorAction::notifyAuthor($articleId, $send);
			Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));
		} else {
			SectionEditorAction::notifyAuthor($articleId);
		}
	}
	
	function selectCopyeditor($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$journal = &Request::getJournal();
		
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
	
	function replaceCopyeditor($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		$journal = &Request::getJournal();
		
		if (isset($args[1]) && $args[1] != '') {
			$copyeditorId = $args[1];
			SectionEditorAction::replaceCopyeditor($articleId, $copyeditorId);
		
			Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));
		} else {
			$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
			$copyeditors = $sectionEditorSubmissionDao->getCopyeditorsNotAssignedToArticle($journal->getJournalId(), $articleId);
		
			$templateMgr = &TemplateManager::getManager();
		
			$templateMgr->assign('copyeditors', $copyeditors);
			$templateMgr->assign('articleId', $articleId);
	
			$templateMgr->display('sectionEditor/replaceCopyeditor.tpl');
		}
	}
	
	function notifyCopyeditor($args) {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		if (isset($args[0]) && $args[0] == 'send') {
			$send = true;
			SectionEditorAction::notifyCopyeditor($articleId, $send);
			Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));
		} else {
			SectionEditorAction::notifyCopyeditor($articleId);
		}
	}

	function thankCopyeditor() {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);

		SectionEditorAction::thankCopyeditor($articleId);
		
		Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));
	}
	
	function notifyAuthorCopyedit($args) {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		if (isset($args[0]) && $args[0] == 'send') {
			$send = true;
			SectionEditorAction::notifyAuthorCopyedit($articleId, $send);
			Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));
		} else {
			SectionEditorAction::notifyAuthorCopyedit($articleId);
		}
		
		Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));
	}
	
	function thankAuthorCopyedit() {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);

		SectionEditorAction::thankAuthorCopyedit($articleId);
		
		Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));
	}
	
	function initiateFinalCopyedit() {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);

		SectionEditorAction::initiateFinalCopyedit($articleId);
		
		Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));
	}

	function thankFinalCopyedit() {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);

		SectionEditorAction::thankFinalCopyedit($articleId);
		
		Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));
	}
	
	function uploadReviewVersion() {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		SectionEditorAction::uploadReviewVersion($articleId);
		
		Request::redirect(sprintf('sectionEditor/submissionReview/%d', $articleId));	
	}
	
	function uploadCopyeditVersion() {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		SectionEditorAction::uploadCopyeditVersion($articleId);
		
		Request::redirect(sprintf('sectionEditor/submissionEditing/%d', $articleId));	
	}
	
	function addSuppFile($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
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
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;
		
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
	
	function archiveSubmission() {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);

		SectionEditorAction::archiveSubmission($articleId);
		
		Request::redirect(sprintf('sectionEditor/submission/%d', $articleId));
	}
	
	function restoreToQueue() {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);

		SectionEditorAction::restoreToQueue($articleId);
		
		Request::redirect(sprintf('sectionEditor/submission/%d', $articleId));
	}
	
	
	//
	// Submission History (FIXME Move to separate file?)
	//
	
	/**
	 * View submission event log.
	 */
	function submissionEventLog($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$logId = isset($args[1]) ? (int) $args[1] : 0;
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$submission = $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('isEditor', Validation::isEditor());
		$templateMgr->assign('submission', $submission);
		
		if ($logId) {
			$logDao = &DAORegistry::getDAO('ArticleEventLogDAO');
			$logEntry = &$logDao->getLogEntry($logId, $articleId);
		}
		
		if (isset($logEntry)) {
			$templateMgr->assign('logEntry', $logEntry);
			$templateMgr->display('sectionEditor/submissionEventLogEntry.tpl');
			
		} else {
			$eventLogEntries = &ArticleLog::getEventLogEntries($articleId);
			$templateMgr->assign('eventLogEntries', $eventLogEntries);
			$templateMgr->display('sectionEditor/submissionEventLog.tpl');
		}
	}
	
	/**
	 * View submission event log by record type.
	 */
	function submissionEventLogType($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$assocType = isset($args[1]) ? (int) $args[1] : null;
		$assocId = isset($args[2]) ? (int) $args[2] : null;
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$submission = $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$logDao = &DAORegistry::getDAO('ArticleEventLogDAO');
		$eventLogEntries = &$logDao->getArticleLogEntriesByAssoc($articleId, $assocType, $assocId);
		
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('showBackLink', true);
		$templateMgr->assign('isEditor', Validation::isEditor());
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('eventLogEntries', $eventLogEntries);
		$templateMgr->display('sectionEditor/submissionEventLog.tpl');
	}
	
	/**
	 * Clear submission event log entries.
	 */
	function clearSubmissionEventLog($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$logId = isset($args[1]) ? (int) $args[1] : 0;
		TrackSubmissionHandler::validate($articleId, true);
		parent::setupTemplate(true);
		
		$logDao = &DAORegistry::getDAO('ArticleEventLogDAO');
		
		if ($logId) {
			$logDao->deleteLogEntry($logId, $articleId);
			
		} else {
			$logDao->deleteArticleLogEntries($articleId);
		}
		
		Request::redirect(sprintf('%s/submissionEventLog/%d', Request::getRequestedPage(), $articleId));
	}
	
	/**
	 * View submission email log.
	 */
	function submissionEmailLog($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$logId = isset($args[1]) ? (int) $args[1] : 0;
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$submission = $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('isEditor', Validation::isEditor());
		$templateMgr->assign('submission', $submission);
		
		if ($logId) {
			$logDao = &DAORegistry::getDAO('ArticleEmailLogDAO');
			$logEntry = &$logDao->getLogEntry($logId, $articleId);
		}
		
		if (isset($logEntry)) {
			$templateMgr->assign('logEntry', $logEntry);
			$templateMgr->display('sectionEditor/submissionEmailLogEntry.tpl');
			
		} else {
			$emailLogEntries = &ArticleLog::getEmailLogEntries($articleId);
			$templateMgr->assign('emailLogEntries', $emailLogEntries);
			$templateMgr->display('sectionEditor/submissionEmailLog.tpl');
		}
	}
	
	/**
	 * View submission email log by record type.
	 */
	function submissionEmailLogType($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$assocType = isset($args[1]) ? (int) $args[1] : null;
		$assocId = isset($args[2]) ? (int) $args[2] : null;
		TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true);
		
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$submission = $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		
		$logDao = &DAORegistry::getDAO('ArticleEmailLogDAO');
		$emailLogEntries = &$logDao->getArticleLogEntriesByAssoc($articleId, $assocType, $assocId);
		
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('showBackLink', true);
		$templateMgr->assign('isEditor', Validation::isEditor());
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('emailLogEntries', $emailLogEntries);
		$templateMgr->display('sectionEditor/submissionEmailLog.tpl');
	}
	
	/**
	 * Clear submission email log entries.
	 */
	function clearSubmissionEmailLog($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$logId = isset($args[1]) ? (int) $args[1] : 0;
		TrackSubmissionHandler::validate($articleId, true);
		parent::setupTemplate(true);
		
		$logDao = &DAORegistry::getDAO('ArticleEmailLogDAO');
		
		if ($logId) {
			$logDao->deleteLogEntry($logId, $articleId);
			
		} else {
			$logDao->deleteArticleLogEntries($articleId);
		}
		
		Request::redirect(sprintf('%s/submissionEmailLog/%d', Request::getRequestedPage(), $articleId));
	}
	
	
	//
	// Validation
	//
	
	/**
	 * Validate that the user is the assigned section editor for
	 * the article, or is a managing editor.
	 * Redirects to sectionEditor index page if validation fails.
	 * @param $mustBeEditor boolean user must be an editor
	 */
	function validate($articleId, $mustBeEditor = false) {
		parent::validate();
		
		$isValid = true;
		
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);

		if ($sectionEditorSubmission == null) {
			$isValid = false;
			
		} else if ($sectionEditorSubmission->getJournalId() != $journal->getJournalId()) {
			$isValid = false;
			
		} else {
			$editor = $sectionEditorSubmission->getEditor();
			if (($mustBeEditor || $editor == null || $editor->getEditorId() != $user->getUserId()) && !Validation::isEditor()) {
				$isValid = false;
			}
		}
		
		if (!$isValid) {
			Request::redirect(Request::getRequestedPage());
		}
	}
}
?>
