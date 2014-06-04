<?php

/**
 * @file classes/tasks/SubscriptionExpiryReminder.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionExpiryReminder
 * @ingroup tasks
 *
 * @brief Class to perform automated reminders for reviewers.
 */

import('lib.pkp.classes.scheduledTask.ScheduledTask');

define('SECONDS_PER_WEEK', 7 * 24 * 60 * 60);

class SubscriptionExpiryReminder extends ScheduledTask {

	/**
	 * Constructor.
	 */
	function SubscriptionExpiryReminder() {
		parent::ScheduledTask();
	}

	/**
	 * @see ScheduledTask::getName()
	 */
	function getName() {
		return __('admin.scheduledTask.subscriptionExpiryReminder');
	}

	function sendReminder ($subscription, $journal, $emailKey) {

		$userDao =& DAORegistry::getDAO('UserDAO');
		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');

		$journalName = $journal->getLocalizedTitle();
		$journalId = $journal->getId();
		$user =& $userDao->getById($subscription->getUserId());
		if (!isset($user)) return false;

		$subscriptionType =& $subscriptionTypeDao->getSubscriptionType($subscription->getTypeId());

		$subscriptionName = $journal->getSetting('subscriptionName');
		$subscriptionEmail = $journal->getSetting('subscriptionEmail');
		$subscriptionPhone = $journal->getSetting('subscriptionPhone');
		$subscriptionFax = $journal->getSetting('subscriptionFax');
		$subscriptionMailingAddress = $journal->getSetting('subscriptionMailingAddress');

		$subscriptionContactSignature = $subscriptionName;

		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_USER, LOCALE_COMPONENT_APPLICATION_COMMON);

		if ($subscriptionMailingAddress != '') {
			$subscriptionContactSignature .= "\n" . $subscriptionMailingAddress;
		}
		if ($subscriptionPhone != '') {
			$subscriptionContactSignature .= "\n" . AppLocale::Translate('user.phone') . ': ' . $subscriptionPhone;
		}
		if ($subscriptionFax != '') {
			$subscriptionContactSignature .= "\n" . AppLocale::Translate('user.fax') . ': ' . $subscriptionFax;
		}

		$subscriptionContactSignature .= "\n" . AppLocale::Translate('user.email') . ': ' . $subscriptionEmail;

		$paramArray = array(
			'subscriberName' => $user->getFullName(),
			'journalName' => $journalName,
			'subscriptionType' => $subscriptionType->getSummaryString(),
			'expiryDate' => $subscription->getDateEnd(),
			'username' => $user->getUsername(),
			'subscriptionContactSignature' => $subscriptionContactSignature
		);

		import('classes.mail.MailTemplate');
		$mail = new MailTemplate($emailKey, $journal->getPrimaryLocale());
		$mail->setFrom($subscriptionEmail, $subscriptionName);
		$mail->addRecipient($user->getEmail(), $user->getFullName());
		$mail->setSubject($mail->getSubject($journal->getPrimaryLocale()));
		$mail->setBody($mail->getBody($journal->getPrimaryLocale()));
		$mail->assignParams($paramArray);
		$mail->send();
	}

	function sendJournalReminders ($journal) {

		// Only send reminders if subscriptions are enabled
		if ($journal->getSetting('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION) {

			// Check if expiry notification before weeks is enabled
			if ($journal->getSetting('enableSubscriptionExpiryReminderBeforeWeeks')) {
				$beforeWeeks = $journal->getSetting('numWeeksBeforeSubscriptionExpiryReminder');
				$expiryTime = time() + (SECONDS_PER_WEEK * $beforeWeeks);


				// Retrieve all subscriptions that match expiry date
				$individualSubscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
				$institutionalSubscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
				$individualSubscriptions =& $individualSubscriptionDao->getSubscriptionsToRemind($expiryTime, $journal->getId(), SUBSCRIPTION_REMINDER_FIELD_BEFORE_EXPIRY);
				$institutionalSubscriptions =& $institutionalSubscriptionDao->getSubscriptionsToRemind($expiryTime, $journal->getId(), SUBSCRIPTION_REMINDER_FIELD_BEFORE_EXPIRY);

				while (!$individualSubscriptions->eof()) {
					$subscription =& $individualSubscriptions->next();
					$this->sendReminder($subscription, $journal, 'SUBSCRIPTION_BEFORE_EXPIRY');
					$individualSubscriptionDao->flagReminded($subscription->getId(), SUBSCRIPTION_REMINDER_FIELD_BEFORE_EXPIRY);
					unset($subscription);
				}

				while (!$institutionalSubscriptions->eof()) {
					$subscription =& $institutionalSubscriptions->next();
					$this->sendReminder($subscription, $journal, 'SUBSCRIPTION_BEFORE_EXPIRY');
					$institutionalSubscriptionDao->flagReminded($subscription->getId(), SUBSCRIPTION_REMINDER_FIELD_BEFORE_EXPIRY);
					unset($subscription);
				}
			}

			// Check if expiry notification after weeks is enabled
			if ($journal->getSetting('enableSubscriptionExpiryReminderAfterWeeks')) {
				$afterWeeks = $journal->getSetting('numWeeksAfterSubscriptionExpiryReminder');
				$expiryTime = time() - (SECONDS_PER_WEEK * $afterWeeks);
				// Retrieve all subscriptions that match expiry date
				$individualSubscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
				$institutionalSubscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
				$individualSubscriptions =& $individualSubscriptionDao->getSubscriptionsToRemind($expiryTime, $journal->getId(), SUBSCRIPTION_REMINDER_FIELD_AFTER_EXPIRY);
				$institutionalSubscriptions =& $institutionalSubscriptionDao->getSubscriptionsToRemind($expiryTime, $journal->getId(), SUBSCRIPTION_REMINDER_FIELD_AFTER_EXPIRY);

				while (!$individualSubscriptions->eof()) {
					$subscription =& $individualSubscriptions->next();
					$this->sendReminder($subscription, $journal, 'SUBSCRIPTION_AFTER_EXPIRY');
					$individualSubscriptionDao->flagReminded($subscription->getId(), SUBSCRIPTION_REMINDER_FIELD_AFTER_EXPIRY);
					unset($subscription);
				}

				while (!$institutionalSubscriptions->eof()) {
					$subscription =& $institutionalSubscriptions->next();
					$this->sendReminder($subscription, $journal, 'SUBSCRIPTION_AFTER_EXPIRY');
					$institutionalSubscriptionDao->flagReminded($subscription->getId(), SUBSCRIPTION_REMINDER_FIELD_AFTER_EXPIRY);
					unset($subscription);
				}
			}
		}
	}

	/**
	 * @see ScheduledTask::executeActions()
	 */
	function executeActions() {
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journals =& $journalDao->getJournals(true);

		while (!$journals->eof()) {
			$journal =& $journals->next();

			// Send reminders based on current date
			$this->sendJournalReminders($journal);
			unset($journal);
		}

		return true;
	}
}

?>
