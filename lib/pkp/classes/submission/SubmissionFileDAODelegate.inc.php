<?php

/**
 * @file classes/submission/SubmissionFileDAODelegate.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFileDAODelegate
 * @ingroup submission
 * @see SubmissionFile
 *
 * @brief Abstract class to support DAO delegates that provide operations
 *  to retrieve and modify SubmissionFile objects.
 */

import('lib.pkp.classes.db.DAO');
import('lib.pkp.classes.submission.SubmissionFile');

class SubmissionFileDAODelegate extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Abstract public methods to be implemented by subclasses.
	//
	/**
	 * Insert a new submission file.
	 * @param $submissionFile SubmissionFile
	 * @param $sourceFile string The place where the physical file
	 *  resides right now or the file name in the case of an upload.
	 *  The file will be copied to its canonical target location.
	 * @param $isUpload boolean set to true if the file has just been
	 *  uploaded.
	 * @return SubmissionFile the inserted file
	 */
	function insertObject($submissionFile, $sourceFile, $isUpload = false) {
		$fileId = $submissionFile->getFileId();

		if (!is_numeric($submissionFile->getRevision())) {
			// Set the initial revision.
			$submissionFile->setRevision(1);
		}

		if (!is_bool($submissionFile->getViewable())) {
			// Set the viewable default.
			$submissionFile->setViewable(false);
		}

		$params = array(
			(int)$submissionFile->getRevision(),
			(int)$submissionFile->getSubmissionId(),
			is_null($submissionFile->getSourceFileId()) ? null : (int)$submissionFile->getSourceFileId(),
			is_null($submissionFile->getSourceRevision()) ? null : (int)$submissionFile->getSourceRevision(),
			$submissionFile->getFileType(),
			(int)$submissionFile->getFileSize(),
			$submissionFile->getOriginalFileName(),
			(int)$submissionFile->getFileStage(),
			(boolean)$submissionFile->getViewable() ? 1 : 0,
			is_null($submissionFile->getUploaderUserId()) ? null : (int)$submissionFile->getUploaderUserId(),
			is_null($submissionFile->getUserGroupId()) ? null : (int)$submissionFile->getUserGroupId(),
			is_null($submissionFile->getAssocType()) ? null : (int)$submissionFile->getAssocType(),
			is_null($submissionFile->getAssocId()) ? null : (int)$submissionFile->getAssocId(),
			is_null($submissionFile->getGenreId()) ? null : (int)$submissionFile->getGenreId(),
			$submissionFile->getDirectSalesPrice(),
			$submissionFile->getSalesType(),
		);

		if ($fileId) {
			array_unshift($params, (int) $fileId);
		}

		$this->update(
			sprintf('INSERT INTO submission_files
				(' . ($fileId ? 'file_id, ' : '') . 'revision, submission_id, source_file_id, source_revision, file_type, file_size, original_file_name, file_stage, date_uploaded, date_modified, viewable, uploader_user_id, user_group_id, assoc_type, assoc_id, genre_id, direct_sales_price, sales_type)
				VALUES
				(' . ($fileId ? '?, ' : '') . '?, ?, ?, ?, ?, ?, ?, ?, %s, %s, ?, ?, ?, ?, ?, ?, ?, ?)',
				$this->datetimeToDB($submissionFile->getDateUploaded()), $this->datetimeToDB($submissionFile->getDateModified())),
			$params
		);

		if (!$fileId) {
			$submissionFile->setFileId($this->_getInsertId('submission_files', 'file_id'));
		}

		$submissionLocale = $submissionFile->getSubmissionLocale();

		$reviewStage = in_array($submissionFile->getFileStage(), array(
				SUBMISSION_FILE_REVIEW_FILE, SUBMISSION_FILE_REVIEW_ATTACHMENT, SUBMISSION_FILE_REVIEW_REVISION
		));
		if (!$submissionFile->getName($submissionLocale)) {
			if ($reviewStage) {
				$submissionFile->setName($submissionFile->_generateName(true), $submissionLocale);
			} else {
				$submissionFile->setName($submissionFile->_generateName(), $submissionLocale);
			}
		} else {
			if ($reviewStage &&	$submissionFile->getName($submissionLocale) == $submissionFile->_generateName()) {
				$submissionFile->setName($submissionFile->_generateName(true), $submissionLocale);
			}
		}

		$this->updateLocaleFields($submissionFile);

		// Determine the final destination of the file (requires
		// the file id we just generated).
		$targetFilePath = $submissionFile->getFilePath();

		// Only copy the file if it is not yet in the target position.
		if ($isUpload || $sourceFile != $targetFilePath) {
			// Copy the file from its current location to the target destination.
			import('lib.pkp.classes.file.FileManager');
			$fileManager = new FileManager();
			if ($isUpload) {
				$success = $fileManager->uploadFile($sourceFile, $targetFilePath);
			} else {
				assert(is_readable($sourceFile));
				$success = $fileManager->copyFile($sourceFile, $targetFilePath);
			}
			if (!$success) {
				// If the copy/upload operation fails then remove
				// the already inserted meta-data.
				$this->deleteObject($submissionFile);
				$nullVar = null;
				return $nullVar;
			}
		}
		assert(is_readable($targetFilePath));

		return $submissionFile;
	}

	/**
	 * Update a submission file.
	 * @param $submissionFile SubmissionFile The target state
	 *  of the updated file.
	 * @param $previousFile SubmissionFile The current state
	 *  of the updated file.
	 * @return boolean
	 */
	function updateObject($submissionFile, $previousFile) {
		// Update the file in the database.
		$this->update(
			sprintf('UPDATE submission_files
				SET
					file_id = ?,
					revision = ?,
					submission_id = ?,
					source_file_id = ?,
					source_revision = ?,
					file_type = ?,
					file_size = ?,
					original_file_name = ?,
					file_stage = ?,
					date_uploaded = %s,
					date_modified = %s,
					viewable = ?,
					uploader_user_id = ?,
					user_group_id = ?,
					assoc_type = ?,
					assoc_id = ?,
					genre_id = ?,
					direct_sales_price = ?,
					sales_type = ?
				WHERE file_id = ? AND revision = ?',
				$this->datetimeToDB($submissionFile->getDateUploaded()), $this->datetimeToDB($submissionFile->getDateModified())),
			array(
				(int)$submissionFile->getFileId(),
				(int)$submissionFile->getRevision(),
				(int)$submissionFile->getSubmissionId(),
				is_null($submissionFile->getSourceFileId()) ? null : (int)$submissionFile->getSourceFileId(),
				is_null($submissionFile->getSourceRevision()) ? null : (int)$submissionFile->getSourceRevision(),
				$submissionFile->getFileType(),
				$submissionFile->getFileSize(),
				$submissionFile->getOriginalFileName(),
				$submissionFile->getFileStage(),
				(boolean)$submissionFile->getViewable() ? 1 : 0,
				is_null($submissionFile->getUploaderUserId()) ? null : (int)$submissionFile->getUploaderUserId(),
				is_null($submissionFile->getUserGroupId()) ? null : (int)$submissionFile->getUserGroupId(),
				is_null($submissionFile->getAssocType()) ? null : (int)$submissionFile->getAssocType(),
				is_null($submissionFile->getAssocId()) ? null : (int)$submissionFile->getAssocId(),
				is_null($submissionFile->getGenreId()) ? null : (int)$submissionFile->getGenreId(),
				$submissionFile->getDirectSalesPrice(),
				$submissionFile->getSalesType(),
				(int)$previousFile->getFileId(),
				(int)$previousFile->getRevision(),
			)
		);

		$this->updateLocaleFields($submissionFile);

		// Update all dependent objects.
		$this->_updateDependentObjects($submissionFile, $previousFile);

		// Copy the file from its current location to the target destination
		// if necessary.
		$previousFilePath = $previousFile->getFilePath();
		$targetFilePath = $submissionFile->getFilePath();
		if ($previousFilePath != $targetFilePath && is_file($previousFilePath)) {
			// The file location changed so let's move the file on
			// the file system, too.
			assert(is_readable($previousFilePath));
			import('lib.pkp.classes.file.FileManager');
			$fileManager = new FileManager();
			if (!$fileManager->copyFile($previousFilePath, $targetFilePath)) return false;
			if (!$fileManager->deleteFile($previousFilePath)) return false;
		}

		return file_exists($targetFilePath);
	}

	/**
	 * Delete a submission file from the database.
	 * @param $submissionFile SubmissionFile
	 * @return boolean
	 */
	function deleteObject($submissionFile) {
		if (!$this->update(
			'DELETE FROM submission_files
			 WHERE file_id = ? AND revision = ?',
			array(
				(int)$submissionFile->getFileId(),
				(int)$submissionFile->getRevision()
			))) return false;

		// if we've removed the last revision of this file, clean up
		// the settings for this file as well.
		$result = $this->retrieve(
			'SELECT * FROM submission_files WHERE file_id = ?',
			array((int)$submissionFile->getFileId())
		);

		if ($result->RecordCount() == 0) {
			$this->update('DELETE FROM submission_file_settings WHERE file_id = ?',
			array((int) $submissionFile->getFileId()));
		}

		// Delete all dependent objects.
		$this->_deleteDependentObjects($submissionFile);

		// Delete the file on the file system, too.
		$filePath = $submissionFile->getFilePath();
		if(!(is_file($filePath) && is_readable($filePath))) return false;
		assert(is_writable(dirname($filePath)));

		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();
		$fileManager->deleteFile($filePath);

		return !file_exists($filePath);
	}

	/**
	 * Function to return a SubmissionFile object from a row.
	 * @param $row array
	 * @return SubmissionFile
	 */
	function fromRow($row) {
		$submissionFile = $this->newDataObject();
		$submissionFile->setFileId((int)$row['submission_file_id']);
		$submissionFile->setSubmissionLocale($row['submission_locale']);
		$submissionFile->setRevision((int)$row['submission_revision']);
		$submissionFile->setAssocType(is_null($row['assoc_type']) ? null : (int)$row['assoc_type']);
		$submissionFile->setAssocId(is_null($row['assoc_id']) ? null : (int)$row['assoc_id']);
		$submissionFile->setSourceFileId(is_null($row['source_file_id']) ? null : (int)$row['source_file_id']);
		$submissionFile->setSourceRevision(is_null($row['source_revision']) ? null : (int)$row['source_revision']);
		$submissionFile->setSubmissionId((int)$row['submission_id']);
		$submissionFile->setFileStage((int)$row['file_stage']);
		$submissionFile->setOriginalFileName($row['original_file_name']);
		$submissionFile->setFileType($row['file_type']);
		$submissionFile->setGenreId(is_null($row['genre_id']) ? null : (int)$row['genre_id']);
		$submissionFile->setFileSize((int)$row['file_size']);
		$submissionFile->setUploaderUserId(is_null($row['uploader_user_id']) ? null : (int)$row['uploader_user_id']);
		$submissionFile->setUserGroupId(is_null($row['user_group_id']) ? null : (int)$row['user_group_id']);
		$submissionFile->setViewable((boolean)$row['viewable']);
		$submissionFile->setDateUploaded($this->datetimeFromDB($row['date_uploaded']));
		$submissionFile->setDateModified($this->datetimeFromDB($row['date_modified']));
		$submissionFile->setDirectSalesPrice($row['direct_sales_price']);
		$submissionFile->setSalesType($row['sales_type']);

		$this->getDataObjectSettings('submission_file_settings', 'file_id', $row['submission_file_id'], $submissionFile);

		return $submissionFile;
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return SubmissionFile
	 */
	function newDataObject() {
		return new SubmissionFile();
	}


	//
	// Protected helper methods
	//
	/**
	 * Get the list of fields for which data is localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		$localeFieldNames = parent::getLocaleFieldNames();
		$localeFieldNames[] = 'name';
		return $localeFieldNames;
	}

	/**
	 * Get a list of additional fields that do not have
	 * dedicated accessors.
	 * @return array
	 */
	function getAdditionalFieldNames() {
		$additionalFields = parent::getAdditionalFieldNames();
		$additionalFields[] = 'pub-id::publisher-id';
		$additionalFields[] = 'chapterId';
		return $additionalFields;
	}

	/**
	 * Update the localized fields for this submission file.
	 * @param $submissionFile SubmissionFile
	 */
	function updateLocaleFields($submissionFile) {
		// Update the locale fields.
		$this->updateDataObjectSettings('submission_file_settings', $submissionFile, array(
			'file_id' => $submissionFile->getFileId()
		));
	}

	/**
	 * Checks if public identifier exists (other than for the specified
	 * submission file ID, which is treated as an exception).
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 * @param $fileId int An ID to be excluded from the search.
	 * @param $contextId int
	 * @return boolean
	 */
	function pubIdExists($pubIdType, $pubId, $fileId, $contextId) {
		$result = $this->retrieve(
			'SELECT COUNT(*)
			FROM submission_file_settings sfs
				INNER JOIN submission_files sf ON sfs.file_id = sf.file_id
				INNER JOIN submissions s ON sf.submission_id = s.submission_id
			WHERE sfs.setting_name = ? AND sfs.setting_value = ? AND sfs.file_id <> ? AND s.context_id = ?',
			array(
				'pub-id::'.$pubIdType,
				$pubId,
				(int) $fileId,
				(int) $contextId
			)
		);
		$returner = $result->fields[0] ? true : false;
		$result->Close();
		return $returner;
	}

	/**
	 * @copydoc PKPPubIdPluginDAO::changePubId()
	 */
	function changePubId($fileId, $pubIdType, $pubId) {
		$idFields = array(
			'file_id', 'locale', 'setting_name'
		);
		$updateArray = array(
			'file_id' => (int) $fileId,
			'locale' => '',
			'setting_name' => 'pub-id::'.$pubIdType,
			'setting_type' => 'string',
			'setting_value' => (string)$pubId
		);
		$this->replace('submission_file_settings', $updateArray, $idFields);
		$this->flushCache();
	}

	/**
	 * @copydoc PKPPubIdPluginDAO::deletePubId()
	 */
	function deletePubId($fileId, $pubIdType) {
		$settingName = 'pub-id::'.$pubIdType;
		$this->update(
			'DELETE FROM submission_file_settings WHERE setting_name = ? AND file_id = ?',
			array(
				$settingName,
				(int)$fileId
			)
		);
		$this->flushCache();
	}

	/**
	 * @copydoc PKPPubIdPluginDAO::deleteAllPubIds()
	 */
	function deleteAllPubIds($contextId, $pubIdType) {
		$contextId = (int) $contextId;
		$settingName = 'pub-id::'.$pubIdType;

		$submissionDao = Application::getSubmissionDAO();
		$submissions = $submissionDao->getByContextId($contextId);
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		while ($submission = $submissions->next()) {
			$submissionFiles = $submissionFileDao->getBySubmissionId($submission->getId());
			foreach ($submissionFiles as $submissionFile) {
				$this->update(
					'DELETE FROM submission_file_settings WHERE setting_name = ? AND file_id = ?',
					array(
						$settingName,
						(int)$submissionFile->getFileId()
					)
				);
			}
		}
		$this->flushCache();
	}

	//
	// Private helper methods
	//
	/**
	 * Update all objects that depend on the given file.
	 * @param $submissionFile SubmissionFile
	 * @param $previousFile SubmissionFile
	 */
	function _updateDependentObjects($submissionFile, $previousFile) {
		// If the file ids didn't change then we do not have to
		// do anything.
		if (
				$previousFile->getFileId() == $submissionFile->getFileId() ||
				$previousFile->getRevision() == $submissionFile->getRevision()
		) return;

		// Update file views that refer to this file.
		$viewsDao = DAORegistry::getDAO('ViewsDAO'); /* @var $viewsDao ViewsDAO */
		$viewsDao->moveViews(
				ASSOC_TYPE_SUBMISSION_FILE,
				$previousFile->getFileIdAndRevision(), $submissionFile->getFileIdAndRevision()
		);
	}

	/**
	 * Delete all objects that depend on the given file.
	 * @param $submissionFile SubmissionFile
	 */
	function _deleteDependentObjects($submissionFile) {
		// Delete file views that refer to this file.
		$viewsDao = DAORegistry::getDAO('ViewsDAO'); /* @var $viewsDao ViewsDAO */
		$viewsDao->deleteViews(
				ASSOC_TYPE_SUBMISSION_FILE, $submissionFile->getFileIdAndRevision()
		);
	}
}

?>
