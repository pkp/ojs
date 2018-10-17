<?php

/**
 * @file classes/tasks/OpenAccessNotification.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OpenAccessNotification
 * @ingroup tasks
 *
 * @brief Class to perform automated email notifications when an issue becomes open access.
 */

import('lib.pkp.classes.scheduledTask.ScheduledTask');

class OpenAccessNotification extends ScheduledTask {

	/**
	 * @see ScheduledTask::getName()
	 */
	function getName() {
		return __('admin.scheduledTask.openAccessNotification');
	}

	function sendNotification ($users, $journal, $issue) {
		if ($users->getCount() != 0) {

			import('lib.pkp.classes.mail.MailTemplate');
			$email = new MailTemplate('OPEN_ACCESS_NOTIFY', $journal->getPrimaryLocale(), $journal, false);

			$email->setSubject($email->getSubject($journal->getPrimaryLocale()));
			$email->setReplyTo(null);
			$email->addRecipient($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));

			$paramArray = array(
				'journalName' => $journal->getLocalizedName(),
				'journalUrl' => Request::url($journal->getPath()),
				'editorialContactSignature' => $journal->getSetting('contactName') . "\n" . $journal->getLocalizedName()
			);
			$email->assignParams($paramArray);

			$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
			$publishedArticles = $publishedArticleDao->getPublishedArticlesInSections($issue->getId());
			$mimeBoundary = '==boundary_' . md5(microtime());

			$templateMgr = TemplateManager::getManager();
			$templateMgr->assign(array(
				'body' => $email->getBody($journal->getPrimaryLocale()),
				'templateSignature' => $journal->getSetting('emailSignature'),
				'mimeBoundary' => $mimeBoundary,
				'issue' => $issue,
				'publishedArticles' => $publishedArticles,
			));

			$email->addHeader('MIME-Version', '1.0');
			$email->setContentType('multipart/alternative; boundary="'.$mimeBoundary.'"');
			$email->setBody($templateMgr->fetch('subscription/openAccessNotifyEmail.tpl'));

			while ($user = $users->next()) {
				$email->addBcc($user->getEmail(), $user->getFullName());
			}

			$email->send();
		}
	}

	function sendNotifications ($journal, $curDate) {

		// Only send notifications if subscriptions and open access notifications are enabled
		if ($journal->getSetting('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION && $journal->getSetting('enableOpenAccessNotification')) {

			$curYear = $curDate['year'];
			$curMonth = $curDate['month'];
			$curDay = $curDate['day'];

			// Check if the current date corresponds to the open access date of any issues
			$issueDao = DAORegistry::getDAO('IssueDAO');
			$issues = $issueDao->getPublishedIssues($journal->getId());

			while ($issue = $issues->next()) {
				$accessStatus = $issue->getAccessStatus();
				$openAccessDate = $issue->getOpenAccessDate();

				if ($accessStatus == ISSUE_ACCESS_SUBSCRIPTION && !empty($openAccessDate) && strtotime($openAccessDate) == mktime(0,0,0,$curMonth, $curDay, $curYear)) {
					// Notify all users who have open access notification set for this journal
					$userSettingsDao = DAORegistry::getDAO('UserSettingsDAO');
					$users = $userSettingsDao->getUsersBySetting('openAccessNotification', true, 'bool', $journal->getId());
					$this->sendNotification($users, $journal, $issue);
				}
			}
		}
	}

	/**
	 * @see ScheduledTask::executeActions()
	 */
	protected function executeActions() {
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journals = $journalDao->getAll(true);

		$todayDate = array(
			'year' => date('Y'),
			'month' => date('n'),
			'day' => date('j')
		);

		while ($journal = $journals->next()) {
			// Send notifications based on current date			
			$this->sendNotifications($journal, $todayDate);
		}

		// If it is the first day of a month but previous month had only
		// 30 days then simulate 31st day for open access dates that end on
		// that day.
		$shortMonths = array(2,4,6,8,10,12);

		if (($todayDate['day'] == 1) && in_array(($todayDate['month'] - 1), $shortMonths)) {

			$curDate['day'] = 31;
			$curDate['month'] = $todayDate['month'] - 1;

			if ($curDate['month'] == 12) {
				$curDate['year'] = $todayDate['year'] - 1;
			} else {
				$curDate['year'] = $todayDate['year'];
			}

			$journals = $journalDao->getAll(true);
			while ($journal = $journals->next()) {
				// Send reminders for simulated 31st day of short month		
				$this->sendNotifications($journal, $curDate);
			}
		}

		// If it is the first day of March, simulate 29th and 30th days for February
		// or just the 30th day in a leap year.
		if (($todayDate['day'] == 1) && ($todayDate['month'] == 3)) {

			$curDate['day'] = 30;
			$curDate['month'] = 2;
			$curDate['year'] = $todayDate['year'];

			$journals = $journalDao->getAll(true);
			while ($journal = $journals->next()) {
				// Send reminders for simulated 30th day of February		
				$this->sendNotifications($journal, $curDate);
			}

			// Check if it's a leap year
			if (date("L", mktime(0,0,0,0,0,$curDate['year'])) != '1') {

				$curDate['day'] = 29;

				$journals = $journalDao->getAll(true);
				while ($journal = $journals->next()) {
					// Send reminders for simulated 29th day of February		
					$this->sendNotifications($journal, $curDate);
				}
			}
		}
	}
}


