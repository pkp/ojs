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

class ReviewReminder extends ScheduledTask {

	/**
	 * Constructor.
	 */
	function ReviewReminder() {
		$this->ScheduledTask();
	}

	function sendReminder ($reviewAssignment, $article, $journal) {
		$userDao = &DAORegistry::getDAO('UserDAO');

		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());

		$email = &new ArticleMailTemplate($article->getArticleId(), 'SUBMISSION_REVIEW_REM_AUTO');
		$email->setFrom($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
		$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
		$email->setAssoc(ARTICLE_EMAIL_REVIEW_REMIND, ARTICLE_EMAIL_TYPE_REVIEW, $reviewAssignment->getReviewId());

		$email->setSubject($email->getSubject($journal->getLocale()));
		$email->setBody($email->getBody($journal->getLocale()));

		$paramArray = array(
			'reviewerName' => $reviewer->getFullName(),
			'journalUrl' => 'FIXME',
			'articleTitle' => $article->getTitle(),
			'sectionName' => $article->getSectionTitle(),
			'reviewerUsername' => $reviewer->getUsername(),
			'reviewerPassword' => $reviewer->getPassword(),
			'reviewDueDate' => $reviewAssignment->getDateDue(),
			'editorialContactSignature' => $journal->getSetting('contactName') . "\n" . $journal->getSetting('journalTitle')
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

					$reminderEnabled = $journal->getSetting('remindForSubmit');
					$reminderDays = $journal->getSetting('numDaysBeforeSubmitReminder');
				}
			}

			// $article, $journal, $reminderEnabled, $reminderDays, and $reviewAssignment
			// are initialized by this point.

			if ($reminderEnabled==1 && $reminderDays>=1) {
				// Reminders are enabled for this journal. Check if one is necessary.
				if ($reviewAssignment->getDateReminded() != null) {
					$checkDate = strtotime($reviewAssignment->getDateReminded());
				} else {
					$checkDate = strtotime($reviewAssignment->getDateNotified());
				}
				if (time() - $checkDate > 60 * 60 * 24 * $reminderDays) {
					// This reviewAssignment is due for a reminder.
					$this->sendReminder ($reviewAssignment, $article, $journal);
				}
			}
		}
	}
}

?>
