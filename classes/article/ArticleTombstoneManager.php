<?php

/**
 * @file classes/article/ArticleTombstoneManager.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
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
     * version-of-record major number.
     *
     * @see \APP\oai\ojs\JournalOAI::articleIdToIdentifier()
     */
    protected function buildOaiIdentifier(int $articleId, ?int $versionMajor = null): string
    {
        $identifier = 'oai:' . Config::getVar('oai', 'repository_id') . ':' . 'article/' . $articleId;
        if ($versionMajor) {
            $identifier .= '/version/' . $versionMajor;
        }
        return $identifier;
    }

    /**
     * Whether this journal exposes one OAI record per published major version,
     * instead of only the current publication.
     *
     * @see \APP\oai\ojs\OAIDAO::getRecordsRecordSetQuery()
     */
    protected function isVersioningEnabled(Context $context): bool
    {
        return (bool) $context->getData(Context::SETTING_DOI_VERSIONING)
            && (bool) $context->getData(Context::SETTING_ENABLE_DOIS);
    }

    /**
     * The version-of-record major numbers that currently have at least one
     * published minor version, for a submission.
     *
     * @return int[]
     */
    protected function getLiveVersionMajors(Submission $submission): array
    {
        $majors = [];
        foreach (Repo::publication()->getCollector()
            ->filterBySubmissionIds([$submission->getId()])
            ->filterByVersionStage(VersionStage::VERSION_OF_RECORD->value)
            ->filterByStatus([PKPPublication::STATUS_PUBLISHED])
            ->getMany() as $publication) {
            $majors[$publication->getData('versionMajor')] = true;
        }
        return array_keys($majors);
    }

    /**
     * Whether this publication is the latest published minor version within
     * its (version stage, version major) group -- the one that represents
     * that group in OAI.
     *
     * @see \APP\oai\ojs\OAIDAO::getRecordsRecordSetQuery() ($versionQuery's p2 self-join)
     */
    protected function isLatestPublishedMinor(Publication $publication): bool
    {
        foreach (Repo::publication()->getCollector()
            ->filterBySubmissionIds([$publication->getData('submissionId')])
            ->filterByVersionStage($publication->getData('versionStage'))
            ->filterByVersionMajor($publication->getData('versionMajor'))
            ->filterByStatus([PKPPublication::STATUS_PUBLISHED])
            ->getMany() as $sibling) {
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
    protected function insertIdentifierTombstone(Submission $article, Context $journal, Section $section, ?int $versionMajor): void
    {
        $tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO'); /** @var DataObjectTombstoneDAO $tombstoneDao */
        $oaiIdentifier = $this->buildOaiIdentifier($article->getId(), $versionMajor);

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
     */
    public function insertArticleTombstone($article, $journal, $section)
    {
        $tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO'); /** @var DataObjectTombstoneDAO $tombstoneDao */
        // delete any existing tombstones for this article, to avoid duplicates/staleness
        $tombstoneDao->deleteByDataObjectId($article->getId());

        // Always tombstone the bare identifier.
        $this->insertIdentifierTombstone($article, $journal, $section, null);

        // If this journal exposes per-version records, also tombstone every
        // major version this article ever reached VoR for, since each may
        // have had its own identifier exposed independently of the bare one.
        // Not filtered by current publication status: by the time this runs,
        // the publications involved may already be unpublished.
        if ($this->isVersioningEnabled($journal)) {
            $majors = [];
            foreach (Repo::publication()->getCollector()
                ->filterBySubmissionIds([$article->getId()])
                ->filterByVersionStage(VersionStage::VERSION_OF_RECORD->value)
                ->getMany() as $publication) {
                $majors[$publication->getData('versionMajor')] = true;
            }
            foreach (array_keys($majors) as $major) {
                $this->insertIdentifierTombstone($article, $journal, $section, $major);
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
    public function reconcileTombstonesOnPublish(Publication $publication, Submission $submission, Context $context): void
    {
        if ($publication->getData('status') !== PKPPublication::STATUS_PUBLISHED) {
            return;
        }

        $tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO'); /** @var DataObjectTombstoneDAO $tombstoneDao */
        $articleId = $submission->getId();

        // Bare identifier: tracks the current publication, regardless of its
        // version stage -- independent of whether this journal has per-version
        // OAI records at all.
        if ($publication->getId() == $submission->getData('currentPublicationId')) {
            $tombstoneDao->deleteByOAIIdentifier($this->buildOaiIdentifier($articleId, null));
        }

        // Versioned identifier: only relevant for VoR majors in versioning
        // journals, and only the latest published minor of a major represents it.
        if ($this->isVersioningEnabled($context)
            && $publication->getData('versionStage') === VersionStage::VERSION_OF_RECORD->value
            && $this->isLatestPublishedMinor($publication)) {
            $tombstoneDao->deleteByOAIIdentifier($this->buildOaiIdentifier($articleId, $publication->getData('versionMajor')));
        }
    }

    /**
     * Reconcile OAI tombstones after a single publication is unpublished:
     * tombstone the identifier(s) that are no longer live as a result, if any.
     *
     * Unlike insertArticleTombstone(), this isn't gated on the submission's
     * aggregate status -- an article whose only published publication was
     * AO/PMUR (submission status never reaches STATUS_PUBLISHED for those,
     *
     * @see Submission\Repository::getStatusByPublications()) is still
     * correctly tombstoned when that publication is unpublished.
     */
    public function reconcileTombstonesOnUnpublish(Publication $publication, bool $wasCurrentPublication, Submission $submission, Context $context): void
    {
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
                $this->insertIdentifierTombstone($submission, $context, $section, null);
            }
        }

        // Versioned identifier: only relevant for VoR majors in versioning
        // journals. Independent of the bare-identifier check above -- both
        // can apply to the same unpublish event.
        if ($this->isVersioningEnabled($context) && $publication->getData('versionStage') === VersionStage::VERSION_OF_RECORD->value) {
            $major = $publication->getData('versionMajor');
            if (!in_array($major, $this->getLiveVersionMajors($submission), true)) {
                // No published minor left for this major: its versioned
                // identifier is dead, regardless of whether it used to be
                // current (bare) or not.
                $this->insertIdentifierTombstone($submission, $context, $section, $major);
            }
        }
    }

    /**
     * Insert tombstone for every published submission
     */
    public function insertTombstonesByContext(Context $context)
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
     * Delete tombstones for published submissions in this context
     */
    public function deleteTombstonesByContextId(int $contextId)
    {
        $tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO'); /** @var \PKP\tombstone\DataObjectTombstoneDAO $tombstoneDao */
        $submissions = Repo::submission()->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->getMany();

        foreach ($submissions as $submission) {
            $tombstoneDao->deleteByDataObjectId($submission->getId());
        }
    }
}
