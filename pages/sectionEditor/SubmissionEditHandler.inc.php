<?php

/**
 * SubmissionEditHandler.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.sectionEditor
 *
 * Handle requests for submission tracking. 
 *
 * $Id$
 */

define('SECTION_EDITOR_ACCESS_EDIT', 0x00001);
define('SECTION_EDITOR_ACCESS_REVIEW', 0x00002);

class SubmissionEditHandler extends SectionEditorHandler {
	
	function submission($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		parent::setupTemplate(true, $articleId);

		$user = &Request::getUser();
		
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$journalSettings = $journalSettingsDao->getJournalSettings($journal->getJournalId());
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$isEditor = $roleDao->roleExists($journal->getJournalId(), $user->getUserId(), ROLE_ID_EDITOR);

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$section = &$sectionDao->getSection($submission->getSectionId());

		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('section', $section);
		$templateMgr->assign_by_ref('authors', $submission->getAuthors());
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign_by_ref('reviewFile', $submission->getReviewFile());
		$templateMgr->assign_by_ref('journalSettings', $journalSettings);
		$templateMgr->assign('userId', $user->getUserId());
		$templateMgr->assign('isEditor', $isEditor);
		
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$templateMgr->assign_by_ref('sections', $sectionDao->getSectionTitles($journal->getJournalId()));

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = &$publishedArticleDao->getPublishedArticleByArticleId($submission->getArticleId());
		if ($publishedArticle) {
			$issueDao = &DAORegistry::getDAO('IssueDAO');
			$issue = &$issueDao->getIssueById($publishedArticle->getIssueId());
			$templateMgr->assign_by_ref('issue', $issue);
			$templateMgr->assign_by_ref('publishedArticle', $publishedArticle);
		}

		if ($isEditor) {
			$templateMgr->assign('helpTopicId', 'editorial.editorsRole.submissionSummary');
		}

		$templateMgr->display('sectionEditor/submission.tpl');
	}
	
	function submissionRegrets($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'review');

		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$cancelsAndRegrets = $reviewAssignmentDao->getCancelsAndRegrets($articleId);
		$reviewFilesByRound = $reviewAssignmentDao->getReviewFilesByRound($articleId);

		$reviewAssignments =& $submission->getReviewAssignments();
		$editorDecisions = $submission->getDecisions();
		$numRounds = $submission->getCurrentRound();
		
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('reviewAssignments', $reviewAssignments);
		$templateMgr->assign_by_ref('cancelsAndRegrets', $cancelsAndRegrets);
		$templateMgr->assign_by_ref('reviewFilesByRound', $reviewFilesByRound);
		$templateMgr->assign_by_ref('editorDecisions', $editorDecisions);
		$templateMgr->assign('numRounds', $numRounds);
		$templateMgr->assign('rateReviewerOnQuality', $journal->getSetting('rateReviewerOnQuality'));

		$templateMgr->assign_by_ref('editorDecisionOptions', SectionEditorSubmission::getEditorDecisionOptions());

		import('submission.reviewAssignment.ReviewAssignment');
		$templateMgr->assign_by_ref('reviewerRatingOptions', ReviewAssignment::getReviewerRatingOptions());
		$templateMgr->assign_by_ref('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());

		$templateMgr->display('sectionEditor/submissionRegrets.tpl');
	}
	
	function submissionReview($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		parent::setupTemplate(true, $articleId);

		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');

		// Setting the round.
		$round = isset($args[1]) ? $args[1] : $submission->getCurrentRound();

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sections = &$sectionDao->getJournalSections($journal->getJournalId());

		$showPeerReviewOptions = $round == $submission->getCurrentRound() && $submission->getReviewFile() != null ? true : false;

		$editorDecisions = $submission->getDecisions($round);
		$lastDecision = count($editorDecisions) >= 1 ? $editorDecisions[count($editorDecisions) - 1]['decision'] : null;				

		$editAssignments =& $submission->getEditAssignments();
		$allowRecommendation = $submission->getCurrentRound() == $round && $submission->getReviewFileId() != null && !empty($editAssignments);
		$allowResubmit = $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT && $sectionEditorSubmissionDao->getMaxReviewRound($articleId) == $round ? true : false;
		$allowCopyedit = $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT && $submission->getCopyeditFileId() == null ? true : false;
		
		// Prepare an array to store the 'Notify Reviewer' email logs
		$notifyReviewerLogs = array();
		foreach ($submission->getReviewAssignments($round) as $reviewAssignment) {
			$notifyReviewerLogs[$reviewAssignment->getReviewId()] = array();
		}
		
		// Parse the list of email logs and populate the array.
		import('article.log.ArticleLog');
		$emailLogEntries = &ArticleLog::getEmailLogEntries($articleId);
		foreach ($emailLogEntries->toArray() as $emailLog) {
			if ($emailLog->getEventType() == ARTICLE_EMAIL_REVIEW_NOTIFY_REVIEWER) {
				if (isset($notifyReviewerLogs[$emailLog->getAssocId()]) && is_array($notifyReviewerLogs[$emailLog->getAssocId()])) {
					array_push($notifyReviewerLogs[$emailLog->getAssocId()], $emailLog);
				}
			}
		}

		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('reviewIndexes', $reviewAssignmentDao->getReviewIndexesForRound($articleId, $round));
		$templateMgr->assign('round', $round);
		$templateMgr->assign_by_ref('reviewAssignments', $submission->getReviewAssignments($round));
		$templateMgr->assign_by_ref('notifyReviewerLogs', $notifyReviewerLogs);
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign_by_ref('reviewFile', $submission->getReviewFile());
		$templateMgr->assign_by_ref('revisedFile', $submission->getRevisedFile());
		$templateMgr->assign_by_ref('editorFile', $submission->getEditorFile());
		$templateMgr->assign('rateReviewerOnQuality', $journal->getSetting('rateReviewerOnQuality'));
		$templateMgr->assign('showPeerReviewOptions', $showPeerReviewOptions);
		$templateMgr->assign_by_ref('sections', $sections->toArray());
		$templateMgr->assign('editorDecisionOptions',
			array(
				'' => 'common.chooseOne',
				SUBMISSION_EDITOR_DECISION_ACCEPT => 'editor.article.decision.accept',
				SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => 'editor.article.decision.pendingRevisions',
				SUBMISSION_EDITOR_DECISION_RESUBMIT => 'editor.article.decision.resubmit',
				SUBMISSION_EDITOR_DECISION_DECLINE => 'editor.article.decision.decline'
			)
		);
		$templateMgr->assign_by_ref('lastDecision', $lastDecision);

		import('submission.reviewAssignment.ReviewAssignment');
		$templateMgr->assign_by_ref('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());
		$templateMgr->assign_by_ref('reviewerRatingOptions', ReviewAssignment::getReviewerRatingOptions());

		$templateMgr->assign('allowRecommendation', $allowRecommendation);
		$templateMgr->assign('allowResubmit', $allowResubmit);
		$templateMgr->assign('allowCopyedit', $allowCopyedit);

		$templateMgr->assign('helpTopicId', 'editorial.sectionEditorsRole.review');
		$templateMgr->display('sectionEditor/submissionReview.tpl');
	}
	
	function submissionEditing($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		parent::setupTemplate(true, $articleId);
		
		$useCopyeditors = $journal->getSetting('useCopyeditors');
		$useLayoutEditors = $journal->getSetting('useLayoutEditors');
		$useProofreaders = $journal->getSetting('useProofreaders');

		// check if submission is accepted
		$round = isset($args[1]) ? $args[1] : $submission->getCurrentRound();
		$editorDecisions = $submission->getDecisions($round);
		$lastDecision = count($editorDecisions) >= 1 ? $editorDecisions[count($editorDecisions) - 1]['decision'] : null;				
		$submissionAccepted = ($lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT) ? true : false;

		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('copyeditFile', $submission->getCopyeditFile());
		$templateMgr->assign_by_ref('initialCopyeditFile', $submission->getInitialCopyeditFile());
		$templateMgr->assign_by_ref('editorAuthorCopyeditFile', $submission->getEditorAuthorCopyeditFile());
		$templateMgr->assign_by_ref('finalCopyeditFile', $submission->getFinalCopyeditFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign_by_ref('copyeditor', $submission->getCopyeditor());

		import('issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($submission->getArticleId());
		$templateMgr->assign_by_ref('publishedArticle', $publishedArticle);

		$templateMgr->assign('useCopyeditors', $useCopyeditors);
		$templateMgr->assign('useLayoutEditors', $useLayoutEditors);
		$templateMgr->assign('useProofreaders', $useProofreaders);
		$templateMgr->assign_by_ref('proofAssignment', $submission->getProofAssignment());
		$templateMgr->assign_by_ref('layoutAssignment', $submission->getLayoutAssignment());
		$templateMgr->assign('submissionAccepted', $submissionAccepted);

		$templateMgr->assign('helpTopicId', 'editorial.sectionEditorsRole.editing');
		$templateMgr->display('sectionEditor/submissionEditing.tpl');
	}
	
	/**
	 * View submission history
	 */
	function submissionHistory($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);

		parent::setupTemplate(true, $articleId);
		
		// submission notes
		$articleNoteDao = &DAORegistry::getDAO('ArticleNoteDAO');
		
		$rangeInfo = &Handler::getRangeInfo('submissionNotes');
		$submissionNotes =& $articleNoteDao->getArticleNotes($articleId, $rangeInfo);

		import('article.log.ArticleLog');
		$rangeInfo = &Handler::getRangeInfo('eventLogEntries');
		$eventLogEntries = &ArticleLog::getEventLogEntries($articleId, $rangeInfo);
		$rangeInfo = &Handler::getRangeInfo('emailLogEntries');
		$emailLogEntries = &ArticleLog::getEmailLogEntries($articleId, $rangeInfo);
		
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('isEditor', Validation::isEditor());
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('eventLogEntries', $eventLogEntries);
		$templateMgr->assign_by_ref('emailLogEntries', $emailLogEntries);
		$templateMgr->assign_by_ref('submissionNotes', $submissionNotes);

		$templateMgr->display('sectionEditor/submissionHistory.tpl');
	}
	
	function changeSection() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		
		$sectionId = Request::getUserVar('sectionId');

		SectionEditorAction::changeSection($submission, $sectionId);
		
		Request::redirect(null, null, 'submission', $articleId);
	}
	
	function recordDecision() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		
		$decision = Request::getUserVar('decision');

		switch ($decision) {
			case SUBMISSION_EDITOR_DECISION_ACCEPT:
			case SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS:
			case SUBMISSION_EDITOR_DECISION_RESUBMIT:
			case SUBMISSION_EDITOR_DECISION_DECLINE:
				SectionEditorAction::recordDecision($submission, $decision);
				break;
		}
		
		Request::redirect(null, null, 'submissionReview', $articleId);
	}
	
