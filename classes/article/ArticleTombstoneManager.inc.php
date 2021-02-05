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


class ArticleTombstoneManager {
	/**
	 * Constructor
	 */
	function __construct() {
	}

	function insertArticleTombstone(&$article, &$journal) {
		$sectionDao = DAORegistry::getDAO('SectionDAO'); /* @var $sectionDao SectionDAO */
		$tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO'); /* @var $tombstoneDao DataObjectTombstoneDAO */
		// delete article tombstone -- to ensure that there aren't more than one tombstone for this article
		$tombstoneDao->deleteByDataObjectId($article->getId());
		// insert article tombstone
		$section = $sectionDao->getById($article->getSectionId());
		$setSpec = urlencode($journal->getPath()) . ':' . urlencode($section->getLocalizedAbbrev());
		$oaiIdentifier = 'oai:' . Config::getVar('oai', 'repository_id') . ':' . 'article/' . $article->getId();
		$OAISetObjectsIds = array(
			ASSOC_TYPE_JOURNAL => $journal->getId(),
			ASSOC_TYPE_SECTION => $section->getId(),
		);

		$articleTombstone = $tombstoneDao->newDataObject();
		$articleTombstone->setDataObjectId($article->getId());
		$articleTombstone->stampDateDeleted();
		$articleTombstone->setSetSpec($setSpec);
		$articleTombstone->setSetName($section->getLocalizedTitle());
		$articleTombstone->setOAIIdentifier($oaiIdentifier);
		$articleTombstone->setOAISetObjectsIds($OAISetObjectsIds);
		$tombstoneDao->insertObject($articleTombstone);

		if (HookRegistry::call('ArticleTombstoneManager::insertArticleTombstone', array(&$articleTombstone, &$article, &$journal))) return;
	}

	/**
	 * Insert tombstone for every published submission
	 * @param Context $context
	 */
	function insertTombstonesByContext(Context $context) {
		import('classes.submission.Submission'); // STATUS_PUBLISHED
		$submissionsIterator = Services::get('submission')->getMany(['contextId' => $context->getId(), 'status' => STATUS_PUBLISHED]);
		foreach ($submissionsIterator as $submission) {
			$this->insertArticleTombstone($submission, $context);
		}
	}

	/**
	 * Delete tombstones for published submissions in this context
	 * @param int $contextId
	 */
	function deleteTombstonesByContextId(int $contextId) {
		import('classes.submission.Submission'); // STATUS_PUBLISHED
		$tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO'); /* @var $tombstoneDao DataObjectTombstoneDAO */
		$submissionsIterator = Services::get('submission')->getMany(['contextId' => $contextId, 'status' => STATUS_PUBLISHED]);
		foreach ($submissionsIterator as $submission) {
			$tombstoneDao->deleteByDataObjectId($submission->getId());
		}
	}
}


