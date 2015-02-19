<?php

/**
 * @file classes/article/PublishedArticle.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
		return $this->getData('publishedArticleId');
	}

	/**
	 * Set ID of published article.
	 * @param $publishedArticleId int
	 */
	function setPublishedArticleId($publishedArticleId) {
		return $this->setData('publishedArticleId', $publishedArticleId);
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
		$application =& PKPApplication::getApplication();
		return $application->getPrimaryMetricByAssoc(ASSOC_TYPE_ARTICLE, $this->getId());
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
}

?>
