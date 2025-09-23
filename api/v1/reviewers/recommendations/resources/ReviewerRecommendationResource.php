<?php

/**
 * @file api/v1/reviewers/recommendations/resources/ReviewerRecommendationResource.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewerRecommendationResource
 *
 * @brief API resource class
 *
 */

namespace APP\API\v1\reviewers\recommendations\resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewerRecommendationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'contextId' => $this->contextId,
            'status' => $this->status,
            'removable' => $this->removable,
            'title' => $this->title,
        ];
    }
}
