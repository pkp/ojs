<?php

/**
 * @file classes/tasks/UsageStatsLoader.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UsageStatsLoader
 *
 * @ingroup tasks
 *
 * @brief Scheduled task to extract transform and load usage statistics data into database.
 */

namespace APP\tasks;

use APP\jobs\statistics\CompileCounterSubmissionDailyMetrics;
use APP\jobs\statistics\CompileCounterSubmissionInstitutionDailyMetrics;
use APP\jobs\statistics\CompileIssueMetrics;
use APP\jobs\statistics\CompileSubmissionGeoDailyMetrics;
use APP\jobs\statistics\CompileUniqueInvestigations;
use APP\jobs\statistics\CompileUniqueRequests;
use APP\jobs\statistics\DeleteUsageStatsTemporaryRecords;
use APP\jobs\statistics\ProcessUsageStatsLogFile;
use PKP\jobs\statistics\ArchiveUsageStatsLogFile;
use PKP\jobs\statistics\CompileContextMetrics;
use PKP\jobs\statistics\CompileSubmissionMetrics;
use PKP\jobs\statistics\RemoveDoubleClicks;
use PKP\site\Site;
use PKP\task\PKPUsageStatsLoader;

class UsageStatsLoader extends PKPUsageStatsLoader
{
    protected function getFileJobs(string $filePath, Site $site): array
    {
        $logFileName = basename($filePath);
        return [
            new ProcessUsageStatsLogFile($filePath, $logFileName),
            new RemoveDoubleClicks($logFileName),
            new CompileUniqueInvestigations($logFileName),
            new CompileUniqueRequests($logFileName),
            new CompileContextMetrics($logFileName),
            new CompileIssueMetrics($logFileName),
            new CompileSubmissionMetrics($logFileName),
            new CompileSubmissionGeoDailyMetrics($logFileName),
            new CompileCounterSubmissionDailyMetrics($logFileName),
            new CompileCounterSubmissionInstitutionDailyMetrics($logFileName),
            new DeleteUsageStatsTemporaryRecords($logFileName),
            new ArchiveUsageStatsLogFile($logFileName, $site),
        ];
    }
}
