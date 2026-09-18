<?php

/**
 * @file classes/testing/SubmissionScenarioBuilder.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionScenarioBuilder
 *
 * @brief OJS submission scenario overlays: `section` (abbrev; defaults to the
 * journal's first section) and, for published scenarios, `issue`
 * ({volume, number, year} matching a seeded issue).
 */

namespace APP\testing;

use APP\facades\Repo;
use PKP\context\Context;
use PKP\publication\PKPPublication;
use PKP\submission\PKPSubmission;
use PKP\testing\scenario\PKPSubmissionScenarioBuilder;
use PKP\testing\Spec;
use PKP\testing\SpecException;

class SubmissionScenarioBuilder extends PKPSubmissionScenarioBuilder
{
    protected function parsePublicationOverlay(Context $context, Spec $root): array
    {
        $abbrev = $root->get('section');
        if ($abbrev !== null) {
            $sectionId = BootstrapSeeder::findSectionId($context, (string) $abbrev);
            if (!$sectionId) {
                throw new SpecException('section', "Unknown section abbrev \"{$abbrev}\" in context \"{$context->getPath()}\"");
            }
            return ['sectionId' => $sectionId];
        }
        $firstSection = Repo::section()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getMany()
            ->first();
        return $firstSection ? ['sectionId' => $firstSection->getId()] : [];
    }

    protected function parsePublishOverlay(Context $context, Spec $root): array
    {
        $issueSpec = $root->child('issue');
        if (!$issueSpec) {
            return [];
        }
        $volume = $issueSpec->get('volume');
        $number = $issueSpec->get('number');
        $year = $issueSpec->get('year');

        $issues = Repo::issue()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getMany();
        foreach ($issues as $issue) {
            if (
                ($volume === null || (int) $issue->getData('volume') === (int) $volume) &&
                ($number === null || (string) $issue->getData('number') === (string) $number) &&
                ($year === null || (int) $issue->getData('year') === (int) $year)
            ) {
                return ['issueId' => $issue->getId()];
            }
        }
        throw new SpecException('issue', 'No issue matches the given volume/number/year in this context');
    }

    protected function beforePublish(Context $context, PKPSubmission $submission, PKPPublication $publication, array $overlayPlan): void
    {
        if (!empty($overlayPlan['issueId'])) {
            Repo::publication()->edit($publication, ['issueId' => $overlayPlan['issueId']]);
        }
    }
}
