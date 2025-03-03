<?php


/**
 * @file classes/security/authorization/RecommendationRequiredPolicy.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RecommendationRequiredPolicy
 *
 * @brief
 */

namespace APP\security\authorization;

use PKP\security\authorization\AuthorizationPolicy;
use PKP\submission\reviewer\recommendation\ReviewerRecommendation;

class RecommendationRequiredPolicy extends AuthorizationPolicy
{
    public $recommendationId;

    public function __construct(int $recommendationId, string $message = 'api.404.resourceNotFound')
    {
        parent::__construct($message);
        $this->recommendationId = $recommendationId;
    }

    /**
     * @see \PKP\security\authorization\AuthorizationPolicy::effect()
     */
    public function effect(): int
    {
        return ReviewerRecommendation::find($this->recommendationId)
            ? AuthorizationPolicy::AUTHORIZATION_PERMIT
            : AuthorizationPolicy::AUTHORIZATION_DENY;
    }
}
