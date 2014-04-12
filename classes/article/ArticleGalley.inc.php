<?php

/**
 * @file classes/article/ArticleGalley.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalley
 * @ingroup article
 * @see ArticleGalleyDAO
 *
 * @brief A galley is a final presentation version of the full-text of an article.
 */

import('classes.article.ArticleFile');

class ArticleGalley extends ArticleFile {

	/**
	 * Constructor.
	 */
	function ArticleGalley() {
		parent::DataObject();
	}

	/**
	 * Check if galley is an HTML galley.
	 * @return boolean
	 */
	function isHTMLGalley() {
		return false;
	}

	/**
	 * Check if galley is a PDF galley.
	 * @return boolean
	 */
	function isPdfGalley() {
		switch ($this->getFileType()) {
			case 'application/pdf':
			case 'application/x-pdf':
			case 'text/pdf':
			case 'text/x-pdf':
				return true;
			default: return false;
		}
	}

	/**
	 * Check if the specified file is a dependent file.
	 * @param $fileId int
	 * @return boolean
	 */
	function isDependentFile($fileId) {
		return false;
	}

	//
	// Get/set methods
	//

	/**
	 * Get ID of galley.
	 * @return int
	 */
	function getGalleyId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getId();
	}

	/**
	 * Set ID of galley.
	 * @param $galleyId int
	 */
	function setGalleyId($galleyId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setId($galleyId);
	}

	/**
	 * Get views count.
	 * @return int
	 */
	function getViews() {
		$application =& PKPApplication::getApplication();
		return $application->getPrimaryMetricByAssoc(ASSOC_TYPE_GALLEY, $this->getId());
	}

	/**
	 * Get the localized value of the galley label.
	 * @return $string
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
	 * Get sequence order of supplementary file.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set sequence order of supplementary file.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}

	/**
	 * Return the "best" article ID -- If a public article ID is set,
	 * use it; otherwise use the internal article Id. (Checks the journal
	 * settings to ensure that the public ID feature is enabled.)
	 * @param $journal Object the journal this galley is in
	 * @return string
	 */
	function getBestGalleyId(&$journal = null) {
		if (is_null($journal)) {
			$articleDao =& DAORegistry::getDAO('ArticleDAO');  /* @var $articleDao ArticleDAO */
			$journalDao =& DAORegistry::getDAO('JournalDAO');  /* @var $journalDao JournalDAO */
			$journalId = $articleDao->getArticleJournalId($this->getArticleId());
			$journal =& $journalDao->getById($journalId);
		}

		if ($journal->getSetting('enablePublicGalleyId')) {
			$publicGalleyId = $this->getPubId('publisher-id');
			if (!empty($publicGalleyId)) return $publicGalleyId;
		}
		return $this->getId();
	}

	/**
	 * Set remote URL of the galley.
	 * @param $remoteURL string
	 */
	function setRemoteURL($remoteURL) {
		return $this->setData('remoteURL', $remoteURL);
	}

	/**
	 * Get remote URL of the galley.
	 * @return string
	 */
	function getRemoteURL() {
		return $this->getData('remoteURL');
	}
}

?>
