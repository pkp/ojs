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

	/**
	 * Generate the unique filename for this submission file.
	 * Overridden from SubmissionFile in PKP-lib specificall for Article files.
	 * @return string
	 */
	function _generateFileName() {
		// Remember the ID information we generated the file name
		// on so that we only have to re-generate the name if the
		// relevant information changed.
		static $lastIds = array();
		static $fileName = null;

		// Retrieve the current id information.
		$currentIds = array(
				'genreId' => $this->getGenreId(),
				'dateUploaded' => $this->getDateUploaded(),
				'submissionId' => $this->getSubmissionId(),
				'fileId' => $this->getFileId(),
				'revision' => $this->getRevision(),
				'fileStage' => $this->getFileStage(),
				'extension' => strtolower_codesafe($this->getExtension())
		);

		// Check whether we need a refresh.
		$refreshRequired = false;
		foreach($currentIds as $key => $currentId) {
			if (!isset($lastIds[$key]) || $lastIds[$key] !== $currentId) {
				$refreshRequired = true;
				$lastIds = $currentIds;
				break;
			}
		}

		// Refresh the file name if required.
		if ($refreshRequired) {
			// Make the file name unique across all files and file revisions.
			// Also make sure that files can be ordered sensibly by file name.
			$fileName = $currentIds['submissionId'].'-'.$currentIds['fileId'].'-'.$currentIds['revision'].'-'.$currentIds['fileStage'].'.'.$currentIds['extension'];
		}

		return $fileName;
	}

}

?>
