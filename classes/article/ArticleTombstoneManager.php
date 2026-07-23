<?php

/**
 * @file classes/article/ArticleTombstoneManager.php
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2000-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleTombstoneManager
 *
 * @ingroup article
 *
 * @brief Class defining basic operations for article tombstones.
 */

namespace APP\article;

use APP\core\Application;
use APP\facades\Repo;
use APP\oai\ojs\JournalOAI;
use APP\oai\ojs\OAIDAO;
use APP\publication\enums\VersionStage;
use APP\publication\Publication;
use APP\section\Section;
use APP\submission\Submission;
use PKP\config\Config;
use PKP\context\Context;
use PKP\db\DAORegistry;
use PKP\plugins\Hook;
use PKP\publication\PKPPublication;
use PKP\tombstone\DataObjectTombstoneDAO;

class ArticleTombstoneManager
{
    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Build the OAI identifier for an article, optionally for a specific
     * version stage and major version number. The format must match the
     * identifiers OAI records are exposed under.
     *
     * @see \APP\oai\ojs\JournalOAI::articleIdToIdentifier()
     */
    protected function buildOaiIdentifier(int $articleId, ?string $versionStage = null, ?int $versionMajor = null): string
    {
        return JournalOAI::formatIdentifier(Config::getVar('oai', 'repository_id'), $articleId, $versionStage, $versionMajor);
    }

    /**
     * The version stages exposed as their own OAI record when DOI versioning
     * is enabled.
     *
     * @return string[]
     *
     * @see OAIDAO::getRecordsRecordSetQuery ($versionedStages)
     *
     */
    protected function versionedStages(): array
    {
        return [
            VersionStage::AUTHOR_ORIGINAL->value,
            VersionStage::PUBLISHED_MANUSCRIPT_UNDER_REVIEW->value,
            VersionStage::VERSION_OF_RECORD->value,
        ];
    }

    /**
     * Whether this journal exposes one OAI record per published version,
     * instead of only the current publication.
     *
     * @see OAIDAO::getRecordsRecordSetQuery
     */
    protected function isVersioningEnabled(Context $context): bool
    {
        return $context->getData(Context::SETTING_DOI_VERSIONING)
            && $context->getData(Context::SETTING_ENABLE_DOIS);
    }

    /**
     * Whether a (version stage, major) group still has at least one published
     * minor version for a submission -- i.e. its versioned OAI identifier is
     * still live.
     */
    protected function isVersionGroupLive(Submission $submission, string $versionStage, int $versionMajor): bool
    {
        return Repo::publication()->getCollector()
            ->filterBySubmissionIds([$submission->getId()])
            ->filterByVersionStage($versionStage)
            ->filterByVersionMajor($versionMajor)
            ->filterByStatus([PKPPublication::STATUS_PUBLISHED])
            ->getMany()
            ->isNotEmpty();
    }

    /**
     * Whether this publication is the latest published minor version within
     * its (version stage, version major) group -- the one that represents
     * that group in OAI.
     *
     * @see OAIDAO::getRecordsRecordSetQuery ($versionQuery's p2 self-join)
     */
    protected function isLatestPublishedMinor(Publication $publication): bool
    {
        foreach (
            Repo::publication()->getCollector()
                ->filterBySubmissionIds([$publication->getData('submissionId')])
                ->filterByVersionStage($publication->getData('versionStage'))
                ->filterByVersionMajor($publication->getData('versionMajor'))
                ->filterByStatus([PKPPublication::STATUS_PUBLISHED])
                ->getMany() as $sibling
        ) {
            if ((int) $sibling->getData('versionMinor') > (int) $publication->getData('versionMinor')) {
                return false;
            }
        }
        return true;
    }

    /**
     * Create a tombstone for a specific article/version OAI identifier, if one
     * doesn't already exist for it.
     *
     * @hook ArticleTombstoneManager::insertArticleTombstone [[&$articleTombstone, &$article, &$journal, &$section]]
     */
    protected function insertIdentifierTombstone(Submission $article, Context $journal, Section $section, ?string $versionStage, ?int $versionMajor): void
    {
        $tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO'); /** @var DataObjectTombstoneDAO $tombstoneDao */
        $oaiIdentifier = $this->buildOaiIdentifier($article->getId(), $versionStage, $versionMajor);

        if ($tombstoneDao->getByOAIIdentifier($oaiIdentifier)) {
            return;
        }

        $setSpec = OAIDAO::setSpec($journal, $section);
        $articleTombstone = $tombstoneDao->newDataObject();
        $articleTombstone->setDataObjectId($article->getId());
        $articleTombstone->stampDateDeleted();
        $articleTombstone->setSetSpec($setSpec);
        $articleTombstone->setSetName($section->getLocalizedTitle());
        $articleTombstone->setOAIIdentifier($oaiIdentifier);
        $articleTombstone->setOAISetObjectsIds([
            Application::ASSOC_TYPE_JOURNAL => $journal->getId(),
            Application::ASSOC_TYPE_SECTION => $section->getId(),
        ]);
        $tombstoneDao->insertObject($articleTombstone);

        if (Hook::call('ArticleTombstoneManager::insertArticleTombstone', [&$articleTombstone, &$article, &$journal, &$section])) {
            return;
        }
    }

