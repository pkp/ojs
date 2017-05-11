<?php

/**
 * @file classes/submission/SupplementaryFile.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SupplementaryFile
 * @ingroup submission
 * @see SubmissionFileDAO
 *
 * @brief Supplementary file class. This represents submission files that
 *  support a complete Dublin Core metadata set, and are typically indexed
 *  separately from the submission document itself (e.g. the article). This
 *  typically would be used for genres such as data sets etc.
 */

import('lib.pkp.classes.submission.SubmissionFile');

class SupplementaryFile extends SubmissionFile {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Getters and Setters
	//
	/**
	 * Get "localized" creator (if applicable).
	 * @param $preferredLocale string
	 * @return string
	 */
	function getLocalizedCreator($preferredLocale = null) {
		return $this->getLocalizedData('creator', $preferredLocale);
	}

	/**
	 * Get creator.
	 * @param $locale
	 * @return string
	 */
	function getCreator($locale) {
		return $this->getData('creator', $locale);
	}

	/**
	 * Set creator.
	 * @param $creator string
	 * @param $locale
	 */
	function setCreator($creator, $locale) {
		$this->setData('creator', $creator, $locale);
	}

	/**
	 * Get localized subject
	 * @return string
	 */
	function getLocalizedSubject() {
		return $this->getLocalizedData('subject');
	}

	/**
	 * Get subject.
	 * @param $locale string
	 * @return string
	 */
	function getSubject($locale) {
		return $this->getData('subject', $locale);
	}

	/**
	 * Set subject.
	 * @param $subject string
	 * @param $locale string
	 */
	function setSubject($subject, $locale) {
		$this->setData('subject', $subject, $locale);
	}

	/**
	 * Get localized description
	 * @return string
	 */
	function getLocalizedDescription() {
		return $this->getLocalizedData('description');
	}

	/**
	 * Get file description.
	 * @param $locale string
	 * @return string
	 */
	function getDescription($locale) {
		return $this->getData('description', $locale);
	}

	/**
	 * Set file description.
	 * @param $description string
	 * @param $locale string
	 */
	function setDescription($description, $locale) {
		$this->setData('description', $description, $locale);
	}

	/**
	 * Get localized publisher
	 * @return string
	 */
	function getLocalizedPublisher() {
		return $this->getLocalizedData('publisher');
	}

	/**
	 * Get publisher.
	 * @param $locale string
	 * @return string
	 */
	function getPublisher($locale) {
		return $this->getData('publisher', $locale);
	}

	/**
	 * Set publisher.
	 * @param $publisher string
	 * @param $locale string
	 */
	function setPublisher($publisher, $locale) {
		$this->setData('publisher', $publisher, $locale);
	}

	/**
	 * Get localized sponsor
	 * @return string
	 */
	function getLocalizedSponsor() {
		return $this->getLocalizedData('sponsor');
	}

	/**
	 * Get sponsor.
	 * @param $locale string
	 * @return string
	 */
	function getSponsor($locale) {
		return $this->getData('sponsor', $locale);
	}

	/**
	 * Set sponsor.
	 * @param $sponsor string
	 * @param $locale string
	 */
	function setSponsor($sponsor, $locale) {
		$this->setData('sponsor', $sponsor, $locale);
	}

	/**
	 * Get date created.
	 * @return date
	 */
	function getDateCreated() {
		return $this->getData('dateCreated');
	}

	/**
	 * Set date created.
	 * @param $dateCreated date
	 */
	function setDateCreated($dateCreated) {
		$this->setData('dateCreated', $dateCreated);
	}

	/**
	 * Get localized source
	 * @return string
	 */
	function getLocalizedSource() {
		return $this->getLocalizedData('source');
	}

	/**
	 * Get source.
	 * @param $locale string
	 * @return string
	 */
	function getSource($locale) {
		return $this->getData('source', $locale);
	}

	/**
	 * Set source.
	 * @param $source string
	 * @param $locale string
	 */
	function setSource($source, $locale) {
		$this->setData('source', $source, $locale);
	}

	/**
	 * Get language.
	 * @return string
	 */
	function getLanguage() {
		return $this->getData('language');
	}

	/**
	 * Set language.
	 * @param $language string
	 */
	function setLanguage($language) {
		$this->setData('language', $language);
	}

	/**
	 * Copy the user-facing (editable) metadata from another submission
	 * file.
	 * @param $submissionFile SubmissionFile
	 */
	function copyEditableMetadataFrom($submissionFile) {
		if (is_a($submissionFile, 'SupplementaryFile')) {
			$this->setCreator($submissionFile->getCreator(null), null);
			$this->setSubject($submissionFile->getSubject(null), null);
			$this->setDescription($submissionFile->getDescription(null), null);
			$this->setPublisher($submissionFile->getPublisher(null), null);
			$this->setSponsor($submissionFile->getSponsor(null), null);
			$this->setDateCreated($submissionFile->getDateCreated());
			$this->setSource($submissionFile->getSource(null), null);
			$this->setLanguage($submissionFile->getLanguage());
		}

		parent::copyEditableMetadataFrom($submissionFile);
	}

	/**
	 * @copydoc SubmissionFile::getMetadataForm
	 */
	function getMetadataForm($stageId, $reviewRound) {
		import('lib.pkp.controllers.wizard.fileUpload.form.SupplementaryFileMetadataForm');
		return new SupplementaryFileMetadataForm($this, $stageId, $reviewRound);
	}
}

?>
