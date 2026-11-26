<?php

/**
 * @file api/v1/_test/JournalScenarioController.php
 *
 * Copyright (c) 2023-2026 Simon Fraser University
 * Copyright (c) 2023-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class JournalScenarioController
 *
 * @ingroup api_v1__test
 *
 * @brief OJS overlay for the shared context scenario endpoint.
 *
 * The app-neutral spec knows about a "context"; this subclass declares the OJS
 * concepts on top of it — sections, issues and the journal-only publishing
 * settings — as OVERLAY PROPERTIES, so a spec that names them is validated here
 * and rejected as an unknown key on an app that has no such concept.
 */

namespace APP\API\v1\_test;

use APP\core\Application;
use APP\facades\Repo;
use APP\issue\Issue;
use PKP\API\v1\_test\PKPContextScenarioController;
use PKP\context\Context;
use PKP\context\SubEditorsDAO;
use PKP\core\Core;
use PKP\db\DAORegistry;
use PKP\security\Role;
use PKP\testing\scenario\ScenarioException;
use PKP\user\User;
use PKP\userGroup\UserGroup;

class JournalScenarioController extends PKPContextScenarioController
{
    /**
     * @copydoc \PKP\API\v1\_test\PKPTestApiController::schemaOverlayProperties()
     */
    public function schemaOverlayProperties(): array
    {
        return [
            'sections' => [
                'type' => 'array',
                'description' => 'OJS overlay. Sections of the journal, matched to existing sections by abbrev so the default section created with the journal is edited rather than duplicated.',
                'items' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['title', 'abbrev'],
                    'properties' => [
                        'title' => ['type' => 'string', 'minLength' => 1],
                        'abbrev' => ['type' => 'string', 'minLength' => 1],
                        'policy' => ['type' => 'string'],
                        'editorRestricted' => ['type' => 'boolean'],
                        'abstractsNotRequired' => ['type' => 'boolean'],
                        'wordCount' => ['type' => 'integer'],
                        'identifyType' => ['type' => 'string'],
                    ],
                ],
            ],
            'issues' => [
                'type' => 'array',
                'description' => 'OJS overlay. Issues of the journal.',
                'items' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'volume' => ['type' => 'integer'],
                        'number' => ['type' => 'string'],
                        'year' => ['type' => 'integer'],
                        'description' => ['type' => 'string'],
                        'published' => ['type' => 'boolean'],
                        'current' => ['type' => 'boolean'],
                        'accessStatus' => ['type' => 'integer'],
                    ],
                ],
            ],
            'publishingMode' => ['type' => 'integer'],
            'onlineIssn' => ['type' => 'string'],
            'printIssn' => ['type' => 'string'],
        ];
    }

    /**
     * @copydoc \PKP\API\v1\_test\PKPTestApiController::userSchemaOverlayProperties()
     */
    public function userSchemaOverlayProperties(): array
    {
        return [
            'sections' => [
                'type' => 'array',
                'description' => 'OJS overlay. Abbreviations of the sections this user edits, as the Sections settings form assigns section editors. The user must already be enrolled in an editorially assignable role.',
                'items' => ['type' => 'string', 'minLength' => 1],
            ],
        ];
    }

    /**
     * Assign a seeded user as a section editor of the sections it names.
     *
     * Mirrors PKPSectionForm::execute(): the assignment is a row in
     * subeditor_submission_group keyed by the user group it was made under, and
     * only users enrolled in an assignable role may be assigned. Assignments made
     * this way are what SubEditorsDAO::assignEditors() reads when a submission
     * enters the section, so a seeded section editor is a participant on new
     * submissions exactly as one configured through the UI would be.
     *
     * @throws ScenarioException
     */
    protected function afterUserSeeded(Context $context, array $userSpec, User $user, string $specKey): void
    {
        $abbrevs = $userSpec['sections'] ?? [];

        if (empty($abbrevs)) {
            return;
        }

        $locale = $context->getPrimaryLocale();
        $sections = Repo::section()->getCollector()->filterByContextIds([$context->getId()])->getMany();
        $group = $this->assignableUserGroupFor($context, $user, "{$specKey}.sections");
        /** @var SubEditorsDAO $subEditorsDao */
        $subEditorsDao = DAORegistry::getDAO('SubEditorsDAO');

        foreach (array_values($abbrevs) as $index => $abbrev) {
            $section = $sections->first(fn ($section) => $section->getAbbrev($locale) === $abbrev);

            if (!$section) {
                throw new ScenarioException(
                    "Context '{$context->getPath()}' has no section with abbreviation '{$abbrev}'. Available: "
                        . $sections->map(fn ($section) => $section->getAbbrev($locale))->filter()->join(', ') . '.',
                    "{$specKey}.sections.{$index}"
                );
            }

            $assigned = $subEditorsDao
                ->getBySubmissionGroupIds([$section->getId()], Application::ASSOC_TYPE_SECTION, $context->getId())
                ->contains(fn ($row) => (int) $row->userId === $user->getId());

            if ($assigned) {
                continue;
            }

            $subEditorsDao->insertEditor(
                $context->getId(),
                $section->getId(),
                $user->getId(),
                Application::ASSOC_TYPE_SECTION,
                $group->id
            );
        }
    }

    /**
     * The user group a section assignment is recorded under.
     *
     * The Sections form offers only groups in PKPSectionForm::$assignableRoles and
     * stores the assignment against one of them; the sub-editor slot is the
     * natural one, with manager and assistant groups as the fallbacks the form
     * also offers. A user with none of those roles is a spec error, not a silent
     * skip.
     *
     * @throws ScenarioException
     */
    protected function assignableUserGroupFor(Context $context, User $user, string $specKey): UserGroup
    {
        $preference = [Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_MANAGER, Role::ROLE_ID_ASSISTANT];

        $group = UserGroup::withContextIds([$context->getId()])
            ->withUserIds([$user->getId()])
            ->withRoleIds($preference)
            ->get()
            ->sortBy(fn (UserGroup $group) => array_search($group->roleId, $preference))
            ->first();

        if (!$group) {
            throw new ScenarioException(
                "User '{$user->getUsername()}' cannot be assigned to a section: it is not enrolled in any "
                    . 'editorially assignable role (sub-editor, manager or assistant) in context '
                    . "'{$context->getPath()}'.",
                $specKey
            );
        }

        return $group;
    }

    /**
     * @copydoc \PKP\API\v1\_test\PKPContextScenarioController::nonSettingOverlayKeys()
     */
    protected function nonSettingOverlayKeys(): array
    {
        return ['sections', 'issues'];
    }

    /**
     * @copydoc \PKP\API\v1\_test\PKPContextScenarioController::afterContextCreated()
     */
    protected function afterContextCreated(Context $context, array $spec, string $specKeyPrefix): void
    {
        $key = fn (string $name) => $specKeyPrefix === '' ? $name : "{$specKeyPrefix}.{$name}";

        $this->seededSectionIds = $this->seedSections($context, $spec['sections'] ?? [], $key('sections'));
        $this->seededIssueIds = $this->seedIssues($context, $spec['issues'] ?? [], $key('issues'));
    }

    /** @var array<string, int> abbrev => sectionId */
    protected array $seededSectionIds = [];

    /** @var array<int, int> */
    protected array $seededIssueIds = [];

    /**
     * @copydoc \PKP\API\v1\_test\PKPContextScenarioController::contextEcho()
     */
    protected function contextEcho(Context $context, array $spec): array
    {
        return array_filter([
            'sections' => $this->seededSectionIds,
            'issues' => $this->seededIssueIds,
        ]);
    }

    /**
     * Seed sections, editing the default section the journal was created with
     * when its abbreviation matches — the same thing an editor does rather than
     * leaving a stray "Articles" section behind.
     *
     * @return array<string, int> abbrev => sectionId
     */
    protected function seedSections(Context $context, array $sectionSpecs, string $specKeyPrefix): array
    {
        if (empty($sectionSpecs)) {
            return [];
        }

        $locale = $context->getPrimaryLocale();
        $existing = Repo::section()->getCollector()->filterByContextIds([$context->getId()])->getMany();
        $ids = [];

        foreach (array_values($sectionSpecs) as $index => $sectionSpec) {
            $abbrev = $sectionSpec['abbrev'];
            $match = $existing->first(fn ($section) => $section->getAbbrev($locale) === $abbrev);

            $props = [
                'title' => [$locale => $sectionSpec['title']],
                'abbrev' => [$locale => $abbrev],
                'policy' => [$locale => $sectionSpec['policy'] ?? ''],
                'editorRestricted' => (bool) ($sectionSpec['editorRestricted'] ?? false),
                'abstractsNotRequired' => (bool) ($sectionSpec['abstractsNotRequired'] ?? false),
                'wordCount' => (int) ($sectionSpec['wordCount'] ?? 0),
                'metaIndexed' => true,
                'metaReviewed' => true,
                'hideTitle' => false,
            ];

            if (isset($sectionSpec['identifyType'])) {
                $props['identifyType'] = [$locale => $sectionSpec['identifyType']];
            }

            if ($match) {
                Repo::section()->edit($match, $props);
                $ids[$abbrev] = $match->getId();

                continue;
            }

            $section = Repo::section()->newDataObject($props);
            $section->setContextId($context->getId());
            $section->setSequence(REALLY_BIG_NUMBER);
            $ids[$abbrev] = Repo::section()->add($section);
        }

        return $ids;
    }

    /**
     * @return array<int, int>
     */
    protected function seedIssues(Context $context, array $issueSpecs, string $specKeyPrefix): array
    {
        $ids = [];
        $current = null;

        foreach (array_values($issueSpecs) as $issueSpec) {
            $issue = Repo::issue()->newDataObject();
            $issue->setJournalId($context->getId());
            $issue->setVolume((int) ($issueSpec['volume'] ?? 1));
            $issue->setNumber((string) ($issueSpec['number'] ?? '1'));
            $issue->setYear((int) ($issueSpec['year'] ?? (int) date('Y')));
            $issue->setShowVolume(1);
            $issue->setShowNumber(1);
            $issue->setShowYear(1);
            $issue->setShowTitle(isset($issueSpec['title']) ? 1 : 0);
            $issue->setAccessStatus($issueSpec['accessStatus'] ?? Issue::ISSUE_ACCESS_OPEN);
            $issue->setPublished((bool) ($issueSpec['published'] ?? false));

            if (isset($issueSpec['title'])) {
                $issue->setTitle($issueSpec['title'], $context->getPrimaryLocale());
            }

            if (isset($issueSpec['description'])) {
                $issue->setDescription($issueSpec['description'], $context->getPrimaryLocale());
            }

            if ($issueSpec['published'] ?? false) {
                $issue->setDatePublished(Core::getCurrentDate());
            }

            $issueId = Repo::issue()->add($issue);
            $ids[] = $issueId;

            if ($issueSpec['current'] ?? false) {
                $current = $issueId;
            }
        }

        if ($current) {
            Repo::issue()->updateCurrent($context->getId(), Repo::issue()->get($current));
        }

        return $ids;
    }
}
