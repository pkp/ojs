<?php

/**
 * @file plugins/generic/dataverse/classes/DataverseFileDAO.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataverseFileDAO
 * @ingroup plugins_generic_dataverse
 *
 * @brief Operations for retrieving and modifying DataverseFile objects.
 */

import('lib.pkp.classes.db.DAO');

class DataverseFileDAO extends DAO {
	
	/** @var $_parentPluginName string Name of parent plugin */
	var $_parentPluginName;

	/**
	 * Constructor.
	 */
	function DataverseFileDAO($parentPluginName) {
		$this->_parentPluginName = $parentPluginName;
		parent::DAO();
	}
	
	/**
	 * Insert a new Dataverse file.
	 * @param $dvFile DataverseFile
	 * @return int 
	 */
	function insertDataverseFile(&$dvFile) {
		$this->update(
			'INSERT INTO dataverse_files
				(supp_id, submission_id, study_id, content_source_uri)
				VALUES
				(?, ?, ?, ?)',
			array(
				(int)$dvFile->getSuppFileId(),
				(int)$dvFile->getSubmissionId(),
				// Parent study and Dataverse Uri may not exist when record is inserted					 
				$dvFile->getStudyId() ? (int)$dvFile->getStudyId() : 0,
				$dvFile->getContentSourceUri() ? $dvFile->getContentSourceUri() : ''
			)
		);
		$dvFile->setId($this->getInsertDataverseFileId());
		return $dvFile->getId();
	}
	
	/**
	 * Update Dataverse file.
	 * @param $dvFile
	 * @return boolean 
	 */
	function updateDataverseFile(&$dvFile) {
		$returner = $this->update(
						'UPDATE dataverse_files
							SET
							supp_id = ?,
							study_id = ?,
							submission_id = ?,
							content_source_uri = ?
							WHERE dvfile_id = ?',
						array(
								(int)$dvFile->getSuppFileId(),
								(int)$dvFile->getStudyId(),
								(int)$dvFile->getSubmissionId(),
								$dvFile->getContentSourceUri(),
								(int)$dvFile->getId()
						)
		);
		return $returner;
	}	 
	
	/**
	 * Get ID of the last inserted Dataverse file.
	 * @return int
	 */
	function getInsertDataverseFileId() {
		return $this->getInsertId('dataverse_files', 'dvfile_id');
	}	 
	
	
	/**
	 * Delete a Dataverse file.
	 * @param $dvFile DataverseFile
	 * @return boolean
	 */
	function deleteDataverseFile(&$dvFile) {
		return $this->deleteDataverseFileById($dvFile->getId());
	}

	/**
	 * Delete a Dataverse file by ID.
	 * @param $dvFileId int
	 * @param $submissionId int optional
	 * @return boolean
	 */
	function deleteDataverseFileById($dvFileId, $submissionId = null) {
		if (isset($submissionId)) {
			$returner = $this->update('DELETE FROM dataverse_files WHERE dvfile_id = ? AND submission_id = ?', array((int)$dvFileId, (int)$submissionId));
			return $returner;
		}
		return $this->update('DELETE FROM dataverse_files WHERE dvfile_id = ?', (int)$dvFileId);
	}
	
	/**
	 * Delete Dataverse files associated with a study.
	 * @param $studyId int
	 * @return boolean
	 */
	function deleteDataverseFilesByStudyId($studyId) {
		$dvFiles =& $this->getDataverseFilesByStudyId($studyId);
		foreach ($dvFiles as $dvFile) {
			$this->deleteDataverseFile($dvFile);
		}
	}
	
	
	/**
	 * Retrieve Dataverse file by supp id & optional submission.
	 * @param int $suppFileId
	 * @param int $submissionId
	 * @return DataverseFile
	 */
	function &getDataverseFileBySuppFileId($suppFileId, $submissionId = null) {
		$params = array((int)$suppFileId);
		if ($submissionId) $params[] = (int)$submissionId;
		$result =& $this->retrieve(
			'SELECT * FROM dataverse_files WHERE supp_id = ?' . ($submissionId?' AND submission_id = ?':''),
			$params
		);
		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnDataverseFileFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		unset($result);
		return $returner;		 
	}
	
	/**
	 * Retrieve Dataverse files for a submission.
	 * @param $submissionId int
	 * @return array
	 */
	function &getDataverseFilesBySubmissionId($submissionId) {
		$dvFiles = array();
		$result =& $this->retrieve(
			'SELECT * FROM dataverse_files WHERE submission_id = ?',
			(int) $submissionId
		);
		while (!$result->EOF) {
			$dvFiles[] =& $this->_returnDataverseFileFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
		unset($result);
		return $dvFiles;
	}	 
	
	/**
	 * Retrieve Dataverse files for a study.
	 * @param $submissionId int
	 * @return array
	 */
	function &getDataverseFilesByStudyId($studyId) {
		$dvFiles = array();
		$result =& $this->retrieve(
			'SELECT * FROM dataverse_files WHERE study_id = ?',
			(int) $studyId
		);
		while (!$result->EOF) {
			$dvFiles[] =& $this->_returnDataverseFileFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
		unset($result);
		return $dvFiles;
	}
	
	/**
	 * Internal function to return DataverseFile object from a row.
	 * @param $row array
	 * @return DataverseFile
	 */
	function &_returnDataverseFileFromRow(&$row) {
		$dataversePlugin =& PluginRegistry::getPlugin('generic', $this->_parentPluginName);
		$dataversePlugin->import('classes.DataverseFile');
		$dvFile = new DataverseFile();
		$dvFile->setId($row['dvfile_id']);		
		$dvFile->setSuppFileId($row['supp_id']);				
		$dvFile->setStudyId($row['study_id']);
		$dvFile->setSubmissionId($row['submission_id']);
		$dvFile->setContentSourceUri($row['content_source_uri']);
		return $dvFile;
	}		 
	
	/**
	 * Update the Dataverse deposit status of a supplementary file.
	 * Files with deposit status = true will be deposited/updated in Dataverse.
	 * @param $suppFileId int
	 * @param $depositStatus boolean
	 */
	function setDepositStatus($suppFileId, $depositStatus) {
		$idFields = array(
			'supp_id', 'locale', 'setting_name'
		);
		$updateArray = array(
			'supp_id' => (int)$suppFileId,
			'locale' => '',
			'setting_name' => 'dataverseDeposit',
			'setting_type' => 'bool',
			'setting_value' => (bool)$depositStatus
		);
		$this->replace('article_supp_file_settings', $updateArray, $idFields);
	}	 

	/** 
	 * Set content source URI of Dataverse file.
	 * @param $suppFileId int
	 * @param $contentSourceUri string
	 */
	function setContentSourceUri($suppFileId, $contentSourceUri) {
		$idFields = array(
			'supp_id', 'locale', 'setting_name'
		);
		$updateArray = array(
			'supp_id' => (int)$suppFileId,
			'locale' => '',
			'setting_name' => 'dataverseContentSourceUri',
			'setting_type' => 'string',
			'setting_value' => $contentSourceUri
		);
		$this->replace('article_supp_file_settings', $updateArray, $idFields);
		
	}
}
?>
