<?php

/**
 * ReviewReminder.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Class to perform automated reminders for reviewers.
 *
 * $Id$
 */

import('scheduledTask.ScheduledTask');

class ReviewReminder extends ScheduledTask {

	/**
	 * Constructor.
	 */
	function ReviewReminder() {
		$this->ScheduledTask();
	}

	function sendReminder ($reviewAssignment, $article, $journal) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');

		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return false;

		import('mail.ArticleMailTemplate');
		$email = &new ArticleMailTemplate($article, 'REVIEW_REMIND_AUTO');
		$email->setJournal($journal);
		$email->setFrom($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
		$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
		$email->setAssoc(ARTICLE_EMAIL_REVIEW_REMIND, ARTICLE_EMAIL_TYPE_REVIEW, $reviewAssignment->getReviewId());

		$email->setSubject($email->getSubject($journal->getLocale()));
		$email->setBody($email->getBody($journal->getLocale()));

		$paramArray = array(
			'reviewerName' => $reviewer->getFullName(),
			'reviewerUsername' => $reviewer->getUsername(),
			'journalUrl' => $journal->getSetting('journalUrl'),
			'reviewerPassword' => $reviewer->getPassword(),
			'reviewDueDate' => date('Y-m-d', strtotime($reviewAssignment->getDateDue())),
			'editorialContactSignature' => $journal->getSetting('contactName') . "\n" . $journal->getTitle(),
			'passwordResetUrl' => sprintf('%s/login/resetPassword/%s?confirm=%s', $journal->getSetting('journalUrl'), $reviewer->getUsername(), Validation::generatePasswordResetHash($reviewer->getUserId())),
			'submissionReviewUrl' => $journal->getSetting('journalUrl') . '/reviewer/submission/' . $reviewAssignment->getReviewId()
		);
		$email->assignParams($paramArray);

		$email->send();

		$reviewAssignment->setDateReminded(Core::getCurrentDate());
		$reviewAssignment->setReminderWasAutomatic(1);
		$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

	}

	function execute() {
		$article = null;
		$journal = null;

		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$journalDao = &DAORegistry::getDAO('JournalDAO');

		$incompleteAssignments = &$reviewAssignmentDao->getIncompleteReviewAssignments();
		foreach ($incompleteAssignments as $reviewAssignment) {
			// Fetch the Article and the Journal if necessary.
			if ($article == null || $article->getArticleId() != $reviewAssignment->getArticleId()) {
				$article = &$articleDao->getArticle($reviewAssignment->getArticleId());
				if ($journal == null || $journal->getJournalId() != $article->getJournalId()) {
					$journal = &$journalDao->getJournal($article->getJournalId());

					$inviteReminderEnabled = $journal->getSetting('remindForInvite');
					$submitReminderEnabled = $journal->getSetting('remindForSubmit');
					$inviteReminderDays = $journal->getSetting('numDaysBeforeInviteReminder');
					$submitReminderDays = $journal->getSetting('numDaysBeforeSubmitReminder');
				}
			}

			// $article, $journal, $...ReminderEnabled, $...ReminderDays, and $reviewAssignment
			// are initialized by this point.
			$shouldRemind = false;
			if ($inviteReminderEnabled==1 && $reviewAssignment->getDateConfirmed() == null) {
				if ($reviewAssignment->getDateReminded() != null) {
					$checkDate = strtotime($reviewAssignment->getDateReminded());
				} else {
					$checkDate = strtotime($reviewAssignment->getDateNotified());
				}
				if (time() - $checkDate > 60 * 60 * 24 * $inviteReminderDays) {
					$shouldRemind = true;
				}
			}
			if ($submitReminderEnabled==1 && $reviewAssignment->getDateDue() != null) {
				if ($reviewAssignment->getDateReminded() != null) {
					$remindedDate = strtotime($reviewAssignment->getDateReminded());
				}
				$dueDate = strtotime($reviewAssignment->getDateDue());
				$checkDate = isset($remindedDate)?max($remindedDate, $dueDate):$dueDate;
				if (time() - $checkDate > 60 * 60 * 24 * $submitReminderDays) {
					$shouldRemind = true;
				}
			}

			if ($shouldRemind) $this->sendReminder ($reviewAssignment, $article, $journal);
		}
	}
}

?>
