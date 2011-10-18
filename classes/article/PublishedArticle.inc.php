<?php

/**
 * @file classes/article/PublishedArticle.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublishedArticle
 * @ingroup article
 * @see PublishedArticleDAO
 *
 * @brief Published article class.
 */

import('classes.article.Article');

// Access status
define('ARTICLE_ACCESS_ISSUE_DEFAULT', 0);
define('ARTICLE_ACCESS_OPEN', 1);

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
	function getPublishedArticleId() {
		return $this->getData('pubId');
	}

	/**
	 * Set ID of published article.
	 * @param $pubId int
	 */
	function setPublishedArticleId($pubId) {
		return $this->setData('pubId', $pubId);
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
	 * get access status (ARTICLE_ACCESS_...)
	 * @return int
	 */
	function getAccessStatus() {
		return $this->getData('accessStatus');
	}

	/**
	 * set access status (ARTICLE_ACCESS_...)
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
		$galleys =& $this->getData('galleys');
		return $galleys;
	}

	/**
	 * Get the localized galleys for an article.
	 * @return array ArticleGalley
	 */
	function &getLocalizedGalleys() {
		$primaryLocale = AppLocale::getPrimaryLocale();

		$allGalleys =& $this->getData('galleys');
		$galleys = array();
		foreach (array(AppLocale::getLocale(), AppLocale::getPrimaryLocale()) as $tryLocale) {
			foreach (array_keys($allGalleys) as $key) {
				if ($allGalleys[$key]->getLocale() == $tryLocale) {
					$galleys[] =& $allGalleys[$key];
				}
			}
			if (!empty($galleys)) {
				HookRegistry::call('ArticleGalleyDAO::getLocalizedGalleysByArticle', array(&$galleys, &$articleId));
				return $galleys;
			}
		}

		return $galleys;
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
	function &getSuppFiles() {
		$returner =& $this->getData('suppFiles');
		return $returner;
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
		// Ensure that blanks are treated as nulls.
		$returner = $this->getData('publicArticleId');
		if ($returner === '') return null;
		return $returner;
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
			$journalDao =& DAORegistry::getDAO('JournalDAO');
			$journal = $journalDao->getById($this->getJournalId());
		}

		if ($journal->getSetting('enablePublicArticleId')) {
			$publicArticleId = $this->getPublicArticleId();
			if (!empty($publicArticleId)) return $publicArticleId;
		}
		return $this->getId();
	}

	/**
	 * Get a DOI for this article.
	 * @var $preview boolean If true, generate a non-persisted preview only.
	 */
	function getDOI($preview = false) {
		import('classes.article.DoiHelper');
		$doiHelper = new DoiHelper();
		return $doiHelper->getDOI($this, $preview);
                                // %Y - year
				$suffixPattern = String::regexp_replace('/%Y/', $issue->getYear(), $suffixPattern);
	}
}

?>
