<?php

/**
 * @file classes/submission/Repository.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Repository
 *
 * @brief A repository to find and manage submissions.
 */

namespace APP\submission;

use APP\article\ArticleTombstoneManager;
use APP\facades\Repo;
use APP\section\Section;
use PKP\context\Context;
use PKP\db\DAORegistry;
use PKP\tombstone\DataObjectTombstoneDAO;

class Repository extends \PKP\submission\Repository
{
    /** @copydoc \PKP\submission\Repository::$schemaMap */
    public $schemaMap = maps\Schema::class;

    /**
     * Get submissions ordered by section id
     *
     * @return array [int $sectionId => [?Submission, ...]]
     */
    public function getInSections(int $issueId, int $contextId): array
    {
        $submissions = $this->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByIssueIds([$issueId])
            ->filterByStatus([Submission::STATUS_PUBLISHED, Submission::STATUS_SCHEDULED])
            ->orderBy(Collector::ORDERBY_SEQUENCE, Collector::ORDER_DIR_ASC)
            ->getMany();

        $bySections = [];
        foreach ($submissions as $submission) {
            $sectionId = $submission->getCurrentPublication()->getData('sectionId');
            if (empty($bySections[$sectionId])) {
                $section = Repo::section()->get($sectionId);
                $bySections[$sectionId] = [
                    'articles' => [],
                    'title' => $section->getData('hideTitle') ? '' : $section->getLocalizedData('title'),
                    'abstractsNotRequired' => $section->getData('abstractsNotRequired'),
                    'hideAuthor' => $section->getData('hideAuthor'),
                ];
            }
            $bySections[$sectionId]['articles'][] = $submission;
        }

        return $bySections;
    }

    public function validateSubmit(Submission $submission, Context $context): array
    {
        $errors = parent::validateSubmit($submission, $context);

        $locale = $submission->getData('locale');
        $publication = $submission->getCurrentPublication();

        $section = Repo::section()->get($submission->getCurrentPublication()->getData('sectionId'), $context->getId());

        // Required abstract
        if (!$section->getAbstractsNotRequired() && !$publication->getData('abstract', $locale)) {
            $errors['abstract'] = [$locale => [__('validator.required')]];
        }

        // Abstract/Plain Language Summary word limit
        if ($section->getAbstractWordCount()) {

            // validate abstract error count and add to errors
            $abstractErrors = $this->validateWordCount(
                $context,
                $submission,
                $section->getAbstractWordCount(),
                'publication.abstract.wordCountLong',
                $publication->getData('abstract') ?? []
            );
            if (count($abstractErrors)) {
                $errors['abstract'] = $abstractErrors;
            }

            // validate plain language summary error count and add to errors
            $plainLanguageSummaryErrors = $this->validateWordCount(
                $context,
                $submission,
                $section->getAbstractWordCount(),
                'publication.plainLanguageSummary.wordCountLong',
                $publication->getData('plainLanguageSummary') ?? []
            );
            if (count($plainLanguageSummaryErrors)) {
                $errors['plainLanguageSummary'] = $plainLanguageSummaryErrors;
            }
        }

        return $errors;
    }

    public function updateStatus(Submission $submission, ?int $newStatus = null, ?Section $section = null)
    {
        $oldStatus = $submission->getData('status');
        parent::updateStatus($submission, $newStatus, $section);
        $newStatus = $submission->getData('status');

        // Add or remove tombstones when submission is published or unpublished
        if ($newStatus === Submission::STATUS_PUBLISHED && $newStatus !== $oldStatus) {
            $tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO'); /** @var DataObjectTombstoneDAO $tombstoneDao */
            $tombstoneDao->deleteByDataObjectId($submission->getId());
        } elseif ($oldStatus === Submission::STATUS_PUBLISHED && $newStatus !== $oldStatus) {
            $requestContext = $this->request->getContext();
            if ($requestContext && $requestContext->getId() === $submission->getData('contextId')) {
                $context = $requestContext;
            } else {
                $context = app()->get('context')->get($submission->getData('contextId'));
            }
            $articleTombstoneManager = new ArticleTombstoneManager();
            if (!$section) {
                $section = Repo::section()->get($submission->getCurrentPublication()->getData('sectionId'), $submission->getData('contextId'));
            }
            $articleTombstoneManager->insertArticleTombstone($submission, $context, $section);
        }
    }

    /**
     * Creates and assigns DOIs to all sub-objects if:
     * 1) the suffix pattern can currently be created, and
     * 2) it does not already exist.
     *
     */
    public function createDois(Submission $submission): array
    {
        return Repo::publication()->createDois($submission->getCurrentPublication(), $submission);
    }
}
