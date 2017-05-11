<?php

/**
 * @file classes/submission/SubmissionArtworkFileDAODelegate.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionArtworkFileDAODelegate
 * @ingroup submission
 * @see ArtworkFile
 *
 * @brief Base class for operations for retrieving and modifying ArtworkFile objects.
 *
 * The SubmissionFileDAO will delegate to this class if it wishes
 * to access ArtworkFile classes.
 */

import('lib.pkp.classes.submission.SubmissionFileDAODelegate');
import('lib.pkp.classes.submission.SubmissionArtworkFile');

class SubmissionArtworkFileDAODelegate extends SubmissionFileDAODelegate {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Public methods
	//
	/**
	 * @see SubmissionFileDAODelegate::insert()
	 * @param $artworkFile ArtworkFile
	 * @param $sourceFile object Source file
	 * @param $isUpload boolean True iff this is a new upload.
	 * @return ArtworkFile|null
	 */
	function insertObject($artworkFile, $sourceFile, $isUpload = false) {
		// First insert the data for the super-class.
		$artworkFile = parent::insertObject($artworkFile, $sourceFile, $isUpload);
		if (!$artworkFile) return null;

		// Now insert the artwork-specific data.
		$this->update(
			'INSERT INTO submission_artwork_files
				(file_id, revision, caption, chapter_id, contact_author, copyright_owner, copyright_owner_contact, credit, permission_file_id, permission_terms)
			VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$artworkFile->getFileId(),
				$artworkFile->getRevision(),
				$artworkFile->getCaption(),
				$artworkFile->getChapterId(),
				$artworkFile->getContactAuthor(),
				$artworkFile->getCopyrightOwner(),
				$artworkFile->getCopyrightOwnerContactDetails(),
				$artworkFile->getCredit(),
				$artworkFile->getPermissionFileId(),
				$artworkFile->getPermissionTerms()
			)
		);

		return $artworkFile;
	}

	/**
	 * @see SubmissionFileDAODelegate::update()
	 * @param $artworkFile ArtworkFile
	 * @param $previousFile ArtworkFile
	 * @return boolean True if success.
	 */
	function updateObject($artworkFile, $previousFile) {
		// Update the parent class table first.
		if (!parent::updateObject($artworkFile, $previousFile)) return false;

		// Now update the artwork file table.
		$this->update(
			'UPDATE submission_artwork_files
				SET
					file_id = ?,
					revision = ?,
					caption = ?,
					chapter_id = ?,
					contact_author = ?,
					copyright_owner = ?,
					copyright_owner_contact = ?,
					credit = ?,
					permission_file_id = ?,
					permission_terms = ?
				WHERE file_id = ? and revision = ?',
			array(
				(int)$artworkFile->getFileId(),
				(int)$artworkFile->getRevision(),
				$artworkFile->getCaption(),
				is_null($artworkFile->getChapterId()) ? null : (int)$artworkFile->getChapterId(),
				$artworkFile->getContactAuthor(),
				$artworkFile->getCopyrightOwner(),
				$artworkFile->getCopyrightOwnerContactDetails(),
				$artworkFile->getCredit(),
				is_null($artworkFile->getPermissionFileId()) ? null : (int)$artworkFile->getPermissionFileId(),
				$artworkFile->getPermissionTerms(),
				(int)$previousFile->getFileId(),
				(int)$previousFile->getRevision()
			)
		);
		return true;
	}

	/**
	 * @see SubmissionFileDAODelegate::deleteObject()
	 */
	function deleteObject($submissionFile) {
		// First delete the submission file entry.
		if (!parent::deleteObject($submissionFile)) return false;

		// Delete the artwork file entry.
		return $this->update(
			'DELETE FROM submission_artwork_files
			 WHERE file_id = ? AND revision = ?',
			array(
				(int)$submissionFile->getFileId(),
				(int)$submissionFile->getRevision()
			)
		);
	}

	/**
	 * @see SubmissionFileDAODelegate::fromRow()
	 * @return ArtworkFile
	 */
	function fromRow($row) {
		$artworkFile = parent::fromRow($row);
		$artworkFile->setCredit($row['credit']);
		$artworkFile->setCaption($row['caption']);
		$artworkFile->setChapterId(is_null($row['chapter_id']) ? null : (int)$row['chapter_id']);
		$artworkFile->setContactAuthor($row['contact_author']);
		$artworkFile->setCopyrightOwner($row['copyright_owner']);
		$artworkFile->setPermissionTerms($row['permission_terms']);
		$artworkFile->setPermissionFileId(is_null($row['permission_file_id']) ? null : (int)$row['permission_file_id']);
		$artworkFile->setCopyrightOwnerContactDetails($row['copyright_owner_contact']);

		return $artworkFile;
	}

	/**
	 * @copydoc SubmissionFileDAODelegate::newDataObject()
	 */
	function newDataObject() {
		return new SubmissionArtworkFile();
	}
}

?>
