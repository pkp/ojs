<?php

/**
 * @defgroup api_v1_stats Publication statistics API requests
 */

/**
 * @file api/v1/stats/index.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_stats
 *
 * @brief Handle API requests for publication statistics
 *
 */

use APP\core\Application;

$requestPath = Application::get()->getRequest()->getRequestPath();

if (strpos($requestPath, '/stats/publications')) {
    return new \PKP\handler\APIHandler(new \APP\API\v1\stats\publications\StatsPublicationController());
} elseif (strpos($requestPath, '/stats/editorial')) {
    return new \PKP\handler\APIHandler(new \APP\API\v1\stats\editorial\StatsEditorialController());
} elseif (strpos($requestPath, '/stats/users')) {
    return new \PKP\handler\APIHandler(new \PKP\API\v1\stats\users\PKPStatsUserController());
} elseif (strpos($requestPath, '/stats/issues')) {
    return new \PKP\handler\APIHandler(new \APP\API\v1\stats\issues\StatsIssueController());
} elseif (strpos($requestPath, '/stats/contexts')) {
    return new \PKP\handler\APIHandler(new \PKP\API\v1\stats\contexts\PKPStatsContextController());
} elseif (strpos($requestPath, '/stats/sushi')) {
    return new \PKP\handler\APIHandler(new \APP\API\v1\stats\sushi\StatsSushiController());
} else {
    response()->json([
        'error' => 'api.404.endpointNotFound',
        'errorMessage' => __('api.404.endpointNotFound'),
    ], \Illuminate\Http\Response::HTTP_NOT_FOUND)->send();
    exit;
}
