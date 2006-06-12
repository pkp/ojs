<?php

/**
 * ThesisForm.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins 
 *
 * Form for journal managers to create/edit thesis abstracts.
 *
 * $Id$
 */

import('form.Form');

class ThesisForm extends Form {

	/** @var thesisId int the ID of the thesis being edited */
	var $thesisId;

	/** @var validStatus array keys are valid thesis status values */
	var $validStatus;

	/** @var validDegrees array keys are valid thesis degree values */
	var $validDegrees;


	/**
	 * Constructor
	 * @param thesisId int leave as default for new thesis
	 */
	function ThesisForm($thesisId = null) {
		$thesisPlugin = &PluginRegistry::getPlugin('generic', 'ThesisPlugin');
		$thesisPlugin->import('Thesis');

		$this->validStatus = array (
			THESIS_STATUS_INACTIVE => Locale::translate('plugins.generic.thesis.manager.status.inactive'),
			THESIS_STATUS_ACTIVE => Locale::translate('plugins.generic.thesis.manager.status.active')
		);

		$this->validDegrees = array (
			THESIS_DEGREE_MASTERS => Locale::translate('plugins.generic.thesis.manager.degree.masters'),
			THESIS_DEGREE_DOCTORATE => Locale::translate('plugins.generic.thesis.manager.degree.doctorate')
		);

		$this->thesisId = isset($thesisId) ? (int) $thesisId : null;

		$journal = &Request::getJournal();
		parent::Form($thesisPlugin->getTemplatePath() . 'thesisForm.tpl');

	
		// Status is provided and is valid value
		$this->addCheck(new FormValidator($this, 'status', 'required', 'plugins.generic.thesis.manager.form.statusRequired'));	
		$this->addCheck(new FormValidatorInSet($this, 'status', 'required', 'plugins.generic.thesis.manager.form.statusValid', array_keys($this->validStatus)));

		// Degree is provided and is valid value
		$this->addCheck(new FormValidator($this, 'degree', 'required', 'plugins.generic.thesis.form.degreeRequired'));	
		$this->addCheck(new FormValidatorInSet($this, 'degree', 'required', 'plugins.generic.thesis.form.degreeValid', array_keys($this->validDegrees)));
	
		// Department is provided
		$this->addCheck(new FormValidator($this, 'department', 'required', 'plugins.generic.thesis.form.departmentRequired'));

		// University is provided
		$this->addCheck(new FormValidator($this, 'university', 'required', 'plugins.generic.thesis.form.universityRequired'));

		// Approval date is provided and valid
		$this->addCheck(new FormValidator($this, 'dateApprovedYear', 'required', 'plugins.generic.thesis.form.dateApprovedRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateApprovedYear', 'required', 'plugins.generic.thesis.form.dateApprovedValid', create_function('$dateApprovedYear', '$minYear = date(\'Y\') + THESIS_APPROVED_YEAR_OFFSET_PAST; $maxYear = date(\'Y\'); return ($dateApprovedYear >= $minYear && $dateApprovedYear <= $maxYear) ? true : false;')));

		$this->addCheck(new FormValidator($this, 'dateApprovedMonth', 'required', 'plugins.generic.thesis.form.dateApprovedRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateApprovedMonth', 'required', 'plugins.generic.thesis.form.dateApprovedValid', create_function('$dateApprovedMonth', 'return ($dateApprovedMonth >= 1 && $dateApprovedMonth <= 12) ? true : false;')));

		$this->addCheck(new FormValidator($this, 'dateApprovedDay', 'required', 'plugins.generic.thesis.form.dateApprovedRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateApprovedDay', 'required', 'plugins.generic.thesis.form.dateApprovedValid', create_function('$dateApprovedDay', 'return ($dateApprovedDay >= 1 && $dateApprovedDay <= 31) ? true : false;')));

		// Title is provided
		$this->addCheck(new FormValidator($this, 'title', 'required', 'plugins.generic.thesis.form.titleRequired'));

		// Student first name is provided
		$this->addCheck(new FormValidator($this, 'studentFirstName', 'required', 'plugins.generic.thesis.form.studentFirstNameRequired'));

		// Student last name is provided
		$this->addCheck(new FormValidator($this, 'studentLastName', 'required', 'plugins.generic.thesis.form.studentLastNameRequired'));

		// Student email is provided and valid
		$this->addCheck(new FormValidatorEmail($this, 'studentEmail', 'required', 'plugins.generic.thesis.form.studentEmailValid'));

		// Supervisor first name is provided
		$this->addCheck(new FormValidator($this, 'supervisorFirstName', 'required', 'plugins.generic.thesis.form.supervisorFirstNameRequired'));

		// Supervisor last name is provided
		$this->addCheck(new FormValidator($this, 'supervisorLastName', 'required', 'plugins.generic.thesis.form.supervisorLastNameRequired'));

		// Supervisor email is provided
		$this->addCheck(new FormValidatorEmail($this, 'supervisorEmail', 'required', 'plugins.generic.thesis.form.supervisorEmailValid'));

		// Abstract is provided
		$this->addCheck(new FormValidator($this, 'abstract', 'required', 'plugins.generic.thesis.form.abstractRequired'));

	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$thesisPlugin = &PluginRegistry::getPlugin('generic', 'ThesisPlugin');
		$thesisPlugin->import('Thesis');

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('thesisId', $this->thesisId);
		$templateMgr->assign('validStatus', $this->validStatus);
		$templateMgr->assign('validDegrees', $this->validDegrees);
		$templateMgr->assign('yearOffsetPast', THESIS_APPROVED_YEAR_OFFSET_PAST);
	
		parent::display();
	}
	
