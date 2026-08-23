<?php

/**
 * @file classes/testing/BootstrapSeeder.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class BootstrapSeeder
 *
 * @brief OJS base seed: sections (by abbrev) and the issues overlay.
 */

namespace APP\testing;

use APP\facades\Repo;
use PKP\context\Context;
use PKP\core\Core;
use PKP\plugins\Hook;
use PKP\testing\PKPBootstrapSeeder;
use PKP\testing\Spec;

class BootstrapSeeder extends PKPBootstrapSeeder
{
    protected function structureKey(): string
    {
        return 'sections';
    }

    protected function parseStructure(Spec $spec): array
    {
        return [
            'abbrev' => (string) $spec->require('abbrev'),
            'title' => $spec->get('title'),
            'policy' => $spec->get('policy'),
            'wordCount' => $spec->get('wordCount'),
            'abstractsNotRequired' => (bool) $spec->get('abstractsNotRequired', false),
            'identifyType' => $spec->get('identifyType'),
        ];
    }

    protected function addStructure(Context $context, array $plan, int $sequence): int
    {
        return self::addSection($context, $plan, $sequence);
    }

    protected function resolveStructureId(Context $context, string $identifier): ?int
    {
        return self::findSectionId($context, $identifier);
    }

    public static function addSection(Context $context, array $plan, int $sequence): int
    {
        $locale = $context->getPrimaryLocale();
        $localize = fn ($value, $default = null) => is_array($value) ? $value : [$locale => $value ?? $default];

        // The Context::add hook already created the default "Articles"
        // section; the FIRST declared section renames/updates it (as a journal
        // manager would) instead of seeding a same-abbrev duplicate.
        if ($sequence === 1) {
            $existing = Repo::section()->getCollector()
                ->filterByContextIds([$context->getId()])
                ->getMany();
            if ($existing->count() === 1) {
                $params = [
                    'title' => $localize($plan['title'], $plan['abbrev']),
                    'abbrev' => $localize($plan['abbrev']),
                    'abstractsNotRequired' => $plan['abstractsNotRequired'] ?? false,
                ];
                if (($plan['policy'] ?? null) !== null) {
                    $params['policy'] = $localize($plan['policy']);
                }
                if (($plan['wordCount'] ?? null) !== null) {
                    $params['wordCount'] = (int) $plan['wordCount'];
                }
                if (($plan['identifyType'] ?? null) !== null) {
                    $params['identifyType'] = $localize($plan['identifyType']);
                }
                $defaultSection = $existing->first();
                Repo::section()->edit($defaultSection, $params);
                return $defaultSection->getId();
            }
        }

        $section = Repo::section()->newDataObject();
        $section->setData('contextId', $context->getId());
        $section->setData('sequence', $sequence);
        $section->setData('editorRestricted', false);
        $section->setData('metaIndexed', true);
        $section->setData('metaReviewed', true);
        $section->setData('abstractsNotRequired', $plan['abstractsNotRequired'] ?? false);
        foreach ($localize($plan['title'], $plan['abbrev']) as $l => $value) {
            $section->setData('title', $value, $l);
        }
        foreach ($localize($plan['abbrev']) as $l => $value) {
            $section->setData('abbrev', $value, $l);
        }
        if (($plan['policy'] ?? null) !== null) {
            foreach ($localize($plan['policy']) as $l => $value) {
                $section->setData('policy', $value, $l);
            }
        }
        if (($plan['wordCount'] ?? null) !== null) {
            $section->setData('wordCount', (int) $plan['wordCount']);
        }
        if (($plan['identifyType'] ?? null) !== null) {
            foreach ($localize($plan['identifyType']) as $l => $value) {
                $section->setData('identifyType', $value, $l);
            }
        }
        return Repo::section()->add($section);
    }

    /** Match a section by abbrev (any locale), case-insensitively. */
    public static function findSectionId(Context $context, string $identifier): ?int
    {
        $sections = Repo::section()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getMany();
        foreach ($sections as $section) {
            $abbrevs = (array) ($section->getData('abbrev') ?? []);
            foreach ($abbrevs as $abbrev) {
                if (strcasecmp((string) $abbrev, $identifier) === 0) {
                    return $section->getId();
                }
            }
        }
        return null;
    }

    protected function parseOverlay(Spec $root): array
    {
        $issues = [];
        foreach ($root->childList('issues') as $spec) {
            $issues[] = [
                'volume' => (int) $spec->require('volume'),
                'number' => (string) $spec->require('number'),
                'year' => (int) $spec->require('year'),
                'published' => (bool) $spec->get('published', false),
            ];
        }
        return ['issues' => $issues];
    }

    protected function executeOverlay(Context $context, array $overlayPlan): void
    {
        foreach ($overlayPlan['issues'] ?? [] as $plan) {
            $issue = Repo::issue()->newDataObject();
            $issue->setData('journalId', $context->getId());
            $issue->setData('volume', $plan['volume']);
            $issue->setData('number', $plan['number']);
            $issue->setData('year', $plan['year']);
            $issue->setData('showVolume', true);
            $issue->setData('showNumber', true);
            $issue->setData('showYear', true);
            // Access status as the Create Issue form derives it from the
            // journal's publishing mode (IssueForm::execute ~246-258):
            // subscription/none → ISSUE_ACCESS_SUBSCRIPTION, open (and the
            // seed's unset default) → ISSUE_ACCESS_OPEN.
            $issue->setData('accessStatus', match ((int) $context->getData('publishingMode')) {
                \APP\journal\Journal::PUBLISHING_MODE_SUBSCRIPTION,
                \APP\journal\Journal::PUBLISHING_MODE_NONE => \APP\issue\Issue::ISSUE_ACCESS_SUBSCRIPTION,
                default => \APP\issue\Issue::ISSUE_ACCESS_OPEN,
            });
            $issue->setData('published', false);
            Repo::issue()->add($issue);

            if ($plan['published']) {
                // The publish flow, mirrored from
                // IssueGridHandler::publishIssue (~556-597): DOI creation
                // (internally a no-op unless the journal enables issue DOIs
                // — the seed does not), published flag + datePublished, the
                // publish hook, current-issue update, stale-DOI marking.
                // The handler's delayed-open-access branch (subscription
                // journals only) and its scheduled-publication sweep (no
                // publications exist at bootstrap) don't apply here.
                Repo::issue()->createDoi($issue);
                $issue->setData('published', true);
                if (!$issue->getData('datePublished')) {
                    $issue->setData('datePublished', Core::getCurrentDate());
                }
                Hook::call('IssueGridHandler::publishIssue', [&$issue]);
                Repo::issue()->updateCurrent($context->getId(), $issue);
                Repo::doi()->issueUpdated($issue);
            }
        }
    }
}
