<?php

/**
 * @file api/v1/reviewers/recommendations/formRequests/UpdateStatusReviewerRecommendation.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UpdateStatusReviewerRecommendation
 *
 * @brief Form request class to validation updating of resource status
 *
 */

namespace APP\API\v1\reviewers\recommendations\formRequests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusReviewerRecommendation extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'boolean'
            ],
        ];
    }
}
