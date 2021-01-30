<?php

/**
 * @file classes/issue/IssueFileDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueFileDAO
 * @ingroup issue
 * @see IssueFile
 *
 * @brief Operations for retrieving and modifying IssueFile objects.
 */

import('lib.pkp.classes.db.DAO');
import('classes.issue.IssueFile');

class IssueFileDAO extends DAO {

	 /** @var array MIME types that can be displayed inline in a browser */
	var $_inlineableTypes = null;


	/**
	 * Get inlineable file types.
	 * @return array
	 */
	function getInlineableTypes() {
		return $this->_inlineableTypes;
	}

	/**
	 * Set inlineable file types.
	 * @param $inlineableTypes array
	 */
	function setInlineableTypes($inlineableTypes) {
		$this->_inlineableTypes = $inlineableTypes;
	}

	/**
	 * Retrieve an issue file by ID.
	 * @param $fileId int
	 * @param $issueId int optional
	 * @return IssueFile
	 */
	function getById($fileId, $issueId = null) {
		$params = [(int) $fileId];
		if ($issueId) $params[] = (int) $issueId;
		$result = $this->retrieve(
			'SELECT f.*
			FROM	issue_files f
			WHERE	f.file_id = ?
				' . ($issueId?' AND f.issue_id = ?':''),
				$params
		);
		$row = $result->current();
		return $row ? $this->_fromRow((array) $row) : null;
	}

	/**
	 * Construct a new IssueFile data object.
	 * @return IssueFile
	 */
	function newDataObject() {
		return new IssueFile();
	}

	/**
	 * Internal function to return an IssueFile object from a row.
	 * @param $row array
	 * @return IssueFile
	 */
	function _fromRow($row) {
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
		HookRegistry::call('IssueFileDAO::_returnIssueFileFromRow', array(&$issueFile, &$row));
		return $issueFile;
	}

	/**
	 * Insert a new IssueFile.
	 * @param $issueFile IssueFile
	 * @return int
	 */
	function insertObject($issueFile) {
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
	 * @param $issue IssueFile
	 */
	function updateObject($issueFile) {
		$this->update(
			sprintf('UPDATE issue_files
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
	 * @param $issue IssueFile
	 */
	function deleteObject($issueFile) {
		$this->deleteById($issueFile->getId());
	}

	/**
	 * Delete an issue file by ID.
	 * @param $issueId int
	 */
	function deleteById($fileId) {
		$this->update('DELETE FROM issue_files WHERE file_id = ?', [(int) $fileId]);
	}

	/**
	 * Delete all issue files for an issue.
	 * @param $issueId int
	 */
	function deleteByIssueId($issueId) {
		$this->update('DELETE FROM issue_files WHERE issue_id = ?', [(int) $issueId]);
	}

	/**
	 * Get the ID of the last inserted issue file.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('issue_files', 'file_id');
	}
}


