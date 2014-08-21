<?php

/**
 * @file classes/tasks/ReviewReminder.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
		parent::ScheduledTask();
	}

	/**
	 * @see ScheduledTask::getName()
	 */
	function getName() {
		return __('admin.scheduledTask.reviewReminder');
	}

	function sendReminder ($reviewAssignment, $article, $journal) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$reviewId = $reviewAssignment->getId();

		$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return false;

		import('classes.mail.ArticleMailTemplate');

		$reviewerAccessKeysEnabled = $journal->getSetting('reviewerAccessKeysEnabled');

		$email = new ArticleMailTemplate($article, $reviewerAccessKeysEnabled?'REVIEW_REMIND_AUTO_ONECLICK':'REVIEW_REMIND_AUTO', $journal->getPrimaryLocale(), false, $journal);
		$email->setJournal($journal);
		$email->setFrom($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
		$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
		$email->setSubject($email->getSubject($journal->getPrimaryLocale()));
		$email->setBody($email->getBody($journal->getPrimaryLocale()));

		$urlParams = array();
		if ($reviewerAccessKeysEnabled) {
			import('lib.pkp.classes.security.AccessKeyManager');
			$accessKeyManager = new AccessKeyManager();

			// Key lifetime is the typical review period plus four weeks
			$keyLifetime = ($journal->getSetting('numWeeksPerReview') + 4) * 7;
			$urlParams['key'] = $accessKeyManager->createKey('ReviewerContext', $reviewer->getId(), $reviewId, $keyLifetime);
		}
		$submissionReviewUrl = Request::url($journal->getPath(), 'reviewer', 'submission', $reviewId, $urlParams);

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
			'journalUrl' => $journal->getUrl(),
			'reviewerPassword' => $reviewer->getPassword(),
			'reviewDueDate' => $reviewDueDate,
			'weekLaterDate' => strftime(Config::getVar('general', 'date_format_short'), strtotime('+1 week')),
			'editorialContactSignature' => $journal->getSetting('contactName') . "\n" . $journal->getLocalizedTitle(),
			'passwordResetUrl' => Request::url($journal->getPath(), 'login', 'resetPassword', $reviewer->getUsername(), array('confirm' => Validation::generatePasswordResetHash($reviewer->getId()))),
			'submissionReviewUrl' => $submissionReviewUrl
		);
		$email->assignParams($paramArray);

		$email->send();

		$reviewAssignment->setDateReminded(Core::getCurrentDate());
		$reviewAssignment->setReminderWasAutomatic(1);
		$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

	}

	/**
	 * @see ScheduledTask::executeActions()
	 */
	function executeActions() {
		$article = null;
		$journal = null;

		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');

		$incompleteAssignments =& $reviewAssignmentDao->getIncompleteReviewAssignments();
		foreach ($incompleteAssignments as $reviewAssignment) {
			// Fetch the Article and the Journal if necessary.
			if ($article == null || $article->getId() != $reviewAssignment->getSubmissionId()) {
				unset($article);
				$article =& $articleDao->getArticle($reviewAssignment->getSubmissionId());
				// Avoid review assignments without article in database anymore.
				if (!$article) continue;

				if ($journal == null || $journal->getId() != $article->getJournalId()) {
					unset($journal);
					$journal =& $journalDao->getById($article->getJournalId());

					$inviteReminderEnabled = $journal->getSetting('remindForInvite');
					$submitReminderEnabled = $journal->getSetting('remindForSubmit');
					$inviteReminderDays = $journal->getSetting('numDaysBeforeInviteReminder');
					$submitReminderDays = $journal->getSetting('numDaysBeforeSubmitReminder');
				}
			}

			if ($article->getStatus() != STATUS_QUEUED) continue;

			// $article, $journal, $...ReminderEnabled, $...ReminderDays, and $reviewAssignment
			// are initialized by this point.
			$shouldRemind = false;
			if ($inviteReminderEnabled==1 && $reviewAssignment->getDateConfirmed() == null) {
				$checkDate = strtotime($reviewAssignment->getDateNotified());
				if (time() - $checkDate > 60 * 60 * 24 * $inviteReminderDays) {
					$shouldRemind = true;
				}
			}
			if ($submitReminderEnabled==1 && $reviewAssignment->getDateDue() != null) {
				$checkDate = strtotime($reviewAssignment->getDateDue());
				if (time() - $checkDate > 60 * 60 * 24 * $submitReminderDays) {
					$shouldRemind = true;
				}
			}

			if ($reviewAssignment->getDateReminded() !== null) {
				$shouldRemind = false;
			}

			if ($shouldRemind) $this->sendReminder ($reviewAssignment, $article, $journal);
		}

		return true;
	}
}

?>
