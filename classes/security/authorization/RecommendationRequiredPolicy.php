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

use Illuminate\Http\Response;
use PKP\security\authorization\AuthorizationPolicy;
use PKP\submission\reviewer\recommendation\ReviewerRecommendation;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RecommendationRequiredPolicy extends AuthorizationPolicy
{
    public $reviewerRecommendationId;
    protected string $message;

    public function __construct(int $reviewerRecommendationId, string $message = 'api.404.resourceNotFound')
    {
        parent::__construct($message);
        $this->reviewerRecommendationId = $reviewerRecommendationId;
        $this->message = $message;
    }

    /**
     * @see \PKP\security\authorization\AuthorizationPolicy::effect()
     */
    public function effect(): int
    {
        if (!ReviewerRecommendation::find($this->reviewerRecommendationId)) {
            throw new NotFoundHttpException(
                message: __($this->message),
                code: Response::HTTP_NOT_FOUND
            );
        }

        return AuthorizationPolicy::AUTHORIZATION_PERMIT;
    }
}
