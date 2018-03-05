<?php
/**
 * @defgroup issue_galley Issue Galleys
 * Issue galleys allow for the representation of an entire journal issue with
 * a single file, typically a PDF.
 */

/**
 * @file classes/issue/IssueGalley.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueGalley
 * @ingroup issue_galley
 * @see IssueGalleyDAO
 *
 * @brief A galley is a final presentation version of the full-text of an issue.
 */

import('classes.issue.IssueFile');

class IssueGalley extends IssueFile {
	/** @var IssueFile */
	var $_issueFile;


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

	//
	// Get/set methods
	//

	/**
	 * Get views count.
	 * @return int
	 */
	function getViews() {
		$application = PKPApplication::getApplication();
		return $application->getPrimaryMetricByAssoc(ASSOC_TYPE_ISSUE_GALLEY, $this->getId());
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
	 * Get sequence order.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set sequence order.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}

	/**
	 * Get file ID.
	 * @return int
	 */
	function getFileId() {
		return $this->getData('fileId');
	}

	/**
	 * Set file ID.
	 * @param $fileId
	 */
	function setFileId($fileId) {
		return $this->setData('fileId', $fileId);
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
	 * Return the "best" issue galley ID -- If a public isue galley ID is set,
	 * use it; otherwise use the internal article Id.
	 * @return string
	 */
	function getBestGalleyId() {
		$publicGalleyId = $this->getStoredPubId('publisher-id');
		if (!empty($publicGalleyId)) return $publicGalleyId;
		return $this->getId();
	}

	/**
	 * Get the file corresponding to this galley.
	 * @return IssueFile
	 */
	function getFile() {
		if (!isset($this->_issueFile)) {
			$issueFileDao = DAORegistry::getDAO('IssueFileDAO');
			$this->_issueFile = $issueFileDao->getById($this->getFileId());
		}
		return $this->_issueFile;
	}

}

?>
