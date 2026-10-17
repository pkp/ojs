<?php

/**
 * @defgroup api_v1_test Test-only API requests
 */

/**
 * @file api/v1/_test/index.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_test
 *
 * @brief Dispatcher for test-only endpoints. Routes inside each
 *        controller are gated by TestModeGate middleware
 *        (APPLICATION_ENV === 'test' + key). Mirrors the path-based
 *        dispatch pattern used by api/v1/stats/index.php.
 */

use APP\core\Application;

$requestPath = Application::get()->getRequest()->getRequestPath();

// Scenarios share the `_test/scenarios` handler path but split by resource:
// /scenarios/journal builds a scratch (or baseline) context;
// /scenarios/submission builds a submission. Each needs its own controller
// because Laravel routes are registered per-controller via getGroupRoutes().
if (strpos($requestPath, '/_test/scenarios/journal') !== false) {
    return new \PKP\handler\APIHandler(new \APP\API\v1\_test\JournalScenarioController());
}

return new \PKP\handler\APIHandler(new \APP\API\v1\_test\SubmissionScenarioController());
