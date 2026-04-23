<?php

/**
 * @file api/v1/_test/SubmissionScenarioController.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionScenarioController
 *
 * @ingroup api_v1_test
 *
 * @brief OJS-specific subclass for the submission scenario endpoint.
 *        Initially empty; entry point for OJS-only scenario extensions.
 */

namespace APP\API\v1\_test;

use PKP\API\v1\_test\PKPSubmissionScenarioController;

class SubmissionScenarioController extends PKPSubmissionScenarioController
{
    // OJS uses the shared implementation as-is. Override
    // getGroupRoutes() or submission() here to wire in OJS-only
    // processors (e.g. issue-assignment-specific logic).
}
