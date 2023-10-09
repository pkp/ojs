<?php

/**
 * @defgroup api_v1_jobs Queue Jobs API requests
 */

/**
 * @file api/v1/jobs/index.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_users
 *
 * @brief Handle requests for Queue Jobs API functions.
 *
 */

return new \PKP\handler\APIHandler(new \PKP\API\v1\jobs\PKPJobController());
