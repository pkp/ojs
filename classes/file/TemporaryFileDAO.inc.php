<?php

/**
 * TemporaryFileDAO.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package file
 *
 * Class for TemporaryFile DAO.
 * Operations for retrieving and modifying TemporaryFile objects.
 *
 * $Id$
 */

import('file.TemporaryFile');

class TemporaryFileDAO extends DAO {


	/**
	 * Constructor.
	 */
	function TemporaryFileDAO() {
		parent::DAO();
	}
	
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
			
		if (!isset($result) || $result->RecordCount() == 0) return null;
		return $this->_returnTemporaryFileFromRow($result->GetRowAssoc(false));
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
		$temporaryFile->setDateUploaded($row['date_uploaded']);
		return $temporaryFile;
	}

	/**
	 * Insert a new TemporaryFile.
	 * @param $temporaryFile TemporaryFile
	 * @return int
	 */	
	function insertTemporaryFile(&$temporaryFile) {
		$this->update(
			'INSERT INTO temporary_files
				(file_id, user_id, file_name, file_type, file_size, original_file_name, date_uploaded)
				VALUES
				(?, ?, ?, ?, ?, ?, ?)',
			array(
				$temporaryFile->getFileId(),
				$temporaryFile->getUserId(),
				$temporaryFile->getFileName(),
				$temporaryFile->getFileType(),
				$temporaryFile->getFileSize(),
				$temporaryFile->getOriginalFileName(),
				$temporaryFile->getDateUploaded()
			)
		);
		
		$temporaryFile->setFileId($this->getInsertTemporaryFileId());
		return $this->getInsertTemporaryFileId();
	}
	
	/**
	 * Update an existing temporary file.
	 * @param $temporary TemporaryFile
	 */
	function updateTemporaryFile(&$temporaryFile) {
		$this->update(
			'UPDATE temporary_files
				SET
					file_name = ?,
					file_type = ?,
					file_size = ?,
					user_id = ?,
					original_file_name = ?,
					date_uploaded = ?
				WHERE file_id = ?',
			array(
				$temporaryFile->getFileName(),
				$temporaryFile->getFileType(),
				$temporaryFile->getFileSize(),
				$temporaryFile->getUserId(),
				$temporaryFile->getOriginalFileName(),
				$temporaryFile->getDateUploaded(),
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
	 * Delete a temporary file by user ID.
	 * @param $userId int
	 */
	function deleteTemporaryFileByUserId($userId) {
		return $this->update(
			'DELETE FROM temporary_files WHERE user_id = ?', $userId
		);
	}
	
	function &getExpiredFiles() {
		// Files older than one day can be cleaned up.
		$expiryThresholdTimestamp = time() - (60 * 60 * 24);
		$expiryThresholdDate = Core::getCurrentDate(date('Y-m-d H:i:s', $expiryThresholdTimestamp));

		$temporaryFiles = array();

		$result = &$this->retrieve(
			'SELECT * FROM temporary_files WHERE date_uploaded < ?',
			array($expiryThresholdDate)
		);

		while (!$result->EOF) {
			$temporaryFiles[] = $this->_returnTemporaryFileFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();

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
