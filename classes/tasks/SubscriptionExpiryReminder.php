<?php

/**
 * @file classes/tasks/SubscriptionExpiryReminder.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionExpiryReminder
 *
 * @ingroup tasks
 *
 * @brief Class to perform automated reminders for reviewers.
 */

namespace APP\tasks;

use APP\facades\Repo;
use APP\journal\Journal;
use APP\journal\JournalDAO;
use APP\mail\mailables\SubscriptionExpired;
use APP\mail\mailables\SubscriptionExpiredLast;
use APP\mail\mailables\SubscriptionExpiresSoon;
use APP\subscription\IndividualSubscriptionDAO;
use APP\subscription\InstitutionalSubscriptionDAO;
use APP\subscription\Subscription;
use APP\subscription\SubscriptionTypeDAO;
use Illuminate\Support\Facades\Mail;
use PKP\db\DAORegistry;
use PKP\mail\Mailable;
use PKP\scheduledTask\ScheduledTask;

class SubscriptionExpiryReminder extends ScheduledTask
{
    /**
     * @copydoc ScheduledTask::getName()
     */
    public function getName()
    {
        return __('admin.scheduledTask.subscriptionExpiryReminder');
    }

    /**
     * Send a particular subscription expiry reminder.
     */
    protected function sendReminder(Journal $journal, Subscription $subscription, Mailable $mailable): void
    {
        $user = Repo::user()->get($subscription->getUserId());
        if (!isset($user)) {
            return;
        }

        $locale = $journal->getPrimaryLocale();
        $template = Repo::emailTemplate()->getByKey($journal->getId(), $mailable::getEmailTemplateKey());

        $mailable
            ->from($journal->getData('subscriptionEmail'), $journal->getData('subscriptionName'))
            ->recipients([$user])
            ->subject($template->getLocalizedData('subject', $locale))
            ->body($template->getLocalizedData('body', $locale))
            ->setData($locale);

        Mail::send($mailable);
    }

