<?php

/**
 * PublishedArticle.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article
 *
 * Published article class.
 *
 * $Id$
 */

import('article.Article');

class PublishedArticle extends Article {

	/**
	 * Constructor.
	 */
	function PublishedArticle() {
		parent::Article();
	}
	
	/**
	 * Get ID of published article.
	 * @return int
	 */
	function getPubId() {
		return $this->getData('pubId');
	}
	
	/**
	 * Set ID of published article.
	 * @param $pubId int
	 */
	function setPubId($pubId) {
		return $this->setData('pubId', $pubId);
	}

	/**
	 * Get ID of associated article.
	 * @return int
	 */
	function getArticleId() {
		return $this->getData('articleId');
	}
	
	/**
	 * Set ID of associated article.
	 * @param $articleId int
	 */
	function setArticleId($articleId) {
		return $this->setData('articleId', $articleId);
	}
	
	/**
	 * Get ID of the issue this article is in.
	 * @return int
	 */
	function getIssueId() {
		return $this->getData('issueId');
	}
	
	/**
	 * Set ID of the issue this article is in.
	 * @param $issueId int
	 */
	function setIssueId($issueId) {
		return $this->setData('issueId', $issueId);
	}

	/**
	 * Get section ID of the issue this article is in.
	 * @return int
	 */
	function getSectionId() {
		return $this->getData('sectionId');
	}
	
	/**
	 * Set section ID of the issue this article is in.
	 * @param $sectionId int
	 */
	function setSectionId($sectionId) {
		return $this->setData('sectionId', $sectionId);
	}

	/**
	 * Get date published.
	 * @return date
	 */
	
	function getDatePublished() {
		return $this->getData('datePublished');	
	}
	

	/**
	 * Set date published.
	 * @param $datePublished date
	 */
	 
	function setDatePublished($datePublished) {
		return $this->SetData('datePublished', $datePublished);
	}
	
	/**
	 * Get sequence of article in table of contents.
	 * @return float
	 */
	function getSeq() {
		return $this->getData('seq');
	}
	
	/**
	 * Set sequence of article in table of contents.
	 * @param $sequence float
	 */
	function setSeq($seq) {
		return $this->setData('seq', $seq);
	}

	/**
	 * Get views of the published article.
	 * @return int
	 */
	function getViews() {
		return $this->getData('views');
	}
	
	/**
	 * Set views of the published article.
	 * @param $views int
	 */
	function setViews($views) {
		return $this->setData('views', $views);
	}

	/**
	 * get access status
	 * @return int
	 */
	function getAccessStatus() {
		return $this->getData('accessStatus');
	}
	 
	/**
	 * set access status
	 * @param $accessStatus int
	 */
	function setAccessStatus($accessStatus) {
		return $this->setData('accessStatus',$accessStatus);
	}

	/**
	 * Get the galleys for an article.
	 * @return array ArticleGalley
	 */
	function &getGalleys() {
		$returner = $this->getData('galleys');
		return $returner;
	}
	
	/**
	 * Set the galleys for an article.
	 * @param $galleys array ArticleGalley
	 */
	function setGalleys(&$galleys) {
		return $this->setData('galleys', $galleys);
	}
		
	/**
	 * Get supplementary files for this article.
	 * @return array SuppFiles
	 */
	function getSuppFiles() {
		return $this->getData('suppFiles');
	}
	
	/**
	 * Set supplementary file for this article.
	 * @param $suppFiles array SuppFiles
	 */
	function setSuppFiles($suppFiles) {
		return $this->setData('suppFiles', $suppFiles);
	}
	
	/**
	 * Get public article id
	 * @return string
	 */
	function getPublicArticleId() {
		return $this->getData('publicArticleId');
	}

	/**
	 * Set public article id
	 * @param $publicArticleId string
	 */
	function setPublicArticleId($publicArticleId) {
		return $this->setData('publicArticleId', $publicArticleId);
	}

	/**
	 * Return the "best" article ID -- If a public article ID is set,
	 * use it; otherwise use the internal article Id. (Checks the journal
	 * settings to ensure that the public ID feature is enabled.)
	 * @param $journal Object the journal this article is in
	 * @return string
	 */
	function getBestArticleId($journal = null) {
		// Retrieve the journal, if necessary.
		if (!isset($journal)) {
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$journal = $journalDao->getJournal($this->getJournalId());
		}

		if ($journal->getSetting('enablePublicArticleId')) {
			$publicArticleId = $this->getPublicArticleId();
			if (!empty($publicArticleId)) return $publicArticleId;
		}
		return $this->getArticleId();
	}
}

?>
