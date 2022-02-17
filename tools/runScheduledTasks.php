<?php

/**
 * @file tools/runScheduledTasks.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class runScheduledTasks
 * @ingroup tools
 *
 * @brief CLI tool to execute a set of scheduled tasks.
 */

require(dirname(__FILE__) . '/bootstrap.inc.php');

class runScheduledTasks extends \PKP\cliTool\ScheduledTaskTool
{
}

$tool = new runScheduledTasks($argv ?? []);
$tool->execute();

$taskDao = \PKP\db\DAORegistry::getDAO('ScheduledTaskDAO');

$scheduleBag = app(\Illuminate\Console\Scheduling\Schedule::class);

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

(new \PKP\core\PKPScheduler($scheduleBag))
    ->run();
