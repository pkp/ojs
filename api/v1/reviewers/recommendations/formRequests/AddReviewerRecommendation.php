<?php

/**
 * @file api/v1/reviewers/recommendations/formRequests/AddReviewerRecommendation.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AddReviewerRecommendation
 *
 * @brief Form request class to validation storing of resource
 *
 */

namespace APP\API\v1\reviewers\recommendations\formRequests;

use APP\core\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use PKP\submission\reviewer\recommendation\ReviewerRecommendation;
use PKP\validation\traits\HasMultilingualRule;

class AddReviewerRecommendation extends FormRequest
{
    use HasMultilingualRule;

    /**
     * @copydoc \PKP\validation\traits\HasMultilingualRule::multilingualInputs()
     */
    public function multilingualInputs(): array
    {
        return (new ReviewerRecommendation())->getMultilingualProps();
    }

    /**
     * @copydoc \PKP\validation\traits\HasMultilingualRule::primaryLocale()
     */
    public function primaryLocale(): ?string
    {
        return Application::get()->getRequest()->getContext()->getPrimaryLocale();
    }

    /**
     * @copydoc \PKP\validation\traits\HasMultilingualRule::allowedLocales()
     */
    public function allowedLocales(): array
    {
        return Application::get()->getRequest()->getContext()->getSupportedFormLocales();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $contextDao = Application::getContextDAO();

        return [
            'contextId' => [
                'required',
                'integer',
                Rule::exists($contextDao->tableName, $contextDao->primaryKeyColumn),
            ],
            'title' => [
                'required',
            ],
            'status' => [
                'required',
                'boolean'
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'contextId' => Application::get()->getRequest()->getContext()->getId(),
        ]);
    }
}
