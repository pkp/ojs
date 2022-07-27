<?php
/**
 * @file classes/decision/Repository.inc.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class decision
 *
 * @brief A repository to find and manage editorial decisions.
 */

namespace APP\decision;

use APP\decision\types\Accept;
use APP\decision\types\SkipExternalReview;
use APP\notification\Notification;
use Illuminate\Database\Eloquent\Collection;
use PKP\decision\types\BackToCopyediting;
use PKP\decision\types\BackToReview;
use PKP\decision\types\BackToSubmissionFromCopyediting;
use PKP\decision\types\Decline;
use PKP\decision\types\InitialDecline;
use PKP\decision\types\NewExternalReviewRound;
use PKP\decision\types\RecommendAccept;
use PKP\decision\types\RecommendDecline;
use PKP\decision\types\RecommendResubmit;
use PKP\decision\types\RecommendRevisions;
use PKP\decision\types\RequestRevisions;
use PKP\decision\types\Resubmit;
use PKP\decision\types\RevertDecline;
use PKP\decision\types\RevertInitialDecline;
use PKP\decision\types\SendExternalReview;
use PKP\decision\types\SendToProduction;
use PKP\plugins\HookRegistry;

class Repository extends \PKP\decision\Repository
{
    /** The valid decision types */
    protected ?Collection $decisionTypes;

    public function getDecisionTypes(): Collection
    {
        if (!isset($this->decisionTypes)) {
            $decisionTypes = new Collection([
                new Accept(),
                new BackToCopyediting(),
                new BackToReview(),
                new BackToSubmissionFromCopyediting(),
                new Decline(),
                new InitialDecline(),
                new NewExternalReviewRound(),
                new RecommendAccept(),
                new RecommendDecline(),
                new RecommendResubmit(),
                new RecommendRevisions(),
                new Resubmit(),
                new RequestRevisions(),
                new RevertDecline(),
                new RevertInitialDecline(),
                new SendExternalReview(),
                new SendToProduction(),
                new SkipExternalReview(),
            ]);
            HookRegistry::call('Decision::types', [$decisionTypes]);
            $this->decisionTypes = $decisionTypes;
        }

        return $this->decisionTypes;
    }

    public function getDeclineDecisionTypes(): array
    {
        return [
            new InitialDecline(),
            new Decline(),
        ];
    }

    protected function getReviewNotificationTypes(): array
    {
        return [Notification::NOTIFICATION_TYPE_PENDING_EXTERNAL_REVISIONS];
    }
}
