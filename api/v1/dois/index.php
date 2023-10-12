<?php
/**
 * @defgroup api_v1_dois DOI API requests
 */

/**
 * @file api/v1/dois/index.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_dois
 *
 * @brief Handle API requests for DOI operations.
 */

return new \PKP\handler\APIHandler(new \APP\API\v1\dois\DoiController());
