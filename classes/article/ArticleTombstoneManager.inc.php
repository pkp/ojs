<?php

/**
 * @file classes/article/ArticleTombstoneManager.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
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
		$sectionDao = DAORegistry::getDAO('SectionDAO');
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
}

?>
