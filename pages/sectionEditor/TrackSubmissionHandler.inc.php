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

class TrackSubmissionHandler extends SectionEditorHandler {
	
	function submission($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId);

		$user = &Request::getUser();
		
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
		
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$templateMgr->assign('sections', $sectionDao->getSectionTitles($journal->getJournalId()));

		if ($isEditor) {
			$templateMgr->assign('helpTopicId', 'editorial.editorsRole.submissionSummary');
		}

		$templateMgr->display('sectionEditor/submission.tpl');
	}
	
	function submissionRegrets($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'review');

		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$cancelsAndRegrets = $reviewAssignmentDao->getCancelsAndRegrets($articleId);
		$reviewFilesByRound = $reviewAssignmentDao->getReviewFilesByRound($articleId);

		$reviewAssignments = $submission->getReviewAssignments();
		$editorDecisions = $submission->getDecisions();
		$numRounds = $submission->getCurrentRound();
		
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('reviewAssignments', $reviewAssignments);
		$templateMgr->assign('cancelsAndRegrets', $cancelsAndRegrets);
		$templateMgr->assign('reviewFilesByRound', $reviewFilesByRound);
		$templateMgr->assign('editorDecisions', $editorDecisions);
		$templateMgr->assign('numRounds', $numRounds);
		$templateMgr->assign('rateReviewerOnQuality', $journal->getSetting('rateReviewerOnQuality'));
		$templateMgr->assign('reviewerRatingOptions',
			array(
				SUBMISSION_REVIEWER_RATING_VERY_GOOD => 'editor.article.reviewerRating.veryGood',
				SUBMISSION_REVIEWER_RATING_GOOD => 'editor.article.reviewerRating.good',
				SUBMISSION_REVIEWER_RATING_AVERAGE => 'editor.article.reviewerRating.average',
				SUBMISSION_REVIEWER_RATING_POOR => 'editor.article.reviewerRating.poor',
				SUBMISSION_REVIEWER_RATING_VERY_POOR => 'editor.article.reviewerRating.veryPoor'
			)
		);
		$templateMgr->assign('editorDecisionOptions',
			array(
				'' => 'common.chooseOne',
				SUBMISSION_EDITOR_DECISION_ACCEPT => 'editor.article.decision.accept',
				SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => 'editor.article.decision.pendingRevisions',
				SUBMISSION_EDITOR_DECISION_RESUBMIT => 'editor.article.decision.resubmit',
				SUBMISSION_EDITOR_DECISION_DECLINE => 'editor.article.decision.decline'
			)
		);
		$templateMgr->assign('reviewerRecommendationOptions',
			array(
				'' => 'common.chooseOne',
				SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT => 'reviewer.article.decision.accept',
				SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS => 'reviewer.article.decision.pendingRevisions',
				SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_HERE => 'reviewer.article.decision.resubmitHere',
				SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_ELSEWHERE => 'reviewer.article.decision.resubmitElsewhere',
				SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE => 'reviewer.article.decision.decline',
				SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS => 'reviewer.article.decision.seeComments'
			)
		);
	
		$templateMgr->display('sectionEditor/submissionRegrets.tpl');
	}
	
	function submissionReview($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId);

		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');

		// Setting the round.
		$round = isset($args[1]) ? $args[1] : $submission->getCurrentRound();

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sections = $sectionDao->getJournalSections($journal->getJournalId());

		/* This feature has been removed -AW
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$numReviewers = $journalSettingsDao->getSetting($journal->getJournalId(), 'numReviewersPerSubmission');
		
		if ($round == $submission->getCurrentRound() && count($submission->getReviewAssignments()) < $numReviewers) {
			$numSelectReviewers = $numReviewers - count($submission->getReviewAssignments());
		} else {
			$numSelectReviewers = 0;
		}
		*/
		
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

		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('reviewIndexes', $reviewAssignmentDao->getReviewIndexesForRound($articleId, $round));
		$templateMgr->assign('round', $round);
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('reviewAssignments', $submission->getReviewAssignments($round));
		$templateMgr->assign('notifyReviewerLogs', $notifyReviewerLogs);
		$templateMgr->assign('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('reviewFile', $submission->getReviewFile());
		$templateMgr->assign('revisedFile', $submission->getRevisedFile());
		$templateMgr->assign('editorFile', $submission->getEditorFile());
		//$templateMgr->assign('numSelectReviewers', $numSelectReviewers); REMOVED -AW
		$templateMgr->assign('rateReviewerOnQuality', $journal->getSetting('rateReviewerOnQuality'));
		$templateMgr->assign('showPeerReviewOptions', $showPeerReviewOptions);
		$templateMgr->assign('sections', $sections);
		$templateMgr->assign('editorDecisionOptions',
			array(
				'' => 'common.chooseOne',
				SUBMISSION_EDITOR_DECISION_ACCEPT => 'editor.article.decision.accept',
				SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => 'editor.article.decision.pendingRevisions',
				SUBMISSION_EDITOR_DECISION_RESUBMIT => 'editor.article.decision.resubmit',
				SUBMISSION_EDITOR_DECISION_DECLINE => 'editor.article.decision.decline'
			)
		);
		$templateMgr->assign('lastDecision', $lastDecision);
		$templateMgr->assign('reviewerRecommendationOptions',
			array(
				'' => 'common.chooseOne',
				SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT => 'reviewer.article.decision.accept',
				SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS => 'reviewer.article.decision.pendingRevisions',
				SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_HERE => 'reviewer.article.decision.resubmitHere',
				SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_ELSEWHERE => 'reviewer.article.decision.resubmitElsewhere',
				SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE => 'reviewer.article.decision.decline',
				SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS => 'reviewer.article.decision.seeComments'
			)
		);
		$templateMgr->assign('reviewerRatingOptions',
			array(
				SUBMISSION_REVIEWER_RATING_VERY_GOOD => 'editor.article.reviewerRating.veryGood',
				SUBMISSION_REVIEWER_RATING_GOOD => 'editor.article.reviewerRating.good',
				SUBMISSION_REVIEWER_RATING_AVERAGE => 'editor.article.reviewerRating.average',
				SUBMISSION_REVIEWER_RATING_POOR => 'editor.article.reviewerRating.poor',
				SUBMISSION_REVIEWER_RATING_VERY_POOR => 'editor.article.reviewerRating.veryPoor'
			)
		);
		$templateMgr->assign('allowRecommendation', $allowRecommendation);
		$templateMgr->assign('allowResubmit', $allowResubmit);
		$templateMgr->assign('allowCopyedit', $allowCopyedit);

		$templateMgr->assign('helpTopicId', 'editorial.sectionEditorsRole.review');
		$templateMgr->display('sectionEditor/submissionReview.tpl');
	}
	
	function submissionEditing($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
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
		
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('editor', $submission->getEditor());
		$templateMgr->assign('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign('copyeditFile', $submission->getCopyeditFile());
		$templateMgr->assign('initialCopyeditFile', $submission->getInitialCopyeditFile());
		$templateMgr->assign('editorAuthorCopyeditFile', $submission->getEditorAuthorCopyeditFile());
		$templateMgr->assign('finalCopyeditFile', $submission->getFinalCopyeditFile());
		$templateMgr->assign('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('copyeditor', $submission->getCopyeditor());
		$templateMgr->assign('useCopyeditors', $useCopyeditors);
		$templateMgr->assign('useLayoutEditors', $useLayoutEditors);
		$templateMgr->assign('useProofreaders', $useProofreaders);
		$templateMgr->assign('proofAssignment', $submission->getProofAssignment());
		$templateMgr->assign('layoutAssignment', $submission->getLayoutAssignment());
		$templateMgr->assign('submissionAccepted', $submissionAccepted);

		$templateMgr->assign('helpTopicId', 'editorial.sectionEditorsRole.editing');		
		$templateMgr->display('sectionEditor/submissionEditing.tpl');
	}
	
	/**
	 * View submission history
	 */
	function submissionHistory($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);

		parent::setupTemplate(true, $articleId);
		
		// submission notes
		$articleNoteDao = &DAORegistry::getDAO('ArticleNoteDAO');
		$submissionNotes = $articleNoteDao->getArticleNotes($articleId, 5);

		import('article.log.ArticleLog');
		$eventLogEntries = &ArticleLog::getEventLogEntries($articleId, 5);
		$emailLogEntries = &ArticleLog::getEmailLogEntries($articleId, 5);
		
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign('isEditor', Validation::isEditor());
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('eventLogEntries', $eventLogEntries);
		$templateMgr->assign('emailLogEntries', $emailLogEntries);
		$templateMgr->assign('submissionNotes', $submissionNotes);

		$templateMgr->display('sectionEditor/submissionHistory.tpl');
	}
	
	function designateReviewVersion() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$designate = Request::getUserVar('designate');

		SectionEditorAction::designateReviewVersion($submission, $designate);
		
		Request::redirect(sprintf('%s/submissionReview/%d', Request::getRequestedPage(), $articleId));
	}
	
	function changeSection() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$sectionId = Request::getUserVar('sectionId');

		SectionEditorAction::changeSection($submission, $sectionId);
		
		Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $articleId));
	}
	
	function recordDecision() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$decision = Request::getUserVar('decision');

		switch ($decision) {
			case SUBMISSION_EDITOR_DECISION_ACCEPT:
			case SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS:
			case SUBMISSION_EDITOR_DECISION_RESUBMIT:
			case SUBMISSION_EDITOR_DECISION_DECLINE:
				SectionEditorAction::recordDecision($submission, $decision);
				break;
		}
		
		Request::redirect(sprintf('%s/submissionReview/%d', Request::getRequestedPage(), $articleId));
	}
	
	//
	// Peer Review
	//
	
	function selectReviewer($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');

		if (isset($args[1]) && $args[1] != null) {
			// Assign reviewer to article			
			SectionEditorAction::addReviewer($submission, $args[1]);
			Request::redirect(sprintf('%s/submissionReview/%d', Request::getRequestedPage(), $articleId));
			
			// FIXME: Prompt for due date.
		} else {
			parent::setupTemplate(true, $articleId, 'review');
		
			$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');

			$searchType = null;
			$searchMatch = null;
			$search = Request::getUserVar('search');
			$search_initial = Request::getUserVar('search_initial');
			if (isset($search)) {
				$searchType = Request::getUserVar('searchField');
				$searchMatch = Request::getUserVar('searchMatch');
			}
			else if (isset($search_initial)) {
				$searchType = USER_FIELD_INITIAL;
				$search = $search_initial;
			}

			$reviewers = $sectionEditorSubmissionDao->getReviewersForArticle($journal->getJournalId(), $articleId, $submission->getCurrentRound(), $searchType, $search, $searchMatch);
			
			$journal = Request::getJournal();
			$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		
			$templateMgr = &TemplateManager::getManager();
		
			$templateMgr->assign('reviewers', $reviewers);
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('reviewerStatistics', $sectionEditorSubmissionDao->getReviewerStatistics($journal->getJournalId()));
			$templateMgr->assign('fieldOptions', Array(
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username',
				USER_FIELD_INTERESTS => 'user.interests'
			));
			$templateMgr->assign('rateReviewerOnQuality', $journal->getSetting('rateReviewerOnQuality'));
			$templateMgr->assign('averageQualityRatings', $reviewAssignmentDao->getAverageQualityRatings($journal->getJournalId()));

			$templateMgr->assign('helpTopicId', 'journal.roles.reviewer');
			$templateMgr->display('sectionEditor/selectReviewer.tpl');
		}
	}

	/**
	 * Search for users to enroll as reviewers.
	 */
	function enrollSearch($args) {
		parent::validate();

		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath('reviewer');

		$user = &Request::getUser();

		$templateMgr = &TemplateManager::getManager();
		parent::setupTemplate(true);

		$searchType = null;
		$searchMatch = null;
		$search = Request::getUserVar('search');
		$search_initial = Request::getUserVar('search_initial');
		if (isset($search)) {
			$searchType = Request::getUserVar('searchField');
			$searchMatch = Request::getUserVar('searchMatch');
		}
		else if (isset($search_initial)) {
			$searchType = USER_FIELD_INITIAL;
			$search = $search_initial;
		}

		$userDao = &DAORegistry::getDAO('UserDAO');
		$users = &$userDao->getUsersByField($searchType, $searchMatch, $search);

		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username'
		));
		$templateMgr->assign('roleId', $roleId);
		$templateMgr->assign('users', $users);

		$templateMgr->assign('helpTopicId', 'journal.roles.index');
		$templateMgr->display('sectionEditor/searchUsers.tpl');
	}

	function enroll($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
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
		Request::redirect(sprintf('%s/selectReviewer/%d', Request::getRequestedPage(), $articleId));
	}
	
	function reinitiateReview($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$reviewId = isset($args[1]) ? (int) $args[1] : 0;
		
		SectionEditorAction::reinitiateReview($articleId, $reviewId);
		Request::redirect(sprintf('%s/submissionReview/%d', Request::getRequestedPage(), $articleId));
	}

	function notifyReviewer($args = array()) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$reviewId = Request::getUserVar('reviewId');
		
		if (Request::getUserVar('send')) {
			SectionEditorAction::notifyReviewer($submission, $reviewId, true);
			Request::redirect(sprintf('%s/submissionReview/%d', Request::getRequestedPage(), $articleId));
		} else {
			parent::setupTemplate(true, $articleId, 'review');
			SectionEditorAction::notifyReviewer($submission, $reviewId);
		}
	}
	
	function notifyAllReviewers($args = array()) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		if (Request::getUserVar('send')) {
			SectionEditorAction::notifyAllReviewers($submission, true);
			Request::redirect(sprintf('%s/submissionReview/%d', Request::getRequestedPage(), $articleId));
		} else {
			parent::setupTemplate(true, $articleId, 'review');
			SectionEditorAction::notifyAllReviewers($submission);
		}
	}
	
	function clearReview($args) {
		$articleId = isset($args[0])?$args[0]:0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$reviewId = $args[1];
		
		SectionEditorAction::clearReview($submission, $reviewId);

		Request::redirect(sprintf('%s/submissionReview/%d', Request::getRequestedPage(), $articleId));
	}
	
	function cancelReview($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$reviewId = Request::getUserVar('reviewId');
		
		if (Request::getUserVar('send')) {
			SectionEditorAction::cancelReview($submission, $reviewId, true);
			Request::redirect(sprintf('%s/submissionReview/%d', Request::getRequestedPage(), $articleId));
		} else {
			parent::setupTemplate(true, $articleId, 'review');
			SectionEditorAction::cancelReview($submission, $reviewId);
		}
	}
	
	function remindReviewer($args = null) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$reviewId = Request::getUserVar('reviewId');
		
		if (Request::getUserVar('send')) {
			SectionEditorAction::remindReviewer($submission, $reviewId, true);
		} else {
			SectionEditorAction::remindReviewer($submission, $reviewId);
		}
		
		Request::redirect(sprintf('%s/submissionReview/%d', Request::getRequestedPage(), $articleId));
	}
	
	function thankReviewer($args = array()) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$reviewId = Request::getUserVar('reviewId');
		
		if (Request::getUserVar('send')) {
			SectionEditorAction::thankReviewer($submission, $reviewId, true);
			Request::redirect(sprintf('%s/submissionReview/%d', Request::getRequestedPage(), $articleId));
			
		} else {
			parent::setupTemplate(true, $articleId, 'review');
			SectionEditorAction::thankReviewer($submission, $reviewId);
		}
	}
	
	function rateReviewer() {
		$articleId = Request::getUserVar('articleId');
		TrackSubmissionHandler::validate($articleId);
		list($journal, $submission) = parent::setupTemplate(true, $articleId, 'review');
		
		$reviewId = Request::getUserVar('reviewId');
		$quality = Request::getUserVar('quality');
		
		SectionEditorAction::rateReviewer($articleId, $reviewId, $quality);
		
		Request::redirect(sprintf('%s/submissionReview/%d', Request::getRequestedPage(), $articleId));
	}

	function acceptReviewForReviewer($args) {
		$articleId = $args[0];
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$reviewId = $args[1];
		
		SectionEditorAction::acceptReviewForReviewer($reviewId);
		Request::redirect(sprintf('%s/submissionReview/%d', Request::getRequestedPage(), $articleId));
	}

	function makeReviewerFileViewable() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$reviewId = Request::getUserVar('reviewId');
		$fileId = Request::getUserVar('fileId');
		$revision = Request::getUserVar('revision');
		$viewable = Request::getUserVar('viewable');
		
		SectionEditorAction::makeReviewerFileViewable($articleId, $reviewId, $fileId, $revision, $viewable);
		
		Request::redirect(sprintf('%s/submissionReview/%d', Request::getRequestedPage(), $articleId));
	}

	function setDueDate($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$reviewId = isset($args[1]) ? $args[1] : 0;
		$dueDate = Request::getUserVar('dueDate');
		$numWeeks = Request::getUserVar('numWeeks');
		
		if ($dueDate != null || $numWeeks != null) {
			SectionEditorAction::setDueDate($articleId, $reviewId, $dueDate, $numWeeks);
			Request::redirect(sprintf('%s/submissionReview/%d', Request::getRequestedPage(), $articleId));
			
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
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$reviewId = Request::getUserVar('reviewId');
		
		$recommendation = Request::getUserVar('recommendation');
		
		if ($recommendation != null) {
			SectionEditorAction::setReviewerRecommendation($articleId, $reviewId, $recommendation, SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT);
			Request::redirect(sprintf('%s/submissionReview/%d', Request::getRequestedPage(), $articleId));
				
		} else {
			parent::setupTemplate(true, $articleId, 'review');
			
			$templateMgr = &TemplateManager::getManager();
			
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('reviewId', $reviewId);
			$templateMgr->assign('reviewerRecommendationOptions',
				array(
					'' => 'common.chooseOne',
					SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT => 'reviewer.article.decision.accept',
					SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS => 'reviewer.article.decision.pendingRevisions',
					SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_HERE => 'reviewer.article.decision.resubmitHere',
					SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_ELSEWHERE => 'reviewer.article.decision.resubmitElsewhere',
					SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE => 'reviewer.article.decision.decline',
					SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS => 'reviewer.article.decision.seeComments'
				)
			);
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
		$templateMgr->assign('currentUrl', Request::getPageUrl() . '/sectionEditor');
		
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
			
			$templateMgr->assign('user', $user);
			$templateMgr->assign('profileLocalesEnabled', $site->getProfileLocalesEnabled());
			$templateMgr->assign('localeNames', Locale::getAllLocales());
			$templateMgr->assign('helpTopicId', 'journal.roles.index');
			$templateMgr->display('sectionEditor/userProfile.tpl');
		}
	}
	
	function viewMetadata($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'summary');
		
		SectionEditorAction::viewMetadata($submission, ROLE_ID_SECTION_EDITOR);
	}
	
	function saveMetadata() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'summary');
		
		if (SectionEditorAction::saveMetadata($submission)) {
			Request::redirect(Request::getRequestedPage() . "/submission/$articleId");
		}
	}
	
	//
	// Editor Review
	//
	
	function editorReview() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);

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
		
		Request::redirect(sprintf('%s/%s/%d', Request::getRequestedPage(), $redirectTarget, $articleId));
	}
	
	function notifyAuthor($args = array()) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		if (Request::getUserVar('send')) {
			SectionEditorAction::notifyAuthor($submission, true);
			Request::redirect(sprintf('%s/submissionReview/%d', Request::getRequestedPage(), $articleId));
			
		} else {
			parent::setupTemplate(true, $articleId, 'editing');
			SectionEditorAction::notifyAuthor($submission);
		}
	}
	
	//
	// Copyedit
	//
	
	function selectCopyeditor($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		
		if (isset($args[1]) && $args[1] != null && $roleDao->roleExists($journal->getJournalId(), $args[1], ROLE_ID_COPYEDITOR)) {
			SectionEditorAction::selectCopyeditor($submission, $args[1]);
			Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
			
		} else {
			parent::setupTemplate(true, $articleId, 'editing');

			$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');

			$searchType = null;
			$searchMatch = null;
			$search = Request::getUserVar('search');
			$search_initial = Request::getUserVar('search_initial');
			if (isset($search)) {
				$searchType = Request::getUserVar('searchField');
				$searchMatch = Request::getUserVar('searchMatch');
			}
			else if (isset($search_initial)) {
				$searchType = USER_FIELD_INITIAL;
				$search = $search_initial;
			}

			$copyeditors = $sectionEditorSubmissionDao->getCopyeditorsNotAssignedToArticle($journal->getJournalId(), $articleId, $searchType, $search, $searchMatch);
			$copyeditorStatistics = $sectionEditorSubmissionDao->getCopyeditorStatistics($journal->getJournalId());

			$templateMgr = &TemplateManager::getManager();
		
			$templateMgr->assign('users', $copyeditors);
			$templateMgr->assign('statistics', $copyeditorStatistics);
			$templateMgr->assign('pageSubTitle', 'editor.article.selectCopyeditor');
			$templateMgr->assign('pageTitle', 'user.role.copyeditors');
			$templateMgr->assign('actionHandler', 'selectCopyeditor');
			$templateMgr->assign('fieldOptions', Array(
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username'
			));
			$templateMgr->assign('articleId', $args[0]);

			$templateMgr->assign('helpTopicId', 'journal.roles.copyeditor');
			$templateMgr->display('sectionEditor/selectUser.tpl');
		}
	}
	
	function notifyCopyeditor($args = array()) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		if (Request::getUserVar('send')) {
			SectionEditorAction::notifyCopyeditor($submission, true);
			Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
			
		} else {
			parent::setupTemplate(true, $articleId, 'editing');
			SectionEditorAction::notifyCopyeditor($submission);
		}
	}
	
	/* Initiates the copyediting process when the editor does the copyediting */
	function initiateCopyedit() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		SectionEditorAction::initiateCopyedit($submission);
		Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
	}

	function thankCopyeditor($args = array()) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		if (Request::getUserVar('send')) {
			SectionEditorAction::thankCopyeditor($submission, true);
			Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));

		} else {
			parent::setupTemplate(true, $articleId, 'editing');
			SectionEditorAction::thankCopyeditor($submission);
		}
	}
	
	function notifyAuthorCopyedit($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		if (Request::getUserVar('send')) {
			SectionEditorAction::notifyAuthorCopyedit($submission, true);
			Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
			
		} else {
			parent::setupTemplate(true, $articleId, 'editing');
			SectionEditorAction::notifyAuthorCopyedit($submission);
		}
	}
	
	function thankAuthorCopyedit($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);

		if (Request::getUserVar('send')) {
			SectionEditorAction::thankAuthorCopyedit($submission, true);
			Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
			
		} else {
			parent::setupTemplate(true, $articleId, 'editing');
			SectionEditorAction::thankAuthorCopyedit($submission);
		}
	}
	
	function notifyFinalCopyedit($args = array()) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		if (Request::getUserVar('send')) {
			SectionEditorAction::notifyFinalCopyedit($submission, true);
			Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
			
		} else {
			parent::setupTemplate(true, $articleId, 'editing');
			SectionEditorAction::notifyFinalCopyedit($submission);
		}
	}

	function completeCopyedit($args) {
		parent::validate();
		$articleId = Request::getUserVar('articleId');
 
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
 
		SectionEditorAction::completeCopyedit($submission);
		Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
	}
 
	function completeFinalCopyedit($args) {
		parent::validate();
		$articleId = Request::getUserVar('articleId');
 
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
 
		SectionEditorAction::completeFinalCopyedit($submission);
		Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
	}

	function thankFinalCopyedit($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		if (Request::getUserVar('send')) {
			SectionEditorAction::thankFinalCopyedit($articleId, true);
			Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
			
		} else {
			parent::setupTemplate(true, $articleId, 'editing');
			SectionEditorAction::thankFinalCopyedit($articleId);
		}
	}

	function uploadReviewVersion() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		SectionEditorAction::uploadReviewVersion($submission);
		
		Request::redirect(sprintf('%s/submissionReview/%d', Request::getRequestedPage(), $articleId));
	}
	
	function uploadCopyeditVersion() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$copyeditStage = Request::getUserVar('copyeditStage');
		SectionEditorAction::uploadCopyeditVersion($submission, $copyeditStage);
		
		Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));	
	}
	
	/**
	 * Add a supplementary file.
	 * @param $args array ($articleId)
	 */
	function addSuppFile($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
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
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
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
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$suppFileId = Request::getUserVar('fileId');
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$suppFile = $suppFileDao->getSuppFile($suppFileId, $articleId);

		if (isset($suppFile) && $suppFile != null) {
			$suppFile->setShowReviewers(Request::getUserVar('show')==1?1:0);
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
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;
		
		import('submission.form.SuppFileForm');
		
		$submitForm = &new SuppFileForm($submission, $suppFileId);
		$submitForm->readInputData();
		
		if ($submitForm->validate()) {
			$submitForm->execute();
			Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
		
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

		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		SectionEditorAction::deleteArticleFile($submission, $fileId, $revisionId);
		
		Request::redirect(sprintf('%s/submissionReview/%d', Request::getRequestedPage(), $articleId));
	}
	
	/**
	 * Delete a supplementary file.
	 * @param $args array ($articleId, $suppFileId)
	 */
	function deleteSuppFile($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$suppFileId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		SectionEditorAction::deleteSuppFile($submission, $suppFileId);
		
		Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
	}
	
	function archiveSubmission($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);

		SectionEditorAction::archiveSubmission($submission);
		
		Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $articleId));
	}
	
	function restoreToQueue($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);

		SectionEditorAction::restoreToQueue($submission);
		
		Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
	}
	
	/**
	 * Set section ID.
	 * @param $args array ($articleId)
	 */
	function updateSection($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);		
		SectionEditorAction::updateSection($submission, Request::getUserVar('section'));
		Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $articleId));
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
			TrackSubmissionHandler::uploadLayoutVersion();
			
		} else if ($layoutFileType == 'galley') {
			TrackSubmissionHandler::uploadGalley('layoutFile');
		
		} else if ($layoutFileType == 'supp') {
			TrackSubmissionHandler::uploadSuppFile('layoutFile');
		
		} else {
			Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), Request::getUserVar('articleId')));
		}
	}
	
	/**
	 * Upload the layout version of the submission file
	 */
	function uploadLayoutVersion() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		SectionEditorAction::uploadLayoutVersion($submission);
		
		Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
	}
	
	/**
	 * Assign/reassign a layout editor to the submission.
	 * @param $args array ($articleId, [$userId])
	 */
	function assignLayoutEditor($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$editorId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		
		if ($editorId && $roleDao->roleExists($journal->getJournalId(), $editorId, ROLE_ID_LAYOUT_EDITOR)) {
			SectionEditorAction::assignLayoutEditor($submission, $editorId);
			Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
			
		} else {
			$searchType = null;
			$searchMatch = null;
			$search = Request::getUserVar('search');
			$search_initial = Request::getUserVar('search_initial');
			if (isset($search)) {
				$searchType = Request::getUserVar('searchField');
				$searchMatch = Request::getUserVar('searchMatch');
			}
			else if (isset($search_initial)) {
				$searchType = USER_FIELD_INITIAL;
				$search = $search_initial;
			}

			$layoutEditors = $roleDao->getUsersByRoleId(ROLE_ID_LAYOUT_EDITOR, $journal->getJournalId(), $searchType, $search, $searchMatch);

			$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
			$layoutEditorStatistics = $sectionEditorSubmissionDao->getLayoutEditorStatistics($journal->getJournalId());

			parent::setupTemplate(true, $articleId, 'editing');

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('pageTitle', 'user.role.layoutEditors');
			$templateMgr->assign('pageSubTitle', 'editor.article.selectLayoutEditor');
			$templateMgr->assign('actionHandler', 'assignLayoutEditor');
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('users', $layoutEditors);
			$templateMgr->assign('fieldOptions', Array(
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username'
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
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		if (Request::getUserVar('send')) {
			SectionEditorAction::notifyLayoutEditor($submission, true);
			Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
			
		} else {
			parent::setupTemplate(true, $articleId, 'editing');
			SectionEditorAction::notifyLayoutEditor($submission);
		}
	}
	
	/**
	 * Thank the layout editor.
	 */
	function thankLayoutEditor($args) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		if (Request::getUserVar('send')) {
			SectionEditorAction::thankLayoutEditor($submission, true);
			Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
			
		} else {
			parent::setupTemplate(true, $articleId, 'editing');
			SectionEditorAction::thankLayoutEditor($submission);
		}
	}
	
	/**
	 * Create a new galley with the uploaded file.
	 */
	function uploadGalley($fileName = null) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		import('submission.form.ArticleGalleyForm');
		
		$galleyForm = &new ArticleGalleyForm($articleId);
		$galleyId = $galleyForm->execute($fileName);
		
		Request::redirect(sprintf('%s/editGalley/%d/%d', Request::getRequestedPage(), $articleId, $galleyId));
	}
	
	/**
	 * Edit a galley.
	 * @param $args array ($articleId, $galleyId)
	 */
	function editGalley($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
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
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		import('submission.form.ArticleGalleyForm');
		
		$submitForm = &new ArticleGalleyForm($articleId, $galleyId);
		$submitForm->readInputData();
		
		if (Request::getUserVar('uploadImage')) {
			// Attach galley image
			$submitForm->uploadImage();
			
			parent::setupTemplate(true, $articleId, 'editing');
			$submitForm->display();
		
		} else if(($deleteImage = Request::getUserVar('deleteImage')) && count($deleteImage) == 1) {
			// Delete galley image
			list($imageId) = array_keys($deleteImage);
			$submitForm->deleteImage($imageId);
			
			parent::setupTemplate(true, $articleId, 'editing');
			$submitForm->display();
			
		} else if ($submitForm->validate()) {
			$submitForm->execute();
			Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
		
		} else {
			parent::setupTemplate(true, $articleId, 'editing');
			$submitForm->display();
		}
	}
	
	/**
	 * Change the sequence order of a galley.
	 */
	function orderGalley() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		SectionEditorAction::orderGalley($submission, Request::getUserVar('galleyId'), Request::getUserVar('d'));

		Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
	}
	
	/**
	 * Delete a galley file.
	 * @param $args array ($articleId, $galleyId)
	 */
	function deleteGalley($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		SectionEditorAction::deleteGalley($submission, $galleyId);
		
		Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
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
	 * Upload a new supplementary file.
	 */
	function uploadSuppFile($fileName = null) {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		import('submission.form.SuppFileForm');
		
		$suppFileForm = &new SuppFileForm($submission);
		$suppFileForm->setData('title', Locale::translate('common.untitled'));
		$suppFileId = $suppFileForm->execute($fileName);
		
		Request::redirect(sprintf('%s/editSuppFile/%d/%d', Request::getRequestedPage(), $articleId, $suppFileId));
	}
	
	/**
	 * Change the sequence order of a supplementary file.
	 */
	function orderSuppFile() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		SectionEditorAction::orderSuppFile($submission, Request::getUserVar('suppFileId'), Request::getUserVar('d'));

		Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
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
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'history');
		
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
			import('article.log.ArticleLog');
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
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'history');
		
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
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId, true);
		
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
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'history');
		
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
			import('article.log.ArticleLog');
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
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'history');
		
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
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId, true);
		
		$logDao = &DAORegistry::getDAO('ArticleEmailLogDAO');
		
		if ($logId) {
			$logDao->deleteLogEntry($logId, $articleId);
			
		} else {
			$logDao->deleteArticleLogEntries($articleId);
		}
		
		Request::redirect(sprintf('%s/submissionEmailLog/%d', Request::getRequestedPage(), $articleId));
	}
	
	// Submission Notes Functions

	/**
	 * Creates a submission note.
	 * Redirects to submission notes list
	 */
	function addSubmissionNote() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		
		SectionEditorAction::addSubmissionNote($articleId);
		Request::redirect(sprintf('%s/submissionNotes/%d', Request::getRequestedPage(), $articleId));
	}

	/**
	 * Removes a submission note.
	 * Redirects to submission notes list
	 */
	function removeSubmissionNote() {
		$articleId = Request::getUserVar('articleId');		
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);

		SectionEditorAction::removeSubmissionNote($articleId);
		Request::redirect(sprintf('%s/submissionNotes/%d', Request::getRequestedPage(), $articleId));
	}
	
	/**
	 * Updates a submission note.
	 * Redirects to submission notes list
	 */
	function updateSubmissionNote() {
		$articleId = Request::getUserVar('articleId');		
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);

		SectionEditorAction::updateSubmissionNote($articleId);
		Request::redirect(sprintf('%s/submissionNotes/%d', Request::getRequestedPage(), $articleId));
	}

	/**
	 * Clear all submission notes.
	 * Redirects to submission notes list
	 */
	function clearAllSubmissionNotes() {
		$articleId = Request::getUserVar('articleId');		
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);

		SectionEditorAction::clearAllSubmissionNotes($articleId);
		Request::redirect(sprintf('%s/submissionNotes/%d', Request::getRequestedPage(), $articleId));
	}
	
	/**
	 * View submission notes.
	 */
	function submissionNotes($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$noteViewType = isset($args[1]) ? $args[1] : '';
		$noteId = isset($args[2]) ? (int) $args[2] : 0;

		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'history');

		$articleNoteDao = &DAORegistry::getDAO('ArticleNoteDAO');
		$submissionNotes = $articleNoteDao->getArticleNotes($articleId);

		// submission note edit
		if ($noteViewType == 'edit') {
			$articleNote = $articleNoteDao->getArticleNoteById($noteId);
		}
		
		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('submissionNotes', $submissionNotes);
		$templateMgr->assign('noteViewType', $noteViewType);
		if (isset($articleNote)) {
			$templateMgr->assign('articleNote', $articleNote);		
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

		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		if (!SectionEditorAction::downloadFile($articleId, $fileId, $revision)) {
			Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $articleId));
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
		if (!SectionEditorAction::viewFile($articleId, $fileId, $revision)) {
			Request::redirect(sprintf('%s/submission/%d', Request::getRequestedPage(), $articleId));
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

		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);

		$roleDao = &DAORegistry::getDAO('RoleDAO');

		if ($userId && $articleId  && $roleDao->roleExists($journal->getJournalId(), $userId, ROLE_ID_PROOFREADER)) {
			import('submission.proofreader.ProofreaderAction');
			ProofreaderAction::selectProofreader($userId, $submission);
			Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
		} else {
			parent::setupTemplate(true, $articleId, 'editing');

			$searchType = null;
			$searchMatch = null;
			$search = Request::getUserVar('search');
			$search_initial = Request::getUserVar('search_initial');
			if (isset($search)) {
				$searchType = Request::getUserVar('searchField');
				$searchMatch = Request::getUserVar('searchMatch');
			}
			else if (isset($search_initial)) {
				$searchType = USER_FIELD_INITIAL;
				$search = $search_initial;
			}

			$proofreaders = $roleDao->getUsersByRoleId(ROLE_ID_PROOFREADER, $journal->getJournalId(), $searchType, $search, $searchMatch);
				
			$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
			$proofreaderStatistics = $sectionEditorSubmissionDao->getProofreaderStatistics($journal->getJournalId());
		
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('users', $proofreaders);
			$templateMgr->assign('statistics', $proofreaderStatistics);
			$templateMgr->assign('fieldOptions', Array(
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username'
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
	 * Queue submission for scheduling
	 * @param $args array ($articleId)
	 */
	function queueForScheduling($args) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);

		import('submission.proofreader.ProofreaderAction');
		ProofreaderAction::queueForScheduling($submission);

		Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
	}

	/**
	 * Notify author for proofreading
	 */
	function notifyAuthorProofreader($args) {
		$articleId = Request::getUserVar('articleId');
		$send = Request::getUserVar('send')?1:0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'editing');

		import('submission.proofreader.ProofreaderAction');
		if ($send) {
			ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_AUTHOR_REQUEST');
			Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
		} else {
			ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_AUTHOR_REQUEST', '/sectionEditor/notifyAuthorProofreader');
		}
	}

	/**
	 * Thank author for proofreading
	 */
	function thankAuthorProofreader($args) {
		$articleId = Request::getUserVar('articleId');
		$send = Request::getUserVar('send')?1:0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'editing');

		import('submission.proofreader.ProofreaderAction');
		if ($send) {
			ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_AUTHOR_ACK');
			Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
		} else {
			ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_AUTHOR_ACK', '/sectionEditor/thankAuthorProofreader');
		}
	}

	/**
	 * Editor initiates proofreading
	 */
	function editorInitiateProofreader() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);

		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = &$proofAssignmentDao->getProofAssignmentByArticleId($articleId);
		$proofAssignment->setDateProofreaderNotified(Core::getCurrentDate());
		$proofAssignmentDao->updateProofAssignment($proofAssignment);

		Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
	}

	/**
	 * Editor completes proofreading
	 */
	function editorCompleteProofreader() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);

		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = &$proofAssignmentDao->getProofAssignmentByArticleId($articleId);
		$proofAssignment->setDateProofreaderCompleted(Core::getCurrentDate());
		$proofAssignmentDao->updateProofAssignment($proofAssignment);

		Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
	}

	/**
	 * Notify proofreader for proofreading
	 */
	function notifyProofreader($args) {
		$articleId = Request::getUserVar('articleId');
		$send = Request::getUserVar('send');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'editing');

		import('submission.proofreader.ProofreaderAction');
		if ($send) {
			ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_REQUEST');
			Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
		} else {
			ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_REQUEST', '/sectionEditor/notifyProofreader');
		}
	}

	/**
	 * Thank proofreader for proofreading
	 */
	function thankProofreader($args) {
		$articleId = Request::getUserVar('articleId');
		$send = Request::getUserVar('send')?1:0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'editing');

		import('submission.proofreader.ProofreaderAction');
		if ($send) {
			ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_ACK');
			Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
		} else {
			ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_ACK', '/sectionEditor/thankProofreader');
		}
	}

	/**
	 * Editor initiates layout editor proofreading
	 */
	function editorInitiateLayoutEditor() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);

		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = &$proofAssignmentDao->getProofAssignmentByArticleId($articleId);
		$proofAssignment->setDateLayoutEditorNotified(Core::getCurrentDate());
		$proofAssignmentDao->updateProofAssignment($proofAssignment);

		Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
	}

	/**
	 * Editor completes layout editor proofreading
	 */
	function editorCompleteLayoutEditor() {
		$articleId = Request::getUserVar('articleId');
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);

		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = &$proofAssignmentDao->getProofAssignmentByArticleId($articleId);
		$proofAssignment->setDateLayoutEditorCompleted(Core::getCurrentDate());
		$proofAssignmentDao->updateProofAssignment($proofAssignment);

		Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
	}

	/**
	 * Notify layout editor for proofreading
	 */
	function notifyLayoutEditorProofreader($args) {
		$articleId = Request::getUserVar('articleId');
		$send = Request::getUserVar('send')?1:0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'editing');

		import('submission.proofreader.ProofreaderAction');
		if ($send) {
			ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_LAYOUT_REQUEST');
			Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
		} else {
			ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_LAYOUT_REQUEST', '/sectionEditor/notifyLayoutEditorProofreader');
		}
	}

	/**
	 * Thank layout editor for proofreading
	 */
	function thankLayoutEditorProofreader($args) {
		$articleId = Request::getUserVar('articleId');
		$send = Request::getUserVar('send')?1:0;
		list($journal, $submission) = TrackSubmissionHandler::validate($articleId);
		parent::setupTemplate(true, $articleId, 'editing');

		import('submission.proofreader.ProofreaderAction');
		if ($send) {
			ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_LAYOUT_ACK');
			Request::redirect(sprintf('%s/submissionEditing/%d', Request::getRequestedPage(), $articleId));
		} else {
			ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_LAYOUT_ACK', '/sectionEditor/thankLayoutEditorProofreader');
		}
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
	function &validate($articleId, $mustBeEditor = false) {
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
			$editor = $sectionEditorSubmission->getEditor();
			if (($mustBeEditor || $editor == null || $editor->getEditorId() != $user->getUserId()) && !Validation::isEditor()) {
				$isValid = false;
			}
		}
		
		if (!$isValid) {
			Request::redirect(Request::getRequestedPage());
		}

		return array($journal, $sectionEditorSubmission);
	}
}
?>
