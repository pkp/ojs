<?php

/**
 * @file classes/tasks/UsageStatsLoader.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UsageStatsLoader
 * @ingroup tasks
 *
 * @brief Scheduled task to extract transform and load usage statistics data into database.
 */

namespace APP\tasks;

use APP\core\Application;
use Exception;
use PKP\task\PKPUsageStatsLoader;

class UsageStatsLoader extends PKPUsageStatsLoader
{
    /**
     * @copydoc PKPUsageStatsLoader::getValidAssocTypes()
     */
    protected function getValidAssocTypes(): array
    {
        return [
            Application::ASSOC_TYPE_SUBMISSION_FILE,
            Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER,
            Application::ASSOC_TYPE_SUBMISSION,
            Application::ASSOC_TYPE_ISSUE_GALLEY,
            Application::ASSOC_TYPE_ISSUE,
            Application::ASSOC_TYPE_JOURNAL,
        ];
    }

    /**
     * @copydoc PKPUsageStatsLoader::isLogEntryValid()
     */
    protected function isLogEntryValid(object $entry): void
    {
        parent::isLogEntryValid($entry);
        if (!empty($entry->issueId) && !is_int($entry->issueId)) {
            throw new Exception(__('admin.scheduledTask.usageStatsLoader.invalidLogEntry.issueId'));
        }
        if (!empty($entry->issueGalleyId) && !is_int($entry->issueGalleyId)) {
            throw new Exception(__('admin.scheduledTask.usageStatsLoader.invalidLogEntry.issueGalleyId'));
        }
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\tasks\UsageStatsLoader', '\UsageStatsLoader');
}
