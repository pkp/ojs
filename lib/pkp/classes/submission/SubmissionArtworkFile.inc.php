<?php

/**
 * @file classes/submission/SubmissionArtworkFile.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionArtworkFile
 * @ingroup submission
 * @see SubmissionFileDAO
 *
 * @brief Artwork file class.
 */

import('lib.pkp.classes.submission.SubmissionFile');

class SubmissionArtworkFile extends SubmissionFile {
	/** @var array image file information */
	var $_imageInfo;

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
	 * Get artwork caption.
	 * @return string
	 */
	function getCaption() {
		return $this->getData('caption');
	}

	/**
	 * Set artwork caption.
	 * @param $caption string
	 */
	function setCaption($caption) {
		$this->setData('caption', $caption);
	}

	/**
	 * Get the credit.
	 * @return string
	 */
	function getCredit() {
		return $this->getData('credit');
	}

	/**
	 * Set the credit.
	 * @param $credit string
	 */
	function setCredit($credit) {
		$this->setData('credit', $credit);
	}

	/**
	 * Get the copyright owner.
	 * @return string
	 */
	function getCopyrightOwner() {
		return $this->getData('copyrightOwner');
	}

	/**
	 * Set the copyright owner.
	 * @param $owner string
	 */
	function setCopyrightOwner($owner) {
		$this->setData('copyrightOwner', $owner);
	}

	/**
	 * Get contact details for the copyright owner.
	 * @return string
	 */
	function getCopyrightOwnerContactDetails() {
		return $this->getData('copyrightOwnerContact');
	}

	/**
	 * Set the contact details for the copyright owner.
	 * @param $contactDetails string
	 */
	function setCopyrightOwnerContactDetails($contactDetails) {
		$this->setData('copyrightOwnerContact', $contactDetails);
	}

	/**
	 * Get the permission terms.
	 * @return string
	 */
	function getPermissionTerms() {
		return $this->getData('terms');
	}

	/**
	 * Set the permission terms.
	 * @param $terms string
	 */
	function setPermissionTerms($terms) {
		$this->setData('terms', $terms);
	}

	/**
	 * Get the permission form file id.
	 * @return int
	 */
	function getPermissionFileId() {
		return $this->getData('permissionFileId');
	}

	/**
	 * Set the permission form file id.
	 * @param $fileId int
	 */
	function setPermissionFileId($fileId) {
		$this->setData('permissionFileId', $fileId);
	}

	/**
	 * Get the contact author's id.
	 * @return int
	 */
	function getContactAuthor() {
		return $this->getData('contactAuthor');
	}

	/**
	 * Set the contact author's id.
	 * @param $authorId int
	 */
	function setContactAuthor($authorId) {
		$this->setData('contactAuthor', $authorId);
	}

	/**
	 * Get the width of the image in pixels.
	 * @return integer
	 */
	function getWidth() {
		if (!$this->_imageInfo) {
			$this->_imageInfo = getimagesize($this->getFilePath());
		}
		return $this->_imageInfo[0];
	}

	/**
	 * Get the height of the image in pixels.
	 * @return integer
	 */
	function getHeight() {
		if (!$this->_imageInfo) {
			$this->_imageInfo = getimagesize($this->getFilePath());
		}
		return $this->_imageInfo[1];
	}

	/**
	 * Get the physical width of an image when printed
	 *
	 * Common use is to print at 300 DPI (dots per inch), but you can pass any
	 * any pixel density to this function to return it's printed width. For
	 * instance, a 300 DPI is roughly equal to 118 dpcm (dots per centimeter),
	 * so you'd pass $dpi = 118 to calculate the width in centimeters.
	 *
	 * @param $dpi int Dots (or pixels) per inch (or any other unit of
	 *  measurement).
	 * @return integer
	 */
	function getPhysicalWidth($dpi) {
		$width = $this->getWidth();
		if (!is_int($width) || $width <= 0) {
			return 0;
		}
		return number_format($width/$dpi,1);
	}

	/**
	 * Get the physical height of an image when printed
	 *
	 * @see self::getPhysicalWidth
	 * @param $dpi int Dots (or pixels) per inch (or any other unit of
	 *  measurement).
	 * @return integer
	 */
	function getPhysicalHeight($dpi) {
		$height = $this->getheight();
		if (!is_int($height) || $height <= 0) {
			return 0;
		}
		return number_format($height/$dpi,1);
	}

	/**
	 * Copy the user-facing (editable) metadata from another submission
	 * file.
	 * @param $submissionFile SubmissionFile
	 */
	function copyEditableMetadataFrom($submissionFile) {
		if (is_a($submissionFile, 'SubmissionArtworkFile')) {
			$this->setCaption($submissionFile->getCaption());
			$this->setCredit($submissionFile->getCredit());
			$this->setCopyrightOwner($submissionFile->getCopyrightOwner());
			$this->setCopyrightOwnerContactDetails($submissionFile->getCopyrightOwnerContactDetails());
			$this->setPermissionTerms($submissionFile->getPermissionTerms());
		}

		parent::copyEditableMetadataFrom($submissionFile);
	}

	/**
	 * @copydoc SubmissionFile::getMetadataForm
	 */
	function getMetadataForm($stageId, $reviewRound) {
		import('lib.pkp.controllers.wizard.fileUpload.form.SubmissionFilesArtworkMetadataForm');
		return new SubmissionFilesArtworkMetadataForm($this, $stageId, $reviewRound);
	}
}

?>