    /**
     * Send a journal's subscription expiry reminders.
     *
     * @param Journal $journal
     * @param array $curDate The current date
     */
    protected function sendJournalReminders($journal, $curDate): void
    {
        // Only send reminders if subscriptions are enabled
        if ($journal->getData('publishingMode') != Journal::PUBLISHING_MODE_SUBSCRIPTION) {
            return;
        }

        $curYear = $curDate['year'];
        $curMonth = $curDate['month'];
        $curDay = $curDate['day'];
        $individualSubscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /** @var IndividualSubscriptionDAO $individualSubscriptionDao */
        $institutionalSubscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /** @var InstitutionalSubscriptionDAO $institutionalSubscriptionDao */
        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */

        // Check if expiry notification before months is enabled
        if ($beforeMonths = $journal->getData('numMonthsBeforeSubscriptionExpiryReminder')) {
            $beforeYears = (int)floor($beforeMonths / 12);
            $beforeMonths = (int)fmod($beforeMonths, 12);

            $expiryYear = $curYear + $beforeYears + (int)floor(($curMonth + $beforeMonths) / 12);
            $expiryMonth = (int)fmod($curMonth + $beforeMonths, 12);
            $expiryDay = $curDay;

            // Retrieve all subscriptions that match expiry date
            $dateEnd = $expiryYear . '-' . $expiryMonth . '-' . $expiryDay;
            $individualSubscriptions = $individualSubscriptionDao->getByDateEnd($dateEnd, $journal->getId());
            $institutionalSubscriptions = $institutionalSubscriptionDao->getByDateEnd($dateEnd, $journal->getId());

            while ($subscription = $individualSubscriptions->next()) {
                $this->sendReminder($journal, $subscription, new SubscriptionExpiresSoon(
                    $journal,
                    $subscription,
                    $subscriptionTypeDao->getById($subscription->getTypeId(), $journal->getId())
                ));
            }

            while ($subscription = $institutionalSubscriptions->next()) {
                $this->sendReminder($journal, $subscription, new SubscriptionExpiresSoon(
                    $journal,
                    $subscription,
                    $subscriptionTypeDao->getById($subscription->getTypeId(), $journal->getId())
                ));
            }
        }

        // Check if expiry notification before weeks is enabled
        if ($beforeWeeks = $journal->getData('numWeeksBeforeSubscriptionExpiryReminder')) {
            $beforeDays = $beforeWeeks * 7;

            $expiryMonth = $curMonth + (int)floor(($curDay + $beforeDays) / 31);
            $expiryYear = $curYear + (int)floor($expiryMonth / 12);
            $expiryDay = (int)fmod($curDay + $beforeDays, 31);
            $expiryMonth = (int)fmod($expiryMonth, 12);

            // Retrieve all subscriptions that match expiry date
            $dateEnd = $expiryYear . '-' . $expiryMonth . '-' . $expiryDay;
            $individualSubscriptions = $individualSubscriptionDao->getByDateEnd($dateEnd, $journal->getId());
            $institutionalSubscriptions = $institutionalSubscriptionDao->getByDateEnd($dateEnd, $journal->getId());

            while ($subscription = $individualSubscriptions->next()) {
                $this->sendReminder($journal, $subscription, new SubscriptionExpiresSoon(
                    $journal,
                    $subscription,
                    $subscriptionTypeDao->getById($subscription->getTypeId(), $journal->getId())
                ));
            }

            while ($subscription = $institutionalSubscriptions->next()) {
                $this->sendReminder(
                    $journal,
                    $subscription,
                    new SubscriptionExpiresSoon(
                        $journal,
                        $subscription,
                        $subscriptionTypeDao->getById($subscription->getTypeId(), $journal->getId())
                    )
                );
            }
        }

        // Check if expiry notification after months is enabled
        if ($afterMonths = $journal->getData('numMonthsAfterSubscriptionExpiryReminder')) {
            $afterYears = (int)floor($afterMonths / 12);
            $afterMonths = (int)fmod($afterMonths, 12);

            if (($curMonth - $afterMonths) <= 0) {
                $afterYears++;
                $expiryMonth = 12 + ($curMonth - $afterMonths);
            } else {
                $expiryMonth = $curMonth - $afterMonths;
            }

            $expiryYear = $curYear - $afterYears;
            $expiryDay = $curDay;

            // Retrieve all subscriptions that match expiry date
            $dateEnd = $expiryYear . '-' . $expiryMonth . '-' . $expiryDay;
            $individualSubscriptions = $individualSubscriptionDao->getByDateEnd($dateEnd, $journal->getId());
            $institutionalSubscriptions = $institutionalSubscriptionDao->getByDateEnd($dateEnd, $journal->getId());

            while ($subscription = $individualSubscriptions->next()) {
                $this->sendReminder($journal, $subscription, new SubscriptionExpiredLast(
                    $journal,
                    $subscription,
                    $subscriptionTypeDao->getById($subscription->getTypeId(), $journal->getId())
                ));
            }

            while ($subscription = $institutionalSubscriptions->next()) {
                $this->sendReminder($journal, $subscription, new SubscriptionExpiredLast(
                    $journal,
                    $subscription,
                    $subscriptionTypeDao->getById($subscription->getTypeId(), $journal->getId())
                ));
            }
        }

        // Check if expiry notification after weeks is enabled
        if ($afterWeeks = $journal->getData('numWeeksAfterSubscriptionExpiryReminder')) {
            $afterDays = $afterWeeks * 7;

            if (($curDay - $afterDays) <= 0) {
                $afterMonths = 1;
                $expiryDay = 31 + ($curDay - $afterDays);
            } else {
                $afterMonths = 0;
                $expiryDay = $curDay - $afterDays;
            }

            if (($curMonth - $afterMonths) == 0) {
                $afterYears = 1;
                $expiryMonth = 12;
            } else {
                $afterYears = 0;
                $expiryMonth = $curMonth - $afterMonths;
            }

            $expiryYear = $curYear - $afterYears;

            // Retrieve all subscriptions that match expiry date
            $dateEnd = $expiryYear . '-' . $expiryMonth . '-' . $expiryDay;
            $individualSubscriptions = $individualSubscriptionDao->getByDateEnd($dateEnd, $journal->getId());
            $institutionalSubscriptions = $institutionalSubscriptionDao->getByDateEnd($dateEnd, $journal->getId());

            while ($subscription = $individualSubscriptions->next()) {
                $this->sendReminder($journal, $subscription, new SubscriptionExpired(
                    $journal,
                    $subscription,
                    $subscriptionTypeDao->getById($subscription->getTypeId(), $journal->getId())
                ));
            }

            while ($subscription = $institutionalSubscriptions->next()) {
                $this->sendReminder($journal, $subscription, new SubscriptionExpired(
                    $journal,
                    $subscription,
                    $subscriptionTypeDao->getById($subscription->getTypeId(), $journal->getId())
                ));
            }
        }
    }

    /**
     * @copydoc ScheduledTask::executeActions()
     */
    protected function executeActions()
    {
        $journalDao = DAORegistry::getDAO('JournalDAO'); /** @var JournalDAO $journalDao */
        $journals = $journalDao->getAll(true);

        $todayDate = [
            'year' => date('Y'),
            'month' => date('n'),
            'day' => date('j')
        ];

        while ($journal = $journals->next()) {
            // Send reminders based on current date
            $this->sendJournalReminders($journal, $todayDate);
        }

        // If it is the first day of a month but previous month had only
        // 30 days then simulate 31st day for expiry dates that end on
        // that day.
        $shortMonths = [2,4,6,9,11];

        if (($todayDate['day'] == 1) && in_array(($todayDate['month'] - 1), $shortMonths)) {
            $curDate['day'] = 31;
            $curDate['month'] = $todayDate['month'] - 1;

            if ($curDate['month'] == 0) {
                $curDate['month'] = 12;
                $curDate['year'] = $todayDate['year'] - 1;
            } else {
                $curDate['year'] = $todayDate['year'];
            }

            $journals = $journalDao->getAll(true);
            while ($journal = $journals->next()) {
                // Send reminders for simulated 31st day of short month
                $this->sendJournalReminders($journal, $curDate);
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
                $this->sendJournalReminders($journal, $curDate);
            }

            // Check if it's a leap year
            if (date('L', mktime(0, 0, 0, 0, 0, $curDate['year'])) != '1') {
                $curDate['day'] = 29;

                $journals = $journalDao->getAll(true);

                while ($journal = $journals->next()) {
                    // Send reminders for simulated 29th day of February
                    $this->sendJournalReminders($journal, $curDate);
                }
            }
        }
        return true;
    }
}
