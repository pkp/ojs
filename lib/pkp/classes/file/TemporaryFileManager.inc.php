<?php

/**
 * @file classes/file/TemporaryFileManager.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPTemporaryFileManager
 * @ingroup file
 * @see TemporaryFileDAO
 *
 * @brief Class defining operations for temporary file management.
 */

import('lib.pkp.classes.file.PrivateFileManager');

class TemporaryFileManager extends PrivateFileManager {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();

		$this->_performPeriodicCleanup();
	}

	/**
	 * Get the base path for temporary file storage.
	 * @return string
	 */
	function getBasePath() {
		return parent::getBasePath() . '/temp/';
	}

	/**
	 * Retrieve file information by file ID.
	 * @return TemporaryFile
	 */
	function getFile($fileId, $userId) {
		$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
		return $temporaryFileDao->getTemporaryFile($fileId, $userId);
	}

	/**
	 * Delete a file by ID.
	 * @param $fileId int
	 */
	function deleteFile($fileId, $userId) {
		$temporaryFile =& $this->getFile($fileId, $userId);

		parent::deleteFile($this->getBasePath() . $temporaryFile->getServerFileName());

		$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
		$temporaryFileDao->deleteTemporaryFileById($fileId, $userId);
	}

	/**
	 * Download a file.
	 * @param $fileId int the file id of the file to download
	 * @param $inline print file as inline instead of attachment, optional
	 * @return boolean
	 */
	function downloadFile($fileId, $userId, $inline = false) {
		$temporaryFile =& $this->getFile($fileId, $userId);
		if (isset($temporaryFile)) {
			$filePath = $this->getBasePath() . $temporaryFile->getServerFileName();
			return parent::downloadFile($filePath, null, $inline);
		} else {
			return false;
		}
	}

	/**
	 * Upload the file and add it to the database.
	 * @param $fileName string index into the $_FILES array
	 * @param $userId int
	 * @return object The new TemporaryFile or false on failure
	 */
	function handleUpload($fileName, $userId) {
		// Get the file extension, then rename the file.
		$fileExtension = $this->parseFileExtension($this->getUploadedFileName($fileName));

		if (!$this->fileExists($this->getBasePath(), 'dir')) {
			// Try to create destination directory
			$this->mkdirtree($this->getBasePath());
		}

		$newFileName = basename(tempnam($this->getBasePath(), $fileExtension));
		if (!$newFileName) return false;

		if ($this->uploadFile($fileName, $this->getBasePath() . $newFileName)) {
			$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
			$temporaryFile = $temporaryFileDao->newDataObject();

			$temporaryFile->setUserId($userId);
			$temporaryFile->setServerFileName($newFileName);
			$temporaryFile->setFileType(PKPString::mime_content_type($this->getBasePath() . $newFileName));
			$temporaryFile->setFileSize($_FILES[$fileName]['size']);
			$temporaryFile->setOriginalFileName($this->truncateFileName($_FILES[$fileName]['name'], 127));
			$temporaryFile->setDateUploaded(Core::getCurrentDate());

			$temporaryFileDao->insertObject($temporaryFile);

			return $temporaryFile;

		} else {
			return false;
		}
	}

	/**
	 * Create a new temporary file from a submission file.
	 * @param $submissionFile object
	 * @param $userId int
	 * @return object The new TemporaryFile or false on failure
	 */
	function submissionToTemporaryFile($submissionFile, $userId) {
		// Get the file extension, then rename the file.
		$fileExtension = $this->parseFileExtension($submissionFile->getServerFileName());

		if (!$this->fileExists($this->filesDir, 'dir')) {
			// Try to create destination directory
			$this->mkdirtree($this->filesDir);
		}

		$newFileName = basename(tempnam($this->filesDir, $fileExtension));
		if (!$newFileName) return false;

		if (copy($submissionFile->getFilePath(), $this->filesDir . $newFileName)) {
			$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
			$temporaryFile = $temporaryFileDao->newDataObject();

			$temporaryFile->setUserId($userId);
			$temporaryFile->setServerFileName($newFileName);
			$temporaryFile->setFileType($submissionFile->getFileType());
			$temporaryFile->setFileSize($submissionFile->getFileSize());
			$temporaryFile->setOriginalFileName($submissionFile->getOriginalFileName());
			$temporaryFile->setDateUploaded(Core::getCurrentDate());

			$temporaryFileDao->insertObject($temporaryFile);

			return $temporaryFile;

		} else {
			return false;
		}
	}

	/**
	 * Perform periodic cleanup tasks. This is used to occasionally
	 * remove expired temporary files.
	 */
	function _performPeriodicCleanup() {
		if (time() % 100 == 0) {
			$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
			$expiredFiles = $temporaryFileDao->getExpiredFiles();
			foreach ($expiredFiles as $expiredFile) {
				$this->deleteFile($expiredFile->getId(), $expiredFile->getUserId());
			}
		}
	}
}

?>
