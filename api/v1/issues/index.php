<?php

/**
 * @defgroup api_v1_issues Issues API requests
 */

/**
 * @file api/v1/issues/index.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_issues
 *
 * @brief Handle requests for issues API functions.
 *
 */

return new \PKP\handler\APIHandler(new \APP\API\v1\issues\IssueController());
