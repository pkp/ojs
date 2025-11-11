<?php

/**
 * @defgroup api_v1_vocabs Controlled vocabulary API requests
 */

/**
 * @file api/v1/vocabs/index.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_vocabs
 *
 * @brief Handle API requests for vocabs.
 */

use APP\core\Application;

$requestPath = Application::get()->getRequest()->getRequestPath();

// Route to interests endpoint (public, no authentication required)
if (strpos($requestPath, '/vocabs/interests') !== false) {
    return new \PKP\handler\APIHandler(new \PKP\API\v1\vocabs\PKPInterestController());
}

// Default route to standard vocabs endpoint (authentication required)
return new \PKP\handler\APIHandler(new \PKP\API\v1\vocabs\PKPVocabController());
