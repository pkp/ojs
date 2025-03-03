<?php

/**
 * @file classes/security/authorization/RecommendationAccessPolicy.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RecommendationAccessPolicy
 *
 * @brief Access policy to limit access to reviewer recommendations to associated journal only
 */

namespace APP\security\authorization;

use APP\core\Request;
use PKP\security\authorization\PolicySet;

class RecommendationAccessPolicy extends PolicySet
{
    /**
     * Constructor
     */
    public function __construct(Request $request, int $recommendationId)
    {
        parent::__construct();
        $this->addPolicy(new RecommendationRequiredPolicy($recommendationId));
        $this->addPolicy(new RecommendationContextPolicy($request, $recommendationId));
    }
}
