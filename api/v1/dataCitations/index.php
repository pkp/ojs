<?php

/**
 * @defgroup api_v1_data_citations Data Citation API requests
 */

/**
 * @file api/v1/dataCitations/index.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_data_citations
 *
 * @brief Handle API requests for data citations.
 */

return new \PKP\handler\APIHandler(new \PKP\API\v1\dataCitations\PKPDataCitationController());
