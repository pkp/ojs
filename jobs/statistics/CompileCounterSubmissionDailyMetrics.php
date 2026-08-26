<?php

/**
 * @file jobs/statistics/CompileCounterSubmissionDailyMetrics.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CompileCounterSubmissionDailyMetrics
 *
 * @ingroup jobs
 *
 * @brief Compile COUNTER submission daily metrics.
 */

namespace APP\jobs\statistics;

use APP\statistics\TemporaryItemInvestigationsDAO;
use APP\statistics\TemporaryItemRequestsDAO;
use APP\statistics\TemporaryTotalsDAO;
use PKP\db\DAORegistry;
use PKP\jobs\BaseJob;

class CompileCounterSubmissionDailyMetrics extends BaseJob
{
    /**
     * Create a new job instance.
     *
     * @param string $loadId Usage stats log file name
     */
    public function __construct(protected string $loadId)
    {
        parent::__construct();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $temporaryTotalsDao = DAORegistry::getDAO('TemporaryTotalsDAO'); /** @var TemporaryTotalsDAO $temporaryTotalsDao */
        $temporaryItemInvestigationsDao = DAORegistry::getDAO('TemporaryItemInvestigationsDAO'); /** @var TemporaryItemInvestigationsDAO $temporaryItemInvestigationsDao */
        $temporaryItemRequestsDao = DAORegistry::getDAO('TemporaryItemRequestsDAO'); /** @var TemporaryItemRequestsDAO $temporaryItemRequestsDao */

        $temporaryTotalsDao->deleteCounterSubmissionDailyByLoadId($this->loadId); // always call first, before loading the data
        $temporaryTotalsDao->compileCounterSubmissionDailyMetrics($this->loadId);
        $temporaryItemInvestigationsDao->compileCounterSubmissionDailyMetrics($this->loadId);
        $temporaryItemRequestsDao->compileCounterSubmissionDailyMetrics($this->loadId);
    }
}
