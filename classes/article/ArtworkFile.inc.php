<?php

/**
 * @file classes/article/ArtworkFile.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArtworkFile
 * @ingroup article
 * @see SubmissionFileDAO
 *
 * @brief Artwork file class.
 */

import('lib.pkp.classes.submission.SubmissionFile');

class ArtworkFile extends SubmissionFile {
	/** @var array image file information */
	var $_imageInfo;

	/**
	 * Constructor
	 */
	function ArtworkFile() {
		parent::SubmissionFile();
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
		return $this->setData('caption', $caption);
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
		return $this->setData('credit', $credit);
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
		return $this->setData('copyrightOwner', $owner);
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
		return $this->setData('copyrightOwnerContact', $contactDetails);
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
		return $this->setData('terms', $terms);
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
		return $this->setData('permissionFileId', $fileId);
	}

	/**
	 * Get the article component id.
	 * @return int
	 */
	function getChapterId() {
		return $this->getData('chapterId');
	}

	/**
	 * Set the article chapter id.
	 * @param $chapterId int
	 */
	function setChapterId($chapterId) {
		return $this->setData('chapterId', $chapterId);
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
		return $this->setData('contactAuthor', $authorId);
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
	 * Copy the user-facing (editable) metadata from another article
	 * file.
	 * @param $submissionFile SubmissionFile
	 */
	function copyEditableMetadataFrom($submissionFile) {
		if (is_a($submissionFile, 'ArtworkFile')) {
			$this->setCaption($submissionFile->getCaption());
			$this->setCredit($submissionFile->getCredit());
			$this->setCopyrightOwner($submissionFile->getCopyrightOwner());
			$this->setCopyrightOwnerContactDetails($submissionFile->getCopyrightOwnerContactDetails());
			$this->setPermissionTerms($submissionFile->getPermissionTerms());
		}

		parent::copyEditableMetadataFrom($submissionFile);
	}
}

?>
