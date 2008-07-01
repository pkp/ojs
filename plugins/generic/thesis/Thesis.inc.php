<?php

/**
 * @file Thesis.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Thesis
 * @ingroup plugins_generic_thesis
 *
 * @brief Basic class describing a thesis.
 */

// $Id$


define('THESIS_STATUS_INACTIVE',			0x01);
define('THESIS_STATUS_ACTIVE',				0x02);
define('THESIS_DEGREE_MASTERS',				0x01);
define('THESIS_DEGREE_DOCTORATE',			0x02);
define('THESIS_APPROVED_YEAR_OFFSET_PAST',	'-10');
define('THESIS_APPROVED_DATE_DEFAULT_DAY',	'1');


class Thesis extends DataObject {

	function Thesis() {
		parent::DataObject();
	}

	//
	// Get/set methods
	//

	/**
	 * Get the ID of the thesis.
	 * @return int
	 */
	function getThesisId() {
		return $this->getData('thesisId');
	}

	/**
	 * Set the ID of the thesis.
	 * @param $thesisId int
	 */
	function setThesisId($thesisId) {
		return $this->setData('thesisId', $thesisId);
	}

	/**
	 * Get the journal ID of the thesis.
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}

	/**
	 * Set the journal ID of the thesis.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}

	/**
	 * Get the status of the thesis.
	 * @return int
	 */
	function getStatus() {
		return $this->getData('status');
	}

	/**
	 * Set the status of the thesis.
	 * @param $status int
	 */
	function setStatus($status) {
		return $this->setData('status', $status);
	}

	/**
	 * Get thesis degree.
	 * @return int 
	 */
	function getDegree() {
		return $this->getData('degree');
	}

	/**
	 * Set thesis degree.
	 * @param $degree int
	 */
	function setDegree($degree) {
		return $this->setData('degree', $degree);
	}

	/**
	 * Get thesis degree name.
	 * @return int 
	 */
	function getDegreeName() {
		return $this->getData('degreeName');
	}

	/**
	 * Set thesis degree name.
	 * @param $degreeName int
	 */
	function setDegreeName($degreeName) {
		return $this->setData('degreeName', $degreeName);
	}

	/**
	 * Get thesis department.
	 * @return string
	 */
	function getDepartment() {
		return $this->getData('department');
	}

	/**
	 * Set thesis department.
	 * @param $department string
	 */
	function setDepartment($department) {
		return $this->setData('department', $department);
	}

	/**
	 * Get thesis university.
	 * @return string
	 */
	function getUniversity() {
		return $this->getData('university');
	}

	/**
	 * Set thesis university.
	 * @param $university string
	 */
	function setUniversity($university) {
		return $this->setData('university', $university);
	}

	/**
	 * Get thesis approval date.
	 * @return date (YYYY-MM-DD)
	 */
	function getDateApproved() {
		return $this->getData('dateApproved');
	}

	/**
	 * Set thesis approval date.
	 * @param $dateApproved date (YYYY-MM-DD)
	 */
	function setDateApproved($dateApproved) {
		return $this->setData('dateApproved', $dateApproved);
	}

	/**
	 * Get thesis title.
	 * @return string 
	 */
	function getTitle() {
		return $this->getData('title');
	}

	/**
	 * Set thesis title.
	 * @param $title string
	 */
	function setTitle($title) {
		return $this->setData('title', $title);
	}

	/**
	 * Get thesis abstract.
	 * @return string 
	 */
	function getAbstract() {
		return $this->getData('abstract');
	}

	/**
	 * Set thesis abstract.
	 * @param $abstract string 
	 */
	function setAbstract($abstract) {
		return $this->setData('abstract', $abstract);
	}

	/**
	 * Get thesis url.
	 * @return string 
	 */
	function getUrl() {
		return $this->getData('url');
	}

	/**
	 * Set thesis url.
	 * @param $url string
	 */
	function setUrl($url) {
		return $this->setData('url', $url);
	}

	/**
	 * Get thesis comment.
	 * @return string 
	 */
	function getComment() {
		return $this->getData('comment');
	}

	/**
	 * Set thesis comment.
	 * @param $comment string 
	 */
	function setComment($comment) {
		return $this->setData('comment', $comment);
	}

	/**
	 * Get thesis student first name.
	 * @return string 
	 */
	function getStudentFirstName() {
		return $this->getData('studentFirstName');
	}

	/**
	 * Set thesis student first name.
	 * @param $studentFirstName string
	 */
	function setStudentFirstName($studentFirstName) {
		return $this->setData('studentFirstName', $studentFirstName);
	}

