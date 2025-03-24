<?php


/**
 * @file classes/security/authorization/RecommendationContextPolicy.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RecommendationContextPolicy
 *
 * @brief
 */

namespace APP\security\authorization;

use APP\core\Request;
use PKP\context\Context;
use PKP\security\authorization\AuthorizationPolicy;
use PKP\submission\reviewer\recommendation\ReviewerRecommendation;

class RecommendationContextPolicy extends AuthorizationPolicy
{
    public ?Context $context;
    public int $recommendationId;

    /**
     * Constructor
     */
    public function __construct(
        Request $request,
        int $recommendationId,
        string $message = 'manager.reviewerRecommendations.context.restriction'
    ) {
        parent::__construct($message);
        $this->recommendationId = $recommendationId;
        $this->context = $request->getContext();
    }

    /**
     * @see \PKP\security\authorization\AuthorizationPolicy::effect()
     */
    public function effect(): int
    {
        if (!$this->context) {
            return AuthorizationPolicy::AUTHORIZATION_DENY;
        }

        $recommendation = ReviewerRecommendation::find($this->recommendationId);

        if ($this->context->getId() !== $recommendation->contextId) {
            return AuthorizationPolicy::AUTHORIZATION_DENY;
        }

        return AuthorizationPolicy::AUTHORIZATION_PERMIT;
    }
}
