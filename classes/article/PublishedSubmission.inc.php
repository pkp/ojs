<?php

/**
 * @file classes/article/PublishedSubmission.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublishedSubmission
 * @ingroup article
 * @see PublishedSubmissionDAO
 *
 * @brief Published submission class.
 */

import('classes.article.Article');

// Access status
define('ARTICLE_ACCESS_ISSUE_DEFAULT', 0);
define('ARTICLE_ACCESS_OPEN', 1);

class PublishedSubmission extends Article {

	/**
	 * Get ID of published submission.
	 * @return int
	 */
	function getPublishedSubmissionId() {
		return $this->getData('publishedSubmissionId');
	}

	/**
	 * Set ID of published submission.
	 * @param $publishedSubmissionId int
	 */
	function setPublishedSubmissionId($publishedSubmissionId) {
		return $this->setData('publishedSubmissionId', $publishedSubmissionId);
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
	 * Get sequence of article in table of contents.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('seq');
	}

	/**
	 * Set sequence of article in table of contents.
	 * @param $seq float
	 */
	function setSequence($seq) {
		return $this->setData('seq', $seq);
	}

	/**
	 * Get views of the published submission.
	 * @return int
	 */
	function getViews() {
		$application = Application::getApplication();
		return $application->getPrimaryMetricByAssoc(ASSOC_TYPE_SUBMISSION, $this->getId());
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
	function getGalleys() {
		return $this->getData('galleys');
	}

	/**
	 * Get the localized galleys for an article.
	 * @return array ArticleGalley
	 */
	function getLocalizedGalleys() {
		$allGalleys = $this->getData('galleys');
		$galleys = array();
		foreach (array(AppLocale::getLocale(), AppLocale::getPrimaryLocale()) as $tryLocale) {
			foreach (array_keys($allGalleys) as $key) {
				if ($allGalleys[$key]->getLocale() == $tryLocale) {
					$galleys[] = $allGalleys[$key];
				}
			}
			if (!empty($galleys)) {
				HookRegistry::call('ArticleGalleyDAO::getLocalizedGalleysByArticle', array(&$galleys));
				return $galleys;
			}
		}

		return $galleys;
	}

	/**
	 * Set the galleys for an article.
	 * @param $galleys array ArticleGalley
	 */
	function setGalleys($galleys) {
		return $this->setData('galleys', $galleys);
	}

	function getIsCurrentSubmissionVersion() {
		return $this->getData('isCurrentSubmissionVersion');
	}

	function setIsCurrentSubmissionVersion($isCurrentSubmissionVersion) {
		return $this->setData('isCurrentSubmissionVersion', $isCurrentSubmissionVersion);
	}
}


