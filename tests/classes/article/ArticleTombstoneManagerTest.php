<?php

/**
 * @file tests/classes/article/ArticleTombstoneManagerTest.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleTombstoneManagerTest
 *
 * @ingroup tests_classes_article
 *
 * @see ArticleTombstoneManager
 *
 * @brief Tests for per-version OAI tombstone reconciliation on publish/unpublish/delete
 * (pkp/pkp-lib#12922 follow-up).
 */

namespace APP\tests\classes\article;

use APP\core\Application;
use APP\facades\Repo;
use APP\oai\ojs\JournalOAI;
use APP\publication\Publication;
use APP\submission\Submission;
use Illuminate\Support\Facades\DB;
use PKP\config\Config;
use PKP\context\Context;
use PKP\db\DAORegistry;
use PKP\publication\PKPPublication;
use PKP\tests\DatabaseTestCase;
use PKP\tombstone\DataObjectTombstoneDAO;

class ArticleTombstoneManagerTest extends DatabaseTestCase
{
    private const CONTEXT_ID = 1;
    private const SECTION_ID = 1;

    /** @var int[] Submission ids created by this test, cleaned up in tearDown(). */
    private array $createdSubmissionIds = [];

    /** @var array<string, ?object> Original journal_settings rows, restored in tearDown(). */
    private array $originalContextSettings = [];

    /**
     * Empty: a whole-table restore isn't safe here (too many FKs into submissions/
     * publications in a populated DB) -- tearDown() cleans up by id instead.
     */
    protected function getAffectedTables(): array
    {
        return [];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // A router-less CLI request crashes ->getContext() calls inside the publication/
        // citation DAOs.
        $this->mockRequest('publicknowledge/test-page/test-op');

        // Save the settings this test flips on, to restore in tearDown().
        foreach ([Context::SETTING_DOI_VERSIONING, Context::SETTING_ENABLE_DOIS] as $settingName) {
            $this->originalContextSettings[$settingName] = DB::table('journal_settings')
                ->where('journal_id', self::CONTEXT_ID)
                ->where('setting_name', $settingName)
                ->first();
        }

        // Enable per-version OAI records for the test context.
        $contextDao = Application::getContextDAO();
        $context = $contextDao->getById(self::CONTEXT_ID);
        $context->setData(Context::SETTING_DOI_VERSIONING, true);
        $context->setData(Context::SETTING_ENABLE_DOIS, true);
        $contextDao->updateObject($context);
    }

