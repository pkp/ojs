<?php

/**
 * @file classes/issue/IssueFileDAO.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueFileDAO
 *
 * @ingroup issue
 *
 * @see IssueFile
 *
 * @brief Operations for retrieving and modifying IssueFile objects.
 */

namespace APP\issue;

use Illuminate\Support\Facades\DB;
use PKP\db\DAO;
use PKP\plugins\Hook;

class IssueFileDAO extends DAO
{
    /** @var array MIME types that can be displayed inline in a browser */
    public ?array $_inlineableTypes = null;

    /**
     * Get inlineable file types.
     */
    public function getInlineableTypes(): ?array
    {
        return $this->_inlineableTypes;
    }

    /**
     * Set inlineable file types.
     */
    public function setInlineableTypes(array $inlineableTypes): void
    {
        $this->_inlineableTypes = $inlineableTypes;
    }

    /**
     * Retrieve an issue file by ID.
     */
    public function getById(int $fileId, ?int $issueId = null): ?IssueFile
    {
        $params = [(int) $fileId];
        if ($issueId) {
            $params[] = (int) $issueId;
        }
        $result = $this->retrieve(
            'SELECT f.*
			FROM	issue_files f
			WHERE	f.file_id = ?
				' . ($issueId ? ' AND f.issue_id = ?' : ''),
            $params
        );
        $row = $result->current();
        return $row ? $this->_fromRow((array) $row) : null;
    }

    /**
     * Construct a new IssueFile data object.
     */
    public function newDataObject(): IssueFile
    {
        return new IssueFile();
    }

    /**
     * Internal function to return an IssueFile object from a row.
     *
     * @hook IssueFileDAO::_returnIssueFileFromRow [[&$issueFile, &$row]]
     */
    public function _fromRow(array $row): IssueFile
    {
        $issueFile = $this->newDataObject();
        $issueFile->setId($row['file_id']);
        $issueFile->setIssueId($row['issue_id']);
        $issueFile->setServerFileName($row['file_name']);
        $issueFile->setFileType($row['file_type']);
        $issueFile->setFileSize($row['file_size']);
        $issueFile->setContentType($row['content_type']);
        $issueFile->setOriginalFileName($row['original_file_name']);
        $issueFile->setDateUploaded($this->datetimeFromDB($row['date_uploaded']));
        $issueFile->setDateModified($this->datetimeFromDB($row['date_modified']));
        Hook::call('IssueFileDAO::_returnIssueFileFromRow', [&$issueFile, &$row]);
        return $issueFile;
    }

    /**
     * Insert a new IssueFile.
     */
    public function insertObject(IssueFile $issueFile): int
    {
        $this->update(
            sprintf(
                'INSERT INTO issue_files
					(issue_id,
					file_name,
					file_type,
					file_size,
					content_type,
					original_file_name,
					date_uploaded,
					date_modified)
				VALUES
					(?, ?, ?, ?, ?, ?, %s, %s)',
                $this->datetimeToDB($issueFile->getDateUploaded()),
                $this->datetimeToDB($issueFile->getDateModified())
            ),
            [
                (int) $issueFile->getIssueId(),
                $issueFile->getServerFileName(),
                $issueFile->getFileType(),
                $issueFile->getFileSize(),
                $issueFile->getContentType(),
                $issueFile->getOriginalFileName()
            ]
        );

        $issueFile->setId($this->getInsertId());
        return $issueFile->getId();
    }

    /**
     * Update an existing issue file.
     */
    public function updateObject(IssueFile $issueFile)
    {
        $this->update(
            sprintf(
                'UPDATE issue_files
				SET
					issue_id = ?,
					file_name = ?,
					file_type = ?,
					file_size = ?,
					content_type = ?,
					original_file_name = ?,
					date_uploaded = %s,
					date_modified = %s
				WHERE file_id = ?',
                $this->datetimeToDB($issueFile->getDateUploaded()),
                $this->datetimeToDB($issueFile->getDateModified())
            ),
            [
                (int) $issueFile->getIssueId(),
                $issueFile->getServerFileName(),
                $issueFile->getFileType(),
                $issueFile->getFileSize(),
                $issueFile->getContentType(),
                $issueFile->getOriginalFileName(),
                (int) $issueFile->getId()
            ]
        );

        return $issueFile->getId();
    }

    /**
     * Delete an issue file.
     */
    public function deleteObject(IssueFile $issueFile): int
    {
        return $this->deleteById($issueFile->getId());
    }

    /**
     * Delete an issue file by ID.
     */
    public function deleteById(int $fileId): int
    {
        return DB::table('issue_files')
            ->where('file_id', '=', $fileId)
            ->delete();
    }

    /**
     * Delete all issue files for an issue.
     */
    public function deleteByIssueId(int $issueId): int
    {
        return DB::table('issue_files')
            ->where('issue_id', '=', $issueId)
            ->delete();
    }
}
