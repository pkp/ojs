<?php

/**
 * @file classes/tasks/ReviewReminder.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewReminder
 * @ingroup tasks
 *
 * @brief Class to perform automated reminders for reviewers.
 */

import('lib.pkp.classes.scheduledTask.ScheduledTask');

class ReviewReminder extends ScheduledTask {

	/**
	 * Constructor.
	 */
	function ReviewReminder() {
		$this->ScheduledTask();
	}

	function sendReminder ($reviewAssignment, $submission, $context) {
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = DAORegistry::getDAO('UserDAO');
		$reviewId = $reviewAssignment->getId();

		$reviewer = $userDao->getById($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return false;

		import('classes.mail.ArticleMailTemplate');

		$reviewerAccessKeysEnabled = $context->getSetting('reviewerAccessKeysEnabled');

		$email = new ArticleMailTemplate($submission, $reviewerAccessKeysEnabled?'REVIEW_REMIND_AUTO_ONECLICK':'REVIEW_REMIND_AUTO', $context->getPrimaryLocale(), false, $context);
		$email->setContext($context);
		$email->setReplyTo(null);
		$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
		$email->setSubject($email->getSubject($context->getPrimaryLocale()));
		$email->setBody($email->getBody($context->getPrimaryLocale()));

		$urlParams = array();
		if ($reviewerAccessKeysEnabled) {
			import('lib.pkp.classes.security.AccessKeyManager');
			$accessKeyManager = new AccessKeyManager();

			// Key lifetime is the typical review period plus four weeks
			$keyLifetime = ($context->getSetting('numWeeksPerReview') + 4) * 7;
			$urlParams['key'] = $accessKeyManager->createKey('ReviewerContext', $reviewer->getId(), $reviewId, $keyLifetime);
		}
		$submissionReviewUrl = Request::url($context->getPath(), 'reviewer', 'submission', $reviewId, $urlParams);

		// Format the review due date
		$reviewDueDate = strtotime($reviewAssignment->getDateDue());
		$dateFormatShort = Config::getVar('general', 'date_format_short');
		if ($reviewDueDate === -1 || $reviewDueDate === false) {
			// Default to something human-readable if no date specified
			$reviewDueDate = '_____';
		} else {
			$reviewDueDate = strftime($dateFormatShort, $reviewDueDate);
		}

		$paramArray = array(
			'reviewerName' => $reviewer->getFullName(),
			'reviewerUsername' => $reviewer->getUsername(),
			'contextUrl' => Request::url($context->getPath()),
			'reviewerPassword' => $reviewer->getPassword(),
			'reviewDueDate' => $reviewDueDate,
			'weekLaterDate' => strftime(Config::getVar('general', 'date_format_short'), strtotime('+1 week')),
			'editorialContactSignature' => $context->getSetting('contactName') . "\n" . $context->getLocalizedName(),
			'passwordResetUrl' => Request::url($context->getPath(), 'login', 'resetPassword', $reviewer->getUsername(), array('confirm' => Validation::generatePasswordResetHash($reviewer->getId()))),
			'submissionReviewUrl' => $submissionReviewUrl
		);
		$email->assignParams($paramArray);

		$email->send();

		$reviewAssignment->setDateReminded(Core::getCurrentDate());
		$reviewAssignment->setReminderWasAutomatic(1);
		$reviewAssignmentDao->updateObject($reviewAssignment);

	}

	function execute() {
		$submission = null;
		$context = null;

		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$submissionDao = Application::getSubmissionDAO();
		$contextDao = Application::getContextDAO();

		$incompleteAssignments = $reviewAssignmentDao->getIncompleteReviewAssignments();
		foreach ($incompleteAssignments as $reviewAssignment) {
			// Fetch the submission and the context if necessary.
			if ($submission == null || $submission->getId() != $reviewAssignment->getSubmissionId()) {
				$submission = $submissionDao->getById($reviewAssignment->getSubmissionId());
				if ($context == null || $context->getId() != $submission->getContextId()) {
					$context = $contextDao->getById($submission->getContextId());

					$inviteReminderDays = $context->getSetting('numDaysBeforeInviteReminder');
					$submitReminderDays = $context->getSetting('numDaysBeforeSubmitReminder');
				}
			}

			if ($submission->getStatus() != STATUS_QUEUED) continue;

			// $submission, $context, $...ReminderDays, and $reviewAssignment
			// are initialized by this point.
			$shouldRemind = false;
			if ($inviteReminderDays && $reviewAssignment->getDateConfirmed() == null) {
				$checkDate = strtotime($reviewAssignment->getDateNotified());
				if (time() - $checkDate > 60 * 60 * 24 * $inviteReminderDays) {
					$shouldRemind = true;
				}
			}
			if ($submitReminderDays && $reviewAssignment->getDateDue() != null) {
				$checkDate = strtotime($reviewAssignment->getDateDue());
				if (time() - $checkDate > 60 * 60 * 24 * $submitReminderDays) {
					$shouldRemind = true;
				}
			}

			if ($reviewAssignment->getDateReminded() !== null) {
				$shouldRemind = false;
			}

			if ($shouldRemind) $this->sendReminder ($reviewAssignment, $submission, $context);
		}
	}
}

?>
