<?php

/**
 * @file classes/file/IssueFileManager.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
	 * Create a manager for handling issue files.
	 * @param $issueId int
	 */
	function __construct($issueId) {
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getById($issueId);
		assert(isset($issue));

		$this->setIssueId($issueId);
		$this->setFilesDir(Config::getVar('files', 'files_dir') . '/journals/' . $issue->getJournalId() . '/issues/' . $issueId . '/');

		parent::__construct();
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
	 * Delete an issue file by ID.
	 * @param $fileId int
	 * @return boolean if successful
	 */
	function deleteById($fileId) {
		$issueFileDao = DAORegistry::getDAO('IssueFileDAO');
		$issueFile = $issueFileDao->getById($fileId);

		if (parent::deleteByPath($this->getFilesDir() . $this->contentTypeToPath($issueFile->getContentType()) . '/' . $issueFile->getServerFileName())) {
			$issueFileDao->deleteById($fileId);
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
	function downloadById($fileId, $inline = false) {
		$issueFileDao = DAORegistry::getDAO('IssueFileDAO');
		$issueFile = $issueFileDao->getById($fileId);

		if ($issueFile) {
			$fileType = $issueFile->getFileType();
			$filePath = $this->getFilesDir() . $this->contentTypeToPath($issueFile->getContentType()) . '/' . $issueFile->getServerFileName();

			return parent::downloadByPath($filePath, $fileType, $inline);

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
	 * Create an issue galley based on a temporary file.
	 * @param $temporaryFile TemporaryFile
	 * @param $contentType int Issue file content type
	 * @return IssueFile the resulting issue file
	 */
	function fromTemporaryFile($temporaryFile, $contentType = ISSUE_FILE_PUBLIC) {
		$result = null;
		if (HookRegistry::call('IssueFileManager::fromTemporaryFile', array(&$temporaryFile, &$contentType, &$result))) return $result;

		$issueId = $this->getIssueId();
		$issueFileDao = DAORegistry::getDAO('IssueFileDAO');

		$contentTypePath = $this->contentTypeToPath($contentType);
		$dir = $this->getFilesDir() . $contentTypePath . '/';

		$issueFile = $issueFileDao->newDataObject();
		$issueFile->setIssueId($issueId);
		$issueFile->setDateUploaded($temporaryFile->getDateUploaded());
		$issueFile->setDateModified(Core::getCurrentDate());
		$issueFile->setServerFileName(''); // Blank until we insert to generate a file ID
		$issueFile->setFileType($temporaryFile->getFileType());
		$issueFile->setFileSize($temporaryFile->getFileSize());
		$issueFile->setOriginalFileName($temporaryFile->getOriginalFileName());
		$issueFile->setContentType($contentType);

		if (!$issueFileDao->insertObject($issueFile)) return false;

		$extension = $this->parseFileExtension($issueFile->getOriginalFileName());
		$newFileName = $issueFile->getIssueId().'-'.$issueFile->getId().'-'.$this->contentTypeToAbbrev($contentType).'.'.$extension;
		$issueFile->setServerFileName($newFileName);

		// Copy the actual file
		if (!$this->copyFile($temporaryFile->getFilePath(), $dir . $newFileName)) {
			// Upload failed; remove the new DB record.
			$issueFileDao->deleteById($issueFile->getId());
			return false;
		}

		// Upload succeeded. Update issue file record with new filename.
		$issueFileDao->updateObject($issueFile);

		return $issueFile;
	}
}


