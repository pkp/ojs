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
		$reviewId = $reviewAssignment->getReviewId();

		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return false;

		import('mail.ArticleMailTemplate');

		$reviewerAccessKeysEnabled = $journal->getSetting('reviewerAccessKeysEnabled');

		$email = &new ArticleMailTemplate($article, $reviewerAccessKeysEnabled?'REVIEW_REMIND_AUTO_ONECLICK':'REVIEW_REMIND_AUTO', null, false, $journal);
		$email->setJournal($journal);
		$email->setFrom($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
		$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
		$email->setAssoc(ARTICLE_EMAIL_REVIEW_REMIND, ARTICLE_EMAIL_TYPE_REVIEW, $reviewId);

		$email->setSubject($email->getSubject($journal->getLocale()));
		$email->setBody($email->getBody($journal->getLocale()));

		$urlParams = array();
		if ($reviewerAccessKeysEnabled) {
			import('security.AccessKeyManager');
			$accessKeyManager =& new AccessKeyManager();

			// Key lifetime is the typical review period plus four weeks
			$keyLifetime = ($journal->getSetting('numWeeksPerReview') + 4) * 7;
			$urlParams['key'] = $accessKeyManager->createKey('ReviewerContext', $reviewer->getUserId(), $reviewId, $keyLifetime);
		}
		$submissionReviewUrl = Request::url($journal->getPath(), 'reviewer', 'submission', $reviewId, $urlParams);

		$paramArray = array(
			'reviewerName' => $reviewer->getFullName(),
			'reviewerUsername' => $reviewer->getUsername(),
			'journalUrl' => $journal->getUrl(),
			'reviewerPassword' => $reviewer->getPassword(),
			'reviewDueDate' => date('Y-m-d', strtotime($reviewAssignment->getDateDue())),
			'editorialContactSignature' => $journal->getSetting('contactName') . "\n" . $journal->getTitle(),
			'passwordResetUrl' => Request::url($journal->getPath(), 'login', 'resetPassword', $reviewer->getUsername(), array('confirm' => Validation::generatePasswordResetHash($reviewer->getUserId()))),
			'submissionReviewUrl' => $submissionReviewUrl
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
	}
}

?>
