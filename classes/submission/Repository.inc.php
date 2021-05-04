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

use APP\core\Application;
use APP\core\Services;
use APP\facades\Repo;
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
    public function updateStatus(Submission $submission)
    {
        $oldStatus = $submission->getData('status');
        parent::updateStatus($submission);
        $newStatus = Repo::submission()->get($submission->getId())->getData('status');

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
            import('classes.article.ArticleTombstoneManager');
            $articleTombstoneManager = new \ArticleTombstoneManager();
            $articleTombstoneManager->insertArticleTombstone($submission, $context);
        }
    }
}
