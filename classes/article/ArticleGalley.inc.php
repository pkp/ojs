<?php

/**
 * @file classes/article/ArticleGalley.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalley
 * @ingroup article
 * @see ArticleGalleyDAO
 *
 * @brief A galley is a final presentation version of the full-text of an article.
 */

import('lib.pkp.classes.submission.Representation');

class ArticleGalley extends Representation {
	/** @var SubmissionFile */
	var $_submissionFile;


	//
	// Get/set methods
	//
	/**
	 * Get views count.
	 * @return int
	 */
	function getViews() {
		$application = PKPApplication::getApplication();
		$fileId = $this->getFileId();
		if ($fileId) {
			return $application->getPrimaryMetricByAssoc(ASSOC_TYPE_SUBMISSION_FILE, $fileId);
		} else {
			return 0;
		}
	}

	/**
	 * Get label/title.
	 * @return string
	 */
	function getLabel() {
		return $this->getData('label');
	}

	/**
	 * Set label/title.
	 * @param $label string
	 */
	function setLabel($label) {
		return $this->setData('label', $label);
	}

	/**
	 * Get locale.
	 * @return string
	 */
	function getLocale() {
		return $this->getData('locale');
	}

	/**
	 * Set locale.
	 * @param $locale string
	 */
	function setLocale($locale) {
		return $this->setData('locale', $locale);
	}

	/**
	 * Return the "best" article ID -- If a public article ID is set,
	 * use it; otherwise use the internal article Id.
	 * @return string
	 */
	function getBestGalleyId() {
		$publicGalleyId = $this->getStoredPubId('publisher-id');
		if (!empty($publicGalleyId)) return $publicGalleyId;
		return $this->getId();
	}

	/**
	 * Set file ID.
	 * @param $fileId int
	 */
	function setFileId($fileId) {
		$this->setData('fileId', $fileId);
	}

	/**
	 * Get file id
	 * @return int
	 */
	function getFileId() {
		return $this->getData('fileId');
	}

	/**
	 * Get the submission file corresponding to this galley.
	 * @return SubmissionFile
	 */
	function getFile() {
		if (!isset($this->_submissionFile)) {
			$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
			$this->_submissionFile = $submissionFileDao->getLatestRevision($this->getFileId());
		}
		return $this->_submissionFile;
	}

	/**
	 * Get the file type corresponding to this galley.
	 * @return string MIME type
	 */
	function getFileType() {
		$galleyFile = $this->getFile();
		return isset($galleyFile) ? $galleyFile->getFileType() : null;
	}

	/**
	 * Determine whether the galley is a PDF.
	 * @return boolean
	 */
	function isPdfGalley() {
		return $this->getFileType() == 'application/pdf';
	}

	/**
	 * Get the localized galley label.
	 * @return string
	 */
	function getGalleyLabel() {
		$label = $this->getLabel();
		if ($this->getLocale() != AppLocale::getLocale()) {
			$locales = AppLocale::getAllLocales();
			$label .= ' (' . $locales[$this->getLocale()] . ')';
		}
		return $label;
	}

	/**
	 * @see Representation::getName()
	 *
	 * This override exists to provide a functional getName() in order to make
	 * native XML export work correctly.  It is only used in that single instance.
	 *
	 * @param $locale string unused, except to match the function prototype in Representation.
	 * @return array
	 */
	function getName($locale) {
		return array($this->getLocale() => $this->getLabel());
	}

	/**
	 * Override the parent class to fetch the non-localized label.
	 * @see Representation::getLocalizedName()
	 * @return string
	 */
	function getLocalizedName() {
		return $this->getLabel();
	}
}

?>
