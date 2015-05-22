<?php

/**
 * @file pages/sectionEditor/SubmissionEditHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionEditHandler
 * @ingroup pages_sectionEditor
 *
 * @brief Handle requests for submission tracking.
 */


define('SECTION_EDITOR_ACCESS_EDIT', 0x00001);
define('SECTION_EDITOR_ACCESS_REVIEW', 0x00002);

import('pages.sectionEditor.SectionEditorHandler');

class SubmissionEditHandler extends SectionEditorHandler {
	/**
	 * Constructor
	 */
	function SubmissionEditHandler() {
		parent::SectionEditorHandler();
	}

	/**
	 * Get the operation name for the page the user is coming from
	 * (persisted via URL parameters for some operations)
	 * @param $default The default value to use if none specified via URL
	 * @return string
	 */
	function _getFrom($default = 'submissionEditing') {
		$from = Request::getUserVar('from');
		if (!in_array($from, array('submission', 'submissionEditing'))) return $default;
		return $from;
	}

	/**
	 * View the submission page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submission($args, &$request) {
		$articleId = (int) array_shift($args);
		$this->validate($articleId);
		$journal =& $request->getJournal();
		$submission =& $this->submission;

		// FIXME? For comments.readerComments under Status and
		// author.submit.selectPrincipalContact under Metadata
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_READER, LOCALE_COMPONENT_OJS_AUTHOR);

		$this->setupTemplate(true, $articleId);

		$user =& $request->getUser();

		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$journalSettings = $journalSettingsDao->getJournalSettings($journal->getId());

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$isEditor = $roleDao->userHasRole($journal->getId(), $user->getId(), ROLE_ID_EDITOR);

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$section =& $sectionDao->getSection($submission->getSectionId());

		$enableComments = $journal->getSetting('enableComments');

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('section', $section);
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign_by_ref('reviewFile', $submission->getReviewFile());
		$templateMgr->assign_by_ref('journalSettings', $journalSettings);
		$templateMgr->assign('userId', $user->getId());
		$templateMgr->assign('isEditor', $isEditor);
		$templateMgr->assign('enableComments', $enableComments);

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$templateMgr->assign_by_ref('sections', $sectionDao->getSectionTitles($journal->getId()));
		if ($enableComments) {
			import('classes.article.Article');
			$templateMgr->assign('commentsStatus', $submission->getCommentsStatus());
			$templateMgr->assign_by_ref('commentsStatusOptions', Article::getCommentsStatusOptions());
		}

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($submission->getId());
		if ($publishedArticle) {
			$issueDao =& DAORegistry::getDAO('IssueDAO');
			$issue =& $issueDao->getIssueById($publishedArticle->getIssueId());
			$templateMgr->assign_by_ref('issue', $issue);
			$templateMgr->assign_by_ref('publishedArticle', $publishedArticle);
		}

		if ($isEditor) {
			$templateMgr->assign('helpTopicId', 'editorial.editorsRole.submissionSummary');
		}

		// Set up required Payment Related Information
		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		if ( $paymentManager->submissionEnabled() || $paymentManager->fastTrackEnabled() || $paymentManager->publicationEnabled()) {
			$templateMgr->assign('authorFees', true);
			$completedPaymentDao =& DAORegistry::getDAO('OJSCompletedPaymentDAO');

			if ( $paymentManager->submissionEnabled() ) {
				$templateMgr->assign_by_ref('submissionPayment', $completedPaymentDao->getSubmissionCompletedPayment ( $journal->getId(), $articleId ));
			}

			if ( $paymentManager->fastTrackEnabled()  ) {
				$templateMgr->assign_by_ref('fastTrackPayment', $completedPaymentDao->getFastTrackCompletedPayment ( $journal->getId(), $articleId ));
			}

			if ( $paymentManager->publicationEnabled()  ) {
				$templateMgr->assign_by_ref('publicationPayment', $completedPaymentDao->getPublicationCompletedPayment ( $journal->getId(), $articleId ));
			}
		}

		$templateMgr->assign('canEditMetadata', true);

		$templateMgr->display('sectionEditor/submission.tpl');
	}

	/**
	 * View the submission regrets page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submissionRegrets($args, &$request) {
		$articleId = (int) array_shift($args);
		$this->validate($articleId);
		$journal =& $request->getJournal();
		$submission =& $this->submission;
		$this->setupTemplate(true, $articleId, 'review');

		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$cancelsAndRegrets = $reviewAssignmentDao->getCancelsAndRegrets($articleId);
		$reviewFilesByRound = $reviewAssignmentDao->getReviewFilesByRound($articleId);

		$reviewAssignments =& $submission->getReviewAssignments();
		$editorDecisions = $submission->getDecisions();
		$numRounds = $submission->getCurrentRound();

		$reviewFormResponseDao =& DAORegistry::getDAO('ReviewFormResponseDAO');
		$reviewFormResponses = array();
		if (isset($reviewAssignments[$numRounds-1])) {
			foreach ($reviewAssignments[$numRounds-1] as $reviewAssignment) {
				$reviewFormResponses[$reviewAssignment->getId()] = $reviewFormResponseDao->reviewFormResponseExists($reviewAssignment->getId());
			}
		}

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('reviewAssignments', $reviewAssignments);
		$templateMgr->assign('reviewFormResponses', $reviewFormResponses);
		$templateMgr->assign_by_ref('cancelsAndRegrets', $cancelsAndRegrets);
		$templateMgr->assign_by_ref('reviewFilesByRound', $reviewFilesByRound);
		$templateMgr->assign_by_ref('editorDecisions', $editorDecisions);
		$templateMgr->assign('numRounds', $numRounds);
		$templateMgr->assign('rateReviewerOnQuality', $journal->getSetting('rateReviewerOnQuality'));

		$templateMgr->assign_by_ref('editorDecisionOptions', SectionEditorSubmission::getEditorDecisionOptions());

		import('classes.submission.reviewAssignment.ReviewAssignment');
		$templateMgr->assign_by_ref('reviewerRatingOptions', ReviewAssignment::getReviewerRatingOptions());
		$templateMgr->assign_by_ref('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());

		$templateMgr->display('sectionEditor/submissionRegrets.tpl');
	}

	/**
	 * View the submission review page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submissionReview($args, &$request) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$journal =& Request::getJournal();
		$submission =& $this->submission;
		$this->setupTemplate(true, $articleId);

		AppLocale::requireComponents(LOCALE_COMPONENT_OJS_MANAGER);

		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');

		// Setting the round.
		$round = isset($args[1]) ? $args[1] : $submission->getCurrentRound();

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$sections =& $sectionDao->getJournalSections($journal->getId());

		$showPeerReviewOptions = $round == $submission->getCurrentRound() && $submission->getReviewFile() != null ? true : false;

		$editorDecisions = $submission->getDecisions($round);
		$lastDecision = count($editorDecisions) >= 1 ? $editorDecisions[count($editorDecisions) - 1]['decision'] : null;

		$editAssignments =& $submission->getEditAssignments();
		$allowRecommendation = $submission->getCurrentRound() == $round && $submission->getReviewFileId() != null && !empty($editAssignments);
		$allowResubmit = $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT && $sectionEditorSubmissionDao->getMaxReviewRound($articleId) == $round ? true : false;
		$allowCopyedit = $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT && $submission->getFileBySignoffType('SIGNOFF_COPYEDITING_INITIAL', true) == null ? true : false;

		// Prepare an array to store the 'Notify Reviewer' email logs
		$notifyReviewerLogs = array();
		foreach ($submission->getReviewAssignments($round) as $reviewAssignment) {
			$notifyReviewerLogs[$reviewAssignment->getId()] = array();
		}

		// Parse the list of email logs and populate the array.
		$emailLogDao =& DAORegistry::getDAO('ArticleEmailLogDAO');
		$emailLogEntries =& $emailLogDao->getByAssoc(ASSOC_TYPE_ARTICLE, $articleId);
		foreach ($emailLogEntries->toArray() as $emailLog) {
			if ($emailLog->getEventType() == ARTICLE_EMAIL_REVIEW_NOTIFY_REVIEWER) {
				if (isset($notifyReviewerLogs[$emailLog->getAssocId()]) && is_array($notifyReviewerLogs[$emailLog->getAssocId()])) {
					array_push($notifyReviewerLogs[$emailLog->getAssocId()], $emailLog);
				}
			}
		}

		$reviewFormResponseDao =& DAORegistry::getDAO('ReviewFormResponseDAO');
		$reviewFormResponses = array();

		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewFormTitles = array();

		foreach ($submission->getReviewAssignments($round) as $reviewAssignment) {
			$reviewForm =& $reviewFormDao->getReviewForm($reviewAssignment->getReviewFormId());
			if ($reviewForm) {
				$reviewFormTitles[$reviewForm->getId()] = $reviewForm->getLocalizedTitle();
			}
			unset($reviewForm);
			$reviewFormResponses[$reviewAssignment->getId()] = $reviewFormResponseDao->reviewFormResponseExists($reviewAssignment->getId());
		}

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('reviewIndexes', $reviewAssignmentDao->getReviewIndexesForRound($articleId, $round));
		$templateMgr->assign('round', $round);
		$templateMgr->assign_by_ref('reviewAssignments', $submission->getReviewAssignments($round));
		$templateMgr->assign('reviewFormResponses', $reviewFormResponses);
		$templateMgr->assign('reviewFormTitles', $reviewFormTitles);
		$templateMgr->assign_by_ref('notifyReviewerLogs', $notifyReviewerLogs);
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign_by_ref('reviewFile', $submission->getReviewFile());
		$templateMgr->assign_by_ref('copyeditFile', $submission->getFileBySignoffType('SIGNOFF_COPYEDITING_INITIAL'));
		$templateMgr->assign_by_ref('revisedFile', $submission->getRevisedFile());
		$templateMgr->assign_by_ref('editorFile', $submission->getEditorFile());
		$templateMgr->assign('rateReviewerOnQuality', $journal->getSetting('rateReviewerOnQuality'));
		$templateMgr->assign('showPeerReviewOptions', $showPeerReviewOptions);
		$templateMgr->assign_by_ref('sections', $sections->toArray());
		$templateMgr->assign('editorDecisionOptions',SectionEditorSubmission::getEditorDecisionOptions());
		$templateMgr->assign_by_ref('lastDecision', $lastDecision);

		import('classes.submission.reviewAssignment.ReviewAssignment');
		$templateMgr->assign_by_ref('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());
		$templateMgr->assign_by_ref('reviewerRatingOptions', ReviewAssignment::getReviewerRatingOptions());

		$templateMgr->assign('allowRecommendation', $allowRecommendation);
		$templateMgr->assign('allowResubmit', $allowResubmit);
		$templateMgr->assign('allowCopyedit', $allowCopyedit);

		$templateMgr->assign('helpTopicId', 'editorial.sectionEditorsRole.review');
		$templateMgr->display('sectionEditor/submissionReview.tpl');
	}

	/**
	 * View the submission editing page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submissionEditing($args, $request) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$journal =& $request->getJournal();
		$submission =& $this->submission;
		$this->setupTemplate(true, $articleId);

		$useCopyeditors = $journal->getSetting('useCopyeditors');
		$useLayoutEditors = $journal->getSetting('useLayoutEditors');
		$useProofreaders = $journal->getSetting('useProofreaders');

		// check if submission is accepted
		$round = isset($args[1]) ? $args[1] : $submission->getCurrentRound();
		$editorDecisions = $submission->getDecisions($round);
		$lastDecision = count($editorDecisions) >= 1 ? $editorDecisions[count($editorDecisions) - 1]['decision'] : null;
		$submissionAccepted = ($lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT) ? true : false;

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('copyeditFile', $submission->getFileBySignoffType('SIGNOFF_COPYEDITING_INITIAL'));
		$templateMgr->assign_by_ref('initialCopyeditFile', $submission->getFileBySignoffType('SIGNOFF_COPYEDITING_INITIAL'));
		$templateMgr->assign_by_ref('editorAuthorCopyeditFile', $submission->getFileBySignoffType('SIGNOFF_COPYEDITING_AUTHOR'));
		$templateMgr->assign_by_ref('finalCopyeditFile', $submission->getFileBySignoffType('SIGNOFF_COPYEDITING_FINAL'));
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign_by_ref('copyeditor', $submission->getUserBySignoffType('SIGNOFF_COPYEDITING_INITIAL'));

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$user =& Request::getUser();
		$templateMgr->assign('isEditor', $roleDao->userHasRole($journal->getId(), $user->getId(), ROLE_ID_EDITOR));

		import('classes.issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($submission->getId());
		$templateMgr->assign_by_ref('publishedArticle', $publishedArticle);

		$templateMgr->assign('useCopyeditors', $useCopyeditors);
		$templateMgr->assign('useLayoutEditors', $useLayoutEditors);
		$templateMgr->assign('useProofreaders', $useProofreaders);
		$templateMgr->assign('submissionAccepted', $submissionAccepted);
		$templateMgr->assign('templates', $journal->getSetting('templates'));

		// Set up required Payment Related Information
		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$completedPaymentDao =& DAORegistry::getDAO('OJSCompletedPaymentDAO');

		$publicationFeeEnabled = $paymentManager->publicationEnabled();
		$templateMgr->assign('publicationFeeEnabled',  $publicationFeeEnabled);
		if ( $publicationFeeEnabled ) {
			$templateMgr->assign_by_ref('publicationPayment', $completedPaymentDao->getPublicationCompletedPayment ( $journal->getId(), $articleId ));
		}

		$templateMgr->assign('helpTopicId', 'editorial.sectionEditorsRole.editing');
		$templateMgr->display('sectionEditor/submissionEditing.tpl');
	}

	/**
	 * View submission history
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submissionHistory($args, &$request) {
		$articleId = (int) array_shift($args);
		$this->validate($articleId);
		$this->setupTemplate(true, $articleId);

		$templateMgr =& TemplateManager::getManager();
		$submission =& $this->submission;
		$templateMgr->assign_by_ref('submission', $submission);

		// Notes
		$noteDao =& DAORegistry::getDAO('NoteDAO');
		$submissionNotes =& $noteDao->getByAssoc(ASSOC_TYPE_ARTICLE, $articleId);
		$templateMgr->assign_by_ref('submissionNotes', $submissionNotes);

		// Event log
		$eventLogDao =& DAORegistry::getDAO('ArticleEventLogDAO');
		$rangeInfo =& $this->getRangeInfo('eventLogEntries');
		$eventLogEntries =& $eventLogDao->getByAssoc(ASSOC_TYPE_ARTICLE, $articleId, $rangeInfo);
		$templateMgr->assign_by_ref('eventLogEntries', $eventLogEntries);
		unset($rangeInfo);

		// Email log
		$emailLogDao =& DAORegistry::getDAO('ArticleEmailLogDAO');
		$rangeInfo =& $this->getRangeInfo('emailLogEntries');
		$emailLogEntries =& $emailLogDao->getByAssoc(ASSOC_TYPE_ARTICLE, $articleId, $rangeInfo);
		$templateMgr->assign_by_ref('emailLogEntries', $emailLogEntries);
		unset($rangeInfo);

		$templateMgr->assign('isEditor', Validation::isEditor());
		$templateMgr->display('sectionEditor/submissionHistory.tpl');
	}

	/**
	 * Display the citation editing assistant.
	 * @param $args array
	 * @param $request Request
	 */
	function submissionCitations($args, $request) {
		// Authorize the request.
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($articleId);

		// Prepare the view.
		$this->setupTemplate(true, $articleId);

		// Insert the citation editing assistant into the view.
		SectionEditorAction::editCitations($request, $this->submission);

		// Render the view.
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->display('sectionEditor/submissionCitations.tpl');
	}

