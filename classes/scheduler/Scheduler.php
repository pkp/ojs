<?php

/**
 * @file classes/scheduler/Scheduler.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Scheduler
 *
 * @brief Core Scheduler to register schedule tasks
 */

namespace APP\scheduler;

use APP\tasks\OpenAccessNotification;
use APP\tasks\SubscriptionExpiryReminder;
use APP\tasks\UsageStatsLoader;
use PKP\scheduledTask\PKPScheduler;

class Scheduler extends PKPScheduler
{
    /**
     * @copydoc \PKP\scheduledTask\PKPScheduler::registerSchedules
     */
    public function registerSchedules(): void
    {
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

        parent::registerSchedules();
    }
}
