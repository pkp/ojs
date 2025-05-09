<?php

/**
 * @defgroup api_v1_categories Categories API requests
 */

/**
 * @file api/v1/categories/index.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_categories
 *
 * @brief Handle API requests for categories.
 */

return new \PKP\handler\APIHandler(new \PKP\API\v1\categories\CategoryCategoryController());