	/**
	 * Change an article's section.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function changeSection($args, &$request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId);
		$submission =& $this->submission;

		$sectionId = $request->getUserVar('sectionId');

		SectionEditorAction::changeSection($submission, $sectionId);

		$request->redirect(null, null, 'submission', $articleId);
	}

	/**
	 * Record an editor decision
	 * @param $args array
	 * @param $request object
	 */
	function recordDecision($args, $request) {
		$articleId = $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$submission =& $this->submission;

		$decision = $request->getUserVar('decision');

		switch ($decision) {
			case SUBMISSION_EDITOR_DECISION_ACCEPT:
			case SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS:
			case SUBMISSION_EDITOR_DECISION_RESUBMIT:
			case SUBMISSION_EDITOR_DECISION_DECLINE:
				SectionEditorAction::recordDecision($submission, $decision, $request);
				break;
		}

		$request->redirect(null, null, 'submissionReview', $articleId);
	}

	//
	// Peer Review
	//

	/**
	 * Select a reviewer.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function selectReviewer($args, &$request) {
		$articleId = (int) array_shift($args);
		$reviewerId = (int) array_shift($args);

		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$journal =& $request->getJournal();
		$submission =& $this->submission;

		$sort = $request->getUserVar('sort');
		$sort = isset($sort) ? $sort : 'reviewerName';
		$sortDirection = $request->getUserVar('sortDirection');
		$sortDirection = (isset($sortDirection) && ($sortDirection == SORT_DIRECTION_ASC || $sortDirection == SORT_DIRECTION_DESC)) ? $sortDirection : SORT_DIRECTION_ASC;

		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');

		if ($reviewerId) {
			// Assign reviewer to article
			SectionEditorAction::addReviewer($submission, $reviewerId, null, $request);
			$request->redirect(null, null, 'submissionReview', $articleId);

			// FIXME: Prompt for due date.
		} else {
			$this->setupTemplate(true, $articleId, 'review');

			$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');

			$searchType = null;
			$searchMatch = null;
			$search = $searchQuery = $request->getUserVar('search');
			$searchInitial = $request->getUserVar('searchInitial');
			if (!empty($search)) {
				$searchType = $request->getUserVar('searchField');
				$searchMatch = $request->getUserVar('searchMatch');

			} elseif (!empty($searchInitial)) {
				$searchInitial = String::strtoupper($searchInitial);
				$searchType = USER_FIELD_INITIAL;
				$search = $searchInitial;
			}

			$rangeInfo =& $this->getRangeInfo('reviewers');
			$reviewers = $sectionEditorSubmissionDao->getReviewersForArticle($journal->getId(), $articleId, $submission->getCurrentRound(), $searchType, $search, $searchMatch, $rangeInfo, $sort, $sortDirection); /* @var $reviewers DAOResultFactory */

			$journal = $request->getJournal();
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');

			$templateMgr =& TemplateManager::getManager();

