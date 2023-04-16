<?php

/**
 * @defgroup api_v1_stats Publication statistics API requests
 */

/**
 * @file api/v1/stats/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
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
    return new \APP\API\v1\stats\publications\StatsPublicationHandler();
} elseif (strpos($requestPath, '/stats/editorial')) {
    return new \APP\API\v1\stats\editorial\StatsEditorialHandler();
} elseif (strpos($requestPath, '/stats/users')) {
    return new \PKP\API\v1\stats\users\PKPStatsUserHandler();
} elseif (strpos($requestPath, '/stats/issues')) {
    return new \APP\API\v1\stats\issues\StatsIssueHandler();
} elseif (strpos($requestPath, '/stats/contexts')) {
    return new \PKP\API\v1\stats\contexts\PKPStatsContextHandler();
} elseif (strpos($requestPath, '/stats/sushi')) {
    return new \APP\API\v1\stats\sushi\StatsSushiHandler();
} else {
    http_response_code('404');
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'api.404.endpointNotFound',
        'errorMessage' => __('api.404.endpointNotFound'),
    ]);
    exit;
}
