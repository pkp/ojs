<?php

/**
 * @file api/v1/reviewers/index.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Handle API requests context's reviewers recommendations
 */

return new \PKP\handler\APIHandler(
    new \APP\API\v1\reviewers\recommendations\ReviewerRecommendationController()
);