			$templateMgr->assign('searchField', $searchType);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $searchQuery);
			$templateMgr->assign('searchInitial', $request->getUserVar('searchInitial'));

			$templateMgr->assign_by_ref('reviewers', $reviewers);
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('reviewerStatistics', $sectionEditorSubmissionDao->getReviewerStatistics($journal->getId()));
			$templateMgr->assign('fieldOptions', array(
				USER_FIELD_INTERESTS => 'user.interests',
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username',
				USER_FIELD_EMAIL => 'user.email'
			));
			$templateMgr->assign('completedReviewCounts', $reviewAssignmentDao->getCompletedReviewCounts($journal->getId()));
			$templateMgr->assign('rateReviewerOnQuality', $journal->getSetting('rateReviewerOnQuality'));
			$templateMgr->assign('averageQualityRatings', $reviewAssignmentDao->getAverageQualityRatings($journal->getId()));

			$templateMgr->assign('helpTopicId', 'journal.roles.reviewer');
			$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
			$templateMgr->assign('reviewerDatabaseLinks', $journal->getSetting('reviewerDatabaseLinks'));
			$templateMgr->assign('sort', $sort);
			$templateMgr->assign('sortDirection', $sortDirection);
			$templateMgr->display('sectionEditor/selectReviewer.tpl');
		}
	}

	/**
	 * Create a new user as a reviewer.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function createReviewer($args, &$request) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$submission =& $this->submission;

		import('classes.sectionEditor.form.CreateReviewerForm');
		$createReviewerForm = new CreateReviewerForm($articleId);
		$this->setupTemplate(true, $articleId);

		if (isset($args[1]) && $args[1] === 'create') {
			$createReviewerForm->readInputData();
			if ($createReviewerForm->validate()) {
				// Create a user and enroll them as a reviewer.
				$newUserId = $createReviewerForm->execute();
				$request->redirect(null, null, 'selectReviewer', array($articleId, $newUserId));
			} else {
				$createReviewerForm->display($args, $request);
			}
		} else {
			// Display the "create user" form.
			if ($createReviewerForm->isLocaleResubmit()) {
				$createReviewerForm->readInputData();
			} else {
				$createReviewerForm->initData();
			}
			$createReviewerForm->display($args, $request);
		}
	}

	/**
	 * Get a suggested username, making sure it's not
	 * already used by the system. (Poor-man's AJAX.)
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function suggestUsername($args, &$request) {
		parent::validate();
		$suggestion = Validation::suggestUsername(
			$request->getUserVar('firstName'),
			$request->getUserVar('lastName')
		);
		echo $suggestion;
	}

	/**
	 * Search for users to enroll as reviewers.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function enrollSearch($args, $request) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER); // manager.people.enrollment, manager.people.enroll
		$submission =& $this->submission;

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath('reviewer');

		$user =& $request->getUser();

		$rangeInfo = $this->getRangeInfo('users');
		$templateMgr =& TemplateManager::getManager();
		$this->setupTemplate(true);

		$searchType = null;
		$searchMatch = null;
		$search = $searchQuery = $request->getUserVar('search');
		$searchInitial = $request->getUserVar('searchInitial');
		if (!empty($search)) {
			$searchType = $request->getUserVar('searchField');
			$searchMatch = $request->getUserVar('searchMatch');

		} elseif (!empty($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$userDao =& DAORegistry::getDAO('UserDAO');
		$users =& $userDao->getUsersByField($searchType, $searchMatch, $search, false, $rangeInfo);

		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $searchQuery);
		$templateMgr->assign('searchInitial', $request->getUserVar('searchInitial'));

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
		$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));

		$templateMgr->assign('helpTopicId', 'journal.roles.index');
		$templateMgr->display('sectionEditor/searchUsers.tpl');
	}

	/**
	 * Enroll a reviewer.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function enroll($args, &$request) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$journal =& $request->getJournal();
		$submission =& $this->submission;

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath('reviewer');

		$users = $request->getUserVar('users');
		if (!is_array($users) && $request->getUserVar('userId') != null) $users = array($request->getUserVar('userId'));

		// Enroll reviewer
		for ($i=0; $i<count($users); $i++) {
			if (!$roleDao->userHasRole($journal->getId(), $users[$i], $roleId)) {
				$role = new Role();
				$role->setJournalId($journal->getId());
				$role->setUserId($users[$i]);
				$role->setRoleId($roleId);

				$roleDao->insertRole($role);
			}
		}
		$request->redirect(null, null, 'selectReviewer', $articleId);
	}

	/**
	 * Notify an assigned reviewer.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function notifyReviewer($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$submission =& $this->submission;

		$reviewId = (int) $request->getUserVar('reviewId');

		$send = $request->getUserVar('send')?true:false;
		$this->setupTemplate(true, $articleId, 'review');

		if (SectionEditorAction::notifyReviewer($submission, $reviewId, $send, $request)) {
			$request->redirect(null, null, 'submissionReview', $articleId);
		}
	}

	/**
	 * Clear an assigned review.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function clearReview($args, $request) {
		$articleId = (int) array_shift($args);
		$reviewId = (int) array_shift($args);
		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$submission =& $this->submission;

		SectionEditorAction::clearReview($submission, $reviewId, $request);

		$request->redirect(null, null, 'submissionReview', $articleId);
	}

	/**
	 * Cancel a review.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function cancelReview($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$submission =& $this->submission;

		$reviewId = (int) $request->getUserVar('reviewId');

		$send = $request->getUserVar('send')?true:false;
		$this->setupTemplate(true, $articleId, 'review');

		if (SectionEditorAction::cancelReview($submission, $reviewId, $send, $request)) {
			$request->redirect(null, null, 'submissionReview', $articleId);
		}
	}

	/**
	 * Remind a reviewer.
	 * @param $args aray
	 * @param $request PKPRequest
	 */
	function remindReviewer($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$submission =& $this->submission;

		$reviewId = (int) $request->getUserVar('reviewId');
		$this->setupTemplate(true, $articleId, 'review');

		if (SectionEditorAction::remindReviewer($submission, $reviewId, $request->getUserVar('send'), $request)) {
			$request->redirect(null, null, 'submissionReview', $articleId);
		}
	}

	/*
	 * Reassign a reviewer to the current round of review
	 * @param $args array
	 * @param $request object
	 */
	function reassignReviewer($args, $request) {
			$articleId = isset($args[0]) ? (int) $args[0] : 0;
			$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
			$userId = isset($args[1]) ? (int) $args[1] : 0;

			$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
			$submission =& $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
			$round = $submission->getCurrentRound();

			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignment =& $reviewAssignmentDao->getReviewAssignment($articleId, $userId, $submission->getCurrentRound()); /* @var $reviewAssignment ReviewAssignment */
			if($reviewAssignment && !$reviewAssignment->getDateCompleted() && $reviewAssignment->getDeclined()) {
				$reviewAssignment->setDeclined(false);
				$reviewAssignment->setDateAssigned(Core::getCurrentDate());
				$reviewAssignment->setDateNotified(null);
				$reviewAssignment->setDateConfirmed(null);
				$reviewAssignment->setRound($submission->getCurrentRound());

				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			}
			$request->redirect(null, null, 'submissionReview', $articleId);
	}

	/**
	 * Thank a reviewer.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function thankReviewer($args, &$request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$submission =& $this->submission;

		$reviewId = (int) $request->getUserVar('reviewId');

		$send = $request->getUserVar('send')?true:false;
		$this->setupTemplate(true, $articleId, 'review');

		if (SectionEditorAction::thankReviewer($submission, $reviewId, $send, $request)) {
			$request->redirect(null, null, 'submissionReview', $articleId);
		}
	}

	/**
	 * Rate a reviewer.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function rateReviewer($args, &$request) {
		$articleId = (int) $request->getUserVar('articleId');
		$reviewId = (int) $request->getUserVar('reviewId');
		$quality = (int) $request->getUserVar('quality');

		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$this->setupTemplate(true, $articleId, 'review');

		SectionEditorAction::rateReviewer($articleId, $reviewId, $quality, $request);

		$request->redirect(null, null, 'submissionReview', $articleId);
	}

	/**
	 * Confirm a review for a reviewer.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function confirmReviewForReviewer($args, &$request) {
		$articleId = (int) array_shift($args);
		$reviewId = (int) array_shift($args);

		$accept = $request->getUserVar('accept')?true:false;
		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$submission =& $this->submission;

		SectionEditorAction::confirmReviewForReviewer($reviewId, $accept, $request);
		$request->redirect(null, null, 'submissionReview', $articleId);
	}

	/**
	 * Upload a review on behalf of a reviewer.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function uploadReviewForReviewer($args, &$request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$submission =& $this->submission;

		$reviewId = (int) $request->getUserVar('reviewId');

		SectionEditorAction::uploadReviewForReviewer($reviewId, $submission, $request);
		$request->redirect(null, null, 'submissionReview', $articleId);
	}

	/**
	 * Make a reviewer file viewable to the author.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function makeReviewerFileViewable($args, &$request) {
		$articleId = $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$submission =& $this->submission;

		$reviewId = $request->getUserVar('reviewId');
		$fileId = $request->getUserVar('fileId');
		$revision = $request->getUserVar('revision');
		$viewable = $request->getUserVar('viewable');

		SectionEditorAction::makeReviewerFileViewable($articleId, $reviewId, $fileId, $revision, $viewable);

		$request->redirect(null, null, 'submissionReview', $articleId);
	}

	/**
	 * Set the review due date.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function setDueDate($args, &$request) {
		$articleId = (int) array_shift($args);
		$reviewId = (int) array_shift($args);

		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$submission =& $this->submission;

		$dueDate = $request->getUserVar('dueDate');
		$numWeeks = $request->getUserVar('numWeeks');

		if ($dueDate != null || $numWeeks != null) {
			SectionEditorAction::setDueDate($articleId, $reviewId, $dueDate, $numWeeks, false, $request);
			$request->redirect(null, null, 'submissionReview', $articleId);
		} else {
			$this->setupTemplate(true, $articleId, 'review');
			$journal =& $request->getJournal();

			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignment = $reviewAssignmentDao->getById($reviewId);

			$settingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
			$settings =& $settingsDao->getJournalSettings($journal->getId());

			$templateMgr =& TemplateManager::getManager();

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

	/**
	 * Enter a reviewer recommendation on behalf of a reviewer
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function enterReviewerRecommendation($args, &$request) {
		$articleId = $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$submission =& $this->submission;

		$reviewId = $request->getUserVar('reviewId');

		$recommendation = $request->getUserVar('recommendation');

		if ($recommendation != null) {
			SectionEditorAction::setReviewerRecommendation($this->submission, $reviewId, $recommendation, SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT, $request);
			$request->redirect(null, null, 'submissionReview', $articleId);
		} else {
			$this->setupTemplate(true, $articleId, 'review');

			$templateMgr =& TemplateManager::getManager();

			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('reviewId', $reviewId);

			import('classes.submission.reviewAssignment.ReviewAssignment');
			$templateMgr->assign_by_ref('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());

			$templateMgr->display('sectionEditor/reviewerRecommendation.tpl');
		}
	}

	/**
	 * Display a user's profile.
	 * @param $args array first parameter is the ID or username of the user to display
	 * @param $request PKPRequest
	 */
	function userProfile($args, &$request) {
		parent::validate();
		$this->setupTemplate(true);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('currentUrl', $request->url(null, $request->getRequestedPage()));

		$userDao =& DAORegistry::getDAO('UserDAO');
		$userId = isset($args[0]) ? $args[0] : 0;
		if (is_numeric($userId)) {
			$userId = (int) $userId;
			$user = $userDao->getById($userId);
		} else {
			$user = $userDao->getByUsername($userId);
		}


		if ($user == null) {
			// Non-existent user requested
			$templateMgr->assign('pageTitle', 'manager.people');
			$templateMgr->assign('errorMsg', 'manager.people.invalidUser');
			$templateMgr->display('common/error.tpl');

		} else {
			$site =& $request->getSite();
			$journal =& $request->getJournal();

			$countryDao =& DAORegistry::getDAO('CountryDAO');
			$country = null;
			if ($user->getCountry() != '') {
				$country = $countryDao->getCountry($user->getCountry());
			}
			$templateMgr->assign('country', $country);

			$templateMgr->assign('userInterests', $user->getInterestString());

			$templateMgr->assign_by_ref('user', $user);
			$templateMgr->assign('localeNames', AppLocale::getAllLocales());
			$templateMgr->assign('helpTopicId', 'journal.roles.index');
			$templateMgr->display('sectionEditor/userProfile.tpl');
		}
	}

	/**
	 * View article metadata.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewMetadata($args, &$request) {
		$articleId = (int) array_shift($args);
		$journal =& $request->getJournal();

		$this->validate($articleId);
		AppLocale::requireComponents(LOCALE_COMPONENT_OJS_AUTHOR);
		$submission =& $this->submission;
		$this->setupTemplate(true, $articleId, 'summary');

		SectionEditorAction::viewMetadata($submission, $journal);
	}

	/**
	 * Save modified metadata.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function saveMetadata($args, &$request) {
		$articleId = $request->getUserVar('articleId');
		$this->validate($articleId);
		AppLocale::requireComponents(LOCALE_COMPONENT_OJS_AUTHOR);
		$submission =& $this->submission;
		$this->setupTemplate(true, $articleId, 'summary');

		if (SectionEditorAction::saveMetadata($submission, $request)) {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}

	/**
	 * Remove cover page from article
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function removeArticleCoverPage($args, &$request) {
		$articleId = isset($args[0]) ? (int)$args[0] : 0;
		$this->validate($articleId);

		$formLocale = $args[1];
		if (!AppLocale::isLocaleValid($formLocale)) {
			$request->redirect(null, null, 'viewMetadata', $articleId);
		}

		$submission =& $this->submission;
		if (SectionEditorAction::removeArticleCoverPage($submission, $formLocale)) {
			$request->redirect(null, null, 'viewMetadata', $articleId);
		}
	}

	//
	// Review Form
	//

	/**
	 * Preview a review form.
	 * @param $args array ($reviewId, $reviewFormId)
	 * @param $request PKPRequest
	 */
	function previewReviewForm($args, &$request) {
		parent::validate();
		$this->setupTemplate(true);

		$reviewId = (int) array_shift($args);
		$reviewFormId = (int) array_shift($args);

		$journal =& $request->getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, ASSOC_TYPE_JOURNAL, $journal->getId());
		$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
		$reviewFormElements =& $reviewFormElementDao->getReviewFormElements($reviewFormId);
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageTitle', 'manager.reviewForms.preview');
		$templateMgr->assign_by_ref('reviewForm', $reviewForm);
		$templateMgr->assign('reviewFormElements', $reviewFormElements);
		$templateMgr->assign('reviewId', $reviewId);
		$templateMgr->assign('articleId', $reviewAssignment->getSubmissionId());
		//$templateMgr->assign('helpTopicId','journal.managementPages.reviewForms');
		$templateMgr->display('sectionEditor/previewReviewForm.tpl');
	}

	/**
	 * Clear a review form, i.e. remove review form assignment to the review.
	 * @param $args array ($articleId, $reviewId)
	 * @param $request PKPRequest
	 */
	function clearReviewForm($args, &$request) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$reviewId = isset($args[1]) ? (int) $args[1] : null;
		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$submission =& $this->submission;

		SectionEditorAction::clearReviewForm($submission, $reviewId);

		$request->redirect(null, null, 'submissionReview', $articleId);
	}

	/**
	 * Select a review form
	 * @param $args array ($articleId, $reviewId, $reviewFormId)
	 * @param $request PKPRequest
	 */
	function selectReviewForm($args, &$request) {
		$articleId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$submission =& $this->submission;

		$reviewId = isset($args[1]) ? (int) $args[1] : null;
		$reviewFormId = isset($args[2]) ? (int) $args[2] : null;

		if ($reviewFormId != null) {
			SectionEditorAction::addReviewForm($submission, $reviewId, $reviewFormId);
			$request->redirect(null, null, 'submissionReview', $articleId);
		} else {
			$journal =& $request->getJournal();
			$rangeInfo =& $this->getRangeInfo('reviewForms');
			$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
			$reviewForms =& $reviewFormDao->getActiveByAssocId(ASSOC_TYPE_JOURNAL, $journal->getId(), $rangeInfo);
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);

			$this->setupTemplate(true, $articleId, 'review');
			$templateMgr =& TemplateManager::getManager();

			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign('reviewId', $reviewId);
			$templateMgr->assign('assignedReviewFormId', $reviewAssignment->getReviewFormId());
			$templateMgr->assign_by_ref('reviewForms', $reviewForms);
			//$templateMgr->assign('helpTopicId','journal.managementPages.reviewForms');
			$templateMgr->display('sectionEditor/selectReviewForm.tpl');
		}
	}

	/**
	 * View review form response.
	 * @param $args array ($articleId, $reviewId)
	 * @param $request PKPRequest
	 */
	function viewReviewFormResponse($args, &$request) {
		$articleId = (int) array_shift($args);
		$reviewId = (int) array_shift($args);

		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$this->setupTemplate(true, $articleId, 'editing');

		SectionEditorAction::viewReviewFormResponse($this->submission, $reviewId);
	}

	//
	// Editor Review
	//

	/**
	 * Perform a review on behalf of the reviewer.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editorReview($args, $request) {
		$articleId = $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		$submission =& $this->submission;

		$signoffDao =& DAORegistry::getDAO('SignoffDAO');

		$redirectTarget = 'submissionReview';

		// If the Upload button was pressed.
		$submit = $request->getUserVar('submit');
		if ($submit != null) {
			SectionEditorAction::uploadEditorVersion($submission, $request);
		}

		if ($request->getUserVar('setCopyeditFile')) {
			// If the Send To Copyedit button was pressed
			$file = explode(',', $request->getUserVar('editorDecisionFile'));
			if (isset($file[0]) && isset($file[1])) {
				$round = $submission->getCurrentRound();
				if ($submission->getMostRecentEditorDecisionComment()) {
					// The conditions are met for being able
					// to send a file to copyediting.
					SectionEditorAction::setCopyeditFile($submission, $file[0], $file[1], $request);
				}
				$redirectTarget = 'submissionEditing';
			}

		} else if ($request->getUserVar('resubmit')) {
			// If the Resubmit button was pressed
			$file = explode(',', $request->getUserVar('editorDecisionFile'));
			if (isset($file[0]) && isset($file[1])) {
				SectionEditorAction::resubmitFile($submission, $file[0], $file[1], $request);
			}
		}

		$request->redirect(null, null, $redirectTarget, $articleId);
	}

	//
	// Copyedit
	//

	/**
	 * Select a copyeditor.
	 * @param $args array
	 * @param $request PKPRequest
 	 */
	function selectCopyeditor($args, $request) {
		$articleId = (int) array_shift($args);
		$userId = (int) array_shift($args);

		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$journal =& $request->getJournal();
		$submission =& $this->submission;

		$roleDao =& DAORegistry::getDAO('RoleDAO');

		if ($roleDao->userHasRole($journal->getId(), $userId, ROLE_ID_COPYEDITOR)) {
			SectionEditorAction::selectCopyeditor($submission, $userId, $request);
			$request->redirect(null, null, 'submissionEditing', $articleId);
		} else {
			$this->setupTemplate(true, $articleId, 'editing');

			$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');

			$searchType = null;
			$searchMatch = null;
			$search = $searchQuery = $request->getUserVar('search');
			$searchInitial = $request->getUserVar('searchInitial');
			if (!empty($search)) {
				$searchType = $request->getUserVar('searchField');
				$searchMatch = $request->getUserVar('searchMatch');

			} elseif (!empty($searchInitial)) {
				$searchInitial = String::strtoupper($searchInitial);
				$searchType = USER_FIELD_INITIAL;
				$search = $searchInitial;
			}

			$copyeditors = $roleDao->getUsersByRoleId(ROLE_ID_COPYEDITOR, $journal->getId(), $searchType, $search, $searchMatch);
			$copyeditorStatistics = $sectionEditorSubmissionDao->getCopyeditorStatistics($journal->getId());

			$templateMgr =& TemplateManager::getManager();

			$templateMgr->assign('searchField', $searchType);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $searchQuery);
			$templateMgr->assign('searchInitial', $request->getUserVar('searchInitial'));

			$templateMgr->assign_by_ref('users', $copyeditors);
			$templateMgr->assign('currentUser', $submission->getUserBySignoffType('SIGNOFF_COPYEDITING_INITIAL'));
			$templateMgr->assign_by_ref('statistics', $copyeditorStatistics);
			$templateMgr->assign('pageSubTitle', 'editor.article.selectCopyeditor');
			$templateMgr->assign('pageTitle', 'user.role.copyeditors');
			$templateMgr->assign('actionHandler', 'selectCopyeditor');
			$templateMgr->assign('fieldOptions', array(
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username',
				USER_FIELD_EMAIL => 'user.email'
			));
			$templateMgr->assign('articleId', $articleId);

			$templateMgr->assign('helpTopicId', 'journal.roles.copyeditor');
			$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
			$templateMgr->display('sectionEditor/selectUser.tpl');
		}
	}

	/**
	 * Notify a copyeditor of their assignment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function notifyCopyeditor($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$submission =& $this->submission;

		$send = $request->getUserVar('send')?true:false;
		$this->setupTemplate(true, $articleId, 'editing');

		if (SectionEditorAction::notifyCopyeditor($submission, $send, $request)) {
			$request->redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Initiate the copyediting process when the editor does the copyediting
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function initiateCopyedit($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		SectionEditorAction::initiateCopyedit($this->submission, $request);
		$request->redirect(null, null, 'submissionEditing', $articleId);
	}

	/**
	 * Thank the copyeditor.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function thankCopyeditor($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$send = $request->getUserVar('send')?true:false;
		$this->setupTemplate(true, $articleId, 'editing');

		if (SectionEditorAction::thankCopyeditor($this->submission, $send, $request)) {
			$request->redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Notify the author of their copyediting task.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function notifyAuthorCopyedit($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$send = $request->getUserVar('send')?true:false;
		$this->setupTemplate(true, $articleId, 'editing');

		if (SectionEditorAction::notifyAuthorCopyedit($this->submission, $send, $request)) {
			$request->redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Thank the author for completing their copyediting task.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function thankAuthorCopyedit($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$send = $request->getUserVar('send')?true:false;
		$this->setupTemplate(true, $articleId, 'editing');

		if (SectionEditorAction::thankAuthorCopyedit($this->submission, $send, $request)) {
			$request->redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Notify the copyeditor of the final copyediting round.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function notifyFinalCopyedit($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$send = $request->getUserVar('send')?true:false;
		$this->setupTemplate(true, $articleId, 'editing');

		if (SectionEditorAction::notifyFinalCopyedit($this->submission, $send, $request)) {
			$request->redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Complete copyediting.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function completeCopyedit($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		SectionEditorAction::completeCopyedit($this->submission, $request);
		$request->redirect(null, null, 'submissionEditing', $articleId);
	}

	/**
	 * Complete the final copyedit.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function completeFinalCopyedit($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		SectionEditorAction::completeFinalCopyedit($this->submission, $request);
		$request->redirect(null, null, 'submissionEditing', $articleId);
	}

	/**
	 * Thank the copyeditor for the final copyedit.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function thankFinalCopyedit($args, $request) {
		$articleId = $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$send = $request->getUserVar('send')?true:false;
		$this->setupTemplate(true, $articleId, 'editing');

		if (SectionEditorAction::thankFinalCopyedit($this->submission, $send, $request)) {
			$request->redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Upload a review version.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function uploadReviewVersion($args, $request) {
		$articleId = $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		SectionEditorAction::uploadReviewVersion($this->submission);
		$request->redirect(null, null, 'submissionReview', $articleId);
	}

	/**
	 * Upload a copyedit version.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function uploadCopyeditVersion($args, $request) {
		$articleId = $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$copyeditStage = $request->getUserVar('copyeditStage');
		SectionEditorAction::uploadCopyeditVersion($this->submission, $copyeditStage);

		$request->redirect(null, null, 'submissionEditing', $articleId);
	}

	/**
	 * Add a supplementary file.
	 * @param $args array ($articleId)
	 * @param $request PKPRequest
	 */
	function addSuppFile($args, $request) {
		$articleId = (int) array_shift($args);
		$journal =& $request->getJournal();

		$this->validate($articleId);
		$submission =& $this->submission;
		$this->setupTemplate(true, $articleId, 'summary');

		import('classes.submission.form.SuppFileForm');

		$submitForm = new SuppFileForm($submission, $journal);

		if ($submitForm->isLocaleResubmit()) {
			$submitForm->readInputData();
		} else {
			$submitForm->initData();
		}
		$submitForm->display();
	}

	/**
	 * Edit a supplementary file.
	 * @param $args array ($articleId, $suppFileId)
	 * @param $request PKPRequest
	 */
	function editSuppFile($args, $request) {
		$articleId = (int) array_shift($args);
		$suppFileId = (int) array_shift($args);
		$journal =& $request->getJournal();

		$this->validate($articleId);
		$submission =& $this->submission;

		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$suppFile =& $suppFileDao->getSuppFile($suppFileId, $articleId);
		if (!$suppFile) {
			$request->redirect(null, null, 'submissionEditing', $articleId);
		}

		$this->setupTemplate(true, $articleId, 'summary');

		import('classes.submission.form.SuppFileForm');
		$submitForm = new SuppFileForm($submission, $journal, $suppFileId);
		if ($submitForm->isLocaleResubmit()) {
			$submitForm->readInputData();
		} else {
			$submitForm->initData();
		}
		$submitForm->display();
	}

	/**
	 * Set reviewer visibility for a supplementary file.
	 * @param $args array ($suppFileId)
	 * @param $request PKPRequest
	 */
	function setSuppFileVisibility($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId);
		$submission =& $this->submission;

		$suppFileId = (int) $request->getUserVar('fileId');
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$suppFile = $suppFileDao->getSuppFile($suppFileId, $articleId);

		if (isset($suppFile) && $suppFile != null) {
			$suppFile->setShowReviewers($request->getUserVar('show')==1?1:0);
			$suppFileDao->updateSuppFile($suppFile);
		}
		$request->redirect(null, null, 'submissionReview', $articleId);
	}

	/**
	 * Save a supplementary file.
	 * @param $args array ($suppFileId)
	 * @param $request Request
	 */
	function saveSuppFile($args, $request) {
		$articleId = $request->getUserVar('articleId');
		$this->validate($articleId);
		$this->setupTemplate(true, $articleId, 'summary');
		$submission =& $this->submission;

		$suppFileId = (int) array_shift($args);
		$journal =& $request->getJournal();

		import('classes.submission.form.SuppFileForm');

		$submitForm = new SuppFileForm($submission, $journal, $suppFileId);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();

			// Send a notification to associated users
			import('classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$article =& $articleDao->getArticle($articleId);
			$notificationUsers = $article->getAssociatedUserIds(true, false);
			foreach ($notificationUsers as $userRole) {
				$notificationManager->createNotification(
					$request, $userRole['id'], NOTIFICATION_TYPE_SUPP_FILE_MODIFIED,
					$article->getJournalId(), ASSOC_TYPE_ARTICLE, $article->getId()
				);
			}

			$request->redirect(null, null, $this->_getFrom(), $articleId);
		} else {
			$submitForm->display();
		}
	}

	/**
	 * Delete an editor version file.
	 * @param $args array ($articleId, $fileId)
	 * @param $request PKPRequest
	 */
	function deleteArticleFile($args, &$request) {
		$articleId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revisionId = (int) array_shift($args);

		$this->validate($articleId, SECTION_EDITOR_ACCESS_REVIEW);
		SectionEditorAction::deleteArticleFile($this->submission, $fileId, $revisionId);

		$request->redirect(null, null, 'submissionReview', $articleId);
	}

	/**
	 * Delete a supplementary file.
	 * @param $args array ($articleId, $suppFileId)
	 * @param $request PKPRequest
	 */
	function deleteSuppFile($args, $request) {
		$articleId = (int) array_shift($args);
		$suppFileId = (int) array_shift($args);
		$this->validate($articleId);
		SectionEditorAction::deleteSuppFile($this->submission, $suppFileId);
		$request->redirect(null, null, $this->_getFrom(), $articleId);
	}

	/**
	 * Archive a submission.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function archiveSubmission($args, $request) {
		$articleId = (int) array_shift($args);
		$this->validate($articleId);
		SectionEditorAction::archiveSubmission($this->submission, $request);
		$request->redirect(null, null, 'submission', $articleId);
	}

	/**
	 * Restore an archived submission to the queue.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function restoreToQueue($args, $request) {
		$articleId = (int) array_shift($args);
		$this->validate($articleId);
		SectionEditorAction::restoreToQueue($this->submission, $request);
		$request->redirect(null, null, 'submissionEditing', $articleId);
	}

	/**
	 * Notify the author of an unsuitable submission.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function unsuitableSubmission($args, $request) {
		$articleId = $request->getUserVar('articleId');
		$this->validate($articleId);
		$send = $request->getUserVar('send')?true:false;
		$this->setupTemplate(true, $articleId, 'summary');

		if (SectionEditorAction::unsuitableSubmission($this->submission, $send, $request)) {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}

	/**
	 * Set section ID.
	 * @param $args array ($articleId)
	 */
	function updateSection($args, &$request) {
		$articleId = (int) array_shift($args);
		$this->validate($articleId);
		SectionEditorAction::updateSection($this->submission, $request->getUserVar('section'));
		$request->redirect(null, null, 'submission', $articleId);
	}

	/**
	 * Set RT comments status for article.
	 * @param $args array ($articleId)
	 * @param $request PKPRequest
	 */
	function updateCommentsStatus($args, &$request) {
		$articleId = (int) array_shift($args);
		$this->validate($articleId);
		SectionEditorAction::updateCommentsStatus($this->submission, $request->getUserVar('commentsStatus'));
		$request->redirect(null, null, 'submission', $articleId);
	}

	//
	// Layout Editing
	//

	/**
	 * Upload a layout file (either layout version, galley, or supp. file).
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function uploadLayoutFile($args, &$request) {
		$layoutFileType = $request->getUserVar('layoutFileType');
		if ($layoutFileType == 'submission') {
			$this->_uploadLayoutVersion($request);

		} else if ($layoutFileType == 'galley') {
			$this->_uploadGalley('layoutFile', $request);

		} else if ($layoutFileType == 'supp') {
			$this->_uploadSuppFile('layoutFile', $request);

		} else {
			$request->redirect(null, null, $this->_getFrom(), $request->getUserVar('articleId'));
		}
	}

	/**
	 * Upload the layout version of the submission file
	 * @param $request PKPRequest
	 */
	function _uploadLayoutVersion(&$request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		SectionEditorAction::uploadLayoutVersion($this->submission);
		$request->redirect(null, null, 'submissionEditing', $articleId);
	}

	/**
	 * Delete an article image.
	 * @param $args array ($articleId, $fileId)
	 * @param $request PKPRequest
	 */
	function deleteArticleImage($args, &$request) {
		$articleId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = (int) array_shift($args);
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		SectionEditorAction::deleteArticleImage($this->submission, $fileId, $revision);
		$request->redirect(null, null, 'editGalley', array($articleId, $galleyId));
	}

	/**
	 * Assign/reassign a layout editor to the submission.
	 * @param $args array ($articleId, [$userId])
	 * @param $request object
	 */
	function assignLayoutEditor($args, $request) {
		$articleId = (int) array_shift($args);
		$editorId = (int) array_shift($args);
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$journal =& $request->getJournal();
		$submission =& $this->submission;

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');

		if ($editorId && $roleDao->userHasRole($journal->getId(), $editorId, ROLE_ID_LAYOUT_EDITOR)) {
			SectionEditorAction::assignLayoutEditor($submission, $editorId, $request);
			$request->redirect(null, null, 'submissionEditing', $articleId);
		} else {
			$searchType = null;
			$searchMatch = null;
			$search = $searchQuery = $request->getUserVar('search');
			$searchInitial = $request->getUserVar('searchInitial');
			if (!empty($search)) {
				$searchType = $request->getUserVar('searchField');
				$searchMatch = $request->getUserVar('searchMatch');

			} elseif (!empty($searchInitial)) {
				$searchInitial = String::strtoupper($searchInitial);
				$searchType = USER_FIELD_INITIAL;
				$search = $searchInitial;
			}

			$layoutEditors = $roleDao->getUsersByRoleId(ROLE_ID_LAYOUT_EDITOR, $journal->getId(), $searchType, $search, $searchMatch);

			$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
			$layoutEditorStatistics = $sectionEditorSubmissionDao->getLayoutEditorStatistics($journal->getId());

			$this->setupTemplate(true, $articleId, 'editing');

			$templateMgr =& TemplateManager::getManager();

			$templateMgr->assign('searchField', $searchType);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $searchQuery);
			$templateMgr->assign('searchInitial', $request->getUserVar('searchInitial'));
			$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));

			$templateMgr->assign('pageTitle', 'user.role.layoutEditors');
			$templateMgr->assign('pageSubTitle', 'editor.article.selectLayoutEditor');
			$templateMgr->assign('actionHandler', 'assignLayoutEditor');
			$templateMgr->assign('articleId', $articleId);
			$templateMgr->assign_by_ref('users', $layoutEditors);

			$layoutSignoff = $signoffDao->build('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $articleId);
			if ($layoutSignoff) {
				$templateMgr->assign('currentUser', $layoutSignoff->getUserId());
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
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function notifyLayoutEditor($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$send = $request->getUserVar('send')?true:false;
		$this->setupTemplate(true, $articleId, 'editing');
		if (SectionEditorAction::notifyLayoutEditor($this->submission, $send, $request)) {
			$request->redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Thank the layout editor.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function thankLayoutEditor($args, $request) {
		$articleId = $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$submission =& $this->submission;

		$send = $request->getUserVar('send')?true:false;
		$this->setupTemplate(true, $articleId, 'editing');

		if (SectionEditorAction::thankLayoutEditor($submission, $send, $request)) {
			$request->redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Create a new galley with the uploaded file.
	 * @param $fileName string
	 * @param $request PKPRequest
	 */
	function _uploadGalley($fileName = null, $request) {
		$articleId = $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		import('classes.submission.form.ArticleGalleyForm');
 		$galleyForm = new ArticleGalleyForm($articleId);
		$galleyId = $galleyForm->execute($fileName, $request->getUserVar('createRemote'));

		Request::redirect(null, null, 'editGalley', array($articleId, $galleyId));
	}

	/**
	 * Edit a galley.
	 * @param $args array ($articleId, $galleyId)
	 * @param $request PKPRequest
	 */
	function editGalley($args, $request) {
		$articleId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId, $articleId);
		if (!$galley) {
			$request->redirect(null, null, 'submissionEditing', $articleId);
		}

		$this->setupTemplate(true, $articleId, 'editing');

		import('classes.submission.form.ArticleGalleyForm');
		$submitForm = new ArticleGalleyForm($articleId, $galleyId);
		if ($submitForm->isLocaleResubmit()) {
			$submitForm->readInputData();
		} else {
			$submitForm->initData();
		}
		$submitForm->display();
	}

	/**
	 * Save changes to a galley.
	 * @param $args array ($articleId, $galleyId)
	 * @param $request Request
	 */
	function saveGalley($args, $request) {
		$articleId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$this->setupTemplate(true, $articleId, 'editing');
		$submission =& $this->submission;

		import('classes.submission.form.ArticleGalleyForm');

		$submitForm = new ArticleGalleyForm($articleId, $galleyId);

		$submitForm->readInputData();
		if ($submitForm->validate()) {
			$submitForm->execute();

			// Send a notification to associated users
			import('classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$article =& $articleDao->getArticle($articleId);
			$notificationUsers = $article->getAssociatedUserIds(true, false);
			foreach ($notificationUsers as $userRole) {
				$notificationManager->createNotification(
					$request, $userRole['id'], NOTIFICATION_TYPE_GALLEY_MODIFIED,
					$article->getJournalId(), ASSOC_TYPE_ARTICLE, $article->getId()
				);
			}

			if ($request->getUserVar('uploadImage')) {
				$submitForm->uploadImage();
				$request->redirect(null, null, 'editGalley', array($articleId, $galleyId));
			} else if(($deleteImage = $request->getUserVar('deleteImage')) && count($deleteImage) == 1) {
				list($imageId) = array_keys($deleteImage);
				$submitForm->deleteImage($imageId);
				$request->redirect(null, null, 'editGalley', array($articleId, $galleyId));
			}
			$request->redirect(null, null, 'submissionEditing', $articleId);
		} else {
			$submitForm->display();
		}
	}

	/**
	 * Change the sequence order of a galley.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function orderGalley($args, &$request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		SectionEditorAction::orderGalley($this->submission, $request->getUserVar('galleyId'), $request->getUserVar('d'));
		$request->redirect(null, null, 'submissionEditing', $articleId);
	}

	/**
	 * Delete a galley file.
	 * @param $args array ($articleId, $galleyId)
	 * @param $request PKPRequest
	 */
	function deleteGalley($args, &$request) {
		$articleId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		SectionEditorAction::deleteGalley($this->submission, $galleyId);
		$request->redirect(null, null, 'submissionEditing', $articleId);
	}

	/**
	 * Proof / "preview" a galley.
	 * @param $args array ($articleId, $galleyId)
	 * @param $request PKPRequest
	 */
	function proofGalley($args, &$request) {
		$articleId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$this->setupTemplate();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->display('submission/layout/proofGalley.tpl');
	}

	/**
	 * Proof galley (shows frame header).
	 * @param $args array ($articleId, $galleyId)
	 * @param $request PKPRequest
	 */
	function proofGalleyTop($args, &$request) {
		$articleId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$this->setupTemplate();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('backHandler', 'submissionEditing');
		$templateMgr->display('submission/layout/proofGalleyTop.tpl');
	}

	/**
	 * Proof galley (outputs file contents).
	 * @param $args array ($articleId, $galleyId)
	 * @param $request PKPRequest
	 */
	function proofGalleyFile($args, &$request) {
		$articleId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId, $articleId);

		import('classes.file.ArticleFileManager'); // FIXME

		if (isset($galley)) {
			if ($galley->isHTMLGalley()) {
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign_by_ref('galley', $galley);
				if ($galley->isHTMLGalley() && $styleFile =& $galley->getStyleFile()) {
					$templateMgr->addStyleSheet(Request::url(null, 'article', 'viewFile', array(
						$articleId, $galleyId, $styleFile->getFileId()
					)));
				}
				$templateMgr->display('submission/layout/proofGalleyHTML.tpl');

			} else {
				// View non-HTML file inline
				$this->viewFile(array($articleId, $galley->getFileId()), $request);
			}
		}
	}

	/**
	 * Helper to upload a new supplementary file.
	 * @param $fileName string
	 * @param $request PKPRequest
	 */
	function _uploadSuppFile($fileName = null, $request) {
		$articleId = $request->getUserVar('articleId');
		$this->validate($articleId);
		$submission =& $this->submission;
		$journal =& $request->getJournal();

		import('classes.submission.form.SuppFileForm');

		$suppFileForm = new SuppFileForm($submission, $journal);
		$suppFileForm->setData('title', array($submission->getLocale() => __('common.untitled')));
		$suppFileId = $suppFileForm->execute($fileName, $request->getUserVar('createRemote'));

		$request->redirect(null, null, 'editSuppFile', array($articleId, $suppFileId));
	}

	/**
	 * Change the sequence order of a supplementary file.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function orderSuppFile($args, &$request) {
		$articleId = $request->getUserVar('articleId');
		$this->validate($articleId);
		SectionEditorAction::orderSuppFile($this->submission, $request->getUserVar('suppFileId'), $request->getUserVar('d'));
		$request->redirect(null, null, 'submissionEditing', $articleId);
	}


	//
	// Submission History (FIXME Move to separate file?)
	//

	/**
	 * View submission event log.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submissionEventLog($args, &$request) {
		$articleId = (int) array_shift($args);
		$logId = (int) array_shift($args);
		$this->validate($articleId);
		$submission =& $this->submission;
		$this->setupTemplate(true, $articleId, 'history');

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('isEditor', Validation::isEditor());
		$templateMgr->assign_by_ref('submission', $submission);

		if ($logId) {
			$logDao =& DAORegistry::getDAO('ArticleEventLogDAO');
			$logEntry =& $logDao->getById($logId, ASSOC_TYPE_ARTICLE, $articleId);
		}

		if (isset($logEntry)) {
			$templateMgr->assign('logEntry', $logEntry);
			$templateMgr->display('sectionEditor/submissionEventLogEntry.tpl');

		} else {
			$rangeInfo =& $this->getRangeInfo('eventLogEntries');
			$eventLogDao =& DAORegistry::getDAO('ArticleEventLogDAO');
			$eventLogEntries =& $eventLogDao->getByAssoc(ASSOC_TYPE_ARTICLE, $articleId, $rangeInfo);
			$templateMgr->assign('eventLogEntries', $eventLogEntries);
			$templateMgr->display('sectionEditor/submissionEventLog.tpl');
		}
	}

	/**
	 * Clear submission event log entries.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function clearSubmissionEventLog($args, &$request) {
		$articleId = (int) array_shift($args);
		$logId = (int) array_shift($args);
		$this->validate($articleId);
		$logDao =& DAORegistry::getDAO('ArticleEventLogDAO');
		if ($logId) {
			$logDao->deleteObject($logId, ASSOC_TYPE_ARTICLE, $articleId);
		} else {
			$logDao->deleteByAssoc(ASSOC_TYPE_ARTICLE, $articleId);
		}
		$request->redirect(null, null, 'submissionEventLog', $articleId);
	}

	/**
	 * View submission email log.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submissionEmailLog($args, $request) {
		$articleId = (int) array_shift($args);
		$logId = (int) array_shift($args);

		$this->validate($articleId);
		$submission =& $this->submission;
		$this->setupTemplate(true, $articleId, 'history');

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('isEditor', Validation::isEditor());
		$templateMgr->assign_by_ref('submission', $submission);

		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		import('classes.file.ArticleFileManager');
		$templateMgr->assign('attachments', $articleFileDao->getArticleFilesByAssocId($logId, ARTICLE_FILE_ATTACHMENT));

		if ($logId) {
			$logDao =& DAORegistry::getDAO('ArticleEmailLogDAO');
			$logEntry =& $logDao->getById($logId, ASSOC_TYPE_ARTICLE, $articleId);
		}

		if (isset($logEntry)) {
			$templateMgr->assign_by_ref('logEntry', $logEntry);
			$templateMgr->display('sectionEditor/submissionEmailLogEntry.tpl');
		} else {
			$rangeInfo =& $this->getRangeInfo('emailLogEntries');

			$emailLogDao =& DAORegistry::getDAO('ArticleEmailLogDAO');
			$emailLogEntries =& $emailLogDao->getByAssoc(ASSOC_TYPE_ARTICLE, $articleId, $rangeInfo);
			$templateMgr->assign_by_ref('emailLogEntries', $emailLogEntries);
			$templateMgr->display('sectionEditor/submissionEmailLog.tpl');
		}
	}

	/**
	 * Clear submission email log entries.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function clearSubmissionEmailLog($args, &$request) {
		$articleId = (int) array_shift($args);
		$logId = (int) array_shift($args);
		$this->validate($articleId);

		$logDao =& DAORegistry::getDAO('ArticleEmailLogDAO');
		if ($logId) {
			$logDao->deleteObject($logId, ASSOC_TYPE_ARTICLE, $articleId);
		} else {
			$logDao->deleteByAssoc(ASSOC_TYPE_ARTICLE, $articleId);
		}
		$request->redirect(null, null, 'submissionEmailLog', $articleId);
	}

	//
	// Submission Notes Functions
	//

	/**
	 * Create a submission note and redirect to submission notes list
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function addSubmissionNote($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId);
		SectionEditorAction::addSubmissionNote($articleId, $request);
		$request->redirect(null, null, 'submissionNotes', $articleId);
	}

	/**
	 * Removes a submission note.
	 * Redirects to submission notes list
	 * @param $args array
	 * @param $request object
	 */
	function removeSubmissionNote($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$noteId = (int) $request->getUserVar('noteId');
		$fileId = (int) $request->getUserVar('fileId');
		$this->validate($articleId);
		SectionEditorAction::removeSubmissionNote($articleId, $noteId, $fileId);
		$request->redirect(null, null, 'submissionNotes', $articleId);
	}

	/**
	 * Updates a submission note.
	 * Redirects to submission notes list
	 * @param $args array
	 * @param $request object
	 */
	function updateSubmissionNote($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId);
		SectionEditorAction::updateSubmissionNote($articleId, $request);
		$request->redirect(null, null, 'submissionNotes', $articleId);
	}

	/**
	 * Clear all submission notes and redirect to submission notes list
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function clearAllSubmissionNotes($args, &$request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId);
		SectionEditorAction::clearAllSubmissionNotes($articleId);
		$request->redirect(null, null, 'submissionNotes', $articleId);
	}

	/**
	 * View submission notes.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submissionNotes($args, &$request) {
		$articleId = (int) array_shift($args);
		$noteViewType = array_shift($args); // May be null/empty
		$noteId = (int) array_shift($args);

		$this->validate($articleId);
		$this->setupTemplate(true, $articleId, 'history');
		$submission =& $this->submission;

		$rangeInfo =& $this->getRangeInfo('submissionNotes');
		$noteDao =& DAORegistry::getDAO('NoteDAO');

		// submission note edit
		if ($noteViewType == 'edit') {
			$note = $noteDao->getById($noteId);
		}

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('articleId', $articleId);
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign('noteViewType', $noteViewType);
		if (isset($note)) {
			$templateMgr->assign_by_ref('articleNote', $note);
		}

		if ($noteViewType == 'edit' || $noteViewType == 'add') {
			$templateMgr->assign('showBackLink', true);
		} else {
			$submissionNotes =& $noteDao->getByAssoc(ASSOC_TYPE_ARTICLE, $articleId);
			$templateMgr->assign_by_ref('submissionNotes', $submissionNotes);
		}

		$templateMgr->display('sectionEditor/submissionNotes.tpl');
	}


	//
	// Misc
	//

	/**
	 * Download a file.
	 * @param $args array ($articleId, $fileId, [$revision])
	 * @param $request PKPRequest
	 */
	function downloadFile($args, &$request) {
		$articleId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = array_shift($args); // May be null

		$this->validate($articleId);
		if (!SectionEditorAction::downloadFile($articleId, $fileId, $revision)) {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}

	/**
	 * View a file (inlines file).
	 * @param $args array ($articleId, $fileId, [$revision])
	 * @param $request PKPRequest
	 */
	function viewFile($args, &$request) {
		$articleId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = array_shift($args); // May be null

		$this->validate($articleId);
		if (!SectionEditorAction::viewFile($articleId, $fileId, $revision)) {
			$request->redirect(null, null, 'submission', $articleId);
		}
	}


	//
	// Proofreading
	//

	/**
	 * Select Proofreader.
	 * @param $args array ($articleId, $userId)
	 * @param $request PKPRequest
	 */
	function selectProofreader($args, $request) {
		$articleId = (int) array_shift($args);
		$userId = (int) array_shift($args);

		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$journal =& $request->getJournal();
		$submission =& $this->submission;

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');

		if ($userId && $articleId && $roleDao->userHasRole($journal->getId(), $userId, ROLE_ID_PROOFREADER)) {
			import('classes.submission.proofreader.ProofreaderAction');
			ProofreaderAction::selectProofreader($userId, $submission, $request);
			$request->redirect(null, null, 'submissionEditing', $articleId);
		} else {
			$this->setupTemplate(true, $articleId, 'editing');

			$searchType = null;
			$searchMatch = null;
			$search = $searchQuery = $request->getUserVar('search');
			$searchInitial = $request->getUserVar('searchInitial');
			if (!empty($search)) {
				$searchType = $request->getUserVar('searchField');
				$searchMatch = $request->getUserVar('searchMatch');

			} elseif (!empty($searchInitial)) {
				$searchInitial = String::strtoupper($searchInitial);
				$searchType = USER_FIELD_INITIAL;
				$search = $searchInitial;
			}

			$proofreaders = $roleDao->getUsersByRoleId(ROLE_ID_PROOFREADER, $journal->getId(), $searchType, $search, $searchMatch);

			$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
			$proofreaderStatistics = $sectionEditorSubmissionDao->getProofreaderStatistics($journal->getId());

			$templateMgr =& TemplateManager::getManager();

			$templateMgr->assign('searchField', $searchType);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $searchQuery);
			$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));

			$templateMgr->assign_by_ref('users', $proofreaders);

			$proofSignoff = $signoffDao->getBySymbolic('SIGNOFF_PROOFREADING_PROOFREADER', ASSOC_TYPE_ARTICLE, $articleId);
			if ($proofSignoff) {
				$templateMgr->assign('currentUser', $proofSignoff->getUserId());
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
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function notifyAuthorProofreader($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$send = $request->getUserVar('send');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$this->setupTemplate(true, $articleId, 'editing');

		import('classes.submission.proofreader.ProofreaderAction');
		if (ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_AUTHOR_REQUEST', $request, $send?'':$request->url(null, null, 'notifyAuthorProofreader'))) {
			$request->redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Thank author for proofreading
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function thankAuthorProofreader($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$send = $request->getUserVar('send');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$this->setupTemplate(true, $articleId, 'editing');

		import('classes.submission.proofreader.ProofreaderAction');
		if (ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_AUTHOR_ACK', $request, $send?'':$request->url(null, null, 'thankAuthorProofreader'))) {
			$request->redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Editor initiates proofreading
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editorInitiateProofreader($args, &$request) {
		$articleId = $request->getUserVar('articleId');
		$user =& $request->getUser();
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$signoff = $signoffDao->build('SIGNOFF_PROOFREADING_PROOFREADER', ASSOC_TYPE_ARTICLE, $articleId);
		if (!$signoff->getUserId()) {
			$signoff->setUserId($user->getId());
		}
		$signoff->setDateNotified(Core::getCurrentDate());
		$signoffDao->updateObject($signoff);

		$request->redirect(null, null, 'submissionEditing', $articleId);
	}

	/**
	 * Editor completes proofreading
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editorCompleteProofreader($args, &$request) {
		$articleId = $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$signoff = $signoffDao->build('SIGNOFF_PROOFREADING_PROOFREADER', ASSOC_TYPE_ARTICLE, $articleId);
		$signoff->setDateCompleted(Core::getCurrentDate());
		$signoffDao->updateObject($signoff);
		$request->redirect(null, null, 'submissionEditing', $articleId);
	}

	/**
	 * Notify proofreader for proofreading
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function notifyProofreader($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$send = $request->getUserVar('send');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$this->setupTemplate(true, $articleId, 'editing');

		import('classes.submission.proofreader.ProofreaderAction');
		if (ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_REQUEST', $request, $send?'':$request->url(null, null, 'notifyProofreader'))) {
			$request->redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Thank proofreader for proofreading
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function thankProofreader($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$send = $request->getUserVar('send');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$this->setupTemplate(true, $articleId, 'editing');

		import('classes.submission.proofreader.ProofreaderAction');
		if (ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_ACK', $request, $send?'':$request->url(null, null, 'thankProofreader'))) {
			$request->redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Editor initiates layout editor proofreading
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editorInitiateLayoutEditor($args, &$request) {
		$articleId = (int) $request->getUserVar('articleId');
		$user =& $request->getUser();
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$signoff = $signoffDao->build('SIGNOFF_PROOFREADING_LAYOUT', ASSOC_TYPE_ARTICLE, $articleId);
		if (!$signoff->getUserId()) {
			$signoff->setUserId($user->getId());
		}
		$signoff->setDateNotified(Core::getCurrentDate());
		$signoff->setDateUnderway(null);
		$signoff->setDateCompleted(null);
		$signoff->setDateAcknowledged(null);
		$signoffDao->updateObject($signoff);

		$request->redirect(null, null, 'submissionEditing', $articleId);
	}

	/**
	 * Editor completes layout editor proofreading
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editorCompleteLayoutEditor($args, &$request) {
		$articleId = $request->getUserVar('articleId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$signoff = $signoffDao->build('SIGNOFF_PROOFREADING_LAYOUT', ASSOC_TYPE_ARTICLE, $articleId);
		$signoff->setDateCompleted(Core::getCurrentDate());
		$signoffDao->updateObject($signoff);

		$request->redirect(null, null, 'submissionEditing', $articleId);
	}

	/**
	 * Notify layout editor for proofreading
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function notifyLayoutEditorProofreader($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$send = $request->getUserVar('send');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$this->setupTemplate(true, $articleId, 'editing');

		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$signoff = $signoffDao->build('SIGNOFF_PROOFREADING_LAYOUT', ASSOC_TYPE_ARTICLE, $articleId);
		$signoff->setDateNotified(Core::getCurrentDate());
		$signoff->setDateUnderway(null);
		$signoff->setDateCompleted(null);
		$signoff->setDateAcknowledged(null);
		$signoffDao->updateObject($signoff);

		import('classes.submission.proofreader.ProofreaderAction');
		if (ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_LAYOUT_REQUEST', $request, $send?'':$request->url(null, null, 'notifyLayoutEditorProofreader'))) {
			$request->redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Thank layout editor for proofreading
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function thankLayoutEditorProofreader($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$send = $request->getUserVar('send');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$this->setupTemplate(true, $articleId, 'editing');

		import('classes.submission.proofreader.ProofreaderAction');
		if (ProofreaderAction::proofreadEmail($articleId, 'PROOFREAD_LAYOUT_ACK', $request, $send?'':$request->url(null, null, 'thankLayoutEditorProofreader'))) {
			$request->redirect(null, null, 'submissionEditing', $articleId);
		}
	}

	/**
	 * Schedule/unschedule an article for publication.
	 * @param $args array
	 * @param $request object
	 */
	function scheduleForPublication($args, $request) {
		$articleId = (int) array_shift($args);
		$issueId = (int) $request->getUserVar('issueId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$journal =& $request->getJournal();
		$submission =& $this->submission;

		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($articleId);

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issue =& $issueDao->getIssueById($issueId, $journal->getId());

		if ($publishedArticle) {
			if (!$issue || !$issue->getPublished()) {
				$fromIssue =& $issueDao->getIssueById($publishedArticle->getIssueId(), $journal->getId());
				if ($fromIssue->getPublished()) {
					// Insert article tombstone
					import('classes.article.ArticleTombstoneManager');
					$articleTombstoneManager = new ArticleTombstoneManager();
					$articleTombstoneManager->insertArticleTombstone($submission, $journal);
				}
			}
		}

		import('classes.search.ArticleSearchIndex');
		$articleSearchIndex = new ArticleSearchIndex();

		if ($issue) {

			// Schedule against an issue.
			if ($publishedArticle) {
				$publishedArticle->setIssueId($issueId);
				$publishedArticle->setSeq(REALLY_BIG_NUMBER);
				$publishedArticleDao->updatePublishedArticle($publishedArticle);

				// Re-index the published article metadata.
				$articleSearchIndex->articleMetadataChanged($publishedArticle);
			} else {
				$publishedArticle = new PublishedArticle();
				$publishedArticle->setId($submission->getId());
				$publishedArticle->setIssueId($issueId);
				$publishedArticle->setDatePublished(Core::getCurrentDate());
				$publishedArticle->setSeq(REALLY_BIG_NUMBER);
				$publishedArticle->setAccessStatus(ARTICLE_ACCESS_ISSUE_DEFAULT);

				$publishedArticleDao->insertPublishedArticle($publishedArticle);

				// If we're using custom section ordering, and if this is the first
				// article published in a section, make sure we enter a custom ordering
				// for it. (Default at the end of the list.)
				if ($sectionDao->customSectionOrderingExists($issueId)) {
					if ($sectionDao->getCustomSectionOrder($issueId, $submission->getSectionId()) === null) {
						$sectionDao->insertCustomSectionOrder($issueId, $submission->getSectionId(), REALLY_BIG_NUMBER);
						$sectionDao->resequenceCustomSectionOrders($issueId);
					}
				}

				// Index the published article metadata and files for the first time.
				$articleSearchIndex->articleMetadataChanged($publishedArticle);
				$articleSearchIndex->articleFilesChanged($publishedArticle);
			}

		} else {
			if ($publishedArticle) {
				// This was published elsewhere; make sure we don't
				// mess up sequencing information.
				$issueId = $publishedArticle->getIssueId();
				$publishedArticleDao->deletePublishedArticleByArticleId($articleId);

				// Delete the article from the search index.
				$articleSearchIndex->articleFileDeleted($articleId);
			}
		}

		// Resequence the articles.
		$publishedArticleDao->resequencePublishedArticles($submission->getSectionId(), $issueId);

		$submission->stampStatusModified();

		if ($issue && $issue->getPublished()) {
			$submission->setStatus(STATUS_PUBLISHED);
			// delete article tombstone
			$tombstoneDao =& DAORegistry::getDAO('DataObjectTombstoneDAO');
			$tombstoneDao->deleteByDataObjectId($submission->getId());
		} else {
			$submission->setStatus(STATUS_QUEUED);
		}

		$sectionEditorSubmissionDao->updateSectionEditorSubmission($submission);

		// Call initialize permissions again to check if copyright year needs to be initialized.
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getArticle($articleId);
		$article->initializePermissions();
		$articleDao->updateLocaleFields($article);

		$articleSearchIndex->articleChangesFinished();

		$request->redirect(null, null, 'submissionEditing', array($articleId), null, 'scheduling');
	}

	/**
	 * Set the publication date for a published article
	 * @param $args array
	 * @param $request object
	 */
	function setDatePublished($args, $request) {
		$articleId = (int) array_shift($args);
		$issueId = (int) $request->getUserVar('issueId');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$journal =& $request->getJournal();
		$submission =& $this->submission;

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($articleId);
		if ($publishedArticle) {
			$datePublished = $request->getUserDateVar('datePublished');
			$publishedArticle->setDatePublished($datePublished);
			$publishedArticleDao->updatePublishedArticle($publishedArticle);

			// Re-index the published article metadata.
			import('classes.search.ArticleSearchIndex');
			$articleSearchIndex = new ArticleSearchIndex();
			$articleSearchIndex->articleMetadataChanged($publishedArticle);
			$articleSearchIndex->articleChangesFinished();
		}
		$request->redirect(null, null, 'submissionEditing', array($articleId), null, 'scheduling');
	}

	//
	// Payments
	//

	/**
	 * Waive a submission fee.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function waiveSubmissionFee($args, $request) {
		$articleId = (int) array_shift($args);
		$markAsPaid = $request->getUserVar('markAsPaid');

		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$submission =& $this->submission;
		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$user =& $request->getUser();
		$journal =& $request->getJournal();

		$queuedPayment =& $paymentManager->createQueuedPayment(
			$journal->getId(),
			PAYMENT_TYPE_SUBMISSION,
			$markAsPaid ? $submission->getUserId() : $user->getId(),
			$articleId,
			$markAsPaid ? $journal->getSetting('submissionFee') : 0,
			$markAsPaid ? $journal->getSetting('currency') : ''
		);

		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

		// Since this is a waiver, fulfill the payment immediately
		$paymentManager->fulfillQueuedPayment($queuedPayment, $markAsPaid?'ManualPayment':'Waiver');
		$request->redirect(null, null, 'submission', array($articleId));
	}

	/**
	 * Waive the fast track fee.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function waiveFastTrackFee($args, &$request) {
		$articleId = (int) array_shift($args);
		$markAsPaid = $request->getUserVar('markAsPaid');
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$journal =& $request->getJournal();
		$submission =& $this->submission;

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$user =& $request->getUser();

		$queuedPayment =& $paymentManager->createQueuedPayment(
			$journal->getId(),
			PAYMENT_TYPE_FASTTRACK,
			$markAsPaid ? $submission->getUserId() : $user->getId(),
			$articleId,
			$markAsPaid ? $journal->getSetting('fastTrackFee') : 0,
			$markAsPaid ? $journal->getSetting('currency') : ''
		);

		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

		// Since this is a waiver, fulfill the payment immediately
		$paymentManager->fulfillQueuedPayment($queuedPayment, $markAsPaid?'ManualPayment':'Waiver');
		$request->redirect(null, null, 'submission', array($articleId));
	}

	/**
	 * Waive the publication fee.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function waivePublicationFee($args, $request) {
		$articleId = (int) array_shift($args);
		$markAsPaid = $request->getUserVar('markAsPaid');
		$sendToScheduling = $request->getUserVar('sendToScheduling')?true:false;

		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);
		$journal =& Request::getJournal();
		$submission =& $this->submission;

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$user =& $request->getUser();

		$queuedPayment =& $paymentManager->createQueuedPayment(
			$journal->getId(),
			PAYMENT_TYPE_PUBLICATION,
			$markAsPaid ? $submission->getUserId() : $user->getId(),
			$articleId,
			$markAsPaid ? $journal->getSetting('publicationFee') : 0,
			$markAsPaid ? $journal->getSetting('currency') : ''
		);

		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

		// Since this is a waiver, fulfill the payment immediately
		$paymentManager->fulfillQueuedPayment($queuedPayment, $markAsPaid?'ManualPayment':'Waiver');

		if ($sendToScheduling) {
			$request->redirect(null, null, 'submissionEditing', array($articleId), null, 'scheduling');
		} else {
			$request->redirect(null, null, 'submission', array($articleId));
		}
	}

	/**
	 * Download a layout template.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function downloadLayoutTemplate($args, &$request) {
		$articleId = (int) array_shift($args);
		$this->validate($articleId, SECTION_EDITOR_ACCESS_EDIT);

		$journal =& $request->getJournal();
		$templates = $journal->getSetting('templates');
		import('classes.file.JournalFileManager');
		$journalFileManager = new JournalFileManager($journal);
		$templateId = (int) array_shift($args);
		if ($templateId >= count($templates) || $templateId < 0) $request->redirect(null, 'index');
		$template =& $templates[$templateId];

		$filename = "template-$templateId." . $journalFileManager->parseFileExtension($template['originalFilename']);
		$journalFileManager->downloadFile($filename, $template['fileType']);
	}
}

?>
