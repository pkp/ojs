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
use APP\submission\Submission;
use PKP\config\Config;
use PKP\context\Context;
use PKP\db\DAORegistry;
use PKP\plugins\Hook;

class ArticleTombstoneManager
{
    /**
     * Constructor
     */
    public function __construct()
    {
    }

    public function insertArticleTombstone($article, $journal, $section)
    {
        $tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO'); /** @var \PKP\tombstone\DataObjectTombstoneDAO $tombstoneDao */
        // delete article tombstone -- to ensure that there aren't more than one tombstone for this article
        $tombstoneDao->deleteByDataObjectId($article->getId());
        // insert article tombstone
        $setSpec = OAIDAO::setSpec($journal, $section);
        $oaiIdentifier = 'oai:' . Config::getVar('oai', 'repository_id') . ':' . 'article/' . $article->getId();
        $OAISetObjectsIds = [
            Application::ASSOC_TYPE_JOURNAL => $journal->getId(),
            Application::ASSOC_TYPE_SECTION => $section->getId(),
        ];

        $articleTombstone = $tombstoneDao->newDataObject();
        $articleTombstone->setDataObjectId($article->getId());
        $articleTombstone->stampDateDeleted();
        $articleTombstone->setSetSpec($setSpec);
        $articleTombstone->setSetName($section->getLocalizedTitle());
        $articleTombstone->setOAIIdentifier($oaiIdentifier);
        $articleTombstone->setOAISetObjectsIds($OAISetObjectsIds);
        $tombstoneDao->insertObject($articleTombstone);

        if (Hook::call('ArticleTombstoneManager::insertArticleTombstone', [&$articleTombstone, &$article, &$journal, &$section])) {
            return;
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

if (!PKP_STRICT_MODE) {
    class_alias('\APP\article\ArticleTombstoneManager', '\ArticleTombstoneManager');
}
