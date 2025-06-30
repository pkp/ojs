<?php

/**
 * @file plugins/generic/doaj/jobs/DOAJRegister.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DOAJRegister
 *
 * @ingroup jobs
 *
 * @brief Register article or publication with DOAJ.
 */

namespace APP\plugins\generic\doaj\jobs;

use APP\statistics\TemporaryItemRequestsDAO;
use PKP\db\DAORegistry;
use PKP\jobs\BaseJob;

class DOAJRegister extends BaseJob
{
    public int $timeout = 600;

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
        $temporaryItemRequestsDao = DAORegistry::getDAO('TemporaryItemRequestsDAO'); /** @var TemporaryItemRequestsDAO $temporaryItemRequestsDao */
        $temporaryItemRequestsDao->compileUniqueClicks($this->loadId);
    }
}
