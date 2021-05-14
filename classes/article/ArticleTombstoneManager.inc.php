<?php

/**
 * @file classes/article/ArticleTombstoneManager.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleTombstoneManager
 * @ingroup article
 *
 * @brief Class defining basic operations for article tombstones.
 */

use PKP\submission\PKPSubmission;

class ArticleTombstoneManager
{
    /**
     * Constructor
     */
    public function __construct()
    {
    }

    public function insertArticleTombstone(&$article, &$journal)
    {
        $sectionDao = DAORegistry::getDAO('SectionDAO'); /* @var $sectionDao SectionDAO */
        $tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO'); /* @var $tombstoneDao DataObjectTombstoneDAO */
        // delete article tombstone -- to ensure that there aren't more than one tombstone for this article
        $tombstoneDao->deleteByDataObjectId($article->getId());
        // insert article tombstone
        $section = $sectionDao->getById($article->getSectionId());
        $setSpec = urlencode($journal->getPath()) . ':' . urlencode($section->getLocalizedAbbrev());
        $oaiIdentifier = 'oai:' . Config::getVar('oai', 'repository_id') . ':' . 'article/' . $article->getId();
        $OAISetObjectsIds = [
            ASSOC_TYPE_JOURNAL => $journal->getId(),
            ASSOC_TYPE_SECTION => $section->getId(),
        ];

        $articleTombstone = $tombstoneDao->newDataObject();
        $articleTombstone->setDataObjectId($article->getId());
        $articleTombstone->stampDateDeleted();
        $articleTombstone->setSetSpec($setSpec);
        $articleTombstone->setSetName($section->getLocalizedTitle());
        $articleTombstone->setOAIIdentifier($oaiIdentifier);
        $articleTombstone->setOAISetObjectsIds($OAISetObjectsIds);
        $tombstoneDao->insertObject($articleTombstone);

        if (HookRegistry::call('ArticleTombstoneManager::insertArticleTombstone', [&$articleTombstone, &$article, &$journal])) {
            return;
        }
    }

    /**
     * Insert tombstone for every published submission
     */
    public function insertTombstonesByContext(Context $context)
    {
        $submissionsIterator = Services::get('submission')->getMany(['contextId' => $context->getId(), 'status' => PKPSubmission::STATUS_PUBLISHED]);
        foreach ($submissionsIterator as $submission) {
            $this->insertArticleTombstone($submission, $context);
        }
    }

    /**
     * Delete tombstones for published submissions in this context
     */
    public function deleteTombstonesByContextId(int $contextId)
    {
        $tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO'); /* @var $tombstoneDao DataObjectTombstoneDAO */
        $submissionsIterator = Services::get('submission')->getMany(['contextId' => $contextId, 'status' => PKPSubmission::STATUS_PUBLISHED]);
        foreach ($submissionsIterator as $submission) {
            $tombstoneDao->deleteByDataObjectId($submission->getId());
        }
    }
}
