<?php

/**
 * @file api/v1/publicationPeerReviews/index.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_publicationPeerReviews
 *
 * @brief Handle API requests for public peer reviews.
 *
 */

return new \PKP\handler\APIHandler(new \PKP\API\v1\publicationPeerReviews\PublicationPeerReviewController());
