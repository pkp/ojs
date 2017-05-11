<?php

/**
 * @file classes/task/ReviewReminder.inc.php
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewReminder
 * @ingroup tasks
 *
 * @brief Class to perform automated reminders for reviewers.
 */

import('lib.pkp.classes.scheduledTask.ScheduledTask');

define('REVIEW_REMIND_AUTO', 'REVIEW_REMIND_AUTO');
define('REVIEW_REQUEST_REMIND_AUTO', 'REVIEW_REQUEST_REMIND_AUTO');

class ReviewReminder extends ScheduledTask {

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * @copydoc ScheduledTask::getName()
	 */
	function getName() {
		return __('admin.scheduledTask.reviewReminder');
	}

	/**
	 * Send the automatic review reminder to the reviewer.
	 * @param $reviewAssignment ReviewAssignment
	 * @param $submission Submission
	 * @param $context Context
	 * @param $reminderType string
	 * 	REVIEW_REMIND_AUTO, REVIEW_REQUEST_REMIND_AUTO
	 */
	function sendReminder ($reviewAssignment, $submission, $context, $reminderType = REVIEW_REMIND_AUTO) {
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = DAORegistry::getDAO('UserDAO');
		$reviewId = $reviewAssignment->getId();

		$reviewer = $userDao->getById($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return false;

		import('lib.pkp.classes.mail.SubmissionMailTemplate');
		$emailKey = $reminderType;
		$reviewerAccessKeysEnabled = $context->getSetting('reviewerAccessKeysEnabled');
		switch (true) {
			case $reviewerAccessKeysEnabled && ($reminderType == REVIEW_REMIND_AUTO):
				$emailKey = 'REVIEW_REMIND_AUTO_ONECLICK';
				break;
			case $reviewerAccessKeysEnabled && ($reminderType == REVIEW_REQUEST_REMIND_AUTO):
				$emailKey = 'REVIEW_REQUEST_REMIND_AUTO_ONECLICK';
				break;
		}
		$email = new SubmissionMailTemplate($submission, $emailKey, $context->getPrimaryLocale(), $context, false);
		$email->setContext($context);
		$email->setReplyTo(null);
		$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
		$email->setSubject($email->getSubject($context->getPrimaryLocale()));
		$email->setBody($email->getBody($context->getPrimaryLocale()));

		$urlParams = array(
			'submissionId' => $reviewAssignment->getSubmissionId(),
		);
		if ($reviewerAccessKeysEnabled) {
			import('lib.pkp.classes.security.AccessKeyManager');
			$accessKeyManager = new AccessKeyManager();

			// Key lifetime is the typical review period plus four weeks
			$keyLifetime = ($context->getSetting('numWeeksPerReview') + 4) * 7;
			$urlParams['key'] = $accessKeyManager->createKey('ReviewerContext', $reviewer->getId(), $reviewId, $keyLifetime);
		}
		$application = PKPApplication::getApplication();
		$request = $application->getRequest();
		$dispatcher = $application->getDispatcher();
		$submissionReviewUrl = $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'reviewer', 'submission', null, $urlParams);

		// Format the review due date
		$reviewDueDate = strtotime($reviewAssignment->getDateDue());
		$dateFormatShort = Config::getVar('general', 'date_format_short');
		if ($reviewDueDate === -1 || $reviewDueDate === false) {
			// Default to something human-readable if no date specified
			$reviewDueDate = '_____';
		} else {
			$reviewDueDate = strftime($dateFormatShort, $reviewDueDate);
		}
		// Format the review response due date
		$responseDueDate = strtotime($reviewAssignment->getDateResponseDue());
		if ($responseDueDate === -1 || $responseDueDate === false) {
			// Default to something human-readable if no date specified
			$responseDueDate = '_____';
		} else {
			$responseDueDate = strftime($dateFormatShort, $responseDueDate);
		}

		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_REVIEWER);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_COMMON);
		$paramArray = array(
			'reviewerName' => $reviewer->getFullName(),
			'reviewerUserName' => $reviewer->getUsername(),
			'reviewDueDate' => $reviewDueDate,
			'responseDueDate' => $responseDueDate,
			'editorialContactSignature' => $context->getSetting('contactName') . "\n" . $context->getLocalizedName(),
			'passwordResetUrl' => $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'login', 'resetPassword', $reviewer->getUsername(), array('confirm' => Validation::generatePasswordResetHash($reviewer->getId()))),
			'submissionReviewUrl' => $submissionReviewUrl,
			'messageToReviewer' => __('reviewer.step1.requestBoilerplate'),
			'abstractTermIfEnabled' => ($submission->getLocalizedAbstract() == '' ? '' : __('common.abstract')),
		);
		$email->assignParams($paramArray);

		$email->send();

		$reviewAssignment->setDateReminded(Core::getCurrentDate());
		$reviewAssignment->setReminderWasAutomatic(1);
		$reviewAssignmentDao->updateObject($reviewAssignment);

	}

	/**
	 * @copydoc ScheduledTask::executeActions()
	 */
	function executeActions() {
		$submission = null;
		$context = null;

		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$submissionDao = Application::getSubmissionDAO();
		$contextDao = Application::getContextDAO();

		$incompleteAssignments = $reviewAssignmentDao->getIncompleteReviewAssignments();
		$inviteReminderDays = $submitReminderDays = null;
		foreach ($incompleteAssignments as $reviewAssignment) {
			// Avoid review assignments that a reminder exists for.
			if ($reviewAssignment->getDateReminded() !== null) continue;

			// Fetch the submission and the context.
			if ($submission == null || $submission->getId() != $reviewAssignment->getSubmissionId()) {
				unset($submission);
				$submission = $submissionDao->getById($reviewAssignment->getSubmissionId());
				// Avoid review assignments without submission in database.
				if (!$submission) continue;

				if ($submission->getStatus() != STATUS_QUEUED) continue;

				if ($context == null || $context->getId() != $submission->getContextId()) {
					unset($context);
					$context = $contextDao->getById($submission->getContextId());

					$inviteReminderDays = $context->getSetting('numDaysBeforeInviteReminder');
					$submitReminderDays = $context->getSetting('numDaysBeforeSubmitReminder');
				}
			}

			$reminderType = false;
			if ($submitReminderDays>=1 && $reviewAssignment->getDateDue() != null) {
				$checkDate = strtotime($reviewAssignment->getDateDue());
				if (time() - $checkDate > 60 * 60 * 24 * $submitReminderDays) {
					$reminderType = REVIEW_REMIND_AUTO;
				}
			}
			if ($inviteReminderDays>=1 && $reviewAssignment->getDateConfirmed() == null) {
				$checkDate = strtotime($reviewAssignment->getDateResponseDue());
				if (time() - $checkDate > 60 * 60 * 24 * $inviteReminderDays) {
					$reminderType = REVIEW_REQUEST_REMIND_AUTO;
				}
			}

			if ($reminderType) $this->sendReminder ($reviewAssignment, $submission, $context, $reminderType);
		}

		return true;
	}
}

?>
