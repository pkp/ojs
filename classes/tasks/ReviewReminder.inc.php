<?php

/**
 * @file classes/tasks/ReviewReminder.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewReminder
 * @ingroup tasks
 *
 * @brief Class to perform automated reminders for reviewers.
 */

// $Id$


import('lib.pkp.classes.scheduledTask.ScheduledTask');

class ReviewReminder extends ScheduledTask {

	/**
	 * Constructor.
	 */
	function ReviewReminder() {
		$this->ScheduledTask();
	}

	function sendReminder ($reviewAssignment, $article, $journal) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$reviewId = $reviewAssignment->getId();

		$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return false;

		import('classes.mail.ArticleMailTemplate');

		$reviewerAccessKeysEnabled = $journal->getSetting('reviewerAccessKeysEnabled');

		$email = new ArticleMailTemplate($article, $reviewerAccessKeysEnabled?'REVIEW_REMIND_AUTO_ONECLICK':'REVIEW_REMIND_AUTO', null, false, $journal);
		$email->setJournal($journal);
		$email->setFrom($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
		$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
		$email->setAssoc(ARTICLE_EMAIL_REVIEW_REMIND, ARTICLE_EMAIL_TYPE_REVIEW, $reviewId);

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

		//20111104 BLH Changing URL to point to subi password reset page.
		$passwordResetUrl = 'http://submit.escholarship.org/subi/forgotPassword';
		
		// Format the review due date
		$reviewDueDate = strtotime($reviewAssignment->getDateDue());
		$dateFormatShort = Config::getVar('general', 'date_format_short');
		if ($reviewDueDate == -1) $reviewDueDate = $dateFormatShort; // Default to something human-readable if no date specified
		else $reviewDueDate = strftime($dateFormatShort, $reviewDueDate);
		
		$paramArray = array(
			'reviewerName' => $reviewer->getFullName(),
			'reviewerUsername' => $reviewer->getUsername(),
			'journalUrl' => $journal->getUrl(),
			'reviewerPassword' => $reviewer->getPassword(),
			'reviewDueDate' => $reviewDueDate,
			'weekLaterDate' => strftime(Config::getVar('general', 'date_format_short'), strtotime('+1 week')),
			'editorialContactSignature' => $journal->getSetting('contactName') . "\n" . $journal->getLocalizedTitle(),
			//'passwordResetUrl' => Request::url($journal->getPath(), 'login', 'resetPassword', $reviewer->getUsername(), array('confirm' => Validation::generatePasswordResetHash($reviewer->getId()))),
			'passwordResetUrl' => $passwordResetUrl,
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

		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');

		$incompleteAssignments =& $reviewAssignmentDao->getIncompleteReviewAssignments();
		foreach ($incompleteAssignments as $reviewAssignment) {
			// Fetch the Article and the Journal if necessary.
			if ($article == null || $article->getId() != $reviewAssignment->getSubmissionId()) {
				unset($article);
				$article =& $articleDao->getArticle($reviewAssignment->getSubmissionId());
				if ($journal == null || $journal->getId() != $article->getJournalId()) {
					unset($journal);
					$journal =& $journalDao->getJournal($article->getJournalId());

					$inviteReminderEnabled = $journal->getSetting('remindForInvite');
					$submitReminderEnabled = $journal->getSetting('remindForSubmit');
					$inviteReminderDays = $journal->getSetting('numDaysBeforeInviteReminder');
					$submitReminderDays = $journal->getSetting('numDaysBeforeSubmitReminder');
				}
			}

			if ($article->getStatus() != STATUS_QUEUED) continue;

			// $article, $journal, $...ReminderEnabled, $...ReminderDays, and $reviewAssignment
			// are initialized by this point.
			
			// 20111026 BLH Cleaned up and refined code. Needed to fix bugs and make sure emails weren't sent for 
			// 				articles entered in bepress rather than OJS.
			/***
			$shouldRemind = false;
			if ($inviteReminderEnabled==1 && $reviewAssignment->getDateConfirmed() == null) {
				$checkDate = strtotime($reviewAssignment->getDateNotified());
				if (time() - $checkDate > 60 * 60 * 24 * $inviteReminderDays) {
					$shouldRemind = true;
				}
			}
			//if ($submitReminderEnabled==1 && $reviewAssignment->getDateDue() != null) {
			if ($submitReminderEnabled==1 && $reviewAssignment->getDateDue() !== null) { // 20111026 BLH changed '!=' to '!=='
				$checkDate = strtotime($reviewAssignment->getDateDue());
				if (time() - $checkDate > 60 * 60 * 24 * $submitReminderDays) {
					$shouldRemind = true;
				}
			}

			if ($reviewAssignment->getDateReminded() !== null) {
				$shouldRemind = false;
			}
			***/
			
			$shouldRemind = 0;
			$inviteReminderDaysInt = 60 * 60 * 24 * $inviteReminderDays;
			$submitReminderDaysInt = 60 * 60 * 24 * $submitReminderDays;
			$journalId = $journal->getJournalId();
			
			//////////
			// 2011-11-03 BLH For eSchol only: Get values for determining whether or not reminder was already sent in EdiKit
			//////////
			$escholTransitionDate = '2011-09-12 00:00:00'; // transition #1, default
			if($journalId >= 4 && $journalId <= 21) {
				$escholTransitionDate = '2011-10-10 00:00:00'; // transition #2
			} elseif($journalId >= 22) {
				$escholTransitionDate = '2011-11-14 00:00:00'; // transition #3
			}
			$escholTransitionDate = strtotime($escholTransitionDate);
			
			if($reviewAssignment->getDateReminded() == '' && $reviewAssignment->getDateDue() != '') {			
				if($inviteReminderEnabled && $reviewAssignment->getDateConfirmed() == '') {
					$checkDate = strtotime($reviewAssignment->getDateNotified());
					if ((time() - $checkDate > $inviteReminderDaysInt) && ($escholTransitionDate - $checkDate <= $inviteReminderDaysInt)) {
						$shouldRemind = 1;		
					}
				}
				
				if($submitReminderEnabled && $reviewAssignment->getDateDue() != '') {
					$checkDate = strtotime($reviewAssignment->getDateDue());
					if ((time() - $checkDate > $submitReminderDaysInt) && ($escholTransitionDate - $checkDate <= $submitReminderDaysInt)) {
						$shouldRemind = 1;
					}					
				}
			}

			if ($shouldRemind) $this->sendReminder ($reviewAssignment, $article, $journal);
		}
	}
}

?>
