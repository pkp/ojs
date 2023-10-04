<?php

/**
 * @defgroup api_v1_submissions Submission API requests
 */

/**
 * @file api/v1/submissions/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_submissions
 *
 * @brief Handle requests for submission API functions.
 *
 */
$urlParts = explode('/', trim($_SERVER['PATH_INFO'], '/'));

if (count($urlParts) >= 6 && $urlParts[5] == 'files') {
    return new \PKP\core\PKPApiRoutingHandler(new \PKP\API\v1\submissions\PKPSubmissionFileController());
} else {
    return new \PKP\core\PKPApiRoutingHandler(new \APP\API\v1\submissions\SubmissionController());
}
