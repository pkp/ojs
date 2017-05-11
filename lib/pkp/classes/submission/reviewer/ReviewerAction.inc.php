<?php

/**
 * @file classes/submission/reviewer/ReviewerAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerAction
 * @ingroup submission
 *
 * @brief ReviewerAction class.
 */


// Access decision actions constants.
import('classes.workflow.EditorDecisionActionsManager');

class ReviewerAction {

	/**
	 * Constructor
	 */
	function __construct() {
	}

	//
	// Actions.
	//
	/**
	 * Records whether or not the reviewer accepts the review assignment.
	 * @param $request PKPRequest
	 * @param $reviewAssignment ReviewAssignment
	 * @param $submission Submission
	 * @param $decline boolean
	 * @param $emailText string optional
	 */
	function confirmReview($request, $reviewAssignment, $submission, $decline, $emailText = null) {
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = DAORegistry::getDAO('UserDAO');

		$reviewer = $userDao->getById($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return true;

		// Only confirm the review for the reviewer if
		// he has not previously done so.
		if ($reviewAssignment->getDateConfirmed() == null) {
			$email = $this->getResponseEmail($submission, $reviewAssignment, $request, $decline);
			// Must explicitly set sender because we may be here on an access
			// key, in which case the user is not technically logged in
			$email->setReplyTo($reviewer->getEmail(), $reviewer->getFullName());
			HookRegistry::call('ReviewerAction::confirmReview', array($request, &$submission, &$email, $decline));
			import('lib.pkp.classes.log.PKPSubmissionEmailLogEntry'); // Import email event constants
			$email->setEventType($decline?SUBMISSION_EMAIL_REVIEW_DECLINE:SUBMISSION_EMAIL_REVIEW_CONFIRM);
			if ($emailText) $email->setBody($emailText);
			$email->send($request);

			$reviewAssignment->setDeclined($decline);
			$reviewAssignment->setDateConfirmed(Core::getCurrentDate());
			$reviewAssignment->stampModified();
			$reviewAssignmentDao->updateObject($reviewAssignment);

			// Add log
			import('lib.pkp.classes.log.SubmissionLog');
			import('classes.log.SubmissionEventLogEntry');

			$entry = new SubmissionEventLogEntry();
			$entry->setSubmissionId($reviewAssignment->getSubmissionId());
			$entry->setUserId($reviewer->getId());
			$entry->setDateLogged(Core::getCurrentDate());
			$entry->setEventType($decline?SUBMISSION_LOG_REVIEW_DECLINE:SUBMISSION_LOG_REVIEW_ACCEPT);

			SubmissionLog::logEvent(
				$request,
				$submission,
				$decline?SUBMISSION_LOG_REVIEW_DECLINE:SUBMISSION_LOG_REVIEW_ACCEPT,
				$decline?'log.review.reviewDeclined':'log.review.reviewAccepted',
				array(
					'reviewerName' => $reviewer->getFullName(),
					'submissionId' => $reviewAssignment->getSubmissionId(),
					'round' => $reviewAssignment->getRound()
				)
			);
		}
	}

	/**
	 * Get the reviewer response email template.
	 */
	function getResponseEmail($submission, $reviewAssignment, $request, $decline) {
		import('lib.pkp.classes.mail.SubmissionMailTemplate');
		$email = new SubmissionMailTemplate($submission, $decline?'REVIEW_DECLINE':'REVIEW_CONFIRM');

		// Get reviewer
		$userDao = DAORegistry::getDAO('UserDAO');
		$reviewer = $userDao->getById($reviewAssignment->getReviewerId());

		// Get editorial contact name
		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$userDao = DAORegistry::getDAO('UserDAO');
		$stageAssignments = $stageAssignmentDao->getBySubmissionAndStageId($submission->getId(), $reviewAssignment->getStageId());
		$recipient = null;
		while ($stageAssignment = $stageAssignments->next()) {
			$userGroup = $userGroupDao->getById($stageAssignment->getUserGroupId());
			if (!in_array($userGroup->getRoleId(), array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR))) continue;

			$recipient = $userDao->getById($stageAssignment->getUserId());
			$email->addRecipient($recipient->getEmail(), $recipient->getFullName());
		}
		if (!$recipient) {
			$context = $request->getContext();
			$email->addRecipient($context->getSetting('contactEmail'), $context->getSetting('contactName'));
		}

		// Get due date
		$reviewDueDate = strtotime($reviewAssignment->getDateDue());
		$dateFormatShort = Config::getVar('general', 'date_format_short');
		if ($reviewDueDate == -1) $reviewDueDate = $dateFormatShort; // Default to something human-readable if no date specified
		else $reviewDueDate = strftime($dateFormatShort, $reviewDueDate);

		$email->setReplyTo($reviewer->getEmail(), $reviewer->getFullName());

		$email->assignParams(array(
			'reviewerName' => $reviewer->getFullName(),
			'reviewDueDate' => $reviewDueDate
		));
		$email->replaceParams();

		return $email;
	}
}

?>
