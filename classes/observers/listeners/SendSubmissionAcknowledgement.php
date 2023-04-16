<?php
/**
 * @file classes/observers/listeners/SendSubmissionAcknowledgement.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SendSubmissionAcknowledgement
 *
 * @ingroup observers_listeners
 *
 * @brief Send an email acknowledgement to the submitting author when a new submission is submitted
 *
 * Sends an email to all users with author stage assignments and
 * sends a separate email to all other contributors named on the
 * submission.
 */

namespace APP\observers\listeners;

use Illuminate\Events\Dispatcher;
use PKP\observers\events\SubmissionSubmitted;

class SendSubmissionAcknowledgement extends \PKP\observers\listeners\SendSubmissionAcknowledgement
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            SubmissionSubmitted::class,
            SendSubmissionAcknowledgement::class
        );
    }
}
