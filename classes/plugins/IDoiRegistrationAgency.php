<?php

/**
 * @file classes/plugins/IDoiRegistrationAgency.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IDoiRegistrationAgency
 *
 * @ingroup plugins
 *
 * @brief Interface that registration agency plugins must implement to support DOI registrations.
 */

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
