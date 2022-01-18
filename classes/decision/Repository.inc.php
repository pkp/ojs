<?php
/**
 * @file classes/decision/Repository.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class decision
 *
 * @brief A repository to find and manage editorial decisions.
 */

namespace APP\decision;

use APP\notification\Notification;
use Illuminate\Database\Eloquent\Collection;
use PKP\decision\types\Accept;
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
use PKP\decision\types\SkipReview;
use PKP\plugins\HookRegistry;

class Repository extends \PKP\decision\Repository
{
    /** The valid decision types */
    protected ?Collection $types;

    /** @copydoc \PKP\decision\Repository::getTypes */
    public function getTypes(): Collection
    {
        if (!isset($this->types)) {
            $types = new Collection([
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
                new SkipReview(),
            ]);
            HookRegistry::call('Decision::types', [$types]);
            $this->types = $types;
        }

        return $this->types;
    }

    protected function getReviewNotificationTypes(): array
    {
        return [Notification::NOTIFICATION_TYPE_PENDING_EXTERNAL_REVISIONS];
    }
}
