<?php

/**
 * @file plugins/generic/dataverse/DataverseStudyDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataverseStudyDAO
 * @ingroup plugins_generic_dataverse
 *
 * @brief Operations for retrieving and modifying DataverseStudy objects.
 */

import('lib.pkp.classes.db.DAO');

class DataverseStudyDAO extends DAO {
  
	/** @var $_parentPluginName string Name of parent plugin */
	var $_parentPluginName;

	/**
	 * Constructor
	 */
	function DataverseStudyDAO($parentPluginName) {
		$this->_parentPluginName = $parentPluginName;
		parent::DAO();
	}  
  
	/**
	 * Retrieve study by study ID.
	 * @param $studyId int
	 * @return DataverseStudy
	 */
	function &getStudy($studyId) {
		$result =& $this->retrieve(
      'SELECT * FROM dataverse_studies WHERE study_id = ?', (int)$studyId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnStudyFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}  

	/**
	 * Retrieve study matching a particular submission ID.
	 * @param $submissionId int
	 * @return DataverseStudy
	 */
	function &getStudyBySubmissionId($submissionId) {
		$result =& $this->retrieve(
      'SELECT * FROM dataverse_studies WHERE submission_id = ?', (int)$submissionId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnStudyFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}  
  
	/**
	 * Insert a new study
	 * @param $study DataverseStudy
	 * @return int 
	 */
	function insertStudy(&$study) {
		$ret = $this->update(
			'INSERT INTO dataverse_studies
				(submission_id, edit_uri, edit_media_uri, statement_uri, persistent_uri, data_citation)
				VALUES
				(?, ?, ?, ?, ?, ?)',
			array(
        (int)$study->getSubmissionId(),
				$study->getEditUri(),
				$study->getEditMediaUri(),
        $study->getStatementUri(),
        $study->getPersistentUri(),
        $study->getDataCitation()
			)
		);
		$study->setId($this->getInsertStudyId());
		return $study->getId();
	}
  
	/**
	 * Update an existing study
	 * @param $study DataverseStudy
	 */
	function updateStudy(&$study) {
		$returner = $this->update(
			'UPDATE dataverse_studies
				SET
					edit_uri = ?,
					edit_media_uri = ?,
					statement_uri = ?,
					persistent_uri = ?,
					data_citation = ?
				WHERE study_id = ?',
			array(
        $study->getEditUri(),
        $study->getEditMediaUri(),
        $study->getStatementUri(),
        $study->getPersistentUri(),
        $study->getDataCitation(),
        (int)$study->getId()
			)
		);
		return $returner;
	}  
  
	/**
	 * Get ID of last inserted study
	 * @return int
	 */
	function getInsertStudyId() {
		return $this->getInsertId('dataverse_studies', 'study_id');
	}  
  
	/**
	 * Delete Dataverse study.
	 * @param $study DataverseStudy
	 * @return boolean
	 */
	function deleteStudy($study) {
		return $this->deleteStudyById($study->getId());
	}

	/**
	 * Delete Dataverse study by ID.
	 * @param $studyId int
	 * @return boolean
	 */
	function deleteStudyById($studyId) {
		$this->update(
      'DELETE FROM dataverse_studies WHERE study_id = ?', (int)$studyId
		);
	}
  
  
	/**
	 * Internal function to return DataverseStudy object from a row.
	 * @param $row array
	 * @return DataverseStudy
	 */
	function &_returnStudyFromRow(&$row) {
		$dataversePlugin =& PluginRegistry::getPlugin('generic', $this->_parentPluginName);
		$dataversePlugin->import('classes.DataverseStudy');

		$study = new DataverseStudy();
		$study->setId($row['study_id']);
		$study->setSubmissionId($row['submission_id']);
		$study->setEditUri($row['edit_uri']);
		$study->setEditMediaUri($row['edit_media_uri']);    
		$study->setStatementUri($row['statement_uri']);
    $study->setPersistentUri($row['persistent_uri']);
    $study->setDataCitation($row['data_citation']);
    
		return $study;
	}  
}
?>
