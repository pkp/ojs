<?php

/**
 * @file ThesisDAO.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ThesisDAO
 * @ingroup plugins_generic_thesis
 *
 * @brief Operations for retrieving and modifying Thesis objects.
 */

// $Id$


import('db.DAO');

/* These constants are used for user-selectable search fields. */
define('THESIS_FIELD_FIRSTNAME',	'student_first_name');
define('THESIS_FIELD_LASTNAME', 	'student_last_name');
define('THESIS_FIELD_EMAIL', 		'student_email');
define('THESIS_FIELD_DEPARTMENT', 	'department');
define('THESIS_FIELD_UNIVERSITY', 	'university');
define('THESIS_FIELD_SUBJECT', 		'subject');
define('THESIS_FIELD_TITLE', 		'title');
define('THESIS_FIELD_ABSTRACT', 	'abstract');
define('THESIS_FIELD_NONE', 		null);

/* These constants are used for sorting thesis abstracts. */
define('THESIS_ORDER_SUBMISSION_DATE_ASC',	1);
define('THESIS_ORDER_SUBMISSION_DATE_DESC',	2);
define('THESIS_ORDER_APPROVAL_DATE_ASC', 	3);
define('THESIS_ORDER_APPROVAL_DATE_DESC',	4);
define('THESIS_ORDER_LASTNAME_ASC',			5);
define('THESIS_ORDER_LASTNAME_DESC',		6);
define('THESIS_ORDER_TITLE_ASC',			7);
define('THESIS_ORDER_TITLE_DESC',			8);

