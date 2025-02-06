<?php

/**
 * @file classes/orcid/actions/SendReviewToOrcid.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2000-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SendReviewToOrcid
 *
 * @brief Trigger review deposit to ORCID if supported by the application (currently only OJS).
 */

namespace APP\orcid\actions;

use APP\core\Application;
use APP\facades\Repo;
use APP\jobs\orcid\DepositOrcidReview;
use APP\jobs\orcid\ReconcileOrcidReviewPutCode;
use Illuminate\Support\Facades\Bus;
use PKP\context\Context;
use PKP\orcid\actions\PKPSendReviewToOrcid;

class SendReviewToOrcid extends PKPSendReviewToOrcid
{
    /** @inheritDoc */
    public function execute(): void
    {
        $review = Repo::reviewAssignment()->get($this->reviewAssignmentId);
        $reviewer = $review ? Repo::user()->get($review->getReviewerId()) : null;

        if ($reviewer && $reviewer->getData('orcidReviewPutCode')) {
            $context = Application::getContextDAO()->getById(
                Repo::submission()->get($review->getSubmissionId())->getData('contextId')
            ); /** @var Context $context */

            // We don't want to deposit new reviews if we haven't successfully reconciled old review put-codes.
            // Execute jobs sequentially, so DepositOrcidReview runs only after ReconcileOrcidReviewPutCode completes successfully.
            Bus::chain([
                new ReconcileOrcidReviewPutCode($reviewer->getId(), $context->getId()),
                new DepositOrcidReview($this->reviewAssignmentId),
            ])->dispatch();
        } else {
            dispatch(new DepositOrcidReview($this->reviewAssignmentId));
        }
    }
}
