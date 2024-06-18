<?php

/**
 * @defgroup api_v1_orcid ORCID API requests
 */

/**
 * @file api/v1/orcid/index.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_orcid
 *
 * @brief Handle requests for ORCID API functions.
 *
 */

return new \PKP\handler\APIHandler(new \PKP\API\v1\orcid\OrcidController());
