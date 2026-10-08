<?php

/**
 * @file api/v1/_test/TestController.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class TestController
 *
 * @brief OJS wiring of the test-only API: journal bootstrap seeder and the
 * journal-flavoured scenario builders (section/issue overlays).
 */

namespace APP\API\v1\_test;

use APP\testing\BootstrapSeeder;
use APP\testing\ContextScenarioBuilder;
use APP\testing\SubmissionScenarioBuilder;
use PKP\API\v1\_test\PKPTestController;
use PKP\testing\PKPBootstrapSeeder;
use PKP\testing\scenario\PKPContextScenarioBuilder;
use PKP\testing\scenario\PKPSubmissionScenarioBuilder;

class TestController extends PKPTestController
{
    protected function bootstrapSeeder(): PKPBootstrapSeeder
    {
        return new BootstrapSeeder();
    }

    protected function contextScenarioBuilder(): PKPContextScenarioBuilder
    {
        return new ContextScenarioBuilder();
    }

    protected function submissionScenarioBuilder(): PKPSubmissionScenarioBuilder
    {
        return new SubmissionScenarioBuilder();
    }
}