class ThesisDAO extends DAO {
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
	 * Retrieve most recently submitted active thesis by journal ID.
	 * @param $journalId int
	 * @return Thesis
	 */
	function &getMostRecentActiveThesisByJournalId($journalId) {
		$result = &$this->retrieve(
			'SELECT * FROM theses WHERE status = ? AND journal_id = ? ORDER BY date_submitted DESC, thesis_id DESC LIMIT 1', array(THESIS_STATUS_ACTIVE, $journalId)
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
		$thesis->setDegreeName($row['degree_name']);
		$thesis->setDepartment($row['department']);
		$thesis->setUniversity($row['university']);
		$thesis->setDateApproved($this->dateFromDB($row['date_approved']));
		$thesis->setTitle($row['title']);
		$thesis->setAbstract($row['abstract']);
		$thesis->setUrl($row['url']);
		$thesis->setComment($row['comment']);
		$thesis->setStudentFirstName($row['student_first_name']);
		$thesis->setStudentMiddleName($row['student_middle_name']);
		$thesis->setStudentLastName($row['student_last_name']);
		$thesis->setStudentEmail($row['student_email']);
		$thesis->setStudentEmailPublish($row['student_email_publish']);
		$thesis->setStudentBio($row['student_bio']);
		$thesis->setSupervisorFirstName($row['supervisor_first_name']);
		$thesis->setSupervisorMiddleName($row['supervisor_middle_name']);
		$thesis->setSupervisorLastName($row['supervisor_last_name']);
		$thesis->setSupervisorEmail($row['supervisor_email']);
		$thesis->setDiscipline($row['discipline']);
		$thesis->setSubjectClass($row['subject_class']);
		$thesis->setSubject($row['subject']);
		$thesis->setCoverageGeo($row['coverage_geo']);
		$thesis->setCoverageChron($row['coverage_chron']);
		$thesis->setCoverageSample($row['coverage_sample']);
		$thesis->setMethod($row['method']);
		$thesis->setLanguage($row['language']);
		$thesis->setDateSubmitted($row['date_submitted']);

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
				(journal_id, status, degree, degree_name, department, university, date_approved, title, abstract, url, comment, student_first_name, student_middle_name, student_last_name, student_email, student_email_publish, student_bio, supervisor_first_name, supervisor_middle_name, supervisor_last_name, supervisor_email, discipline, subject_class, subject, coverage_geo, coverage_chron, coverage_sample, method, language, date_submitted)
				VALUES
				(?, ?, ?, ?, ?, ?, %s, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, %s)',
				$this->dateToDB($thesis->getDateApproved()), $this->datetimeToDB($thesis->getDateSubmitted())),
			array(
				$thesis->getJournalId(),
				$thesis->getStatus(),
				$thesis->getDegree(),
				$thesis->getDegreeName(),
				$thesis->getDepartment(),
				$thesis->getUniversity(),
				$thesis->getTitle(),
				$thesis->getAbstract(),
				$thesis->getUrl(),
				$thesis->getComment(),
				$thesis->getStudentFirstName(),
				$thesis->getStudentMiddleName(),
				$thesis->getStudentLastName(),
				$thesis->getStudentEmail(),
				$thesis->getStudentEmailPublish(),
				$thesis->getStudentBio(),
				$thesis->getSupervisorFirstName(),
				$thesis->getSupervisorMiddleName(),
				$thesis->getSupervisorLastName(),
				$thesis->getSupervisorEmail(),
				$thesis->getDiscipline(),
				$thesis->getSubjectClass(),
				$thesis->getSubject(),
				$thesis->getCoverageGeo(),
				$thesis->getCoverageChron(),
				$thesis->getCoverageSample(),
				$thesis->getMethod(),
				$thesis->getLanguage()
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
					degree_name = ?,
					department = ?,
					university = ?,
					date_approved = %s,
					title = ?,
					abstract = ?,
					url = ?,
					comment = ?,
					student_first_name = ?,
					student_middle_name = ?,
					student_last_name = ?,
					student_email = ?,
					student_email_publish = ?,
					student_bio = ?,
					supervisor_first_name = ?,
					supervisor_middle_name = ?,
					supervisor_last_name = ?,
					supervisor_email = ?,
					discipline = ?,
					subject_class = ?,
					subject = ?,
					coverage_geo = ?,
					coverage_chron = ?,
					coverage_sample = ?,
					method = ?,
					language = ?,
					date_submitted = %s
				WHERE thesis_id = ?',
				$this->dateToDB($thesis->getDateApproved()), $this->datetimeToDB($thesis->getDateSubmitted())),
			array(
				$thesis->getJournalId(),
				$thesis->getStatus(),
				$thesis->getDegree(),
				$thesis->getDegreeName(),
				$thesis->getDepartment(),
				$thesis->getUniversity(),
				$thesis->getTitle(),
				$thesis->getAbstract(),
				$thesis->getUrl(),
				$thesis->getComment(),
				$thesis->getStudentFirstName(),
				$thesis->getStudentMiddleName(),
				$thesis->getStudentLastName(),
				$thesis->getStudentEmail(),
				$thesis->getStudentEmailPublish(),
				$thesis->getStudentBio(),
				$thesis->getSupervisorFirstName(),
				$thesis->getSupervisorMiddleName(),
				$thesis->getSupervisorLastName(),
				$thesis->getSupervisorEmail(),
				$thesis->getDiscipline(),
				$thesis->getSubjectClass(),
				$thesis->getSubject(),
				$thesis->getCoverageGeo(),
				$thesis->getCoverageChron(),
				$thesis->getCoverageSample(),
				$thesis->getMethod(),
				$thesis->getLanguage(),
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
	 * @param $searchType int optional, which field to search
	 * @param $search string optional, string to match
	 * @param $searchMatch string optional, type of match ('is' vs. 'contains')
	 * @param $dateFrom date optional, starting approval date
	 * @param $dateTo date optional, ending approval date
	 * @param $resultOrder int optional, order of the results
	 * @param $rangeInfo object DBRangeInfo object describing range of results to return
	 * @return object DAOResultFactory containing matching Theses 
	 */
	function &getThesesByJournalId($journalId, $searchType = null, $search = null, $searchMatch = null, $dateFrom = null, $dateTo = null, $resultOrder = null, $rangeInfo = null) {
		$paramArray = array((int) $journalId);
		$searchSql = '';

		if (!empty($search)) switch ($searchType) {
			case THESIS_FIELD_FIRSTNAME:
				$searchSql = 'AND LOWER(student_first_name) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case THESIS_FIELD_LASTNAME:
				$searchSql = 'AND LOWER(student_last_name) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case THESIS_FIELD_EMAIL:
				$searchSql = 'AND LOWER(student_email) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case THESIS_FIELD_DEPARTMENT:
				$searchSql = 'AND LOWER(department) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case THESIS_FIELD_UNIVERSITY:
				$searchSql = 'AND LOWER(university) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case THESIS_FIELD_TITLE:
				$searchSql = 'AND LOWER(title) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case THESIS_FIELD_SUBJECT:
				$searchSql = 'AND LOWER(subject) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case THESIS_FIELD_ABSTRACT:
				$searchSql = 'AND LOWER(abstract) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
		}

		if (!empty($dateFrom) || !empty($dateTo)) {
			if (!empty($dateFrom)) {
				$searchSql .= ' AND date_approved >= ' . $this->datetimeToDB($dateFrom);
			}
			if (!empty($dateTo)) {
					$searchSql .= ' AND date_approved <= ' . $this->datetimeToDB($dateTo);
				}
		}

		switch ($resultOrder) {
			case THESIS_ORDER_SUBMISSION_DATE_ASC:
				$searchSql .= ' ORDER BY date_submitted ASC, thesis_id ASC';
				break;
			case THESIS_ORDER_SUBMISSION_DATE_DESC:
				$searchSql .= ' ORDER BY date_submitted DESC, thesis_id DESC';
				break;
			case THESIS_ORDER_APPROVAL_DATE_ASC:
				$searchSql .= ' ORDER BY date_approved ASC, student_last_name ASC, title ASC';
				break;
			case THESIS_ORDER_APPROVAL_DATE_DESC:
				$searchSql .= ' ORDER BY date_approved DESC, student_last_name ASC, title ASC';
				break;
			case THESIS_ORDER_LASTNAME_ASC:
				$searchSql .= ' ORDER BY student_last_name ASC, title ASC';
				break;
			case THESIS_ORDER_LASTNAME_DESC:
				$searchSql .= ' ORDER BY student_last_name DESC, title ASC';
				break;
			case THESIS_ORDER_TITLE_ASC:
				$searchSql .= ' ORDER BY title ASC, student_last_name ASC';
				break;
			case THESIS_ORDER_TITLE_DESC:
				$searchSql .= ' ORDER BY title DESC, student_last_name ASC';
				break;
			default:
				$searchSql .= ' ORDER BY date_submitted DESC, thesis_id DESC';
		}

		$result = &$this->retrieveRange(
			'SELECT * FROM theses WHERE journal_id = ? ' . $searchSql,
			$paramArray,
			$rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnThesisFromRow');
		return $returner;
	}

	/**
	 * Retrieve an array of theses with active status matching a particular journal ID.
	 * @param $journalId int
	 * @param $searchType int optional, which field to search
	 * @param $search string optional, string to match
	 * @param $searchMatch string optional, type of match ('is' vs. 'contains')
	 * @param $dateFrom date optional, starting approval date
	 * @param $dateTo date optional, ending approval date
	 * @param $resultOrder int optional, order of the results
	 * @param $rangeInfo object DBRangeInfo object describing range of results to return
	 * @return object DAOResultFactory containing matching Theses 
	 */
	function &getActiveThesesByJournalId($journalId, $searchType = null, $search = null, $searchMatch = null, $dateFrom = null, $dateTo = null, $resultOrder = null, $rangeInfo = null) {
		$thesisPlugin = &PluginRegistry::getPlugin('generic', 'ThesisPlugin');
		$thesisPlugin->import('Thesis');

		$paramArray = array(THESIS_STATUS_ACTIVE, (int) $journalId);
		$searchSql = '';

		if (!empty($search)) switch ($searchType) {
			case THESIS_FIELD_FIRSTNAME:
				$searchSql = 'AND LOWER(student_first_name) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case THESIS_FIELD_LASTNAME:
				$searchSql = 'AND LOWER(student_last_name) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case THESIS_FIELD_EMAIL:
				$searchSql = 'AND LOWER(student_email) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case THESIS_FIELD_DEPARTMENT:
				$searchSql = 'AND LOWER(department) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case THESIS_FIELD_UNIVERSITY:
				$searchSql = 'AND LOWER(university) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case THESIS_FIELD_TITLE:
				$searchSql = 'AND LOWER(title) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case THESIS_FIELD_SUBJECT:
				$searchSql = 'AND LOWER(subject) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case THESIS_FIELD_ABSTRACT:
				$searchSql = 'AND LOWER(abstract) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
		}

		if (!empty($dateFrom) || !empty($dateTo)) {
			if (!empty($dateFrom)) {
				$searchSql .= ' AND date_approved >= ' . $this->datetimeToDB($dateFrom);
			}
			if (!empty($dateTo)) {
					$searchSql .= ' AND date_approved <= ' . $this->datetimeToDB($dateTo);
				}
		}

		switch ($resultOrder) {
			case THESIS_ORDER_SUBMISSION_DATE_ASC:
				$searchSql .= ' ORDER BY date_submitted ASC, thesis_id ASC';
				break;
			case THESIS_ORDER_SUBMISSION_DATE_DESC:
				$searchSql .= ' ORDER BY date_submitted DESC, thesis_id DESC';
				break;
			case THESIS_ORDER_APPROVAL_DATE_ASC:
				$searchSql .= ' ORDER BY date_approved ASC, student_last_name ASC, title ASC';
				break;
			case THESIS_ORDER_APPROVAL_DATE_DESC:
				$searchSql .= ' ORDER BY date_approved DESC, student_last_name ASC, title ASC';
				break;
			case THESIS_ORDER_LASTNAME_ASC:
				$searchSql .= ' ORDER BY student_last_name ASC, title ASC';
				break;
			case THESIS_ORDER_LASTNAME_DESC:
				$searchSql .= ' ORDER BY student_last_name DESC, title ASC';
				break;
			case THESIS_ORDER_TITLE_ASC:
				$searchSql .= ' ORDER BY title ASC, student_last_name ASC';
				break;
			case THESIS_ORDER_TITLE_DESC:
				$searchSql .= ' ORDER BY title DESC, student_last_name ASC';
				break;
			default:
				$searchSql .= ' ORDER BY date_submitted DESC, thesis_id DESC';
		}

		$result = &$this->retrieveRange(
			'SELECT * FROM theses WHERE status = ? AND journal_id = ? ' . $searchSql,
			$paramArray,
			$rangeInfo
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
