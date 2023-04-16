<?php

/**
 * @file classes/tasks/OpenAccessNotification.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OpenAccessNotification
 *
 * @ingroup tasks
 *
 * @brief Class to perform automated email notifications when an issue becomes open access.
 */

namespace APP\tasks;

use APP\facades\Repo;
use APP\issue\Collector;
use APP\jobs\notifications\OpenAccessMailUsers;
use APP\journal\Journal;
use APP\notification\Notification;
use Illuminate\Support\Facades\Bus;
use PKP\db\DAORegistry;
use PKP\mail\Mailer;
use PKP\notification\NotificationSubscriptionSettingsDAO;
use PKP\scheduledTask\ScheduledTask;

class OpenAccessNotification extends ScheduledTask
{
    /**
     * @copydoc ScheduledTask::getName()
     */
    public function getName()
    {
        return __('admin.scheduledTask.openAccessNotification');
    }

    /**
     * Send notifications for the specified journal based on the specified date.
     *
     * @param Journal $journal
     * @param array $curDate
     */
    public function sendNotifications($journal, $curDate)
    {
        // Only send notifications if subscriptions and open access notifications are enabled
        if ($journal->getData('publishingMode') == \APP\journal\Journal::PUBLISHING_MODE_SUBSCRIPTION && $journal->getData('enableOpenAccessNotification')) {
            $curYear = $curDate['year'];
            $curMonth = $curDate['month'];
            $curDay = $curDate['day'];

            // Check if the current date corresponds to the open access date of any issues
            $issues = Repo::issue()->getCollector()
                ->filterByContextIds([$journal->getId()])
                ->filterByPublished(true)
                ->orderBy(Collector::ORDERBY_PUBLISHED_ISSUES)
                ->getMany();

            // Notify all users who have open access notification set for this journal
            /** @var NotificationSubscriptionSettingsDAO $notificationSubscriptionSettingsDao */
            $notificationSubscriptionSettingsDao = DAORegistry::getDAO('NotificationSubscriptionSettingsDAO');
            $userIds = $notificationSubscriptionSettingsDao->getSubscribedUserIds(
                [NotificationSubscriptionSettingsDAO::BLOCKED_NOTIFICATION_KEY, NotificationSubscriptionSettingsDAO::BLOCKED_EMAIL_NOTIFICATION_KEY],
                [Notification::NOTIFICATION_TYPE_OPEN_ACCESS],
                [$journal->getId()]
            );

            if ($userIds->isEmpty()) {
                return;
            }

            $jobs = [];
            foreach ($issues as $issue) {
                $accessStatus = $issue->getAccessStatus();
                $openAccessDate = $issue->getOpenAccessDate();
                if ($accessStatus == \APP\issue\Issue::ISSUE_ACCESS_SUBSCRIPTION && !empty($openAccessDate) && strtotime($openAccessDate) == mktime(0, 0, 0, $curMonth, $curDay, $curYear)) {
                    $userChunks = $userIds->chunk(Mailer::BULK_EMAIL_SIZE_LIMIT);
                    foreach ($userChunks as $chunk) {
                        $jobs[] = new OpenAccessMailUsers($chunk, $journal->getId(), $issue->getId());
                    }
                }
            }

            if (!empty($jobs)) {
                Bus::batch($jobs)->dispatch();
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
            // Send notifications based on current date
            $this->sendNotifications($journal, $todayDate);
        }

        // If it is the first day of a month but previous month had only
        // 30 days then simulate 31st day for open access dates that end on
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
            if (date('L', mktime(0, 0, 0, 0, 0, $curDate['year'])) != '1') {
                $curDate['day'] = 29;

                $journals = $journalDao->getAll(true);
                while ($journal = $journals->next()) {
                    // Send reminders for simulated 29th day of February
                    $this->sendNotifications($journal, $curDate);
                }
            }
        }
        return true;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\tasks\OpenAccessNotification', '\OpenAccessNotification');
}
