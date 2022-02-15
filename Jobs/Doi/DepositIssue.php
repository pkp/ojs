<?php

/**
 * @file Jobs/Doi/DepositIssue.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DepositIssue
 * @ingroup jobs
 *
 * @brief Job to deposit issue DOI and metadata to the configured registration agency
 */

namespace APP\Jobs\Doi;

use APP\facades\Repo;
use APP\plugins\IDoiRegistrationAgency;
use PKP\context\Context;
use PKP\Domains\Jobs\Exceptions\JobException;
use PKP\Support\Jobs\BaseJob;

class DepositIssue extends BaseJob
{
    protected int $issueId;

    protected Context $context;

    /**
     * @var IDoiRegistrationAgency The configured DOI registration agency
     */
    protected IDoiRegistrationAgency $agency;

    /**
     * Create a new job instance.
     *
     */
    public function __construct(int $issueId, Context $context, IDoiRegistrationAgency $agency)
    {
        parent::__construct();

        $this->issueId = $issueId;
        $this->context = $context;
        $this->agency = $agency;
    }

    public function handle()
    {
        $issue = Repo::issue()->get($this->issueId);

        if (!$issue || !$this->agency) {
            $this->failed(new JobException(JobException::INVALID_PAYLOAD));

            return;
        }
        $retResults = $this->agency->depositIssues([$issue], $this->context);
    }
}