    /**
     * Insert a tombstone for every OAI identifier this article was exposing.
     * Used when the whole submission is removed from OAI (deleted, or
     * unpublished at the submission level -- see Submission\Repository::updateStatus()).
     *
     * This covers the submission-level case; the reconcileTombstonesOn*()
     * methods handle the per-publication cases (AO/PMUR-only, or non-current
     * versions) that don't move the submission's aggregate status.
     */
    public function insertArticleTombstone($article, $journal, $section): void
    {
        /** @var DataObjectTombstoneDAO $tombstoneDao */
        $tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO');
        // delete any existing tombstones for this article, to avoid duplicates/staleness
        $tombstoneDao->deleteByDataObjectId($article->getId());

        // Always tombstone the bare identifier.
        $this->insertIdentifierTombstone($article, $journal, $section, null, null);

        // If this journal exposes per-version records, also tombstone every
        // (version stage, major) group this article ever exposed, since each
        // may have had its own identifier exposed independently of the bare one.
        // Not filtered by current publication status: by the time this runs,
        // the publications involved may already be unpublished.
        if ($this->isVersioningEnabled($journal)) {
            $versionedStages = $this->versionedStages();
            $groups = [];
            foreach (
                Repo::publication()->getCollector()
                    ->filterBySubmissionIds([$article->getId()])
                    ->getMany() as $publication
            ) {
                $versionStage = $publication->getData('versionStage');
                if (!in_array($versionStage, $versionedStages, true)) {
                    continue;
                }
                $major = (int) $publication->getData('versionMajor');
                $groups[$versionStage . '/' . $major] = [$versionStage, $major];
            }
            foreach ($groups as [$versionStage, $major]) {
                $this->insertIdentifierTombstone($article, $journal, $section, $versionStage, $major);
            }
        }
    }

    /**
     * Reconcile OAI tombstones after a single publication is published:
     * clear any stale tombstone for the identifier(s) it now represents.
     *
     * Unlike insertArticleTombstone(), this isn't gated on the submission's
     * aggregate status (which only reflects the Version of Record) -- an
     * AO/PMUR-only publish is handled the same way as a VoR publish.
     */
    public function reconcileTombstonesOnPublish(
        Publication $publication,
        Submission $submission,
        Context $context
    ): void {
        if ($publication->getData('status') !== PKPPublication::STATUS_PUBLISHED) {
            return;
        }

        $tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO'); /** @var DataObjectTombstoneDAO $tombstoneDao */
        $articleId = $submission->getId();

        // Bare identifier: tracks the current publication, regardless of its
        // version stage -- independent of whether this journal has per-version
        // OAI records at all.
        if ($publication->getId() == $submission->getData('currentPublicationId')) {
            $tombstoneDao->deleteByOAIIdentifier($this->buildOaiIdentifier($articleId, null, null));
        }

        // Versioned identifier: only relevant for exposed version stages in
        // versioning journals, and only the latest published minor of a
        // (stage, major) group represents it.
        $versionStage = $publication->getData('versionStage');
        if (
            $this->isVersioningEnabled($context)
            && in_array($versionStage, $this->versionedStages(), true)
            && $this->isLatestPublishedMinor($publication)
        ) {
            $tombstoneDao->deleteByOAIIdentifier(
                $this->buildOaiIdentifier($articleId, $versionStage, (int) $publication->getData('versionMajor'))
            );
        }
    }

    /**
     * Reconcile OAI tombstones after a single publication is unpublished:
     * tombstone the identifier(s) that are no longer live as a result, if any.
     *
     * Unlike insertArticleTombstone(), this isn't gated on the submission's
     * aggregate status -- an article whose only published publication was
     * AO/PMUR (submission status never reaches STATUS_PUBLISHED for those) is
     * still correctly tombstoned when that publication is unpublished.
     *
     * @see \APP\submission\Repository::getStatusByPublications()
     */
    public function reconcileTombstonesOnUnpublish(
        Publication $publication,
        bool $wasCurrentPublication,
        Submission $submission,
        Context $context
    ): void {
        $section = Repo::section()->get((int) $publication->getData('sectionId'), $context->getId());
        if (!$section) {
            return;
        }

        // Bare identifier: was this publication current, and is nothing else
        // published now (of any version stage) to take over as current?
        if ($wasCurrentPublication) {
            $stillLive = Repo::publication()->getCollector()
                ->filterBySubmissionIds([$submission->getId()])
                ->filterByStatus([PKPPublication::STATUS_PUBLISHED])
                ->getMany()
                ->isNotEmpty();
            if (!$stillLive) {
                $this->insertIdentifierTombstone($submission, $context, $section, null, null);
            }
        }

        // Versioned identifier: only relevant for exposed version stages in
        // versioning journals. Independent of the bare-identifier check above
        // -- both can apply to the same unpublish event.
        $versionStage = $publication->getData('versionStage');
        if ($this->isVersioningEnabled($context) && in_array($versionStage, $this->versionedStages(), true)) {
            $major = (int) $publication->getData('versionMajor');
            if (!$this->isVersionGroupLive($submission, $versionStage, $major)) {
                // No published minor left for this (stage, major) group: its
                // versioned identifier is dead, regardless of whether it used
                // to be current (bare) or not.
                $this->insertIdentifierTombstone($submission, $context, $section, $versionStage, $major);
            }
        }
    }

    /**
     * Insert tombstone for every published submission.
     */
    public function insertTombstonesByContext(Context $context): void
    {
        $submissions = Repo::submission()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->getMany();

        foreach ($submissions as $submission) {
            $section = Repo::section()->get($submission->getSectionId());
            $this->insertArticleTombstone($submission, $context, $section);
        }
    }

    /**
     * Delete tombstones for published submissions in this context.
     */
    public function deleteTombstonesByContextId(int $contextId): void
    {
        /** @var DataObjectTombstoneDAO $tombstoneDao */
        $tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO');
        $submissions = Repo::submission()->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->getMany();

        foreach ($submissions as $submission) {
            $tombstoneDao->deleteByDataObjectId($submission->getId());
        }
    }
}
