<?php

/**
 * @file jobs/statistics/DeleteUsageStatsTemporaryRecords.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DeleteUsageStatsTemporaryRecords
 *
 * @ingroup jobs
 *
 * @brief Compile the temporary usage stats and store them in the metrics table.
 */

namespace APP\jobs\statistics;

use APP\statistics\TemporaryItemInvestigationsDAO;
use APP\statistics\TemporaryItemRequestsDAO;
use APP\statistics\TemporaryTotalsDAO;
use PKP\db\DAORegistry;
use PKP\jobs\BaseJob;
use PKP\statistics\TemporaryInstitutionsDAO;

class DeleteUsageStatsTemporaryRecords extends BaseJob
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
        $temporaryInstitutionDao = DAORegistry::getDAO('TemporaryInstitutionsDAO'); /** @var TemporaryInstitutionsDAO $temporaryInstitutionDao */

        $temporaryTotalsDao->deleteByLoadId($this->loadId);
        $temporaryItemInvestigationsDao->deleteByLoadId($this->loadId);
        $temporaryItemRequestsDao->deleteByLoadId($this->loadId);
        $temporaryInstitutionDao->deleteByLoadId($this->loadId);
    }
}
