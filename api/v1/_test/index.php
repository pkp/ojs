<?php

/**
 * @defgroup api_v1__test Test-only API requests
 */

/**
 * @file api/v1/_test/index.php
 *
 * Copyright (c) 2023-2026 Simon Fraser University
 * Copyright (c) 2023-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1__test
 *
 * @brief Dispatch requests for the Playwright e2e harness's test-only API.
 *
 * THE NAMESPACE DOES NOT EXIST WITHOUT THE ENVIRONMENT. When TEST_API_KEY is
 * absent — which is every production install — this file answers 404 and no
 * route is registered at all. When it is present, every route additionally
 * requires the matching X-Test-Key header (\PKP\testing\RequireTestApiKey).
 */

use Illuminate\Http\Response;
use PKP\testing\TestApiGate;

if (!TestApiGate::isEnabled()) {
    response()->json([
        'error' => 'api.404.endpointNotFound',
        'errorMessage' => __('api.404.endpointNotFound'),
    ], Response::HTTP_NOT_FOUND)->send();
    exit;
}

$urlParts = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));

if (in_array('scenarios', $urlParts, true)) {
    if (in_array('submission', $urlParts, true)) {
        return new \PKP\handler\APIHandler(new \APP\API\v1\_test\SubmissionScenarioController());
    }

    if (in_array('context', $urlParts, true)) {
        return new \PKP\handler\APIHandler(new \APP\API\v1\_test\JournalScenarioController());
    }
}

return new \PKP\handler\APIHandler(new \APP\API\v1\_test\TestBootstrapController());
