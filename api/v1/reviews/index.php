<?php
/**
 * @defgroup api_v1_reviews Reviews API requests
 */

/**
 * @file api/v1/reviews/index.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_reviews
 *
 * @brief Handle API requests for reviews.
 */

return new \PKP\handler\APIHandler(new \PKP\API\v1\reviews\PKPReviewController());
