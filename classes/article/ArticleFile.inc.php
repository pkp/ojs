<?php

/**
 * @file classes/article/ArticleFile.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleFile
 * @ingroup article
 * @see ArticleFileDAO
 *
 * @brief Article file class.
 */

import('lib.pkp.classes.submission.SubmissionFile');

class ArticleFile extends SubmissionFile {

	/**
	 * Constructor.
	 */
	function ArticleFile() {
		parent::SubmissionFile();
	}

	//
	// Get/set methods
	//

	/**
	 * Get ID of article.
	 * @return int
	 */
	function getArticleId() {
		return $this->getSubmissionId();
	}

	/**
	 * Set ID of article.
	 * @param $articleId int
	 */
	function setArticleId($articleId) {
		return $this->setSubmissionId($articleId);
	}

	/**
	 * Check if the file may be displayed inline.
	 * @return boolean
	 */
	function isInlineable() {
		$articleFileDao = DAORegistry::getDAO('ArticleFileDAO');
		return $articleFileDao->isInlineable($this);
	}

	/**
	 * Get a public ID for this galley.
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $preview boolean If true, generate a non-persisted preview only.
	 */
	function getPubId($pubIdType, $preview = false) {
		// FIXME: Move publisher-id to PID plug-in.
		if ($pubIdType === 'publisher-id') {
			$pubId = $this->getStoredPubId($pubIdType);
			return ($pubId ? $pubId : null);
		}

		// Retrieve the article.
		$articleDao = DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */
		$article = $articleDao->getById($this->getArticleId(), null, true);
		if (!$article) return null;

		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $article->getJournalId());
		foreach ($pubIdPlugins as $pubIdPlugin) {
			if ($pubIdPlugin->getPubIdType() == $pubIdType) {
				// If we already have an assigned ID, use it.
				$storedId = $this->getStoredPubId($pubIdType);
				if (!empty($storedId)) return $storedId;

				return $pubIdPlugin->getPubId($this, $preview);
			}
		}
		return null;
	}

	/**
	 * Get stored public ID of the galley.
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @return string
	 */
	function getStoredPubId($pubIdType) {
		return $this->getData('pub-id::'.$pubIdType);
	}

	/**
	 * Set stored public galley id.
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 */
	function setStoredPubId($pubIdType, $pubId) {
		return $this->setData('pub-id::'.$pubIdType, $pubId);
	}

	/**
	 * Set file name of the file.
	 * @param $fileName string
	 */
	function setFileName($fileName) {
		return $this->setData('fileName', $fileName);
	}
}

?>
