<?php

/**
 * ThesisDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package thesis
 *
 * Class for Thesis DAO.
 * Operations for retrieving and modifying Thesis objects.
 *
 * $Id$
 */

import('db.DAO');

class ThesisDAO extends DAO {

	/**
	 * Constructor.
	 */
	function ThesisDAO() {
		parent::DAO();
	}

	/**
	 * Retrieve an thesis by thesis ID.
	 * @param $thesisId int
	 * @return Thesis
	 */
	function &getThesis($thesisId) {
		$result = &$this->retrieve(
			'SELECT * FROM theses WHERE thesis_id = ?', $thesisId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnThesisFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve thesis journal ID by thesis ID.
	 * @param $thesisId int
	 * @return int
	 */
	function getThesisJournalId($thesisId) {
		$result = &$this->retrieve(
			'SELECT journal_id FROM theses WHERE thesis_id = ?', $thesisId
		);
		
		return isset($result->fields[0]) ? $result->fields[0] : 0;	
	}

	/**
	 * Check whether thesis with thesis ID has active status.
	 * @param $thesisId int
	 * @return boolean
	 */
	function isThesisActive($thesisId) {
		$thesisPlugin = &PluginRegistry::getPlugin('generic', 'ThesisPlugin');
		$thesisPlugin->import('Thesis');

		$result = &$this->retrieve(
			'SELECT thesis_id FROM theses WHERE status = ? AND thesis_id = ?', array(THESIS_STATUS_ACTIVE, $thesisId)
		);
		
		return isset($result->fields[0]) ? true : false;	
	}

	/**
	 * Internal function to return a Thesis object from a row.
	 * @param $row array
	 * @return Thesis
	 */
	function &_returnThesisFromRow(&$row) {
		$thesisPlugin = &PluginRegistry::getPlugin('generic', 'ThesisPlugin');
		$thesisPlugin->import('Thesis');

		$thesis = &new Thesis();
		$thesis->setThesisId($row['thesis_id']);
		$thesis->setJournalId($row['journal_id']);
		$thesis->setStatus($row['status']);
		$thesis->setDegree($row['degree']);
		$thesis->setDepartment($row['department']);
		$thesis->setUniversity($row['university']);
		$thesis->setDateApproved($this->dateFromDB($row['date_approved']));
		$thesis->setTitle($row['title']);
		$thesis->setAbstract($row['abstract']);
		$thesis->setUrl($row['url']);
		$thesis->setStudentFirstName($row['student_first_name']);
		$thesis->setStudentMiddleName($row['student_middle_name']);
		$thesis->setStudentLastName($row['student_last_name']);
		$thesis->setStudentEmail($row['student_email']);
		$thesis->setSupervisorFirstName($row['supervisor_first_name']);
		$thesis->setSupervisorMiddleName($row['supervisor_middle_name']);
		$thesis->setSupervisorLastName($row['supervisor_last_name']);
		$thesis->setSupervisorEmail($row['supervisor_email']);
		
		return $thesis;
	}

	/**
	 * Insert a new Thesis.
	 * @param $thesis Thesis
	 * @return int 
	 */
	function insertThesis(&$thesis) {
		$ret = $this->update(
			sprintf('INSERT INTO theses
				(journal_id, status, degree, department, university, date_approved, title, abstract, url, student_first_name, student_middle_name, student_last_name, student_email, supervisor_first_name, supervisor_middle_name, supervisor_last_name, supervisor_email)
				VALUES
				(?, ?, ?, ?, ?, %s, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
				$this->dateToDB($thesis->getDateApproved())),
			array(
				$thesis->getJournalId(),
				$thesis->getStatus(),
				$thesis->getDegree(),
				$thesis->getDepartment(),
				$thesis->getUniversity(),
				$thesis->getTitle(),
				$thesis->getAbstract(),
				$thesis->getUrl(),
				$thesis->getStudentFirstName(),
				$thesis->getStudentMiddleName(),
				$thesis->getStudentLastName(),
				$thesis->getStudentEmail(),
				$thesis->getSupervisorFirstName(),
				$thesis->getSupervisorMiddleName(),
				$thesis->getSupervisorLastName(),
				$thesis->getSupervisorEmail()
			)
		);
		$thesis->setThesisId($this->getInsertThesisId());
		return $thesis->getThesisId();
	}

	/**
	 * Update an existing thesis.
	 * @param $thesis Thesis
	 * @return boolean
	 */
	function updateThesis(&$thesis) {
		return $this->update(
			sprintf('UPDATE theses
				SET
					journal_id = ?,
					status = ?,
					degree = ?,
					department = ?,
					university = ?,
					date_approved = %s,
					title = ?,
					abstract = ?,
					url = ?,
					student_first_name = ?,
					student_middle_name = ?,
					student_last_name = ?,
					student_email = ?,
					supervisor_first_name = ?,
					supervisor_middle_name = ?,
					supervisor_last_name = ?,
					supervisor_email = ?
				WHERE thesis_id = ?',
				$this->dateToDB($thesis->getDateApproved())),
			array(
				$thesis->getJournalId(),
				$thesis->getStatus(),
				$thesis->getDegree(),
				$thesis->getDepartment(),
				$thesis->getUniversity(),
				$thesis->getTitle(),
				$thesis->getAbstract(),
				$thesis->getUrl(),
				$thesis->getStudentFirstName(),
				$thesis->getStudentMiddleName(),
				$thesis->getStudentLastName(),
				$thesis->getStudentEmail(),
				$thesis->getSupervisorFirstName(),
				$thesis->getSupervisorMiddleName(),
				$thesis->getSupervisorLastName(),
				$thesis->getSupervisorEmail(),
				$thesis->getThesisId()
			)
		);
	}

	/**
	 * Delete a thesis.
	 * @param $thesis Thesis 
	 * @return boolean
	 */
	function deleteThesis($thesis) {
		return $this->deleteThesisById($thesis->getThesisId());
	}

	/**
	 * Delete a thesis by thesis ID.
	 * @param $thesisId int
	 * @return boolean
	 */
	function deleteThesisById($thesisId) {
		return $this->update(
			'DELETE FROM theses WHERE thesis_id = ?', $thesisId
		);
	}

	/**
	 * Delete theses by journal ID.
	 * @param $journalId int
	 */
	function deleteThesesByJournal($journalId) {
		return $this->update(
			'DELETE FROM theses WHERE journal_id = ?', $journalId
		);
	}

	/**
	 * Retrieve an array of theses matching a particular journal ID.
	 * @param $journalId int
	 * @return object DAOResultFactory containing matching Theses 
	 */
	function &getThesesByJournalId($journalId, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT * FROM theses WHERE journal_id = ? ORDER BY thesis_id DESC', $journalId, $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnThesisFromRow');
		return $returner;
	}

	/**
	 * Retrieve an array of theses with active status matching a particular journal ID.
	 * @param $journalId int
	 * @return object DAOResultFactory containing matching Theses 
	 */
	function &getActiveThesesByJournalId($journalId, $rangeInfo = null) {
		$thesisPlugin = &PluginRegistry::getPlugin('generic', 'ThesisPlugin');
		$thesisPlugin->import('Thesis');

		$result = &$this->retrieveRange(
			'SELECT * FROM theses WHERE status = ? AND journal_id = ? ORDER BY thesis_id DESC', array(THESIS_STATUS_ACTIVE, $journalId), $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnThesisFromRow');
		return $returner;
	}

	/**
	 * Get the ID of the last inserted thesis.
	 * @return int
	 */
	function getInsertThesisId() {
		return $this->getInsertId('theses', 'thesis_id');
	}

}

?>
