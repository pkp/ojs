<?php

/**
 * @file classes/article/ArticleTombstoneManager.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
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
	function ArticleTombstoneManager() {
	}

	function insertArticleTombstone(&$article, &$journal) {
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$section =& $sectionDao->getSection($article->getSectionId());

		$articleTombstoneDao =& DAORegistry::getDAO('ArticleTombstoneDAO');
		// delete article tombstone -- to ensure that there aren't more than one tombstone for this article
		$articleTombstoneDao->deleteBySubmissionId($article->getId());
		// insert article tombstone
		$section =& $sectionDao->getSection($article->getSectionId());
		$setSpec = urlencode($journal->getPath()) . ':' . urlencode($section->getLocalizedAbbrev());
		$oaiIdentifier = 'oai:' . Config::getVar('oai', 'repository_id') . ':' . 'article/' . $article->getId();

		$articleTombstone = $articleTombstoneDao->newDataObject();
		$articleTombstone->setJournalId($article->getJournalId());
		$articleTombstone->setSubmissionId($article->getId());
		$articleTombstone->stampDateDeleted();
		$articleTombstone->setSectionId($article->getSectionId());
		$articleTombstone->setSetSpec($setSpec);
		$articleTombstone->setSetName($section->getLocalizedTitle());
		$articleTombstone->setOAIIdentifier($oaiIdentifier);
		$tombstoneId = $articleTombstoneDao->insertObject($articleTombstone);

		if (HookRegistry::call('ArticleTombstoneManager::insertArticleTombstone', array(&$articleTombstone, &$article, &$journal))) return;
	}


}

?>
