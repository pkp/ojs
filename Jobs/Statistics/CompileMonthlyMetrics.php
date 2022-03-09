<?php

/**
 * @file Jobs/Statistics/CompileMonthlyMetrics.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CompileMonthlyMetrics
 * @ingroup jobs
 *
 * @brief Compile and store monthly usage stats from the daily records.
 */

namespace APP\Jobs\Statistics;

use APP\core\Services;
use PKP\site\Site;
use PKP\Support\Jobs\BaseJob;

class CompileMonthlyMetrics extends BaseJob
{
    /**
     * The number of times the job may be attempted.
     */
    public $tries = 1;

    /**
     * The month the usage metrics should be aggregated by, in format YYYYMM.
     */
    protected string $month;

    protected Site $site;

    /**
     * Create a new job instance.
     *
     * @param string $month In format YYYYMM
     */
    public function __construct(string $month, Site $site)
    {
        parent::__construct();
        $this->month = $month;
        $this->site = $site;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $currentMonth = date('Ym'); // shall we consider only current month or maybe rather previous month?

        $geoService = Services::get('geoStats');
        $geoService->addMonthlyMetrics($this->month);
        if (!$this->site->getData('keepDailyUsageStats') && $this->month != $currentMonth) {
            $geoService->deleteDailyMetrics($this->month);
        }

        $counterService = Services::get('sushiStats');
        $counterService->addMonthlyMetrics($this->month);
        if (!$this->site->getData('keepDailyUsageStats') && $this->month != $currentMonth) {
            $counterService->deleteDailyMetrics($this->month);
        }
    }
}
