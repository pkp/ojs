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
use APP\core\Services;
use APP\facades\Repo;
use APP\journal\JournalDAO;
use APP\section\Section;
use PKP\context\Context;
use PKP\core\PKPString;
use PKP\db\DAORegistry;
use PKP\doi\exceptions\DoiException;
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

        // Abstract word limit
        if ($section->getAbstractWordCount()) {
            $abstracts = $publication->getData('abstract');
            if ($abstracts) {
                $abstractErrors = [];
                foreach ($context->getSupportedSubmissionLocales() as $localeKey) {
                    $abstract = $publication->getData('abstract', $localeKey);
                    $wordCount = $abstract ? PKPString::getWordCount($abstract) : 0;
                    if ($wordCount > $section->getAbstractWordCount()) {
                        $abstractErrors[$localeKey] = [
                            __(
                                'publication.wordCountLong',
                                [
                                    'limit' => $section->getAbstractWordCount(),
                                    'count' => $wordCount
                                ]
                            )
                        ];
                    }
                }
                if (count($abstractErrors)) {
                    $errors['abstract'] = $abstractErrors;
                }
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
                $context = Services::get('context')->get($submission->getData('contextId'));
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
     *
     * @throws \Exception
     */
    public function createDois(Submission $submission): array
    {
        /** @var JournalDAO $contextDao */
        $contextDao = DAORegistry::getDAO('JournalDAO');
        $context = $contextDao->getById($submission->getData('contextId'));

        // Article
        $publication = $submission->getCurrentPublication();

        $doiCreationFailures = [];

        if ($context->isDoiTypeEnabled(Repo::doi()::TYPE_PUBLICATION) && empty($publication->getData('doiId'))) {
            try {
                $doiId = Repo::doi()->mintPublicationDoi($publication, $submission, $context);
                Repo::publication()->edit($publication, ['doiId' => $doiId]);
            } catch (DoiException $exception) {
                $doiCreationFailures[] = $exception;
            }
        }

        // Galleys
        if ($context->isDoiTypeEnabled(Repo::doi()::TYPE_REPRESENTATION)) {
            $galleys = Repo::galley()->getCollector()
                ->filterByPublicationIds(['publicationIds' => $publication->getId()])
                ->getMany();

            foreach ($galleys as $galley) {
                if (empty($galley->getData('doiId'))) {
                    try {
                        $doiId = Repo::doi()->mintGalleyDoi($galley, $publication, $submission, $context);
                        Repo::galley()->edit($galley, ['doiId' => $doiId]);
                    } catch (DoiException $exception) {
                        $doiCreationFailures[] = $exception;
                    }
                }
            }
        }

        return $doiCreationFailures;
    }
}
