<?php

/**
 * @file classes/submission/action/EditorAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditorAction
 * @ingroup submission_action
 *
 * @brief Editor actions.
 */

// Access decision actions constants.
import('classes.workflow.EditorDecisionActionsManager');

class EditorAction {
	/**
	 * Constructor.
	 */
	function __construct() {
	}

	//
	// Actions.
	//
	/**
	 * Records an editor's submission decision.
	 * @param $request PKPRequest
	 * @param $submission Submission
	 * @param $decision integer
	 * @param $decisionLabels array(DECISION_CONSTANT => decision.locale.key, ...)
	 * @param $reviewRound ReviewRound Current review round that user is taking the decision, if any.
	 * @param $stageId int
	 */
	function recordDecision($request, $submission, $decision, $decisionLabels, $reviewRound = null, $stageId = null) {
		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');

		// Define the stage and round data.
		if (!is_null($reviewRound)) {
			$stageId = $reviewRound->getStageId();
		} else {
			if ($stageId == null) {
				// We consider that the decision is being made for the current
				// submission stage.
				$stageId = $submission->getStageId();
			}
		}

		$editorAssigned = $stageAssignmentDao->editorAssignedToStage(
			$submission->getId(),
			$stageId
		);

		// Sanity checks
		if (!$editorAssigned || !isset($decisionLabels[$decision])) return false;

		$user = $request->getUser();
		$editorDecision = array(
			'editDecisionId' => null,
			'editorId' => $user->getId(),
			'decision' => $decision,
			'dateDecided' => date(Core::getCurrentDate())
		);

		$result = $editorDecision;
		if (!HookRegistry::call('EditorAction::recordDecision', array(&$submission, &$editorDecision, &$result))) {
			// Record the new decision
			$editDecisionDao = DAORegistry::getDAO('EditDecisionDAO');
			$editDecisionDao->updateEditorDecision($submission->getId(), $editorDecision, $stageId, $reviewRound);

			// Set a new submission status if necessary
			if ($decision == SUBMISSION_EDITOR_DECISION_DECLINE) {
				$submission->setStatus(STATUS_DECLINED);
			} elseif ($submission->getStatus() == STATUS_DECLINED) {
				$submission->setStatus(STATUS_QUEUED);
			}

			// Stamp the submission modified
			$submission->stampStatusModified();
			$submissionDao = Application::getSubmissionDAO();
			$submissionDao->updateObject($submission);

			// Add log entry
			import('lib.pkp.classes.log.SubmissionLog');
			import('lib.pkp.classes.log.PKPSubmissionEventLogEntry');
			AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_APP_EDITOR);
			SubmissionLog::logEvent($request, $submission, SUBMISSION_LOG_EDITOR_DECISION, 'log.editor.decision', array('editorName' => $user->getFullName(), 'submissionId' => $submission->getId(), 'decision' => __($decisionLabels[$decision])));
		}
		return $result;
	}

	/**
	 * Clears a review assignment from a submission.
	 * @param $request PKPRequest
	 * @param $submission object
	 * @param $reviewId int
	 */
	function clearReview($request, $submissionId, $reviewId) {
		$submissionDao = Application::getSubmissionDAO();
		$submission = $submissionDao->getById($submissionId);
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = DAORegistry::getDAO('UserDAO');

		$reviewAssignment = $reviewAssignmentDao->getById($reviewId);

		if (isset($reviewAssignment) && $reviewAssignment->getSubmissionId() == $submission->getId() && !HookRegistry::call('EditorAction::clearReview', array(&$submission, $reviewAssignment))) {
			$reviewer = $userDao->getById($reviewAssignment->getReviewerId());
			if (!isset($reviewer)) return false;
			$reviewAssignmentDao->deleteById($reviewId);

			// Stamp the modification date
			$submission->stampModified();
			$submissionDao->updateObject($submission);

			$notificationDao = DAORegistry::getDAO('NotificationDAO');
			$notificationDao->deleteByAssoc(
				ASSOC_TYPE_REVIEW_ASSIGNMENT,
				$reviewAssignment->getId(),
				$reviewAssignment->getReviewerId(),
				NOTIFICATION_TYPE_REVIEW_ASSIGNMENT
			);

			// Insert a trivial notification to indicate the reviewer was removed successfully.
			$currentUser = $request->getUser();
			$notificationMgr = new NotificationManager();
			$notificationMgr->createTrivialNotification($currentUser->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.removedReviewer')));

			// Update the review round status, if needed.
			$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
			$reviewRound = $reviewRoundDao->getById($reviewAssignment->getReviewRoundId());
			$reviewAssignments = $reviewAssignmentDao->getBySubmissionId($reviewRound->getSubmissionId(), $reviewRound->getId(), $reviewRound->getStageId());
			$reviewRoundDao->updateStatus($reviewRound, $reviewAssignments);

			$notificationMgr->updateNotification(
				$request,
				array(NOTIFICATION_TYPE_ALL_REVIEWS_IN),
				null,
				ASSOC_TYPE_REVIEW_ROUND,
				$reviewRound->getId()
			);

			// Add log
			import('lib.pkp.classes.log.SubmissionLog');
			import('classes.log.SubmissionEventLogEntry');
			SubmissionLog::logEvent($request, $submission, SUBMISSION_LOG_REVIEW_CLEAR, 'log.review.reviewCleared', array('reviewerName' => $reviewer->getFullName(), 'submissionId' => $submission->getId(), 'stageId' => $reviewAssignment->getStageId(), 'round' => $reviewAssignment->getRound()));

			return true;
		} else return false;
	}

	/**
	 * Assigns a reviewer to a submission.
	 * @param $request PKPRequest
	 * @param $submission object
	 * @param $reviewerId int
	 * @param $reviewRound ReviewRound
	 * @param $reviewDueDate datetime optional
	 * @param $responseDueDate datetime optional
	 */
	function addReviewer($request, $submission, $reviewerId, &$reviewRound, $reviewDueDate = null, $responseDueDate = null, $reviewMethod = null) {
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = DAORegistry::getDAO('UserDAO');

		$reviewer = $userDao->getById($reviewerId);

		// Check to see if the requested reviewer is not already
		// assigned to review this submission.

		$assigned = $reviewAssignmentDao->reviewerExists($reviewRound->getId(), $reviewerId);

		// Only add the reviewer if he has not already
		// been assigned to review this submission.
		$stageId = $reviewRound->getStageId();
		$round = $reviewRound->getRound();
		if (!$assigned && isset($reviewer) && !HookRegistry::call('EditorAction::addReviewer', array(&$submission, $reviewerId))) {
			$reviewAssignment = new ReviewAssignment();
			$reviewAssignment->setSubmissionId($submission->getId());
			$reviewAssignment->setReviewerId($reviewerId);
			$reviewAssignment->setDateAssigned(Core::getCurrentDate());
			$reviewAssignment->setStageId($stageId);
			$reviewAssignment->setRound($round);
			$reviewAssignment->setReviewRoundId($reviewRound->getId());
			if (isset($reviewMethod)) {
				$reviewAssignment->setReviewMethod($reviewMethod);
			}
			$reviewAssignmentDao->insertObject($reviewAssignment);

			// Stamp modification date
			$submission->stampStatusModified();
			$submissionDao = Application::getSubmissionDAO();
			$submissionDao->updateObject($submission);

			$this->setDueDates($request, $submission, $reviewAssignment, $reviewDueDate, $responseDueDate);

			// Add notification
			$notificationMgr = new NotificationManager();
			$notificationMgr->createNotification(
				$request,
				$reviewerId,
				NOTIFICATION_TYPE_REVIEW_ASSIGNMENT,
				$submission->getContextId(),
				ASSOC_TYPE_REVIEW_ASSIGNMENT,
				$reviewAssignment->getId(),
				NOTIFICATION_LEVEL_TASK,
				null,
				true
			);

			// Insert a trivial notification to indicate the reviewer was added successfully.
			$currentUser = $request->getUser();
			$notificationMgr = new NotificationManager();
			$notificationMgr->createTrivialNotification($currentUser->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.addedReviewer')));

			// Update the review round status.
			$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO'); /* @var $reviewRoundDao ReviewRoundDAO */
			$reviewAssignments = $reviewAssignmentDao->getBySubmissionId($submission->getId(), $reviewRound->getId(), $stageId);
			$reviewRoundDao->updateStatus($reviewRound, $reviewAssignments);

			$notificationMgr->updateNotification(
				$request,
				array(NOTIFICATION_TYPE_ALL_REVIEWS_IN),
				null,
				ASSOC_TYPE_REVIEW_ROUND,
				$reviewRound->getId()
			);

			// Add log
			import('lib.pkp.classes.log.SubmissionLog');
			import('lib.pkp.classes.log.PKPSubmissionEventLogEntry');
			SubmissionLog::logEvent($request, $submission, SUBMISSION_LOG_REVIEW_ASSIGN, 'log.review.reviewerAssigned', array('reviewerName' => $reviewer->getFullName(), 'submissionId' => $submission->getId(), 'stageId' => $stageId, 'round' => $round));
		}
	}

	/**
	 * Sets the due date for a review assignment.
	 * @param $request PKPRequest
	 * @param $submission Submission
	 * @param $reviewId int
	 * @param $dueDate string
	 * @param $numWeeks int
	 * @param $logEntry boolean
	 */
	function setDueDates($request, $submission, $reviewAssignment, $reviewDueDate = null, $responseDueDate = null, $logEntry = false) {
		$userDao = DAORegistry::getDAO('UserDAO');
		$context = $request->getContext();

		$reviewer = $userDao->getById($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return false;

		if ($reviewAssignment->getSubmissionId() == $submission->getId() && !HookRegistry::call('EditorAction::setDueDates', array(&$reviewAssignment, &$reviewer, &$reviewDueDate, &$responseDueDate))) {

			// Set the review due date
			$defaultNumWeeks = $context->getSetting('numWeeksPerReview');
			$reviewAssignment->setDateDue(DAO::formatDateToDB($reviewDueDate, $defaultNumWeeks, false));

			// Set the response due date
			$defaultNumWeeks = $context->getSetting('numWeeksPerReponse');
			$reviewAssignment->setDateResponseDue(DAO::formatDateToDB($responseDueDate, $defaultNumWeeks, false));

			// update the assignment (with both the new dates)
			$reviewAssignment->stampModified();
			$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO'); /* @var $reviewAssignmentDao ReviewAssignmentDAO */
			$reviewAssignmentDao->updateObject($reviewAssignment);

			// N.B. Only logging Date Due
			if ($logEntry) {
				// Add log
				import('lib.pkp.classes.log.SubmissionLog');
				import('classes.log.SubmissionEventLogEntry');
				SubmissionLog::logEvent(
					$request,
					$submission,
					SUBMISSION_LOG_REVIEW_SET_DUE_DATE,
					'log.review.reviewDueDateSet',
					array(
						'reviewerName' => $reviewer->getFullName(),
						'dueDate' => strftime(
							Config::getVar('general', 'date_format_short'),
							strtotime($reviewAssignment->getDateDue())
						),
						'submissionId' => $submission->getId(),
						'stageId' => $reviewAssignment->getStageId(),
						'round' => $reviewAssignment->getRound()
					)
				);
			}
		}
	}

	/**
	 * Increment a submission's workflow stage.
	 * @param $submission Submission
	 * @param $newStage integer One of the WORKFLOW_STAGE_* constants.
	 * @param $request Request
	 */
	function incrementWorkflowStage($submission, $newStage, $request) {
		// Change the submission's workflow stage.
		$submission->setStageId($newStage);
		$submissionDao = Application::getSubmissionDAO();
		$submissionDao->updateObject($submission);
	}
}

?>
