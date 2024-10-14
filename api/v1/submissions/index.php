<?php

/**
 * @defgroup api_v1_submissions Submission API requests
 */

/**
 * @file api/v1/submissions/index.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_submissions
 *
 * @brief Handle requests for submission API functions.
 *
 */

$urlParts = explode('/', trim($_SERVER['PATH_INFO'], '/'));

if (count($urlParts) >= 6 && $urlParts[5] == 'files') {
    return new \PKP\handler\APIHandler(new \PKP\API\v1\submissions\PKPSubmissionFileController());
}

if (in_array('jats', $urlParts)) {
    return new \PKP\handler\APIHandler(new \PKP\API\v1\jats\PKPJatsController());
}

if (in_array('suggestions', $urlParts)) {
    return new \PKP\handler\APIHandler(new PKP\API\v1\reviewers\suggestions\ReviewerSuggestionController());
}

return new \PKP\handler\APIHandler(new \APP\API\v1\submissions\SubmissionController());