	/**
	 * Initialize form data.
	 */
	function initData() {
		if (isset($this->thesisId)) {
			$thesisDao = &DAORegistry::getDAO('ThesisDAO');
			$thesis = &$thesisDao->getThesis($this->thesisId);

			if ($thesis != null) {
				$this->_data = array(
					'status' => $thesis->getStatus(),
					'degree' => $thesis->getDegree(),
					'department' => $thesis->getDepartment(),
					'university' => $thesis->getUniversity(),
					'dateApproved' => $thesis->getDateApproved(),
					'title' => $thesis->getTitle(),
					'url' => $thesis->getUrl(),
					'abstract' => $thesis->getAbstract(),
					'studentFirstName' => $thesis->getStudentFirstName(),
					'studentMiddleName' => $thesis->getStudentMiddleName(),
					'studentLastName' => $thesis->getStudentLastName(),
					'studentEmail' => $thesis->getStudentEmail(),
					'supervisorFirstName' => $thesis->getSupervisorFirstName(),
					'supervisorMiddleName' => $thesis->getSupervisorMiddleName(),
					'supervisorLastName' => $thesis->getSupervisorLastName(),
					'supervisorEmail' => $thesis->getSupervisorEmail()
				);

			} else {
				$this->thesisId = null;
			}
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('status', 'degree', 'department', 'university', 'dateApprovedYear', 'dateApprovedMonth', 'dateApprovedDay', 'title', 'url', 'abstract', 'studentFirstName', 'studentMiddleName', 'studentLastName', 'studentEmail', 'supervisorFirstName', 'supervisorMiddleName', 'supervisorLastName', 'supervisorEmail'));
		$this->_data['dateApproved'] = $this->_data['dateApprovedYear'] . '-' . $this->_data['dateApprovedMonth'] . '-' . $this->_data['dateApprovedDay']; 

	}
	
	/**
	 * Save thesis. 
	 */
	function execute() {
		$thesisPlugin = &PluginRegistry::getPlugin('generic', 'ThesisPlugin');
		$thesisPlugin->import('Thesis');

		$thesisDao = &DAORegistry::getDAO('ThesisDAO');
		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();
	
		if (isset($this->thesisId)) {
			$thesis = &$thesisDao->getThesis($this->thesisId);
		}
		
		if (!isset($thesis)) {
			$thesis = &new Thesis();
		}
		
		$thesis->setJournalId($journalId);
		$thesis->setStatus($this->getData('status'));
		$thesis->setDegree($this->getData('degree'));
		$thesis->setDepartment($this->getData('department'));
		$thesis->setUniversity($this->getData('university'));
		$thesis->setTitle($this->getData('title'));
		$thesis->setDateApproved($this->getData('dateApprovedYear') . '-' . $this->getData('dateApprovedMonth') . '-' . $this->getData('dateApprovedDay'));
		$thesis->setUrl($this->getData('url'));
		$thesis->setAbstract($this->getData('abstract'));
		$thesis->setStudentFirstName($this->getData('studentFirstName'));
		$thesis->setStudentMiddleName($this->getData('studentMiddleName'));
		$thesis->setStudentLastName($this->getData('studentLastName'));
		$thesis->setStudentEmail($this->getData('studentEmail'));
		$thesis->setSupervisorFirstName($this->getData('supervisorFirstName'));
		$thesis->setSupervisorMiddleName($this->getData('supervisorMiddleName'));
		$thesis->setSupervisorLastName($this->getData('supervisorLastName'));
		$thesis->setSupervisorEmail($this->getData('supervisorEmail'));

		// Update or insert thesis
		if ($thesis->getThesisId() != null) {
			$thesisDao->updateThesis($thesis);
		} else {
			$thesisDao->insertThesis($thesis);
		}
	}
}

?>
