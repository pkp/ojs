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

use APP\jobs\orcid\DepositOrcidReview;
use PKP\orcid\actions\PKPSendReviewToOrcid;

class SendReviewToOrcid extends PKPSendReviewToOrcid
{
    /** @inheritDoc */
    public function execute(): void
    {
        dispatch(new DepositOrcidReview($this->submission, $this->context, $this->reviewAssignment));
    }
}
