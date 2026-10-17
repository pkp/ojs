<?php

/**
 * @defgroup api_v1__test Test-only API requests (Playwright harness)
 */

/**
 * @file api/v1/_test/index.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1__test
 *
 * @brief Test-only seeding endpoints. The namespace answers 404 unless
 * TEST_API_KEY is present in the server's process environment — it must never
 * exist on a production install. Requests are further gated by the
 * X-Test-Key header (see PKPTestController::authorize()).
 */

if (!getenv('TEST_API_KEY')) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not Found']);
    exit;
}

return new \PKP\handler\APIHandler(new \APP\API\v1\_test\TestController());
