<?php

/**
 * @file classes/file/IssueFileManager.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueFileManager
 *
 * @ingroup file
 *
 * @brief Class defining operations for issue file management.
 *
 * Issue directory structure:
 * [issue id]/public
 */

namespace APP\file;

use APP\facades\Repo;
use APP\issue\IssueFile;
use APP\issue\IssueFileDAO;
use PKP\config\Config;
use PKP\core\Core;
use PKP\db\DAORegistry;
use PKP\file\FileManager;
use PKP\file\TemporaryFile;
use PKP\plugins\Hook;

class IssueFileManager extends FileManager
{
    /** @var string the path to location of the files */
    public $_filesDir = null;

    /** @var int the associated issue ID */
    public $_issueId = null;

    /**
     * Constructor.
     * Create a manager for handling issue files.
     *
     * @param int $issueId
     */
    public function __construct($issueId)
    {
        $issue = Repo::issue()->get($issueId);
        assert(isset($issue));

        $this->setIssueId($issueId);
        $this->setFilesDir(Config::getVar('files', 'files_dir') . '/journals/' . $issue->getJournalId() . '/issues/' . $issueId . '/');

        parent::__construct();
    }

    /**
     * Get the issue files directory.
     *
     * @return string
     */
    public function getFilesDir()
    {
        return $this->_filesDir;
    }

    /**
     * Set the issue files directory.
     *
     * @param string $filesDir
     */
    public function setFilesDir($filesDir)
    {
        $this->_filesDir = $filesDir;
    }

    /**
     * Get the issue ID.
     *
     * @return int
     */
    public function getIssueId()
    {
        return $this->_issueId;
    }

    /**
     * Set the issue ID.
     *
     * @param int $issueId
     */
    public function setIssueId($issueId)
    {
        $this->_issueId = (int) $issueId;
    }

    /**
     * Delete an issue file by ID.
     *
     * @param int $fileId
     *
     * @return bool if successful
     */
    public function deleteById($fileId)
    {
        $issueFileDao = DAORegistry::getDAO('IssueFileDAO'); /** @var IssueFileDAO $issueFileDao */
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
    public function deleteIssueTree()
    {
        parent::rmtree($this->getFilesDir());
    }

    /**
     * Download a file.
     *
     * @param int $fileId the file id of the file to download
     * @param bool $inline print file as inline instead of attachment, optional
     *
     * @return bool
     */
    public function downloadById($fileId, $inline = false)
    {
        $issueFileDao = DAORegistry::getDAO('IssueFileDAO'); /** @var IssueFileDAO $issueFileDao */
        $issueFile = $issueFileDao->getById($fileId);

        if ($issueFile) {
            $fileType = $issueFile->getFileType();
            $filePath = $this->getFilesDir() . $this->contentTypeToPath($issueFile->getContentType()) . '/' . $issueFile->getServerFileName();

            return parent::downloadByPath($filePath, $fileType, $inline, $issueFile->getOriginalFileName());
        } else {
            return false;
        }
    }

    /**
     * Return directory path based on issue content type (used for naming files).
     *
     * @param int $contentType
     *
     * @return string
     */
    public function contentTypeToPath($contentType)
    {
        switch ($contentType) {
            case IssueFile::ISSUE_FILE_PUBLIC: return 'public';
        }
    }

    /**
     * Return abbreviation based on issue content type (used for naming files).
     *
     * @param int $contentType
     *
     * @return string
     */
    public function contentTypeToAbbrev($contentType)
    {
        switch ($contentType) {
            case IssueFile::ISSUE_FILE_PUBLIC: return 'PB';
        }
    }

    /**
     * Create an issue galley based on a temporary file.
     *
     * @param TemporaryFile $temporaryFile
     * @param int $contentType Issue file content type
     *
     * @return ?IssueFile|false the resulting issue file
     */
    public function fromTemporaryFile($temporaryFile, $contentType = IssueFile::ISSUE_FILE_PUBLIC)
    {
        $result = null;
        if (Hook::call('IssueFileManager::fromTemporaryFile', [&$temporaryFile, &$contentType, &$result])) {
            return $result;
        }

        $issueId = $this->getIssueId();
        $issueFileDao = DAORegistry::getDAO('IssueFileDAO'); /** @var IssueFileDAO $issueFileDao */

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

        if (!$issueFileDao->insertObject($issueFile)) {
            return false;
        }

        $extension = $this->parseFileExtension($issueFile->getOriginalFileName());
        $newFileName = $issueFile->getIssueId() . '-' . $issueFile->getId() . '-' . $this->contentTypeToAbbrev($contentType) . '.' . $extension;
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

if (!PKP_STRICT_MODE) {
    class_alias('\APP\file\IssueFileManager', '\IssueFileManager');
}
