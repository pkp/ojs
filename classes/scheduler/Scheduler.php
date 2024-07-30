<?php

/**
 * @file classes/scheduler/Scheduler.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Scheduler
 *
 * @brief Core scheduler class, responsible to register scheduled tasks specific for the application
 */

namespace APP\scheduler;

use APP\tasks\OpenAccessNotification;
use APP\tasks\SubscriptionExpiryReminder;
use APP\tasks\UsageStatsLoader;
use PKP\scheduledTask\PKPScheduler;
use PKP\task\DepositDois;
use PKP\task\EditorialReminders;
use PKP\task\ReviewReminder;

class Scheduler extends PKPScheduler
{
    /**
     * @copydoc \PKP\scheduledTask\PKPScheduler::registerSchedules
     */
    public function registerSchedules(): void
    {
        parent::registerSchedules();

        $this
            ->schedule
            ->call(fn () => (new ReviewReminder())->execute())
            ->hourly()
            ->name(ReviewReminder::class)
            ->withoutOverlapping();

        $this
            ->schedule
            ->call(fn () => (new DepositDois())->execute())
            ->hourly()
            ->name(DepositDois::class)
            ->withoutOverlapping();

        $this
            ->schedule
            ->call(fn () => (new EditorialReminders())->execute())
            ->daily()
            ->name(EditorialReminders::class)
            ->withoutOverlapping();

        $this
            ->schedule
            ->call(fn () => (new SubscriptionExpiryReminder())->execute())
            ->daily()
            ->name(SubscriptionExpiryReminder::class)
            ->withoutOverlapping();

        $this
            ->schedule
            ->call(fn () => (new UsageStatsLoader([]))->execute())
            ->daily()
            ->name(UsageStatsLoader::class)
            ->withoutOverlapping();

        $this
            ->schedule
            ->call(fn () => (new OpenAccessNotification())->execute())
            ->hourly()
            ->name(OpenAccessNotification::class)
            ->withoutOverlapping();
    }
}
