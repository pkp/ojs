<?php

/**
 * @file tests/classes/oai/OAIDAOTest.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OAIDAOTest
 *
 * @ingroup tests_classes_oai
 *
 * @see OAIDAO
 *
 * @brief Tests for the OAI records query with per-version records enabled.
 */

namespace APP\tests\classes\oai;

use APP\core\Application;
use APP\oai\ojs\OAIDAO;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PKP\context\Context;
use PKP\db\DAORegistry;
use PKP\publication\PKPPublication;
use PKP\tests\DatabaseTestCase;

class OAIDAOTest extends DatabaseTestCase
{
    private const CONTEXT_ID = 1;

    /** @var array<string, ?object> Original journal_settings rows, restored in tearDown(). */
    private array $originalContextSettings = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([Context::SETTING_DOI_VERSIONING, Context::SETTING_ENABLE_DOIS] as $settingName) {
            $this->originalContextSettings[$settingName] = DB::table('journal_settings')
                ->where('journal_id', self::CONTEXT_ID)
                ->where('setting_name', $settingName)
                ->first();
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->originalContextSettings as $settingName => $original) {
            DB::table('journal_settings')
                ->where('journal_id', self::CONTEXT_ID)
                ->where('setting_name', $settingName)
                ->delete();
            if ($original) {
                DB::table('journal_settings')->insert((array) $original);
            }
        }

        parent::tearDown();
    }

    private function setVersioning(bool $enabled): void
    {
        $contextDao = Application::getContextDAO();
        $context = $contextDao->getById(self::CONTEXT_ID);
        $context->setData(Context::SETTING_DOI_VERSIONING, $enabled);
        $context->setData(Context::SETTING_ENABLE_DOIS, true);
        $contextDao->updateObject($context);
    }

    private function getQuery(array $setIds = [], ?int $submissionId = null, string $orderBy = 'journal_id, submission_id'): Builder
    {
        /** @var OAIDAO $dao */
        $dao = DAORegistry::getDAO('OAIDAO');
        return $dao->getRecordsRecordSetQuery($setIds, null, null, null, $submissionId, $orderBy);
    }

    /**
     * @return int[] Submissions with a single published version (stage and major)
     */
    private function getSingleVersionSubmissionIds(): array
    {
        $publishedVersions = DB::table('publications')
            ->where('status', '=', PKPPublication::STATUS_PUBLISHED)
            ->select(['submission_id', 'version_stage', 'version_major'])
            ->distinct();

        return DB::query()
            ->fromSub($publishedVersions, 'v')
            ->groupBy('submission_id')
            ->havingRaw('COUNT(*) = 1')
            ->pluck('submission_id')
            ->map(fn ($submissionId) => (int) $submissionId)
            ->all();
    }

    /**
     * @param int[] $submissionIds
     *
     * @return array<array{int, ?int}> [submission_id, publication_id] of each record of these submissions
     */
    private function getRecordKeys(array $setIds, array $submissionIds): array
    {
        return $this->getQuery($setIds)->get()
            ->filter(fn (object $row) => in_array((int) $row->submission_id, $submissionIds, true))
            ->map(fn (object $row) => [(int) $row->submission_id, $row->publication_id === null ? null : (int) $row->publication_id])
            ->values()
            ->all();
    }

    public static function versioningProvider(): array
    {
        return [
            'DOI versioning off' => [false],
            'DOI versioning on' => [true],
        ];
    }

    /**
     * The queries behind ListRecords/ListIdentifiers (journal and site-wide),
     * GetRecord/ListMetadataFormats and Identify all run.
     *
     * @see https://github.com/pkp/pkp-lib/issues/13547
     */
    #[DataProvider('versioningProvider')]
    public function testRecordsQueryRuns(bool $versioning): void
    {
        $this->setVersioning($versioning);

        $this->assertNotEmpty($this->getQuery([self::CONTEXT_ID])->get());
        $this->assertNotEmpty($this->getQuery()->get());
        $this->assertGreaterThanOrEqual(0, $this->getQuery([self::CONTEXT_ID])->getCountForPagination());

        $submissionId = $this->getQuery([self::CONTEXT_ID])->first()->submission_id;
        $this->assertNotNull($this->getQuery([self::CONTEXT_ID], $submissionId)->first());

        $this->assertNotNull($this->getQuery([self::CONTEXT_ID], null, 'last_modified')->first());
    }

    /**
     * DOI versioning must not change the records of a submission with a single
     * published version.
     */
    public function testDoiVersioningListsSameRecordsForSingleVersionSubmissions(): void
    {
        $submissionIds = $this->getSingleVersionSubmissionIds();

        $this->setVersioning(false);
        $journalRecords = $this->getRecordKeys([self::CONTEXT_ID], $submissionIds);
        $siteRecords = $this->getRecordKeys([], $submissionIds);

        $this->setVersioning(true);
        $this->assertNotEmpty($journalRecords);
        $this->assertSame($journalRecords, $this->getRecordKeys([self::CONTEXT_ID], $submissionIds));
        $this->assertSame($siteRecords, $this->getRecordKeys([], $submissionIds));
    }
}
