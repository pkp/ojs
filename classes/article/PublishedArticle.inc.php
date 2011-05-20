<?php

/**
 * @file classes/article/PublishedArticle.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublishedArticle
 * @ingroup article
 * @see PublishedArticleDAO
 *
 * @brief Published article class.
 */

// $Id$


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
		$primaryLocale = Locale::getPrimaryLocale();

		$allGalleys =& $this->getData('galleys');
		$galleys = array();
		foreach (array(Locale::getLocale(), Locale::getPrimaryLocale()) as $tryLocale) {
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
			$journal = $journalDao->getJournal($this->getJournalId());
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
		// If we already have an assigned DOI, use it.
		$storedDOI = $this->getStoredDOI();
		if ($storedDOI) return $storedDOI;

		// Otherwise, create a new one.
		$journalId = $this->getJournalId();

		// Get the Journal object (optimized)
		$journal =& Request::getJournal();
		if (!$journal || $journal->getId() != $journalId) {
			unset($journal);
			$journalDao =& DAORegistry::getDAO('JournalDAO');
			$journal =& $journalDao->getJournal($journalId);
		}

		if (($doiPrefix = $journal->getSetting('doiPrefix')) == '') return null;
		$doiSuffixSetting = $journal->getSetting('doiSuffix');

		// Get the issue
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issue =& $issueDao->getIssueById($this->getIssueId(), $this->getJournalId(), true);

		if (!$issue || !$journal || $journal->getId() != $issue->getJournalId() ) return null;

		switch ( $doiSuffixSetting ) {
			case 'customIdentifier':
				$doi = $doiPrefix . '/' . $this->getBestArticleId();
				break;
			case 'pattern':
				$suffixPattern = $journal->getSetting('doiSuffixPattern');
				// %j - journal initials
				$suffixPattern = String::regexp_replace('/%j/', String::strtolower($journal->getLocalizedSetting('initials')), $suffixPattern);
				// %v - volume number
				$suffixPattern = String::regexp_replace('/%v/', $issue->getVolume(), $suffixPattern);
				// %i - issue number
				$suffixPattern = String::regexp_replace('/%i/', $issue->getNumber(), $suffixPattern);
				// %a - article id
				$suffixPattern = String::regexp_replace('/%a/', $this->getArticleId(), $suffixPattern);
				// %p - page number
				$suffixPattern = String::regexp_replace('/%p/', $this->getPages(), $suffixPattern);
				$doi = $doiPrefix . '/' . $suffixPattern;
				break;
			default:
				$doi = $doiPrefix . '/' . String::strtolower($journal->getLocalizedSetting('initials')) . '.v' . $issue->getVolume() . 'i' . $issue->getNumber() . '.' . $this->getArticleId();
		}

		if (!$preview) {
			// Save the generated DOI
			$this->setStoredDOI($doi);
			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$articleDao->changeDOI($this->getId(), $doi);
		}

		return $doi;
	}
}

?>