    protected function tearDown(): void
    {
        // Deleting the submission cascades through publications, publication_settings,
        // and submission_settings.
        if ($this->createdSubmissionIds) {
            DB::table('data_object_tombstones')->whereIn('data_object_id', $this->createdSubmissionIds)->delete();
            DB::table('submissions')->whereIn('submission_id', $this->createdSubmissionIds)->delete();
        }

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

    private function getContext(): Context
    {
        return Application::getContextDAO()->getById(self::CONTEXT_ID);
    }

    private function identifier(int $submissionId, ?string $versionStage = null, ?int $versionMajor = null): string
    {
        return JournalOAI::formatIdentifier(Config::getVar('oai', 'repository_id'), $submissionId, $versionStage, $versionMajor);
    }

    /**
     * @return string[] OAI identifiers currently tombstoned for this submission.
     */
    private function tombstonedIdentifiers(int $submissionId): array
    {
        /** @var DataObjectTombstoneDAO $dao */
        $dao = DAORegistry::getDAO('DataObjectTombstoneDAO');
        return array_map(
            fn ($tombstone) => $tombstone->getOAIIdentifier(),
            $dao->getManyByDataObjectId($submissionId)
        );
    }

    /**
     * Create a submission with a single initial publication of the given stage/major.
     */
    private function createSubmission(string $versionStage, int $versionMajor, int $versionMinor = 0): Submission
    {
        $context = $this->getContext();

        $submission = Repo::submission()->newDataObject();
        $submission->setData('contextId', self::CONTEXT_ID);
        $submission->setData('locale', 'en');

        $publication = Repo::publication()->newDataObject();
        $publication->setData('sectionId', self::SECTION_ID);
        $publication->setData('locale', 'en');
        $publication->setData('versionStage', $versionStage);
        $publication->setData('versionMajor', $versionMajor);
        $publication->setData('versionMinor', $versionMinor);
        $publication->setData('title', ['en' => 'Test article']);

        $submissionId = Repo::submission()->add($submission, $publication, $context);
        $this->createdSubmissionIds[] = $submissionId;
        return Repo::submission()->get($submissionId);
    }

    /**
     * Add a further publication -- a new (stage, major) pair -- to a submission.
     */
    private function addPublication(Submission $submission, string $versionStage, int $versionMajor, int $versionMinor = 0): Publication
    {
        $publication = Repo::publication()->newDataObject();
        $publication->setData('submissionId', $submission->getId());
        $publication->setData('sectionId', self::SECTION_ID);
        $publication->setData('locale', 'en');
        $publication->setData('versionStage', $versionStage);
        $publication->setData('versionMajor', $versionMajor);
        $publication->setData('versionMinor', $versionMinor);
        $publication->setData('status', PKPPublication::STATUS_QUEUED);
        $publication->setData('title', ['en' => 'Test article']);

        $publicationId = Repo::publication()->add($publication);
        return Repo::publication()->get($publicationId);
    }

    private function publish(Publication $publication): void
    {
        Repo::publication()->publish(Repo::publication()->get($publication->getId()));
    }

    private function unpublish(Publication $publication): void
    {
        Repo::publication()->unpublish(Repo::publication()->get($publication->getId()));
    }

    private function delete(Publication $publication): void
    {
        Repo::publication()->delete(Repo::publication()->get($publication->getId()));
    }

    /**
     * A (stage, major) pair that's current the whole time it exists has no versioned identifier of its
     * own (see setOAIData()'s $isCurrentVersion), so unpublishing it must not tombstone one.
     */
    public function testUnpublishDoesNotPhantomTombstoneAnAlwaysCurrentGroup(): void
    {
        $submission = $this->createSubmission('AO', 1);
        $publication = $submission->getCurrentPublication();

        $this->publish($publication);
        $submission = Repo::submission()->get($submission->getId());
        $this->assertNotEquals(Submission::STATUS_PUBLISHED, $submission->getData('status'));

        $this->unpublish($publication);

        $identifiers = $this->tombstonedIdentifiers($submission->getId());
        $this->assertContains($this->identifier($submission->getId()), $identifiers);
        $this->assertNotContains($this->identifier($submission->getId(), 'AO', 1), $identifiers);
    }

    /**
     * Unpublishing the current major must tombstone the sibling it falls back to, not
     * itself; republishing must clear that sibling's tombstone again.
     */
    public function testFallbackAndRepublishAcrossSameStageMajors(): void
    {
        $submission = $this->createSubmission('VoR', 1);
        $major1 = $submission->getCurrentPublication();
        $this->publish($major1);

        $major2 = $this->addPublication($submission, 'VoR', 2);
        $this->publish($major2);

        $this->unpublish($major2);
        $identifiers = $this->tombstonedIdentifiers($submission->getId());
        $this->assertContains($this->identifier($submission->getId(), 'VoR', 1), $identifiers);
        $this->assertNotContains($this->identifier($submission->getId(), 'VoR', 2), $identifiers);
        $this->assertNotContains($this->identifier($submission->getId()), $identifiers);

        $this->publish($major2);
        $identifiers = $this->tombstonedIdentifiers($submission->getId());
        $this->assertNotContains($this->identifier($submission->getId(), 'VoR', 1), $identifiers);
        $this->assertNotContains($this->identifier($submission->getId(), 'VoR', 2), $identifiers);
    }

    /**
     * Same as above, but across a stage transition (AO <-> VoR) instead of two majors.
     */
    public function testFallbackAcrossStageTransition(): void
    {
        $submission = $this->createSubmission('AO', 1);
        $ao = $submission->getCurrentPublication();
        $this->publish($ao);

        $vor = $this->addPublication($submission, 'VoR', 1);
        $this->publish($vor);

        $this->unpublish($vor);

        $identifiers = $this->tombstonedIdentifiers($submission->getId());
        $this->assertContains($this->identifier($submission->getId(), 'AO', 1), $identifiers);
        $this->assertNotContains($this->identifier($submission->getId(), 'VoR', 1), $identifiers);
        $this->assertNotContains($this->identifier($submission->getId()), $identifiers);
    }

    /**
     * Republishing a (stage, major) pair that becomes current again must not clear a legitimate
     * tombstone for its own versioned identifier.
     */
    public function testRepublishDoesNotClearOwnGroupTombstoneWhenBecomingCurrent(): void
    {
        $submission = $this->createSubmission('AO', 1);
        $publication = $submission->getCurrentPublication();
        $this->publish($publication);
        $this->unpublish($publication);

        // Simulate a legitimate tombstone left over from an earlier point in history.
        /** @var DataObjectTombstoneDAO $tombstoneDao */
        $tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO');
        $staleIdentifier = $this->identifier($submission->getId(), 'AO', 1);
        $tombstone = $tombstoneDao->newDataObject();
        $tombstone->setDataObjectId($submission->getId());
        $tombstone->stampDateDeleted();
        $tombstone->setSetSpec('test');
        $tombstone->setSetName('Test');
        $tombstone->setOAIIdentifier($staleIdentifier);
        $tombstone->setOAISetObjectsIds([]);
        $tombstoneDao->insertObject($tombstone);

        $this->publish($publication);

        $identifiers = $this->tombstonedIdentifiers($submission->getId());
        $this->assertContains($staleIdentifier, $identifiers);
        $this->assertNotContains($this->identifier($submission->getId()), $identifiers);
    }

    /**
     * Deleting an already-unpublished publication must not disturb its already-reconciled
     * tombstones.
     */
    public function testDeleteOfAlreadyUnpublishedNonCurrentPublicationDoesNotChangeTombstones(): void
    {
        $submission = $this->createSubmission('VoR', 1);
        $major1 = $submission->getCurrentPublication();
        $this->publish($major1);

        $major2 = $this->addPublication($submission, 'VoR', 2);
        $this->publish($major2);
        $this->unpublish($major2);

        $before = $this->tombstonedIdentifiers($submission->getId());
        $this->delete($major2);
        $after = $this->tombstonedIdentifiers($submission->getId());

        sort($before);
        sort($after);
        $this->assertSame($before, $after);
    }
}