	/**
	 * Get thesis student middle name.
	 * @return string 
	 */
	function getStudentMiddleName() {
		return $this->getData('studentMiddleName');
	}

	/**
	 * Set thesis student middle name.
	 * @param $studentMiddleName string
	 */
	function setStudentMiddleName($studentMiddleName) {
		return $this->setData('studentMiddleName', $studentMiddleName);
	}

	/**
	 * Get thesis student last name.
	 * @return string 
	 */
	function getStudentLastName() {
		return $this->getData('studentLastName');
	}

	/**
	 * Set thesis student last name.
	 * @param $studentLastName string
	 */
	function setStudentLastName($studentLastName) {
		return $this->setData('studentLastName', $studentLastName);
	}

	/**
	 * Get thesis student full name.
	 * @return string 
	 */
	function getStudentFullName($lastNameFirst = false) {
		$middleName = $this->getData('studentMiddleName'); 
		if (!empty($middleName)) {
			$space = " ";
		} else {
			$space = "";
		}

		if ($lastNameFirst) {
			return Locale::translate('plugins.generic.thesis.studentFullNameLast', array('lastName' => $this->getData('studentLastName'), 'firstName' => $this->getData('studentFirstName'), 'middleName' => $middleName, 'space' => $space));
		} else {
			return Locale::translate('plugins.generic.thesis.studentFullName', array('lastName' => $this->getData('studentLastName'), 'firstName' => $this->getData('studentFirstName'), 'middleName' => $middleName, 'space' => $space));
		}
	}

	/**
	 * Get thesis student email.
	 * @return string 
	 */
	function getStudentEmail() {
		return $this->getData('studentEmail');
	}

	/**
	 * Set thesis student email.
	 * @param $studentEmail string
	 */
	function setStudentEmail($studentEmail) {
		return $this->setData('studentEmail', $studentEmail);
	}

	/**
	 * Get thesis publish student email.
	 * @return int 
	 */
	function getStudentEmailPublish() {
		return $this->getData('studentEmailPublish');
	}

	/**
	 * Set thesis publish student email.
	 * @param $studentEmailPublish int 
	 */
	function setStudentEmailPublish($studentEmailPublish) {
		return $this->setData('studentEmailPublish', $studentEmailPublish);
	}

	/**
	 * Get thesis student bio.
	 * @return string 
	 */
	function getStudentBio() {
		return $this->getData('studentBio');
	}

	/**
	 * Set thesis student bio.
	 * @param $studentBio string 
	 */
	function setStudentBio($studentBio) {
		return $this->setData('studentBio', $studentBio);
	}

	/**
	 * Get thesis supervisor first name.
	 * @return string 
	 */
	function getSupervisorFirstName() {
		return $this->getData('supervisorFirstName');
	}

	/**
	 * Set thesis supervisor first name.
	 * @param $supervisorFirstName string
	 */
	function setSupervisorFirstName($supervisorFirstName) {
		return $this->setData('supervisorFirstName', $supervisorFirstName);
	}

	/**
	 * Get thesis supervisor middle name.
	 * @return string 
	 */
	function getSupervisorMiddleName() {
		return $this->getData('supervisorMiddleName');
	}

	/**
	 * Set thesis supervisor middle name.
	 * @param $supervisorMiddleName string
	 */
	function setSupervisorMiddleName($supervisorMiddleName) {
		return $this->setData('supervisorMiddleName', $supervisorMiddleName);
	}

	/**
	 * Get thesis supervisor last name.
	 * @return string 
	 */
	function getSupervisorLastName() {
		return $this->getData('supervisorLastName');
	}

	/**
	 * Set thesis supervisor last name.
	 * @param $supervisorLastName string
	 */
	function setSupervisorLastName($supervisorLastName) {
		return $this->setData('supervisorLastName', $supervisorLastName);
	}

	/**
	 * Get supervisor full name.
	 * @return string 
	 */
	function getSupervisorFullName($lastNameFirst = false) {
		$middleName = $this->getData('supervisorMiddleName'); 
		if (!empty($middleName)) {
			$space = " ";
		} else {
			$space = "";
		}

		if ($lastNameFirst) {
			return Locale::translate('plugins.generic.thesis.supervisorFullNameLast', array('lastName' => $this->getData('supervisorLastName'), 'firstName' => $this->getData('supervisorFirstName'), 'middleName' => $middleName, 'space' => $space));
		} else {
			return Locale::translate('plugins.generic.thesis.supervisorFullName', array('lastName' => $this->getData('supervisorLastName'), 'firstName' => $this->getData('supervisorFirstName'), 'middleName' => $middleName, 'space' => $space));
		}
	}

