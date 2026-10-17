<?php

/**
 * @file api/v1/_test/SubmissionScenarioController.php
 *
 * Copyright (c) 2023-2026 Simon Fraser University
 * Copyright (c) 2023-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionScenarioController
 *
 * @ingroup api_v1__test
 *
 * @brief OJS overlay for the shared submission scenario endpoint.
 *
 * Adds the two OJS concepts a seeded article needs — the SECTION it is filed
 * under and the ISSUE it is published in — and names the decision OJS uses to
 * open peer review. The shared builder never mentions either.
 */

namespace APP\API\v1\_test;

use APP\facades\Repo;
use PKP\API\v1\_test\PKPSubmissionScenarioController;
use PKP\context\Context;
use PKP\testing\scenario\ScenarioException;

class SubmissionScenarioController extends PKPSubmissionScenarioController
{
    /**
     * @copydoc \PKP\API\v1\_test\PKPTestApiController::schemaOverlayProperties()
     */
    public function schemaOverlayProperties(): array
    {
        return [
            'section' => [
                'type' => 'string',
                'description' => 'OJS overlay. Abbreviation of the section to submit to; defaults to the journal\'s first section.',
            ],
            'issue' => [
                'type' => 'object',
                'additionalProperties' => false,
                'description' => 'OJS overlay. The issue to assign the publication to, matched on the fields given.',
                'properties' => [
                    'volume' => ['type' => 'integer'],
                    'number' => ['type' => 'string'],
                    'year' => ['type' => 'integer'],
                ],
            ],
        ];
    }

    /**
     * OJS opens peer review with the Send to External Review decision.
     */
    protected function promoteToReviewDecision(): ?string
    {
        return 'sendExternalReview';
    }

    /**
     * @copydoc \PKP\API\v1\_test\PKPSubmissionScenarioController::applyPublicationOverlay()
     *
     * @throws ScenarioException
     */
    protected function applyPublicationOverlay(array $spec, Context $context, array &$publicationProps): void
    {
        $publicationProps['sectionId'] = $this->resolveSectionId($spec, $context);

        if (isset($spec['issue'])) {
            $publicationProps['issueId'] = $this->resolveIssueId($spec['issue'], $context);
        }
    }

    /**
     * @throws ScenarioException
     */
    protected function resolveSectionId(array $spec, Context $context): int
    {
        $locale = $context->getPrimaryLocale();
        $sections = Repo::section()->getCollector()->filterByContextIds([$context->getId()])->getMany();

        if ($sections->isEmpty()) {
            throw new ScenarioException("Journal '{$context->getPath()}' has no sections.", 'context');
        }

        if (!isset($spec['section'])) {
            return $sections->first()->getId();
        }

        $match = $sections->first(fn ($section) => $section->getAbbrev($locale) === $spec['section']);

        if (!$match) {
            throw new ScenarioException(
                "Journal '{$context->getPath()}' has no section with abbreviation '{$spec['section']}'. Available: "
                    . $sections->map(fn ($section) => $section->getAbbrev($locale))->implode(', ') . '.',
                'section'
            );
        }

        return $match->getId();
    }

    /**
     * @throws ScenarioException
     */
    protected function resolveIssueId(array $issueSpec, Context $context): int
    {
        $issues = Repo::issue()->getCollector()->filterByContextIds([$context->getId()])->getMany();

        $match = $issues->first(function ($issue) use ($issueSpec) {
            foreach (['volume' => 'getVolume', 'number' => 'getNumber', 'year' => 'getYear'] as $field => $getter) {
                if (isset($issueSpec[$field]) && (string) $issue->{$getter}() !== (string) $issueSpec[$field]) {
                    return false;
                }
            }

            return true;
        });

        if (!$match) {
            throw new ScenarioException(
                'No issue in journal \'' . $context->getPath() . '\' matches ' . json_encode($issueSpec) . '.',
                'issue'
            );
        }

        return $match->getId();
    }

    /**
     * @copydoc \PKP\API\v1\_test\PKPSubmissionScenarioController::submissionEcho()
     */
    protected function submissionEcho(\APP\submission\Submission $submission, array $spec): array
    {
        $publication = $submission->getCurrentPublication();

        return array_filter([
            'sectionId' => $publication?->getData('sectionId'),
            'issueId' => $publication?->getData('issueId'),
        ]);
    }
}
