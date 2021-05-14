<?php

/**
 * @file classes/submission/reviewer/form/ReviewerReviewStep3Form.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewerReviewStep3Form
 * @ingroup submission_reviewer_form
 *
 * @brief Form for Step 3 of a review in OJS.
 */

namespace APP\submission\reviewer\form;

use PKP\submission\reviewer\form\PKPReviewerReviewStep3Form;

class ReviewerReviewStep3Form extends PKPReviewerReviewStep3Form
{
    /**
     * @copydoc PKPReviewerReviewStep3Form::__construct()
     */
    public function __construct($request, $reviewerSubmission, $reviewAssignment)
    {
        parent::__construct($request, $reviewerSubmission, $reviewAssignment);
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'recommendation', 'required', 'reviewer.submission.reviewFormResponse.form.recommendationRequired', function ($recommendation) {
            return isset($recommendation);
        }));
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\submission\reviewer\form\ReviewerReviewStep3Form', '\ReviewerReviewStep3Form');
}
