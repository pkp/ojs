<?php

/**
 * @file tests/classes/plugins/DepositableExportableTest.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DepositableExportableTest
 *
 * @brief Tests that getExportable() with EXPORT_STATUS_DEPOSITABLE returns only the
 *   context's own published, not yet deposited or stale items.
 */

namespace APP\tests\classes\plugins;

use APP\plugins\PubObjectsExportPlugin;
use APP\publication\DAO as PublicationDAO;
use APP\publication\enums\VersionStage;
use APP\publication\Publication;
use APP\submission\DAO as SubmissionDAO;
use APP\submission\Submission;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\CoversClass;
use PKP\tests\DatabaseTestCase;

#[CoversClass(SubmissionDAO::class)]
#[CoversClass(PublicationDAO::class)]
class DepositableExportableTest extends DatabaseTestCase
{
    private const STATUS_SETTING_NAME = 'doaj::status';

    private int $contextId;
    private int $otherContextId;

    protected function setUp(): void
    {
        parent::setUp();

        // Backing up and restoring the tables would cascade deletes into the rest of the database.
        DB::beginTransaction();

        $this->contextId = $this->createContext('depositableTest1');
        $this->otherContextId = $this->createContext('depositableTest2');
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testSubmissionsDepositable(): void
    {
        $notDeposited = $this->createSubmission($this->contextId, [Publication::STATUS_PUBLISHED]);
        $stale = $this->createSubmission($this->contextId, [Publication::STATUS_PUBLISHED]);
        $registered = $this->createSubmission($this->contextId, [Publication::STATUS_PUBLISHED]);
        $unpublishedStale = $this->createSubmission($this->contextId, [Publication::STATUS_QUEUED]);
        $otherContextStale = $this->createSubmission($this->otherContextId, [Publication::STATUS_PUBLISHED]);

        $this->setSubmissionStatus($stale['submissionId'], PubObjectsExportPlugin::EXPORT_STATUS_STALE);
        $this->setSubmissionStatus($registered['submissionId'], PubObjectsExportPlugin::EXPORT_STATUS_REGISTERED);
        $this->setSubmissionStatus($unpublishedStale['submissionId'], PubObjectsExportPlugin::EXPORT_STATUS_STALE);
        $this->setSubmissionStatus($otherContextStale['submissionId'], PubObjectsExportPlugin::EXPORT_STATUS_STALE);

        $ids = $this->getDepositableIds(app(SubmissionDAO::class));

        $this->assertEqualsCanonicalizing([$notDeposited['submissionId'], $stale['submissionId']], $ids);
    }

    public function testPublicationsDepositable(): void
    {
        $notDeposited = $this->createSubmission($this->contextId, [Publication::STATUS_PUBLISHED]);
        $stale = $this->createSubmission($this->contextId, [Publication::STATUS_PUBLISHED]);
        $registered = $this->createSubmission($this->contextId, [Publication::STATUS_PUBLISHED]);
        $unpublishedStale = $this->createSubmission($this->contextId, [Publication::STATUS_QUEUED]);
        $replacedMinorStale = $this->createSubmission($this->contextId, [Publication::STATUS_PUBLISHED, Publication::STATUS_PUBLISHED]);
        $otherContextStale = $this->createSubmission($this->otherContextId, [Publication::STATUS_PUBLISHED]);

        $this->setPublicationStatus($stale['publicationIds'][0], PubObjectsExportPlugin::EXPORT_STATUS_STALE);
        $this->setPublicationStatus($registered['publicationIds'][0], PubObjectsExportPlugin::EXPORT_STATUS_REGISTERED);
        $this->setPublicationStatus($unpublishedStale['publicationIds'][0], PubObjectsExportPlugin::EXPORT_STATUS_STALE);
        $this->setPublicationStatus($replacedMinorStale['publicationIds'][0], PubObjectsExportPlugin::EXPORT_STATUS_STALE);
        $this->setPublicationStatus($replacedMinorStale['publicationIds'][1], PubObjectsExportPlugin::EXPORT_STATUS_REGISTERED);
        $this->setPublicationStatus($otherContextStale['publicationIds'][0], PubObjectsExportPlugin::EXPORT_STATUS_STALE);

        $ids = $this->getDepositableIds(app(PublicationDAO::class));

        $this->assertEqualsCanonicalizing([$notDeposited['publicationIds'][0], $stale['publicationIds'][0]], $ids);
    }

    private function getDepositableIds(SubmissionDAO|PublicationDAO $dao): array
    {
        $result = $dao->getExportable(
            $this->contextId,
            settingName: self::STATUS_SETTING_NAME,
            settingValue: PubObjectsExportPlugin::EXPORT_STATUS_DEPOSITABLE
        );
        return array_map(fn (Submission|Publication $object) => $object->getId(), $result->toArray());
    }

    private function createContext(string $path): int
    {
        return DB::table('journals')->insertGetId(['path' => $path, 'primary_locale' => 'en'], 'journal_id');
    }

    /**
     * Create a VoR submission with one minor version per given publication status.
     *
     * @return array{submissionId: int, publicationIds: int[]}
     */
    private function createSubmission(int $contextId, array $publicationStatuses): array
    {
        $submissionId = DB::table('submissions')->insertGetId([
            'context_id' => $contextId,
            'locale' => 'en',
            'status' => in_array(Publication::STATUS_PUBLISHED, $publicationStatuses) ? Submission::STATUS_PUBLISHED : Submission::STATUS_QUEUED,
        ], 'submission_id');

        $publicationIds = [];
        foreach ($publicationStatuses as $versionMinor => $status) {
            $publicationIds[] = DB::table('publications')->insertGetId([
                'submission_id' => $submissionId,
                'status' => $status,
                'date_published' => $status === Publication::STATUS_PUBLISHED ? '2026-01-01' : null,
                'version_stage' => VersionStage::VERSION_OF_RECORD->value,
                'version_major' => 1,
                'version_minor' => $versionMinor,
            ], 'publication_id');
        }

        DB::table('submissions')
            ->where('submission_id', $submissionId)
            ->update(['current_publication_id' => end($publicationIds)]);

        return ['submissionId' => $submissionId, 'publicationIds' => $publicationIds];
    }

    private function setSubmissionStatus(int $submissionId, string $status): void
    {
        DB::table('submission_settings')->insert([
            'submission_id' => $submissionId,
            'locale' => '',
            'setting_name' => self::STATUS_SETTING_NAME,
            'setting_value' => $status,
        ]);
    }

    private function setPublicationStatus(int $publicationId, string $status): void
    {
        DB::table('publication_settings')->insert([
            'publication_id' => $publicationId,
            'locale' => '',
            'setting_name' => self::STATUS_SETTING_NAME,
            'setting_value' => $status,
        ]);
    }
}
