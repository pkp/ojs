<?php

namespace APP\scheduler;

use APP\core\Application;
use APP\tasks\OpenAccessNotification;
use APP\tasks\SubscriptionExpiryReminder;
use APP\tasks\UsageStatsLoader;
use PKP\plugins\PluginRegistry;
use PKP\scheduledTask\PKPScheduler;

class Scheduler extends PKPScheduler
{
    public function registerSchedules(): void
    {
        parent::registerSchedules();

        $this
            ->schedule
            ->call(fn () => (new SubscriptionExpiryReminder())->execute())
            ->daily()
            ->name(SubscriptionExpiryReminder::class)
            ->withoutOverlapping()
            ->then(fn () => $this->scheduledTaskDao->updateLastRunTime(SubscriptionExpiryReminder::class));

        $this
            ->schedule
            ->call(fn () => (new UsageStatsLoader([]))->execute())
            ->daily()
            ->name(UsageStatsLoader::class)
            ->withoutOverlapping()
            ->then(fn () => $this->scheduledTaskDao->updateLastRunTime(UsageStatsLoader::class));

        $this
            ->schedule
            ->call(fn () => (new OpenAccessNotification())->execute())
            ->hourly()
            ->name(OpenAccessNotification::class)
            ->withoutOverlapping()
            ->then(fn () => $this->scheduledTaskDao->updateLastRunTime(OpenAccessNotification::class));

        // ray(Application::get()->getEnabledProducts());
        // ray(PluginRegistry::getAllPlugins());
    }
}
