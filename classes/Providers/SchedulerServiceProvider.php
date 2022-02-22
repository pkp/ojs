<?php

/**
 * @file classes/Providers/SchedulerServiceProvider.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SchedulerServiceProvider
 * @ingroup Providers
 *
 * @brief Enables Scheduler Service Provider on the application
 */

namespace APP\Providers;

use Illuminate\Console\Scheduling\Schedule;
use PKP\core\SchedulerServiceProvider as PKPSchedulerServiceProvider;
use PKP\db\DAORegistry;

class SchedulerServiceProvider extends PKPSchedulerServiceProvider
{
    /**
     * @inheritDoc
     */
    public function scheduleTasks(Schedule $scheduleBag): void
    {
        $taskDao = DAORegistry::getDAO('ScheduledTaskDAO');

        // Send automated reminders to reviewers to complete their assignments.
        $scheduleBag
            ->call(function () {
                (new \PKP\Commands\ReviewReminderCommand())->execute();
            })
            ->hourly()
            ->name('ojs:reviewreminder:without-overlapping')
            ->withoutOverlapping()
            ->then(function () use ($taskDao) {
                $taskDao->updateLastRunTime('lib.pkp.classes.task.ReviewReminder');
            });

        // Send automated statistics reports to journal managers and editors.
        $scheduleBag
            ->call(function () {
                (new \PKP\Commands\StatisticsReportCommand())->execute();
            })
            ->daily()
            ->name('ojs:statisticsreport:without-overlapping')
            ->withoutOverlapping()
            ->then(function () use ($taskDao) {
                $taskDao->updateLastRunTime('lib.pkp.classes.task.StatisticsReport');
            });

        // Send automated reminders about subscription expiry.
        $scheduleBag
            ->call(function () {
                (new \APP\Commands\SubscriptionExpiryReminderCommand())->execute();
            })
            ->daily()
            ->name('ojs:subscriptionexpiryreminder:without-overlapping')
            ->withoutOverlapping()
            ->then(function () use ($taskDao) {
                $taskDao->updateLastRunTime('classes.tasks.SubscriptionExpiryReminder');
            });

        // Automatically deposit any outstanding DOIs to the configured registration agency
        $scheduleBag
            ->call(function () {
                (new \PKP\Commands\DepositDoisCommand())->execute();
            })
            ->hourly()
            ->name('ojs:depositdois:without-overlapping')
            ->withoutOverlapping()
            ->then(function () use ($taskDao) {
                $taskDao->updateLastRunTime('lib.pkp.classes.task.DepositDois');
            });

        parent::scheduleTasks($scheduleBag);
    }
}
