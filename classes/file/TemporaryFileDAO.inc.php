<?php

/**
 * @file classes/file/TemporaryFileDAO.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TemporaryFileDAO
 * @ingroup file
 * @see TemporaryFile
 *
 * @brief Operations for retrieving and modifying TemporaryFile objects.
 */

// $Id$


import('file.TemporaryFile');

class TemporaryFileDAO extends DAO {
	/**
	 * Retrieve a temporary file by ID.
	 * @param $fileId int
	 * @param $userId int
	 * @return TemporaryFile
	 */
	function &getTemporaryFile($fileId, $userId) {
		$result = &$this->retrieveLimit(
			'SELECT t.* FROM temporary_files t WHERE t.file_id = ? and t.user_id = ?',
			array($fileId, $userId),
			1
		);

		$returner = null;
		if (isset($result) && $result->RecordCount() != 0) {
			$returner = &$this->_returnTemporaryFileFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a TemporaryFile object from a row.
	 * @param $row array
	 * @return TemporaryFile
	 */
	function &_returnTemporaryFileFromRow(&$row) {
		$temporaryFile = &new TemporaryFile();
		$temporaryFile->setFileId($row['file_id']);
		$temporaryFile->setFileName($row['file_name']);
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
	function insertTemporaryFile(&$temporaryFile) {
		$this->update(
			sprintf('INSERT INTO temporary_files
				(user_id, file_name, file_type, file_size, original_file_name, date_uploaded)
				VALUES
				(?, ?, ?, ?, ?, %s)',
				$this->datetimeToDB($temporaryFile->getDateUploaded())),
			array(
				$temporaryFile->getUserId(),
				$temporaryFile->getFileName(),
				$temporaryFile->getFileType(),
				$temporaryFile->getFileSize(),
				$temporaryFile->getOriginalFileName()
			)
		);

		$temporaryFile->setFileId($this->getInsertTemporaryFileId());
		return $temporaryFile->getFileId();
	}

	/**
	 * Update an existing temporary file.
	 * @param $temporary TemporaryFile
	 */
	function updateTemporaryFile(&$temporaryFile) {
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
				$temporaryFile->getFileName(),
				$temporaryFile->getFileType(),
				$temporaryFile->getFileSize(),
				$temporaryFile->getUserId(),
				$temporaryFile->getOriginalFileName(),
				$temporaryFile->getFileId()
			)
		);

		return $temporaryFile->getFileId();

	}

	/**
	 * Delete a temporary file by ID.
	 * @param $fileId int
	 * @param $userId int
	 */
	function deleteTemporaryFileById($fileId, $userId) {
		return $this->update(
			'DELETE FROM temporary_files WHERE file_id = ? AND user_id = ?', array($fileId, $userId)
		);
	}

	/**
	 * Delete temporary files by user ID.
	 * @param $userId int
	 */
	function deleteTemporaryFilesByUserId($userId) {
		return $this->update(
			'DELETE FROM temporary_files WHERE user_id = ?', $userId
		);
	}

	function &getExpiredFiles() {
		// Files older than one day can be cleaned up.
		$expiryThresholdTimestamp = time() - (60 * 60 * 24);

		$temporaryFiles = array();

		$result = &$this->retrieve(
			'SELECT * FROM temporary_files WHERE date_uploaded < ' . $this->datetimeToDB($expiryThresholdTimestamp)
		);

		while (!$result->EOF) {
			$temporaryFiles[] = &$this->_returnTemporaryFileFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $temporaryFiles;
	}

	/**
	 * Get the ID of the last inserted temporary file.
	 * @return int
	 */
	function getInsertTemporaryFileId() {
		return $this->getInsertId('temporary_files', 'file_id');
	}
}

?>
