<?php

/**
 * @file classes/file/IssueFileManager.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueFileManager
 * @ingroup file
 *
 * @brief Class defining operations for issue file management.
 *
 * Issue directory structure:
 * [issue id]/public
 */

import('lib.pkp.classes.file.FileManager');
import('classes.issue.IssueFile');

class IssueFileManager extends FileManager {

	/** @var string the path to location of the files */
	var $_filesDir = null;

	/** @var int the associated issue ID */
	var $_issueId = null;

	/**
	 * Constructor.
	 * Create a manager for handling issue file uploads.
	 * @param $issueId int
	 */
	function IssueFileManager($issueId) {
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issue =& $issueDao->getIssueById($issueId);
		assert($issue);

		$this->setIssueId($issueId);
		$this->setFilesDir(Config::getVar('files', 'files_dir') . '/journals/' . $issue->getJournalId() . '/issues/' . $issueId . '/');

		parent::FileManager();
	}

	/**
	 * Get the issue files directory.
	 * @return string
	 */
	function getFilesDir() {
		return $this->_filesDir;
	}

	/**
	 * Set the issue files directory.
	 * @param $filesDir string
	 */
	function setFilesDir($filesDir) {
		$this->_filesDir = $filesDir;
	}

	/**
	 * Get the issue ID.
	 * @return int
	 */
	function getIssueId() {
		return $this->_issueId;
	}

	/**
	 * Set the issue ID.
	 * @param $issueId int
	 */
	function setIssueId($issueId) {
		$this->_issueId = (int) $issueId;
	}

	/**
	 * Upload a public issue file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID
	 */
	function uploadPublicFile($fileName, $fileId = null) {
		return $this->_handleUpload($fileName, ISSUE_FILE_PUBLIC, $fileId);
	}

	/**
	 * Delete an issue file by ID.
	 * @param $fileId int
	 * @return boolean if successful
	 */
	function deleteFile($fileId) {
		$issueFileDao =& DAORegistry::getDAO('IssueFileDAO');
		$issueFile =& $issueFileDao->getIssueFile($fileId);

		if (parent::deleteFile($this->getFilesDir() . $this->contentTypeToPath($issueFile->getContentType()) . '/' . $issueFile->getFileName())) {
			$issueFileDao->deleteIssueFileById($fileId);
			return true;
		}

		return false;
	}

	/**
	 * Delete the entire tree of files belonging to an issue.
	 */
	function deleteIssueTree() {
		parent::rmtree($this->getFilesDir());
	}

	/**
	 * Download a file.
	 * @param $fileId int the file id of the file to download
	 * @param $inline print file as inline instead of attachment, optional
	 * @return boolean
	 */
	function downloadFile($fileId, $inline = false) {
		$issueFileDao =& DAORegistry::getDAO('IssueFileDAO');
		$issueFile =& $issueFileDao->getIssueFile($fileId);

		if ($issueFile) {
			$fileType = $issueFile->getFileType();
			$filePath = $this->getFilesDir() . $this->contentTypeToPath($issueFile->getContentType()) . '/' . $issueFile->getFileName();

			return parent::downloadFile($filePath, $fileType, $inline);

		} else {
			return false;
		}
	}

	/**
	 * Return directory path based on issue content type (used for naming files).
	 * @param $contentType int
	 * @return string
	 */
	function contentTypeToPath($contentType) {
		switch ($contentType) {
			case ISSUE_FILE_PUBLIC: return 'public';
		}
	}

	/**
	 * Return abbreviation based on issue content type (used for naming files).
	 * @param $contentType int
	 * @return string
	 */
	function contentTypeToAbbrev($contentType) {
		switch ($contentType) {
			case ISSUE_FILE_PUBLIC: return 'PB';
		}
	}

	/**
	 * PRIVATE routine to upload the file and add it to the database.
	 * @param $fileName string index into the $_FILES array
	 * @param $contentType int Issue file content type
	 * @param $fileId int ID of an existing file to update
	 * @param $overwrite boolean overwrite previous version of the file
	 * @return int the file ID
	 */
	function _handleUpload($fileName, $contentType, $fileId = null, $overwrite = false) {
		if (HookRegistry::call('IssueFileManager::_handleUpload', array(&$fileName, &$contentType, &$fileId, &$overwrite, &$result))) return $result;

		$issueId = $this->getIssueId();
		$issueFileDao =& DAORegistry::getDAO('IssueFileDAO');

		$contentTypePath = $this->contentTypeToPath($contentType);
		$dir = $this->getFilesDir() . $contentTypePath . '/';

		$issueFile = new IssueFile();
		$issueFile->setIssueId($issueId);
		$issueFile->setDateUploaded(Core::getCurrentDate());
		$issueFile->setDateModified(Core::getCurrentDate());
		$issueFile->setFileName('');
		$issueFile->setFileType($this->getUploadedFileType($fileName));
		$issueFile->setFileSize($_FILES[$fileName]['size']);
		$issueFile->setOriginalFileName($this->truncateFileName($_FILES[$fileName]['name'], 127));
		$issueFile->setContentType($contentType);

		// If this is a new issue file, add it to the db and get it's new file id
		if (!$fileId) {
			if (!$issueFileDao->insertIssueFile($issueFile)) return false;
		} else {
			$issueFile->setId($fileId);
		}

		$extension = $this->parseFileExtension($this->getUploadedFileName($fileName));
		$newFileName = $issueFile->getIssueId().'-'.$issueFile->getId().'-'.$this->contentTypeToAbbrev($contentType).'.'.$extension;
		$issueFile->setFileName($newFileName);

		// Upload the actual file
		if (!$this->uploadFile($fileName, $dir.$newFileName)) {
			// Upload failed. If this is a new file, remove newly added db record.
			if (!$fileId) $issueFileDao->deleteIssueFileById($issueFile->getId());
			return false;
		}

		// Upload succeeded. Update issue file record with new filename.
		$issueFileDao->updateIssueFile($issueFile);

		return $issueFile->getId();
	}
}

?>
