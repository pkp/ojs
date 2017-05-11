<?php

/**
 * @file classes/file/TemporaryFileDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TemporaryFileDAO
 * @ingroup file
 * @see TemporaryFile
 *
 * @brief Operations for retrieving and modifying TemporaryFile objects.
 */


import('lib.pkp.classes.file.TemporaryFile');

class TemporaryFileDAO extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Retrieve a temporary file by ID.
	 * @param $fileId int
	 * @param $userId int
	 * @return TemporaryFile
	 */
	function &getTemporaryFile($fileId, $userId) {
		$result = $this->retrieveLimit(
			'SELECT t.* FROM temporary_files t WHERE t.file_id = ? and t.user_id = ?',
			array((int) $fileId, (int) $userId),
			1
		);

		$returner = null;
		if (isset($result) && $result->RecordCount() != 0) {
			$returner =& $this->_returnTemporaryFileFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Instantiate and return a new data object.
	 * @return TemporaryFile
	 */
	function newDataObject() {
		return new TemporaryFile();
	}

	/**
	 * Internal function to return a TemporaryFile object from a row.
	 * @param $row array
	 * @return TemporaryFile
	 */
	function _returnTemporaryFileFromRow($row) {
		$temporaryFile = $this->newDataObject();
		$temporaryFile->setId($row['file_id']);
		$temporaryFile->setServerFileName($row['file_name']);
		$temporaryFile->setFileType($row['file_type']);
		$temporaryFile->setFileSize($row['file_size']);
		$temporaryFile->setUserId($row['user_id']);
		$temporaryFile->setOriginalFileName($row['original_file_name']);
		$temporaryFile->setDateUploaded($this->datetimeFromDB($row['date_uploaded']));

		HookRegistry::call('TemporaryFileDAO::_returnTemporaryFileFromRow', array(&$temporaryFile, &$row));

		return $temporaryFile;
	}

	/**
	 * Insert a new TemporaryFile.
	 * @param $temporaryFile TemporaryFile
	 * @return int
	 */
	function insertObject($temporaryFile) {
		$this->update(
			sprintf('INSERT INTO temporary_files
				(user_id, file_name, file_type, file_size, original_file_name, date_uploaded)
				VALUES
				(?, ?, ?, ?, ?, %s)',
				$this->datetimeToDB($temporaryFile->getDateUploaded())),
			array(
				(int) $temporaryFile->getUserId(),
				$temporaryFile->getServerFileName(),
				$temporaryFile->getFileType(),
				(int) $temporaryFile->getFileSize(),
				$temporaryFile->getOriginalFileName()
			)
		);

		$temporaryFile->setId($this->getInsertId());
		return $temporaryFile->getId();
	}

	/**
	 * Update an existing temporary file.
	 * @param $temporary TemporaryFile
	 */
	function updateObject($temporaryFile) {
		$this->update(
			sprintf('UPDATE temporary_files
				SET
					file_name = ?,
					file_type = ?,
					file_size = ?,
					user_id = ?,
					original_file_name = ?,
					date_uploaded = %s
				WHERE file_id = ?',
				$this->datetimeToDB($temporaryFile->getDateUploaded())),
			array(
				$temporaryFile->getServerFileName(),
				$temporaryFile->getFileType(),
				(int) $temporaryFile->getFileSize(),
				(int) $temporaryFile->getUserId(),
				$temporaryFile->getOriginalFileName(),
				(int) $temporaryFile->getId()
			)
		);

		return $temporaryFile->getId();
	}

	/**
	 * Delete a temporary file by ID.
	 * @param $fileId int
	 * @param $userId int
	 */
	function deleteTemporaryFileById($fileId, $userId) {
		return $this->update(
			'DELETE FROM temporary_files WHERE file_id = ? AND user_id = ?',
			array((int) $fileId, (int) $userId)
		);
	}

	/**
	 * Delete temporary files by user ID.
	 * @param $userId int
	 */
	function deleteByUserId($userId) {
		return $this->update(
			'DELETE FROM temporary_files WHERE user_id = ?',
			(int) $userId
		);
	}

	function &getExpiredFiles() {
		// Files older than one day can be cleaned up.
		$expiryThresholdTimestamp = time() - (60 * 60 * 24);

		$temporaryFiles = array();

		$result = $this->retrieve(
			'SELECT * FROM temporary_files WHERE date_uploaded < ' . $this->datetimeToDB($expiryThresholdTimestamp)
		);

		while (!$result->EOF) {
			$temporaryFiles[] =& $this->_returnTemporaryFileFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		return $temporaryFiles;
	}

	/**
	 * Get the ID of the last inserted temporary file.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('temporary_files', 'file_id');
	}
}

?>
