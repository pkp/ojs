<?php

/**
 * @file classes/issue/IssueFileDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueFileDAO
 * @ingroup issue
 * @see IssueFile
 *
 * @brief Operations for retrieving and modifying IssueFile objects.
 */

import('lib.pkp.classes.file.PKPFileDAO');
import('classes.issue.IssueFile');


class IssueFileDAO extends PKPFileDAO {

	 /* @var array MIME types that can be displayed inline in a browser */
	var $_inlineableTypes = null;

	/**
	 * Constructor.
	 */
	function IssueFileDao () {
		parent::DAO();
	}

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
	function &getIssueFile($fileId, $issueId = null) {
		if ($fileId === null) {
			$returner = null;
			return $returner;
		}

		if ($issueId != null) {
			$result =& $this->retrieve(
				'SELECT f.*
				FROM issue_files f
				WHERE f.file_id = ?
				AND f.issue_id = ?',
				array((int) $fileId, (int) $issueId)
			);
		} else {
			$result =& $this->retrieve(
				'SELECT f.*
				FROM issue_files f
				WHERE f.file_id = ?',
				(int) $fileId
			);
		}

		$returner = null;
		if (isset($result) && $result->RecordCount() != 0) {
			$returner =& $this->_returnIssueFileFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all issue files for an issue.
	 * @param $issueId int
	 * @return array IssueFiles
	 */
	function &getIssueFilesByIssue($issueId) {
		$issueFiles = array();

		$result =& $this->retrieve(
			'SELECT * FROM issue_files WHERE issue_id = ?',
			(int) $issueId
		);

		while (!$result->EOF) {
			$issueFiles[] =& $this->_returnIssueFileFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $issueFiles;
	}

	/**
	 * Internal function to return an IssueFile object from a row.
	 * @param $row array
	 * @return IssueFile
	 */
	function &_returnIssueFileFromRow(&$row) {
		$issueFile = new IssueFile();
		$issueFile->setId($row['file_id']);
		$issueFile->setIssueId($row['issue_id']);
		$issueFile->setFileName($row['file_name']);
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
	function insertIssueFile(&$issueFile) {
		$params = array(
			(int) $issueFile->getIssueId(),
			$issueFile->getFileName(),
			$issueFile->getFileType(),
			$issueFile->getFileSize(),
			$issueFile->getContentType(),
			$issueFile->getOriginalFileName()
		);

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
			$params
		);

		$issueFile->setId($this->getInsertIssueFileId());
		return $issueFile->getId();
	}

	/**
	 * Update an existing issue file.
	 * @param $issue IssueFile
	 */
	function updateIssueFile(&$issueFile) {
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
			array(
				(int) $issueFile->getIssueId(),
				$issueFile->getFileName(),
				$issueFile->getFileType(),
				$issueFile->getFileSize(),
				$issueFile->getContentType(),
				$issueFile->getOriginalFileName(),
				$issueFile->getId()
			)
		);

		return $issueFile->getId();

	}

	/**
	 * Delete an issue file.
	 * @param $issue IssueFile
	 */
	function deleteIssueFile(&$issueFile) {
		return $this->deleteIssueFileById($issueFile->getId());
	}

	/**
	 * Delete an issue file by ID.
	 * @param $issueId int
	 * @param $revision int
	 */
	function deleteIssueFileById($fileId) {
		return $this->update(
			'DELETE FROM issue_files WHERE file_id = ?', (int) $fileId
		);
	}

	/**
	 * Delete all issue files for an issue.
	 * @param $issueId int
	 */
	function deleteIssueFiles($issueId) {
		return $this->update(
			'DELETE FROM issue_files WHERE issue_id = ?', (int) $issueId
		);
	}

	/**
	 * Get the ID of the last inserted issue file.
	 * @return int
	 */
	function getInsertIssueFileId() {
		return $this->getInsertId('issue_files', 'file_id');
	}
}

?>
