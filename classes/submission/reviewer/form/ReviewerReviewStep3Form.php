<?php

/**
 * @file classes/submission/reviewer/form/ReviewerReviewStep3Form.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewerReviewStep3Form
 *
 * @ingroup submission_reviewer_form
 *
 * @brief Form for Step 3 of a review in OJS.
 */

namespace APP\submission\reviewer\form;

use APP\submission\Submission;
use PKP\core\PKPRequest;
use PKP\form\validation\FormValidatorCustom;
use PKP\submission\reviewAssignment\ReviewAssignment;
use PKP\submission\reviewer\form\PKPReviewerReviewStep3Form;
use PKP\submission\reviewer\recommendation\ReviewerRecommendation;

class ReviewerReviewStep3Form extends PKPReviewerReviewStep3Form
{
    /**
     * @copydoc PKPReviewerReviewStep3Form::__construct()
     */
    public function __construct(PKPRequest $request, Submission $reviewSubmission, ReviewAssignment $reviewAssignment)
    {
        parent::__construct($request, $reviewSubmission, $reviewAssignment);
        $this->addCheck(new FormValidatorCustom(
            $this,
            'reviewerRecommendationId',
            'required',
            'reviewer.submission.reviewFormResponse.form.recommendationRequired',
            fn ($reviewerRecommendationId) => ReviewerRecommendation::query()
                ->withContextId($reviewSubmission->getData('contextId'))
                ->withRecommendations([$reviewerRecommendationId])
                ->exists()
        ));
    }
}