	/**
	 * Get thesis supervisor email.
	 * @return string 
	 */
	function getSupervisorEmail() {
		return $this->getData('supervisorEmail');
	}

	/**
	 * Set thesis supervisor email.
	 * @param $supervisorEmail string
	 */
	function setSupervisorEmail($supervisorEmail) {
		return $this->setData('supervisorEmail', $supervisorEmail);
	}

	/**
	 * Get thesis discipline.
	 * @return string 
	 */
	function getDiscipline() {
		return $this->getData('discipline');
	}

	/**
	 * Set thesis discipline.
	 * @param $discipline string 
	 */
	function setDiscipline($discipline) {
		return $this->setData('discipline', $discipline);
	}

	/**
	 * Get thesis subject classification.
	 * @return string 
	 */
	function getSubjectClass() {
		return $this->getData('subjectClass');
	}

	/**
	 * Set thesis subject classification.
	 * @param $subjectClass string 
	 */
	function setSubjectClass($subjectClass) {
		return $this->setData('subjectClass', $subjectClass);
	}

	/**
	 * Get thesis subject.
	 * @return string 
	 */
	function getSubject() {
		return $this->getData('subject');
	}

	/**
	 * Set thesis subject.
	 * @param $subject string 
	 */
	function setSubject($subject) {
		return $this->setData('subject', $subject);
	}

	/**
	 * Get thesis coverage geo.
	 * @return string 
	 */
	function getCoverageGeo() {
		return $this->getData('coverageGeo');
	}

	/**
	 * Set thesis coverage geo.
	 * @param $coverageGeo string 
	 */
	function setCoverageGeo($coverageGeo) {
		return $this->setData('coverageGeo', $coverageGeo);
	}

	/**
	 * Get thesis coverage chron.
	 * @return string 
	 */
	function getCoverageChron() {
		return $this->getData('coverageChron');
	}

	/**
	 * Set thesis coverage chron.
	 * @param $coverageChron string 
	 */
	function setCoverageChron($coverageChron) {
		return $this->setData('coverageChron', $coverageChron);
	}

	/**
	 * Get thesis coverage sample.
	 * @return string 
	 */
	function getCoverageSample() {
		return $this->getData('coverageSample');
	}

	/**
	 * Set thesis coverage sample.
	 * @param $coverageSample string 
	 */
	function setCoverageSample($coverageSample) {
		return $this->setData('coverageSample', $coverageSample);
	}

	/**
	 * Get thesis method.
	 * @return string 
	 */
	function getMethod() {
		return $this->getData('method');
	}

	/**
	 * Set thesis method.
	 * @param $method string 
	 */
	function setMethod($method) {
		return $this->setData('method', $method);
	}

	/**
	 * Get thesis language.
	 * @return string 
	 */
	function getLanguage() {
		return $this->getData('language');
	}

	/**
	 * Set thesis language.
	 * @param $language string 
	 */
	function setLanguage($language) {
		return $this->setData('language', $language);
	}

	/**
	 * Get thesis submitted date.
	 * @return date (YYYY-MM-DD HH:MM:SS)
	 */
	function getDateSubmitted() {
		return $this->getData('dateSubmitted');
	}

	/**
	 * Set thesis submitted date.
	 * @param $dateSubmitted date (YYYY-MM-DD HH:MM:SS)
	 */
	function setDateSubmitted($dateSubmitted) {
		return $this->setData('dateSubmitted', $dateSubmitted);
	}

	/**
	 * Get thesis status locale key.
	 * @return int 
	 */
	function getStatusString() {
		switch ($this->getData('status')) {
			case THESIS_STATUS_INACTIVE:
				return 'plugins.generic.thesis.manager.status.inactive';
			case THESIS_STATUS_ACTIVE:
				return 'plugins.generic.thesis.manager.status.active';
			default:
				return 'plugins.generic.thesis.manager.status';
		}
	}

	/**
	 * Get thesis degree locale key.
	 * @return int 
	 */
	function getDegreeString() {
		switch ($this->getData('degree')) {
			case THESIS_DEGREE_MASTERS:
				return 'plugins.generic.thesis.manager.degree.masters';
			case THESIS_DEGREE_DOCTORATE:
				return 'plugins.generic.thesis.manager.degree.doctorate';
			default:
				return 'plugins.generic.thesis.manager.degree';
		}
	}

	/**
	 * Get thesis degree metadata string.
	 * @return string 
	 */
	function getDegreeLevel() {
		switch ($this->getData('degree')) {
			case THESIS_DEGREE_MASTERS:
				return 'Master\'s';
			case THESIS_DEGREE_DOCTORATE:
				return 'Doctorate';
			default:
				return '';
		}
	}
}

?>
