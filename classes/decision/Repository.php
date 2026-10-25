<?php

/**
 * @file classes/decision/Repository.php
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2000-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Repository
 *
 * @brief A repository to find and manage editorial decisions.
 */

namespace APP\decision;

use APP\decision\types\Accept;
use APP\decision\types\SkipExternalReview;
use Illuminate\Database\Eloquent\Collection;
use PKP\decision\types\BackFromCopyediting;
use PKP\decision\types\BackFromProduction;
use PKP\decision\types\CancelReviewRound;
use PKP\decision\types\Decline;
use PKP\decision\types\InitialDecline;
use PKP\decision\types\MoveToDone;
use PKP\decision\types\NewExternalReviewRound;
use PKP\decision\types\RecommendAccept;
use PKP\decision\types\RecommendDecline;
use PKP\decision\types\RecommendResubmit;
use PKP\decision\types\RecommendRevisions;
use PKP\decision\types\RequestRevisions;
use PKP\decision\types\Resubmit;
use PKP\decision\types\ReturnToDone;
use PKP\decision\types\ReturnToWorkflow;
use PKP\decision\types\RevertDecline;
use PKP\decision\types\RevertInitialDecline;
use PKP\decision\types\RevertWithdraw;
use PKP\decision\types\RevertWithdrawInCopyediting;
use PKP\decision\types\RevertWithdrawInProduction;
use PKP\decision\types\RevertWithdrawInReview;
use PKP\decision\types\SendExternalReview;
use PKP\decision\types\SendToProduction;
use PKP\decision\types\Withdraw;
use PKP\decision\types\WithdrawInCopyediting;
use PKP\decision\types\WithdrawInProduction;
use PKP\decision\types\WithdrawInReview;
use PKP\notification\Notification;
use PKP\plugins\Hook;

class Repository extends \PKP\decision\Repository
{
    /** The valid decision types */
    protected ?Collection $decisionTypes;

    public function getDecisionTypes(): Collection
    {
        if (!isset($this->decisionTypes)) {
            $decisionTypes = new Collection([
                new Accept(),
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
                new BackFromProduction(),
                new BackFromCopyediting(),
                new CancelReviewRound(),
                new MoveToDone(),
                new ReturnToWorkflow(),
                new ReturnToDone(),
                new Withdraw(),
                new WithdrawInReview(),
                new WithdrawInCopyediting(),
                new WithdrawInProduction(),
                new RevertWithdraw(),
                new RevertWithdrawInReview(),
                new RevertWithdrawInCopyediting(),
                new RevertWithdrawInProduction(),
            ]);
            Hook::call('Decision::types', [$decisionTypes]);
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

    public function getDecisionTypesMadeByRecommendingUsers(int $stageId): array
    {
        $recommendatorsAvailableDecisions = [];
        switch ($stageId) {
            case WORKFLOW_STAGE_ID_SUBMISSION:
                $recommendatorsAvailableDecisions = [
                    new SendExternalReview()
                ];
        }

        Hook::call('Workflow::RecommendatorDecisions', [&$recommendatorsAvailableDecisions, $stageId]);

        return $recommendatorsAvailableDecisions;
    }
}
