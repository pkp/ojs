<?php
/**
 * @file classes/submission/Repository.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class submission
 *
 * @brief A repository to find and manage submissions.
 */

namespace APP\submission;

use APP\article\ArticleTombstoneManager;
use APP\core\Application;
use APP\core\Services;
use APP\facades\Repo;
use APP\journal\JournalDAO;
use PKP\context\Context;
use PKP\db\DAORegistry;

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
        $collector = $this->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByIssueIds([$issueId])
            ->filterByStatus([Submission::STATUS_PUBLISHED, Submission::STATUS_SCHEDULED])
            ->orderBy(Collector::ORDERBY_SEQUENCE, Collector::ORDER_DIR_ASC);

        $submissions = $this->getMany($collector);

        $bySections = [];
        foreach ($submissions as $submission) {
            $sectionId = $submission->getCurrentPublication()->getData('sectionId');
            if (empty($bySections[$sectionId])) {
                $section = Application::get()->getSectionDao()->getById($sectionId);
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

    /** @copydoc \PKP\submission\Repo::updateStatus() */
    public function updateStatus(Submission $submission, ?int $newStatus = null)
    {
        $oldStatus = $submission->getData('status');
        parent::updateStatus($submission, $newStatus);
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
            $articleTombstoneManager->insertArticleTombstone($submission, $context);
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
    public function createDois(Submission $submission): void
    {
        /** @var JournalDAO $contextDao */
        $contextDao = \DAORegistry::getDAO('JournalDAO');
        /** @var Context $context */
        $context = $contextDao->getById($submission->getData('contextId'));

        // Article
        $publication = $submission->getCurrentPublication();
        if ($context->isDoiTypeEnabled(Repo::doi()::TYPE_PUBLICATION) && empty($publication->getData('doiId'))) {
            $doiId = Repo::doi()->mintPublicationDoi($publication, $submission, $context);
            if ($doiId !== null) {
                Repo::publication()->edit($publication, ['doiId' => $doiId]);
            }
        }

        // Galleys
        if ($context->isDoiTypeEnabled(Repo::doi()::TYPE_REPRESENTATION)) {
            $galleys = Repo::galley()->getMany(
                Repo::galley()
                    ->getCollector()
                    ->filterByPublicationIds(['publicationIds' => $publication->getId()])
            );
            foreach ($galleys as $galley) {
                if (empty($galley->getData('doiId'))) {
                    $doiId = Repo::doi()->mintGalleyDoi($galley, $publication, $submission, $context);
                    if ($doiId !== null) {
                        Repo::galley()->edit($galley, ['doiId' => $doiId]);
                    }
                }
            }
        }
    }
}
