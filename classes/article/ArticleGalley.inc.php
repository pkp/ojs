<?php

/**
 * @file ArticleGalley.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article
 * @class ArticleGalley
 *
 * ArticleGalley class.
 * A galley is a final presentation version of the full-text of an article.
 *
 * $Id$
 */

import('article.ArticleFile');

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
		return $this->getData('galleyId');
	}
	
	/**
	 * Set ID of galley.
	 * @param $galleyId int
	 */
	function setGalleyId($galleyId) {
		return $this->setData('galleyId', $galleyId);
	}

	/**
	 * Get views count.
	 * @return int
	 */
	function getViews() {
		return $this->getData('views');
	}
	
	/**
	 * Set views count.
	 * NOTE that the views count is NOT stored by the DAO update or insert functions.
	 * @param $views int
	 */
	function setViews($views) {
		return $this->setData('views', $views);
	}

	/**
	 * Get the localized value of the galley label.
	 * @return $string
	 */
	function getGalleyLabel() {
		$label = $this->getLabel();
		if ($this->getLocale() != Locale::getLocale()) {
			$locales = Locale::getAllLocales();
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
	
}

?>
