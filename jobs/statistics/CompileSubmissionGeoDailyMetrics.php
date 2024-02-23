<?php

/**
 * @file jobs/statistics/CompileSubmissionGeoDailyMetrics.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CompileSubmissionGeoDailyMetrics
 *
 * @ingroup jobs
 *
 * @brief Compile submission Geo daily metrics.
 */

namespace APP\jobs\statistics;

use APP\statistics\TemporaryItemInvestigationsDAO;
use APP\statistics\TemporaryTotalsDAO;
use PKP\db\DAORegistry;
use PKP\jobs\BaseJob;

class CompileSubmissionGeoDailyMetrics extends BaseJob
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

        // Geo database only contains total and unique investigations (no extra requests differentiation)
        $temporaryTotalsDao->deleteSubmissionGeoDailyByLoadId($this->loadId); // always call first, before loading the data
        $temporaryTotalsDao->compileSubmissionGeoDailyMetrics($this->loadId);
        $temporaryItemInvestigationsDao->compileSubmissionGeoDailyMetrics($this->loadId);
    }
}
