<?php

/**
 * @file plugins/generic/booksForReview/classes/BooksForReviewReminder.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BooksForReviewReminder
 * @ingroup plugins_generic_booksForReview 
 *
 * @brief Class to perform automated reminders for book reviewers.
 */

import('lib.pkp.classes.scheduledTask.ScheduledTask');

class BooksForReviewReminder extends ScheduledTask {

	/**
	 * Constructor.
	 */
	function BooksForReviewReminder() {
		$this->ScheduledTask();
	}

	/**
	 * Send email to a book for review author 
	 */
	function sendReminder($book, $journal, $emailKey) {
		$journalId = $journal->getId();

		$paramArray = array(
			'authorName' => strip_tags($book->getUserFullName()),
			'bookForReviewTitle' => '"' . strip_tags($book->getLocalizedTitle()) . '"',
			'bookForReviewDueDate' => date('l, F j, Y', strtotime($book->getDateDue())),
			'submissionUrl' => Request::url(null, 'author', 'submit'),
			'editorialContactSignature' => strip_tags($book->getEditorContactSignature())
		);

		import('classes.mail.MailTemplate');
		$mail = new MailTemplate($emailKey);

		$mail->setFrom($book->getEditorEmail(), $book->getEditorFullName());
		$mail->addRecipient($book->getUserEmail(), $book->getUserFullName());
		$mail->setSubject($mail->getSubject($journal->getPrimaryLocale()));
		$mail->setBody($mail->getBody($journal->getPrimaryLocale()));
		$mail->assignParams($paramArray);
		$mail->send();
	}

	/**
	 * Send email to a journal's book for review authors 
	 */
	function sendJournalReminders($journal, $curDate) {
		// FIXME: This shouldn't be hard-coded here
		$bfrPlugin =& PluginRegistry::getPlugin('generic', 'booksforreviewplugin');

		if ($bfrPlugin) {
			$bfrPluginName = $bfrPlugin->getName();
			$bfrPlugin->import('classes.BookForReviewDAO');
			$bfrPlugin->import('classes.BookForReviewAuthorDAO');

			// PHP4 Requires explicit instantiation-by-reference
			// FIXME: Plugin name hard-coded.
			if (checkPhpVersion('5.0.0')) {
				$bfrAuthorDao = new BookForReviewAuthorDAO('booksforreviewplugin');
			} else {
				$bfrAuthorDao =& new BookForReviewAuthorDAO('booksforreviewplugin');
			}
			$returner =& DAORegistry::registerDAO('BookForReviewAuthorDAO', $bfrAuthorDao);

			// PHP4 Requires explicit instantiation-by-reference
			// FIXME: Plugin name hard-coded.
			if (checkPhpVersion('5.0.0')) {
				$bfrDao = new BookForReviewDAO('booksforreviewplugin');
			} else {
				$bfrDao =& new BookForReviewDAO('booksforreviewplugin');
			}
			$returner =& DAORegistry::registerDAO('BookForReviewDAO', $bfrDao);

			$journalId = $journal->getId();
			$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
			$booksForReviewEnabled = $pluginSettingsDao->getSetting($journalId, $bfrPluginName, 'enabled');
		}

		if ($booksForReviewEnabled) {
			$curYear = $curDate['year'];
			$curMonth = $curDate['month'];
			$curDay = $curDate['day'];

			// Check if before review due reminders are enabled
			if ($pluginSettingsDao->getSetting($journalId, $bfrPluginName, 'enableDueReminderBefore')) {

				$beforeDays = $pluginSettingsDao->getSetting($journalId, $bfrPluginName, 'numDaysBeforeDueReminder');

				$dueMonth = $curMonth + (int)floor(($curDay+$beforeDays)/31);
				$dueYear = $curYear + (int)floor($dueMonth/12);
				$dueDay = (int)fmod($curDay+$beforeDays,31);
				$dueDate = $dueYear . '-' . $dueMonth . '-' . $dueDay;

				// Retrieve all books for review that match due date
				$books =& $bfrDao->getBooksForReviewByDateDue($journalId, $dueDate);

				while (!$books->eof()) {
					$bookForReview =& $books->next();
					$this->sendReminder($bookForReview, $journal, 'BFR_REVIEW_REMINDER');
				}
			}

			// Check if after/late review due reminders are enabled
			if ($pluginSettingsDao->getSetting($journalId, $bfrPluginName, 'enableDueReminderAfter')) {

				$afterDays = $pluginSettingsDao->getSetting($journalId, $bfrPluginName, 'numDaysAfterDueReminder');

				if (($curDay - $afterDays) <= 0) {
					$afterMonths = 1;
					$dueDay = 31 + ($curDay - $afterDays);
				} else {
					$afterMonths = 0;
					$dueDay = $curDay - $afterDays;
				}

				if (($curMonth - $afterMonths) == 0) {
					$afterYears = 1;
					$dueMonth = 12;
				} else {
					$afterYears = 0;
					$dueMonth = $curMonth - $afterMonths;
				}

				$dueYear = $curYear - $afterYears;
				$dueDate = $dueYear . '-' . $dueMonth . '-' . $dueDay;

				// Retrieve all books for review that match due date
				$books =& $bfrDao->getBooksForReviewByDateDue($journalId, $dueDate);

				while (!$books->eof()) {
					$bookForReview =& $books->next();
					$this->sendReminder($bookForReview, $journal, 'BFR_REVIEW_REMINDER_LATE');
				}
			}
		}
	}

	/**
	 * Run this scheduled task. 
	 */
	function execute() {
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journals =& $journalDao->getJournals(true);

		$todayDate = array(
						'year' => date('Y'),
						'month' => date('n'),
						'day' => date('j')
					);

		while (!$journals->eof()) {
			$journal =& $journals->next();

			// Send reminders based on current date
			$this->sendJournalReminders($journal, $todayDate);
			unset($journal);
		}

		// If it is the first day of a month but previous month had only 30
		// days then simulate 31st day for due dates that end on that day
		$shortMonths = array(2,4,6,8,10,12);

		if (($todayDate['day'] == 1) && in_array(($todayDate['month'] - 1), $shortMonths)) {

			$curDate['day'] = 31;
			$curDate['month'] = $todayDate['month'] - 1;

			if ($curDate['month'] == 12) {
				$curDate['year'] = $todayDate['year'] - 1;
			} else {
				$curDate['year'] = $todayDate['year'];
			}

			$journals =& $journalDao->getJournals(true);

			while (!$journals->eof()) {
				$journal =& $journals->next();

				// Send reminders for simulated 31st day of short month
				$this->sendJournalReminders($journal, $curDate);
				unset($journal);
			}
		}

		// If it is the first day of March, simulate 29th and 30th days for February
		// or just the 30th day in a leap year.
		if (($todayDate['day'] == 1) && ($todayDate['month'] == 3)) {

			$curDate['day'] = 30;
			$curDate['month'] = 2;
			$curDate['year'] = $todayDate['year'];

			$journals =& $journalDao->getJournals(true);

			while (!$journals->eof()) {
				$journal =& $journals->next();

				// Send reminders for simulated 30th day of February
				$this->sendJournalReminders($journal, $curDate);
				unset($journal);
			}

			// Check if it's a leap year
			if (date("L", mktime(0,0,0,0,0,$curDate['year'])) != '1') {

				$curDate['day'] = 29;

				$journals =& $journalDao->getJournals(true);

				while (!$journals->eof()) {
					$journal =& $journals->next();

					// Send reminders for simulated 29th day of February
					$this->sendJournalReminders($journal, $curDate);
					unset($journal);
				}
			}
		}
	}
}

?>
