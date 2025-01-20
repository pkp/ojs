<?php

/**
 * @file classes/submission/maps/Schema.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Schema
 *
 * @brief Map submissions to the properties defined in the submission schema
 */

namespace APP\submission\maps;

use APP\core\Application;
use APP\decision\types\Accept;
use APP\decision\types\SkipExternalReview;
use APP\facades\Repo;
use APP\submission\Submission;
use Illuminate\Support\Collection;
use PKP\db\DAORegistry;
use PKP\decision\DecisionType;
use PKP\decision\types\BackFromCopyediting;
use PKP\decision\types\BackFromProduction;
use PKP\decision\types\CancelReviewRound;
use PKP\decision\types\Decline;
use PKP\decision\types\InitialDecline;
use PKP\decision\types\NewExternalReviewRound;
use PKP\decision\types\RequestRevisions;
use PKP\decision\types\Resubmit;
use PKP\decision\types\RevertDecline;
use PKP\decision\types\RevertInitialDecline;
use PKP\decision\types\SendExternalReview;
use PKP\decision\types\SendToProduction;
use PKP\plugins\Hook;
use PKP\security\Role;
use PKP\submission\PKPSubmission;
use PKP\submission\reviewRound\ReviewRoundDAO;

class Schema extends \PKP\submission\maps\Schema
{
    /**
     * @copydoc \PKP\submission\maps\Schema::mapByProperties()
     */
    protected function mapByProperties(array $props, Submission $submission, bool|Collection $anonymizeReviews = false): array
    {
        $output = parent::mapByProperties($props, $submission, $anonymizeReviews);

        if (in_array('urlPublished', $props)) {
            $output['urlPublished'] = $this->request->getDispatcher()->url(
                $this->request,
                Application::ROUTE_PAGE,
                $this->context->getPath(),
                'article',
                'view',
                [$submission->getBestId()]
            );
        }

        if (in_array('scheduledIn', $props)) {
            $output['scheduledIn'] = $submission->getData('status') == PKPSubmission::STATUS_SCHEDULED ?
                $submission->getCurrentPublication()->getData('issueId') : null;
        }

        $locales = $this->context->getSupportedSubmissionMetadataLocales();

        if (!in_array($primaryLocale = $submission->getData('locale'), $locales)) {
            $locales[] = $primaryLocale;
        }

        $output = $this->schemaService->addMissingMultilingualValues($this->schemaService::SCHEMA_SUBMISSION, $output, $locales);

        ksort($output);

        return $this->withExtensions($output, $submission);
    }

    protected function appSpecificProps(): array
    {
        return [
            'scheduledIn',
        ];
    }


    /**
     * Gets the Editorial decisions available to editors for a given stage of a submission
     *
     * This method returns decisions only for active stages. For inactive stages, it returns an empty array.
     *
     * @return DecisionType[]
     *
     * @hook Workflow::Decisions [[&$decisionTypes, $stageId]]
     */
    protected function getAvailableEditorialDecisions(int $stageId, Submission $submission): array
    {
        $request = Application::get()->getRequest();
        $user = $request->getUser();
        $isActiveStage = $submission->getData('stageId') == $stageId;
        $permissions = $this->checkDecisionPermissions($stageId, $submission, $user, $request->getContext()->getId());
        $userHasAccessibleRoles = $user->hasRole([Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_ASSISTANT], $request->getContext()->getId());

        if (!$userHasAccessibleRoles || !$isActiveStage || !$permissions['canMakeDecision']) {
            return [];
        }

        $decisionTypes = []; /** @var DecisionType[] $decisionTypes */
        $isOnlyRecommending = $permissions['isOnlyRecommending'];

        if ($isOnlyRecommending && $stageId == WORKFLOW_STAGE_ID_SUBMISSION) {
            $decisionTypes = Repo::decision()->getDecisionTypesMadeByRecommendingUsers($stageId);
        } else {
            switch ($stageId) {
                case WORKFLOW_STAGE_ID_SUBMISSION:
                    $decisionTypes = [
                        new SendExternalReview(),
                        new SkipExternalReview(),
                    ];
                    if ($submission->getData('status') === Submission::STATUS_DECLINED) {
                        // when the submission is declined, allow only reverting declined status
                        $decisionTypes = [new RevertInitialDecline()];
                    } elseif ($submission->getData('status') === Submission::STATUS_QUEUED) {
                        $decisionTypes[] = new InitialDecline();
                    }
                    break;
                case WORKFLOW_STAGE_ID_EXTERNAL_REVIEW:
                    $decisionTypes = [
                        new RequestRevisions(),
                        new Resubmit(),
                        new Accept(),
                        new NewExternalReviewRound()
                    ];
                    $cancelReviewRound = new CancelReviewRound();
                    $reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO'); /** @var ReviewRoundDAO $reviewRoundDao */
                    $reviewRound = $reviewRoundDao->getLastReviewRoundBySubmissionId($submission->getId(), $stageId);

                    if ($cancelReviewRound->canRetract($submission, $reviewRound->getId())) {
                        $decisionTypes[] = $cancelReviewRound;
                    }
                    if ($submission->getData('status') === Submission::STATUS_DECLINED) {
                        // when the submission is declined, allow only reverting declined status
                        $decisionTypes = [new RevertDecline()];
                    } elseif ($submission->getData('status') === Submission::STATUS_QUEUED) {
                        $decisionTypes[] = new Decline();
                    }
                    break;
                case WORKFLOW_STAGE_ID_EDITING:
                    $decisionTypes = [
                        new SendToProduction(),
                        new BackFromCopyediting(),
                    ];
                    break;
                case WORKFLOW_STAGE_ID_PRODUCTION:
                    if ($submission->getData('status') !== Submission::STATUS_PUBLISHED) {
                        $decisionTypes[] = new BackFromProduction();
                    }

                    break;
            }
        }

        Hook::call('Workflow::Decisions', [&$decisionTypes, $stageId]);

        return $decisionTypes;
    }
}
