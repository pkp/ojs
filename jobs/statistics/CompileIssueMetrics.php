<?php

/**
 * @file jobs/statistics/CompileIssueMetrics.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CompileIssueMetrics
 *
 * @ingroup jobs
 *
 * @brief Compile issue metrics.
 */

namespace APP\jobs\statistics;

use APP\statistics\TemporaryTotalsDAO;
use PKP\db\DAORegistry;
use PKP\jobs\BaseJob;

class CompileIssueMetrics extends BaseJob
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
        $temporaryTotalsDao->compileIssueMetrics($this->loadId);
    }
}
