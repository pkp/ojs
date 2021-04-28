<?php

namespace APP\plugins;

use APP\issue\Issue;
use APP\submission\Submission;
use PKP\context\Context;
use PKP\plugins\IPKPDoiRegistrationAgency;

interface IDoiRegistrationAgency extends IPKPDoiRegistrationAgency
{
    /**
     * @param Submission[] $submissions
     *
     */
    public function exportSubmissions(array $submissions, Context $context): array;

    /**
     * @param Submission[] $submissions
     *
     */
    public function depositSubmissions(array $submissions, Context $context): array;

    /**
     * @param Issue[] $issues
     *
     */
    public function exportIssues(array $issues, Context $context): array;

    /**
     * @param Issue[] $issues
     *
     */
    public function depositIssues(array $issues, Context $context): array;
}