	//
	// Peer Review
	//
	
	function selectReviewer($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');

		if (isset($args[1]) && $args[1] != null) {
			// Assign reviewer to article			
			SectionEditorAction::addReviewer($submission, $args[1]);
			Request::redirect(null, null, 'submissionReview', $articleId);
			
			// FIXME: Prompt for due date.
		} else {
			parent::setupTemplate(true, $articleId, 'review');
		
			$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');

			$searchType = null;
			$searchMatch = null;
			$search = $searchQuery = Request::getUserVar('search');
			$searchInitial = Request::getUserVar('searchInitial');
			if (isset($search)) {
				$searchType = Request::getUserVar('searchField');
				$searchMatch = Request::getUserVar('searchMatch');
				
			} else if (isset($searchInitial)) {
				$searchInitial = String::strtoupper($searchInitial);
				$searchType = USER_FIELD_INITIAL;
				$search = $searchInitial;
			}
			
			$rangeInfo = &Handler::getRangeInfo('reviewers');
			$reviewers = $sectionEditorSubmissionDao->getReviewersForArticle($journal->getJournalId(), $articleId, $submission->getCurrentRound(), $searchType, $search, $searchMatch, $rangeInfo);
			
			$journal = Request::getJournal();
			$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		
			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign('searchField', $searchType);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $searchQuery);
			$templateMgr->assign('searchInitial', $searchInitial);
		
			$templateMgr->assign_by_ref('reviewers', $reviewers);
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('reviewerStatistics', $sectionEditorSubmissionDao->getReviewerStatistics($journal->getJournalId()));
			$templateMgr->assign('fieldOptions', Array(
				USER_FIELD_INTERESTS => 'user.interests',
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username',
				USER_FIELD_EMAIL => 'user.email'
			));
			$templateMgr->assign('completedReviewCounts', $reviewAssignmentDao->getCompletedReviewCounts($journal->getJournalId()));
			$templateMgr->assign('rateReviewerOnQuality', $journal->getSetting('rateReviewerOnQuality'));
			$templateMgr->assign('averageQualityRatings', $reviewAssignmentDao->getAverageQualityRatings($journal->getJournalId()));

			$templateMgr->assign('helpTopicId', 'journal.roles.reviewer');
			$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
			$templateMgr->display('sectionEditor/selectReviewer.tpl');
		}
	}

	/**
	 * Create a new user as a reviewer.
	 */
	function createReviewer($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);

		import('sectionEditor.form.CreateReviewerForm');
		$createReviewerForm =& new CreateReviewerForm($articleId);
		parent::setupTemplate(true, $articleId);

		if (isset($args[1]) && $args[1] === 'create') {
			$createReviewerForm->readInputData();
			if ($createReviewerForm->validate()) {
				// Create a user and enroll them as a reviewer.
				$createReviewerForm->execute();
				Request::redirect(null, null, 'selectReviewer', $articleId);
			} else {
				$createReviewerForm->display();
			}
		} else {
			// Display the "create user" form.
			$createReviewerForm->display();
		}

	}

	/**
	 * Search for users to enroll as reviewers.
	 */
	function enrollSearch($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath('reviewer');

		$user = &Request::getUser();

		$rangeInfo = Handler::getRangeInfo('users');
		$templateMgr = &TemplateManager::getManager();
		parent::setupTemplate(true);

		$searchType = null;
		$searchMatch = null;
		$search = $searchQuery = Request::getUserVar('search');
		$searchInitial = Request::getUserVar('searchInitial');
		if (isset($search)) {
			$searchType = Request::getUserVar('searchField');
			$searchMatch = Request::getUserVar('searchMatch');
			
		} else if (isset($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$userDao = &DAORegistry::getDAO('UserDAO');
		$users = &$userDao->getUsersByField($searchType, $searchMatch, $search, false, $rangeInfo);

		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $searchQuery);
		$templateMgr->assign('searchInitial', $searchInitial);

		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_INTERESTS => 'user.interests',
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email'
		));
		$templateMgr->assign('roleId', $roleId);
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));

		$templateMgr->assign('helpTopicId', 'journal.roles.index');
		$templateMgr->display('sectionEditor/searchUsers.tpl');
	}

	function enroll($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath('reviewer');

		$users = Request::getUserVar('users');
		if (!is_array($users) && Request::getUserVar('userId') != null) $users = array(Request::getUserVar('userId'));

		// Enroll reviewer
		for ($i=0; $i<count($users); $i++) {
			if (!$roleDao->roleExists($journal->getJournalId(), $users[$i], $roleId)) {
				$role = &new Role();
				$role->setJournalId($journal->getJournalId());
				$role->setUserId($users[$i]);
				$role->setRoleId($roleId);

				$roleDao->insertRole($role);
			}
		}
		Request::redirect(null, null, 'selectReviewer', $articleId);
	}
	
	function notifyReviewer($args = array()) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		
		$reviewId = Request::getUserVar('reviewId');

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $articleId, 'review');

		if (SectionEditorAction::notifyReviewer($submission, $reviewId, $send)) {
			Request::redirect(null, null, 'submissionReview', $articleId);
		}
	}
	
	function clearReview($args) {
		$articleId = isset($args[0])?$args[0]:0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		
		$reviewId = $args[1];
		
		SectionEditorAction::clearReview($submission, $reviewId);

		Request::redirect(null, null, 'submissionReview', $articleId);
	}
	
	function cancelReview($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		
		$reviewId = Request::getUserVar('reviewId');

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $articleId, 'review');
		
		if (SectionEditorAction::cancelReview($submission, $reviewId, $send)) {
			Request::redirect(null, null, 'submissionReview', $articleId);
		}
	}
	
	function remindReviewer($args = null) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		
		$reviewId = Request::getUserVar('reviewId');
		parent::setupTemplate(true, $articleId, 'review');
		
		if (SectionEditorAction::remindReviewer($submission, $reviewId, Request::getUserVar('send'))) {
			Request::redirect(null, null, 'submissionReview', $articleId);
		}
	}
	
	function acknowledgeReviewerUnderway($args = null) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		
		$reviewId = Request::getUserVar('reviewId');
		parent::setupTemplate(true, $articleId, 'review');
		
		if (SectionEditorAction::acknowledgeReviewerUnderway($submission, $reviewId, Request::getUserVar('send'))) {
			Request::redirect(null, null, 'submissionReview', $articleId);
		}
	}
	
	function thankReviewer($args = array()) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		
		$reviewId = Request::getUserVar('reviewId');

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $articleId, 'review');
		
		if (SectionEditorAction::thankReviewer($submission, $reviewId, $send)) {
			Request::redirect(null, null, 'submissionReview', $articleId);
		}
	}
	
	function rateReviewer() {
		$articleId = Request::getUserVar('articleId');
		SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		list($journal, $submission) = parent::setupTemplate(true, $articleId, 'review');
		
		$reviewId = Request::getUserVar('reviewId');
		$quality = Request::getUserVar('quality');
		
		SectionEditorAction::rateReviewer($articleId, $reviewId, $quality);
		
		Request::redirect(null, null, 'submissionReview', $articleId);
	}

	function acceptReviewForReviewer($args) {
		$articleId = (int) isset($args[0])?$args[0]:0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		
		$reviewId = (int) isset($args[1])?$args[1]:0;
		
		SectionEditorAction::acceptReviewForReviewer($reviewId);
		Request::redirect(null, null, 'submissionReview', $articleId);
	}

	function uploadReviewForReviewer($args) {
		$articleId = (int) Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		
		$reviewId = (int) Request::getUserVar('reviewId');
		
		SectionEditorAction::uploadReviewForReviewer($reviewId);
		Request::redirect(null, null, 'submissionReview', $articleId);
	}

	function makeReviewerFileViewable() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		
		$reviewId = Request::getUserVar('reviewId');
		$fileId = Request::getUserVar('fileId');
		$revision = Request::getUserVar('revision');
		$viewable = Request::getUserVar('viewable');
		
		SectionEditorAction::makeReviewerFileViewable($articleId, $reviewId, $fileId, $revision, $viewable);
		
		Request::redirect(null, null, 'submissionReview', $articleId);
	}

	function setDueDate($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		
		$reviewId = isset($args[1]) ? $args[1] : 0;
		$dueDate = Request::getUserVar('dueDate');
		$numWeeks = Request::getUserVar('numWeeks');
		
		if ($dueDate != null || $numWeeks != null) {
			SectionEditorAction::setDueDate($articleId, $reviewId, $dueDate, $numWeeks);
			Request::redirect(null, null, 'submissionReview', $articleId);
			
		} else {
			parent::setupTemplate(true, $articleId, 'review');
			$journal = &Request::getJournal();
			
			$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignment = $reviewAssignmentDao->getReviewAssignmentById($reviewId);
			
			$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
			$settings = &$settingsDao->getJournalSettings($journal->getJournalId());
			
			$templateMgr = &TemplateManager::getManager();
		
			if ($reviewAssignment->getDateDue() != null) {
				$templateMgr->assign('dueDate', $reviewAssignment->getDateDue());
			}
			
			$numWeeksPerReview = $settings['numWeeksPerReview'] == null ? 0 : $settings['numWeeksPerReview'];
			
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('reviewId', $reviewId);
			$templateMgr->assign('todaysDate', date('Y-m-d'));
			$templateMgr->assign('numWeeksPerReview', $numWeeksPerReview);
			$templateMgr->assign('actionHandler', 'setDueDate');
	
			$templateMgr->display('sectionEditor/setDueDate.tpl');
		}
	}
	
	function enterReviewerRecommendation($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		
		$reviewId = Request::getUserVar('reviewId');
		
		$recommendation = Request::getUserVar('recommendation');
		
		if ($recommendation != null) {
			SectionEditorAction::setReviewerRecommendation($articleId, $reviewId, $recommendation, SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT);
			Request::redirect(null, null, 'submissionReview', $articleId);
		} else {
			parent::setupTemplate(true, $articleId, 'review');
			
			$templateMgr = &TemplateManager::getManager();
			
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('reviewId', $reviewId);

			import('submission.reviewAssignment.ReviewAssignment');
			$templateMgr->assign_by_ref('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());

			$templateMgr->display('sectionEditor/reviewerRecommendation.tpl');
		}
	}
	
	/**
	 * Display a user's profile.
	 * @param $args array first parameter is the ID or username of the user to display
	 */
	function userProfile($args) {
		parent::validate();
		parent::setupTemplate(true);
			
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('currentUrl', Request::url(null, Request::getRequestedPage()));
		
		$userDao = &DAORegistry::getDAO('UserDAO');
		$userId = isset($args[0]) ? $args[0] : 0;
		if (is_numeric($userId)) {
			$userId = (int) $userId;
			$user = $userDao->getUser($userId);
		} else {
			$user = $userDao->getUserByUsername($userId);
		}
		
		
		if ($user == null) {
			// Non-existent user requested
			$templateMgr->assign('pageTitle', 'manager.people');
			$templateMgr->assign('errorMsg', 'manager.people.invalidUser');
			$templateMgr->display('common/error.tpl');
			
		} else {
			$site = &Request::getSite();
			$journal = &Request::getJournal();
			
			$templateMgr->assign_by_ref('user', $user);
			$templateMgr->assign('profileLocalesEnabled', $site->getProfileLocalesEnabled());
			$templateMgr->assign('localeNames', Locale::getAllLocales());
			$templateMgr->assign('helpTopicId', 'journal.roles.index');
			$templateMgr->display('sectionEditor/userProfile.tpl');
		}
	}
	
	function viewMetadata($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'summary');
		
		SectionEditorAction::viewMetadata($submission, ROLE_ID_SECTION_EDITOR);
	}
	
	function saveMetadata() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'summary');
		
		if (SectionEditorAction::saveMetadata($submission)) {
			Request::redirect(null, null, 'submission', $articleId);
		}
	}
	
	//
	// Editor Review
	//
	
	function editorReview() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);

		$redirectTarget = 'submissionReview';
		
		// If the Upload button was pressed.
		$submit = Request::getUserVar('submit');
		if ($submit != null) {
			SectionEditorAction::uploadEditorVersion($submission);
		}		
		
		if (Request::getUserVar('setCopyeditFile')) {
			// If the Send To Copyedit button was pressed
			$file = explode(',', Request::getUserVar('editorDecisionFile'));
			if (isset($file[0]) && isset($file[1])) {
				SectionEditorAction::setCopyeditFile($submission, $file[0], $file[1]);
				$redirectTarget = 'submissionEditing';
			}
			
		} else if (Request::getUserVar('resubmit')) {
			// If the Resubmit button was pressed
			$file = explode(',', Request::getUserVar('editorDecisionFile'));
			if (isset($file[0]) && isset($file[1])) {
				SectionEditorAction::resubmitFile($submission, $file[0], $file[1]);
			}
		}
		
		Request::redirect(null, null, $redirectTarget, $articleId);
	}
	
	function notifyAuthor($args = array()) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $articleId, 'editing');

		if (SectionEditorAction::notifyAuthor($submission, $send)) {
			Request::redirect(null, null, 'submissionReview', $articleId);
		}
	}
	
	//
	// Copyedit
	//
	
	function selectCopyeditor($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		
		if (isset($args[1]) && $args[1] != null && $roleDao->roleExists($journal->getJournalId(), $args[1], ROLE_ID_COPYEDITOR)) {
			SectionEditorAction::selectCopyeditor($submission, $args[1]);
			Request::redirect(null, null, 'submissionEditing', $articleId);
		} else {
			parent::setupTemplate(true, $articleId, 'editing');

			$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');

			$searchType = null;
			$searchMatch = null;
			$search = $searchQuery = Request::getUserVar('search');
			$searchInitial = Request::getUserVar('searchInitial');
			if (isset($search)) {
				$searchType = Request::getUserVar('searchField');
				$searchMatch = Request::getUserVar('searchMatch');
				
			} else if (isset($searchInitial)) {
				$searchInitial = String::strtoupper($searchInitial);
				$searchType = USER_FIELD_INITIAL;
				$search = $searchInitial;
			}

			$copyeditors = $roleDao->getUsersByRoleId(ROLE_ID_COPYEDITOR, $journal->getJournalId(), $searchType, $search, $searchMatch);
			$copyeditorStatistics = $sectionEditorSubmissionDao->getCopyeditorStatistics($journal->getJournalId());

			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign('searchField', $searchType);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $searchQuery);
			$templateMgr->assign('searchInitial', $searchInitial);
		
			$templateMgr->assign_by_ref('users', $copyeditors);
			$templateMgr->assign('currentUser', $submission->getCopyeditorId());
			$templateMgr->assign_by_ref('statistics', $copyeditorStatistics);
			$templateMgr->assign('pageSubTitle', 'editor.article.selectCopyeditor');
			$templateMgr->assign('pageTitle', 'user.role.copyeditors');
			$templateMgr->assign('actionHandler', 'selectCopyeditor');
			$templateMgr->assign('fieldOptions', Array(
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username',
				USER_FIELD_EMAIL => 'user.email'
			));
			$templateMgr->assign('articleId', $args[0]);

			$templateMgr->assign('helpTopicId', 'journal.roles.copyeditor');
			$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
			$templateMgr->display('sectionEditor/selectUser.tpl');
		}
	}
	
	function notifyCopyeditor($args = array()) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $articleId, 'editing');

		if (SectionEditorAction::notifyCopyeditor($submission, $send)) {
			Request::redirect(null, null, 'submissionEditing', $articleId);
		}
	}
	
	/* Initiates the copyediting process when the editor does the copyediting */
	function initiateCopyedit() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		
		SectionEditorAction::initiateCopyedit($submission);
		Request::redirect(null, null, 'submissionEditing', $articleId);
	}

	function thankCopyeditor($args = array()) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $articleId, 'editing');

		if (SectionEditorAction::thankCopyeditor($submission, $send)) {
			Request::redirect(null, null, 'submissionEditing', $articleId);
		}
	}
	
	function notifyAuthorCopyedit($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $articleId, 'editing');

		if (SectionEditorAction::notifyAuthorCopyedit($submission, $send)) {
			Request::redirect(null, null, 'submissionEditing', $articleId);
		}
	}
	
	function thankAuthorCopyedit($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $articleId, 'editing');

		if (SectionEditorAction::thankAuthorCopyedit($submission, $send)) {
			Request::redirect(null, null, 'submissionEditing', $articleId);
		}
	}
	
	function notifyFinalCopyedit($args = array()) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $articleId, 'editing');

		if (SectionEditorAction::notifyFinalCopyedit($submission, $send)) {
			Request::redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	function completeCopyedit($args) {
		$articleId = (int) Request::getUserVar('articleId');
 
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
 
		SectionEditorAction::completeCopyedit($submission);
		Request::redirect(null, null, 'submissionEditing', $articleId);
	}
 
	function completeFinalCopyedit($args) {
		$articleId = (int) Request::getUserVar('articleId');
 
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
 
		SectionEditorAction::completeFinalCopyedit($submission);
		Request::redirect(null, null, 'submissionEditing', $articleId);
	}

	function thankFinalCopyedit($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $articleId, 'editing');

		if (SectionEditorAction::thankFinalCopyedit($submission, $send)) {
			Request::redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	function uploadReviewVersion() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		
		SectionEditorAction::uploadReviewVersion($submission);
		
		Request::redirect(null, null, 'submissionReview', $articleId);
	}
	
	function uploadCopyeditVersion() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		
		$copyeditStage = Request::getUserVar('copyeditStage');
		SectionEditorAction::uploadCopyeditVersion($submission, $copyeditStage);
		
		Request::redirect(null, null, 'submissionEditing', $articleId);
	}
	
	/**
	 * Add a supplementary file.
	 * @param $args array ($articleId)
	 */
	function addSuppFile($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'summary');
		
		import('submission.form.SuppFileForm');
		
		$submitForm = &new SuppFileForm($submission);
		
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
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'summary');
		
		import('submission.form.SuppFileForm');
		
		$submitForm = &new SuppFileForm($submission, $suppFileId);
		
		$submitForm->initData();
		$submitForm->display();
	}
	
	/**
	 * Set reviewer visibility for a supplementary file.
	 * @param $args array ($suppFileId)
	 */
	function setSuppFileVisibility($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		
		$suppFileId = Request::getUserVar('fileId');
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$suppFile = $suppFileDao->getSuppFile($suppFileId, $articleId);

		if (isset($suppFile) && $suppFile != null) {
			$suppFile->setShowReviewers(Request::getUserVar('show')==1?1:0);
			$suppFileDao->updateSuppFile($suppFile);
		}
		Request::redirect(null, null, 'submissionReview', $articleId);
	}
	
	/**
	 * Save a supplementary file.
	 * @param $args array ($suppFileId)
	 */
	function saveSuppFile($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		
		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;
		
		import('submission.form.SuppFileForm');
		
		$submitForm = &new SuppFileForm($submission, $suppFileId);
		$submitForm->readInputData();
		
		if ($submitForm->validate()) {
			$submitForm->execute();
			Request::redirect(null, null, 'submissionEditing', $articleId);
		} else {
			parent::setupTemplate(true, $articleId, 'summary');
			$submitForm->display();
		}
	}
	
	/**
	 * Delete an editor version file.
	 * @param $args array ($articleId, $fileId)
	 */
	function deleteArticleFile($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$fileId = isset($args[1]) ? (int) $args[1] : 0;
		$revisionId = isset($args[2]) ? (int) $args[2] : 0;

		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		SectionEditorAction::deleteArticleFile($submission, $fileId, $revisionId);
		
		Request::redirect(null, null, 'submissionReview', $articleId);
	}
	
	/**
	 * Delete a supplementary file.
	 * @param $args array ($articleId, $suppFileId)
	 */
	function deleteSuppFile($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$suppFileId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		
		SectionEditorAction::deleteSuppFile($submission, $suppFileId);
		
		Request::redirect(null, null, 'submissionEditing', $articleId);
	}
	
	function archiveSubmission($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);

		SectionEditorAction::archiveSubmission($submission);
		
		Request::redirect(null, null, 'submission', $articleId);
	}
	
	function restoreToQueue($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);

		SectionEditorAction::restoreToQueue($submission);
		
		Request::redirect(null, null, 'submissionEditing', $articleId);
	}

	function unsuitableSubmission($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $articleId, 'summary');

		if (SectionEditorAction::unsuitableSubmission($submission, $send)) {
			Request::redirect(null, null, 'submission', $articleId);
		}
	}

	/**
	 * Set section ID.
	 * @param $args array ($articleId)
	 */
	function updateSection($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);		
		SectionEditorAction::updateSection($submission, Request::getUserVar('section'));
		Request::redirect(null, null, 'submission', $articleId);
	}
	
	
	//
	// Layout Editing
	//
	
	/**
	 * Upload a layout file (either layout version, galley, or supp. file).
	 */
	function uploadLayoutFile() {
		$layoutFileType = Request::getUserVar('layoutFileType');
		if ($layoutFileType == 'submission') {
			SubmissionEditHandler::uploadLayoutVersion();
			
		} else if ($layoutFileType == 'galley') {
			SubmissionEditHandler::uploadGalley('layoutFile');
		
		} else if ($layoutFileType == 'supp') {
			SubmissionEditHandler::uploadSuppFile('layoutFile');
		
		} else {
			Request::redirect(null, null, 'submissionEditing', Request::getUserVar('articleId'));
		}
	}
	
	/**
	 * Upload the layout version of the submission file
	 */
	function uploadLayoutVersion() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		
		SectionEditorAction::uploadLayoutVersion($submission);
		
		Request::redirect(null, null, 'submissionEditing', $articleId);
	}
	
	/**
	 * Delete an article image.
	 * @param $args array ($articleId, $fileId)
	 */
	function deleteArticleImage($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$fileId = isset($args[2]) ? (int) $args[2] : 0;
		$revisionId = isset($args[3]) ? (int) $args[3] : 0;

		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		SectionEditorAction::deleteArticleImage($submission, $fileId, $revisionId);
		
		Request::redirect(null, null, 'editGalley', array($articleId, $galleyId));
	}
	
	/**
	 * Assign/reassign a layout editor to the submission.
	 * @param $args array ($articleId, [$userId])
	 */
	function assignLayoutEditor($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$editorId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		
		if ($editorId && $roleDao->roleExists($journal->getJournalId(), $editorId, ROLE_ID_LAYOUT_EDITOR)) {
			SectionEditorAction::assignLayoutEditor($submission, $editorId);
			Request::redirect(null, null, 'submissionEditing', $articleId);
		} else {
			$searchType = null;
			$searchMatch = null;
			$search = $searchQuery = Request::getUserVar('search');
			$searchInitial = Request::getUserVar('searchInitial');
			if (isset($search)) {
				$searchType = Request::getUserVar('searchField');
				$searchMatch = Request::getUserVar('searchMatch');
				
			} else if (isset($searchInitial)) {
				$searchInitial = String::strtoupper($searchInitial);
				$searchType = USER_FIELD_INITIAL;
				$search = $searchInitial;
			}

			$layoutEditors = $roleDao->getUsersByRoleId(ROLE_ID_LAYOUT_EDITOR, $journal->getJournalId(), $searchType, $search, $searchMatch);

			$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
			$layoutEditorStatistics = $sectionEditorSubmissionDao->getLayoutEditorStatistics($journal->getJournalId());

			parent::setupTemplate(true, $articleId, 'editing');

			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign('searchField', $searchType);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $searchQuery);
			$templateMgr->assign('searchInitial', $searchInitial);
			$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
			
			$templateMgr->assign('pageTitle', 'user.role.layoutEditors');
			$templateMgr->assign('pageSubTitle', 'editor.article.selectLayoutEditor');
			$templateMgr->assign('actionHandler', 'assignLayoutEditor');
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign_by_ref('users', $layoutEditors);

			$layoutAssignment = &$submission->getLayoutAssignment();
			if ($layoutAssignment) {
				$templateMgr->assign('currentUser', $layoutAssignment->getEditorId());
			}

			$templateMgr->assign('fieldOptions', Array(
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username',
				USER_FIELD_EMAIL => 'user.email'
			));
			$templateMgr->assign('statistics', $layoutEditorStatistics);
			$templateMgr->assign('helpTopicId', 'journal.roles.layoutEditor');
			$templateMgr->display('sectionEditor/selectUser.tpl');
		}
	}
	
	/**
	 * Notify the layout editor.
	 */
	function notifyLayoutEditor($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $articleId, 'editing');

		if (SectionEditorAction::notifyLayoutEditor($submission, $send)) {
			Request::redirect(null, null, 'submissionEditing', $articleId);
		}
	}
	
	/**
	 * Thank the layout editor.
	 */
	function thankLayoutEditor($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $articleId, 'editing');

		if (SectionEditorAction::thankLayoutEditor($submission, $send)) {
			Request::redirect(null, null, 'submissionEditing', $articleId);
		}
	}
	
	/**
	 * Create a new galley with the uploaded file.
	 */
	function uploadGalley($fileName = null) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		
		import('submission.form.ArticleGalleyForm');
		
		$galleyForm = &new ArticleGalleyForm($articleId);
		$galleyId = $galleyForm->execute($fileName);
		
		Request::redirect(null, null, 'editGalley', array($articleId, $galleyId));
	}
	
	/**
	 * Edit a galley.
	 * @param $args array ($articleId, $galleyId)
	 */
	function editGalley($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		
		parent::setupTemplate(true, $articleId, 'editing');
		
		import('submission.form.ArticleGalleyForm');
		
		$submitForm = &new ArticleGalleyForm($articleId, $galleyId);
		
		$submitForm->initData();
		$submitForm->display();
	}
	
	/**
	 * Save changes to a galley.
	 * @param $args array ($articleId, $galleyId)
	 */
	function saveGalley($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		
		import('submission.form.ArticleGalleyForm');
		
		$submitForm = &new ArticleGalleyForm($articleId, $galleyId);
		
		if (Request::getUserVar('uploadImage')) {
			$submitForm->initData();

			// Attach galley image
			$submitForm->uploadImage();
			
			parent::setupTemplate(true, $articleId, 'editing');
			$submitForm->display();

		} else if(($deleteImage = Request::getUserVar('deleteImage')) && count($deleteImage) == 1) {
			$submitForm->initData();

			// Delete galley image
			list($imageId) = array_keys($deleteImage);
			$submitForm->deleteImage($imageId);
			
			parent::setupTemplate(true, $articleId, 'editing');
			$submitForm->display();
			
		} else {
			$submitForm->readInputData();
			if ($submitForm->validate()) {
				$submitForm->execute();
				Request::redirect(null, null, 'submissionEditing', $articleId);

			} else {
				parent::setupTemplate(true, $articleId, 'editing');
				$submitForm->display();
			}
		}
	}
	
	/**
	 * Change the sequence order of a galley.
	 */
	function orderGalley() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		
		SectionEditorAction::orderGalley($submission, Request::getUserVar('galleyId'), Request::getUserVar('d'));

		Request::redirect(null, null, 'submissionEditing', $articleId);
	}
	
	/**
	 * Delete a galley file.
	 * @param $args array ($articleId, $galleyId)
	 */
	function deleteGalley($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		
		SectionEditorAction::deleteGalley($submission, $galleyId);
		
		Request::redirect(null, null, 'submissionEditing', $articleId);
	}
	
	/**
	 * Proof / "preview" a galley.
	 * @param $args array ($articleId, $galleyId)
	 */
	function proofGalley($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		
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
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		
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
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		
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
				SubmissionEditHandler::viewFile(array($articleId, $galley->getFileId()));
			}
		}
	}
	
	/**
	 * Upload a new supplementary file.
	 */
	function uploadSuppFile($fileName = null) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		
		import('submission.form.SuppFileForm');
		
		$suppFileForm = &new SuppFileForm($submission);
		$suppFileForm->setData('title', Locale::translate('common.untitled'));
		$suppFileId = $suppFileForm->execute($fileName);
		
		Request::redirect(null, null, 'editSuppFile', array($articleId, $suppFileId));
	}
	
	/**
	 * Change the sequence order of a supplementary file.
	 */
	function orderSuppFile() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		
		SectionEditorAction::orderSuppFile($submission, Request::getUserVar('suppFileId'), Request::getUserVar('d'));

		Request::redirect(null, null, 'submissionEditing', $articleId);
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
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'history');
		
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('isEditor', Validation::isEditor());
		$templateMgr->assign_by_ref('submission', $submission);
		
		if ($logId) {
			$logDao = &DAORegistry::getDAO('ArticleEventLogDAO');
			$logEntry = &$logDao->getLogEntry($logId, $articleId);
		}
		
		if (isset($logEntry)) {
			$templateMgr->assign('logEntry', $logEntry);
			$templateMgr->display('sectionEditor/submissionEventLogEntry.tpl');
			
		} else {
			$rangeInfo = &Handler::getRangeInfo('eventLogEntries');
			
			import('article.log.ArticleLog');
			$eventLogEntries = &ArticleLog::getEventLogEntries($articleId, $rangeInfo);
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
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'history');
		
		$rangeInfo = &Handler::getRangeInfo('eventLogEntries');
		$logDao = &DAORegistry::getDAO('ArticleEventLogDAO');
		$eventLogEntries = &$logDao->getArticleLogEntriesByAssoc($articleId, $assocType, $assocId, $rangeInfo);
		
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('showBackLink', true);
		$templateMgr->assign('isEditor', Validation::isEditor());
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('eventLogEntries', $eventLogEntries);
		$templateMgr->display('sectionEditor/submissionEventLog.tpl');
	}
	
	/**
	 * Clear submission event log entries.
	 */
	function clearSubmissionEventLog($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$logId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		
		$logDao = &DAORegistry::getDAO('ArticleEventLogDAO');
		
		if ($logId) {
			$logDao->deleteLogEntry($logId, $articleId);
			
		} else {
			$logDao->deleteArticleLogEntries($articleId);
		}
		
		Request::redirect(null, null, 'submissionEventLog', $articleId);
	}
	
	/**
	 * View submission email log.
	 */
	function submissionEmailLog($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$logId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'history');
		
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('isEditor', Validation::isEditor());
		$templateMgr->assign_by_ref('submission', $submission);

		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		import('file.ArticleFileManager');
		$templateMgr->assign('attachments', $articleFileDao->getArticleFilesByAssocId($logId, ARTICLE_FILE_ATTACHMENT));
		
		if ($logId) {
			$logDao = &DAORegistry::getDAO('ArticleEmailLogDAO');
			$logEntry = &$logDao->getLogEntry($logId, $articleId);
		}
		
		if (isset($logEntry)) {
			$templateMgr->assign_by_ref('logEntry', $logEntry);
			$templateMgr->display('sectionEditor/submissionEmailLogEntry.tpl');
			
		} else {
			$rangeInfo = &Handler::getRangeInfo('emailLogEntries');
			
			import('article.log.ArticleLog');
			$emailLogEntries = &ArticleLog::getEmailLogEntries($articleId, $rangeInfo);
			$templateMgr->assign_by_ref('emailLogEntries', $emailLogEntries);
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
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'history');
		
		$rangeInfo = &Handler::getRangeInfo('eventLogEntries');
		$logDao = &DAORegistry::getDAO('ArticleEmailLogDAO');
		$emailLogEntries = &$logDao->getArticleLogEntriesByAssoc($articleId, $assocType, $assocId, $rangeInfo);
		
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('showBackLink', true);
		$templateMgr->assign('isEditor', Validation::isEditor());
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('emailLogEntries', $emailLogEntries);
		$templateMgr->display('sectionEditor/submissionEmailLog.tpl');
	}
	
	/**
	 * Clear submission email log entries.
	 */
	function clearSubmissionEmailLog($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$logId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		
		$logDao = &DAORegistry::getDAO('ArticleEmailLogDAO');
		
		if ($logId) {
			$logDao->deleteLogEntry($logId, $articleId);
			
		} else {
			$logDao->deleteArticleLogEntries($articleId);
		}
		
		Request::redirect(null, null, 'submissionEmailLog', $articleId);
	}
	
	// Submission Notes Functions

	/**
	 * Creates a submission note.
	 * Redirects to submission notes list
	 */
	function addSubmissionNote() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		
		SectionEditorAction::addSubmissionNote($articleId);
		Request::redirect(null, null, 'submissionNotes', $articleId);
	}

	/**
	 * Removes a submission note.
	 * Redirects to submission notes list
	 */
	function removeSubmissionNote() {
		$articleId = Request::getUserVar('articleId');		
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);

		SectionEditorAction::removeSubmissionNote($articleId);
		Request::redirect(null, null, 'submissionNotes', $articleId);
	}
	
	/**
	 * Updates a submission note.
	 * Redirects to submission notes list
	 */
	function updateSubmissionNote() {
		$articleId = Request::getUserVar('articleId');		
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);

		SectionEditorAction::updateSubmissionNote($articleId);
		Request::redirect(null, null, 'submissionNotes', $articleId);
	}

	/**
	 * Clear all submission notes.
	 * Redirects to submission notes list
	 */
	function clearAllSubmissionNotes() {
		$articleId = Request::getUserVar('articleId');		
		list($journal, $submission) = SubmissionEditHandler::validate($articleId);

		SectionEditorAction::clearAllSubmissionNotes($articleId);
		Request::redirect(null, null, 'submissionNotes', $articleId);
	}
	
	/**
	 * View submission notes.
	 */
	function submissionNotes($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$noteViewType = isset($args[1]) ? $args[1] : '';
		$noteId = isset($args[2]) ? (int) $args[2] : 0;

		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'history');

		$rangeInfo = &Handler::getRangeInfo('submissionNotes');
		$articleNoteDao = &DAORegistry::getDAO('ArticleNoteDAO');
		$submissionNotes =& $articleNoteDao->getArticleNotes($articleId, $rangeInfo);

		// submission note edit
		if ($noteViewType == 'edit') {
			$articleNote = $articleNoteDao->getArticleNoteById($noteId);
		}
		
		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('submissionNotes', $submissionNotes);
		$templateMgr->assign('noteViewType', $noteViewType);
		if (isset($articleNote)) {
			$templateMgr->assign_by_ref('articleNote', $articleNote);		
		}

		if ($noteViewType == 'edit' || $noteViewType == 'add') {
			$templateMgr->assign('showBackLink', true);
		}

		$templateMgr->display('sectionEditor/submissionNotes.tpl');
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

		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		if (!SectionEditorAction::downloadFile($articleId, $fileId, $revision)) {
			Request::redirect(null, null, 'submission', $articleId);
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

		list($journal, $submission) = SubmissionEditHandler::validate($articleId);
		if (!SectionEditorAction::viewFile($articleId, $fileId, $revision)) {
			Request::redirect(null, null, 'submission', $articleId);
		}
	}


	//
	// Proofreading
	//
	
	/**
	 * Select Proofreader.
	 * @param $args array ($articleId, $userId)
	 */
	function selectProofreader($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$userId = isset($args[1]) ? (int) $args[1] : 0;

		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$roleDao = &DAORegistry::getDAO('RoleDAO');

		if ($userId && $articleId  && $roleDao->roleExists($journal->getJournalId(), $userId, ROLE_ID_PROOFREADER)) {
			import('submission.proofreader.ProofreaderAction');
			ProofreaderAction::selectProofreader($userId, $submission);
			Request::redirect(null, null, 'submissionEditing', $articleId);
		} else {
			parent::setupTemplate(true, $articleId, 'editing');

			$searchType = null;
			$searchMatch = null;
			$search = $searchQuery = Request::getUserVar('search');
			$searchInitial = Request::getUserVar('searchInitial');
			if (isset($search)) {
				$searchType = Request::getUserVar('searchField');
				$searchMatch = Request::getUserVar('searchMatch');
				
			} else if (isset($searchInitial)) {
				$searchInitial = String::strtoupper($searchInitial);
				$searchType = USER_FIELD_INITIAL;
				$search = $searchInitial;
			}

			$proofreaders = $roleDao->getUsersByRoleId(ROLE_ID_PROOFREADER, $journal->getJournalId(), $searchType, $search, $searchMatch);
				
			$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
			$proofreaderStatistics = $sectionEditorSubmissionDao->getProofreaderStatistics($journal->getJournalId());
		
			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign('searchField', $searchType);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $searchQuery);
			$templateMgr->assign('searchInitial', $searchInitial);
			
			$templateMgr->assign_by_ref('users', $proofreaders);

			$proofAssignment = &$submission->getProofAssignment();
			if ($proofAssignment) {
				$templateMgr->assign('currentUser', $proofAssignment->getProofreaderId());
			}
			$templateMgr->assign('statistics', $proofreaderStatistics);
			$templateMgr->assign('fieldOptions', Array(
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username',
				USER_FIELD_EMAIL => 'user.email'
			));
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('pageSubTitle', 'editor.article.selectProofreader');
			$templateMgr->assign('pageTitle', 'user.role.proofreaders');
			$templateMgr->assign('actionHandler', 'selectProofreader');

			$templateMgr->assign('helpTopicId', 'journal.roles.proofreader');
			$templateMgr->display('sectionEditor/selectUser.tpl');
		}
	}

	/**
	 * Notify author for proofreading
	 */
	function notifyAuthorProofreader($args) {
		$articleId = Request::getUserVar('articleId');
		$send = Request::getUserVar('send')?1:0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		parent::setupTemplate(true, $articleId, 'editing');

		import('submission.proofreader.ProofreaderAction');
		if (ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_AUTHOR_REQUEST', $send?'':Request::url(null, null, 'notifyAuthorProofreader'))) {
			Request::redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Thank author for proofreading
	 */
	function thankAuthorProofreader($args) {
		$articleId = Request::getUserVar('articleId');
		$send = Request::getUserVar('send')?1:0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		parent::setupTemplate(true, $articleId, 'editing');

		import('submission.proofreader.ProofreaderAction');
		if (ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_AUTHOR_ACK', $send?'':Request::url(null, null, 'thankAuthorProofreader'))) {
			Request::redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Editor initiates proofreading
	 */
	function editorInitiateProofreader() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = &$proofAssignmentDao->getProofAssignmentByArticleId($articleId);
		$proofAssignment->setDateProofreaderNotified(Core::getCurrentDate());
		$proofAssignmentDao->updateProofAssignment($proofAssignment);

		Request::redirect(null, null, 'submissionEditing', $articleId);
	}

	/**
	 * Editor completes proofreading
	 */
	function editorCompleteProofreader() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = &$proofAssignmentDao->getProofAssignmentByArticleId($articleId);
		$proofAssignment->setDateProofreaderCompleted(Core::getCurrentDate());
		$proofAssignmentDao->updateProofAssignment($proofAssignment);

		Request::redirect(null, null, 'submissionEditing', $articleId);
	}

	/**
	 * Notify proofreader for proofreading
	 */
	function notifyProofreader($args) {
		$articleId = Request::getUserVar('articleId');
		$send = Request::getUserVar('send');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		parent::setupTemplate(true, $articleId, 'editing');

		import('submission.proofreader.ProofreaderAction');
		if (ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_REQUEST', $send?'':Request::url(null, null, 'notifyProofreader'))) {
			Request::redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Thank proofreader for proofreading
	 */
	function thankProofreader($args) {
		$articleId = Request::getUserVar('articleId');
		$send = Request::getUserVar('send')?1:0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		parent::setupTemplate(true, $articleId, 'editing');

		import('submission.proofreader.ProofreaderAction');
		if (ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_ACK', $send?'':Request::url(null, null, 'thankProofreader'))) {
			Request::redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Editor initiates layout editor proofreading
	 */
	function editorInitiateLayoutEditor() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = &$proofAssignmentDao->getProofAssignmentByArticleId($articleId);
		$proofAssignment->setDateLayoutEditorNotified(Core::getCurrentDate());
		$proofAssignmentDao->updateProofAssignment($proofAssignment);

		Request::redirect(null, null, 'submissionEditing', $articleId);
	}

	/**
	 * Editor completes layout editor proofreading
	 */
	function editorCompleteLayoutEditor() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = &$proofAssignmentDao->getProofAssignmentByArticleId($articleId);
		$proofAssignment->setDateLayoutEditorCompleted(Core::getCurrentDate());
		$proofAssignmentDao->updateProofAssignment($proofAssignment);

		Request::redirect(null, null, 'submissionEditing', $articleId);
	}

	/**
	 * Notify layout editor for proofreading
	 */
	function notifyLayoutEditorProofreader($args) {
		$articleId = Request::getUserVar('articleId');
		$send = Request::getUserVar('send')?1:0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		parent::setupTemplate(true, $articleId, 'editing');

		import('submission.proofreader.ProofreaderAction');
		if (ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_LAYOUT_REQUEST', $send?'':Request::url(null, null, 'notifyLayoutEditorProofreader'))) {
			Request::redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Thank layout editor for proofreading
	 */
	function thankLayoutEditorProofreader($args) {
		$articleId = Request::getUserVar('articleId');
		$send = Request::getUserVar('send')?1:0;
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		parent::setupTemplate(true, $articleId, 'editing');

		import('submission.proofreader.ProofreaderAction');
		if (ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_LAYOUT_ACK', $send?'':Request::url(null, null, 'thankLayoutEditorProofreader'))) {
			Request::redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Schedule/unschedule an article for publication.
	 */
	function scheduleForPublication($args) {
		$articleId = (int) array_shift($args);
		$issueId = (int) Request::getUserVar('issueId');
		list($journal, $submission) = SubmissionEditHandler::validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($articleId);
		if ($issueId) {
			// Schedule against an issue.
			if ($publishedArticle) {
				$publishedArticle->setIssueId($issueId);
				$publishedArticleDao->updatePublishedArticle($publishedArticle);
			} else {
				$publishedArticle =& new PublishedArticle();
				$publishedArticle->setArticleId($submission->getArticleId());
				$publishedArticle->setIssueId($issueId);
				$publishedArticle->setDatePublished(Core::getCurrentDate());
				$publishedArticle->setSeq(100000); // KLUDGE: End of list
				$publishedArticle->setViews(0);
				$publishedArticle->setAccessStatus(0);

				$publishedArticleDao->insertPublishedArticle($publishedArticle);

				// Resequence the articles.
				$publishedArticleDao->resequencePublishedArticles($submission->getSectionId(), $issueId);

				// If we're using custom section ordering, and if this is the first
				// article published in a section, make sure we enter a custom ordering
				// for it. (Default at the end of the list.)
				if ($sectionDao->customSectionOrderingExists($issueId)) {
					if ($sectionDao->getCustomSectionOrder($issueId, $submission->getSectionId()) === null) {
						$sectionDao->insertCustomSectionOrder($issueId, $submission->getSectionId(), 10000); // KLUDGE: End of list
						$sectionDao->resequenceCustomSectionOrders($issueId);
					}
				}
			}
			$submission->setStatus(STATUS_PUBLISHED);
		} else {
			if ($publishedArticle) {
				// This was published elsewhere; make sure we don't
				// mess up sequencing information.
				$publishedArticleDao->resequencePublishedArticles($submission->getSectionId(), $publishedArticle->getIssueId());
				$publishedArticleDao->deletePublishedArticleByArticleId($articleId);
			}

			// Remove from scheduling.
			$submission->setStatus(STATUS_QUEUED);
		}
		$submission->stampStatusModified();
		$sectionEditorSubmissionDao->updateSectionEditorSubmission($submission);

		Request::redirect(null, null, 'submissionEditing', array($articleId), null, 'scheduling');
	}

	//
	// Validation
	//
	
	/**
	 * Validate that the user is the assigned section editor for
	 * the article, or is a managing editor.
	 * Redirects to sectionEditor index page if validation fails.
	 * @param $articleId int Article ID to validate
	 * @param $access int Optional name of access level required -- see SECTION_EDITOR_ACCESS_... constants
	 */
	function validate($articleId, $access = null) {
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
			
		} else if ($sectionEditorSubmission->getDateSubmitted() == null) {
			$isValid = false;
			
		} else {
			$templateMgr =& TemplateManager::getManager();
			if (Validation::isEditor()) {
				// Make canReview and canEdit available to templates.
				// Since this user is an editor, both are available.
				$templateMgr->assign('canReview', true);
				$templateMgr->assign('canEdit', true);
			} else {
				// If this user isn't the submission's editor, they don't have access.
				$editAssignments =& $sectionEditorSubmission->getEditAssignments();
				$wasFound = false;
				foreach ($editAssignments as $editAssignment) {
					if ($editAssignment->getEditorId() == $user->getUserId()) {
						$templateMgr->assign('canReview', $editAssignment->getCanReview());
						$templateMgr->assign('canEdit', $editAssignment->getCanEdit());
						switch ($access) {
							case SECTION_EDITOR_ACCESS_EDIT:
								if ($editAssignment->getCanEdit()) {
									$wasFound = true;
								}
								break;
							case SECTION_EDITOR_ACCESS_REVIEW:
								if ($editAssignment->getCanReview()) {
									$wasFound = true;
								}
								break;

							default:
								$wasFound = true;
						}
						break;
					}
				}

				if (!$wasFound) $isValid = false;
			}
		}
		
		if (!$isValid) {
			Request::redirect(null, Request::getRequestedPage());
		}

		// If necessary, note the current date and time as the "underway" date/time
		$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments = &$sectionEditorSubmission->getEditAssignments();
		foreach ($editAssignments as $editAssignment) {
			if ($editAssignment->getEditorId() == $user->getUserId() && $editAssignment->getDateUnderway() === null) {
				$editAssignment->setDateUnderway(Core::getCurrentDate());
				$editAssignmentDao->updateEditAssignment($editAssignment);
			}
		}

		return array(&$journal, &$sectionEditorSubmission);
	}
}
?>
