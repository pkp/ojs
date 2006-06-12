<?php

/**
 * StudentThesisForm.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins 
 *
 * Form for students to submit thesis abstract.
 *
 * $Id$
 */

import('form.Form');

class StudentThesisForm extends Form {

	/** @var validDegrees array keys are valid thesis degree values */
	var $validDegrees;

	/**
	 * Constructor
	 * @param thesisId int leave as default for new thesis
	 */
	function StudentThesisForm($thesisId = null) {
		$thesisPlugin = &PluginRegistry::getPlugin('generic', 'ThesisPlugin');
		$thesisPlugin->import('Thesis');

		$this->validDegrees = array (
			THESIS_DEGREE_MASTERS => Locale::translate('plugins.generic.thesis.manager.degree.masters'),
			THESIS_DEGREE_DOCTORATE => Locale::translate('plugins.generic.thesis.manager.degree.doctorate')
		);

		$journal = &Request::getJournal();
		parent::Form($thesisPlugin->getTemplatePath() . 'studentThesisForm.tpl');

	
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
		$templateMgr->assign('validDegrees', $this->validDegrees);
		$templateMgr->assign('yearOffsetPast', THESIS_APPROVED_YEAR_OFFSET_PAST);
	
		parent::display();
	}
	
	/**
	 * Initialize form data.
	 */
	function initData() {
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('degree', 'department', 'university', 'dateApprovedYear', 'dateApprovedMonth', 'dateApprovedDay', 'title', 'url', 'abstract', 'studentFirstName', 'studentMiddleName', 'studentLastName', 'studentEmail', 'supervisorFirstName', 'supervisorMiddleName', 'supervisorLastName', 'supervisorEmail'));
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
	
		$thesis = &new Thesis();
		
		$thesis->setJournalId($journalId);
		$thesis->setStatus(THESIS_STATUS_INACTIVE);
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

		$thesisDao->insertThesis($thesis);

		// Send supervisor confirmation email
		$journalName = $journal->getTitle();
		$thesisName = $thesisPlugin->getSetting($journalId, 'thesisName');
		$thesisEmail = $thesisPlugin->getSetting($journalId, 'thesisEmail');
		$thesisPhone = $thesisPlugin->getSetting($journalId, 'thesisPhone');
		$thesisFax = $thesisPlugin->getSetting($journalId, 'thesisFax');
		$thesisMailingAddress = $thesisPlugin->getSetting($journalId, 'thesisMailingAddress');
		$thesisContactSignature = $thesisName;

		if ($thesisMailingAddress != '') {
			$thesisContactSignature .= "\n" . $thesisMailingAddress;
		}
		if ($thesisPhone != '') {
			$thesisContactSignature .= "\n" . Locale::Translate('user.phone') . ': ' . $thesisPhone;
		}
		if ($thesisFax != '') {
			$thesisContactSignature .= "\n" . Locale::Translate('user.fax') . ': ' . $thesisFax;
		}

		$thesisContactSignature .= "\n" . Locale::Translate('user.email') . ': ' . $thesisEmail;
		$studentName = $thesis->getStudentFirstName() . ' ' . $thesis->getStudentLastName();
		$supervisorName = $thesis->getSupervisorFirstName() . ' ' . $thesis->getSupervisorLastName();

		$paramArray = array(
			'journalName' => $journalName,
			'thesisName' => $thesisName,
			'thesisEmail' => $thesisEmail,
			'title' => $thesis->getTitle(),
			'studentName' => $studentName,
			'degree' => Locale::Translate($thesis->getDegreeString()),
			'department' => $thesis->getDepartment(),
			'university' =>	$thesis->getUniversity(),
			'dateApproved' => $thesis->getDateApproved(),
			'supervisorName' => $supervisorName,
			'abstract' => $thesis->getAbstract(),		
			'thesisContactSignature' => $thesisContactSignature 
		);

		import('mail.MailTemplate');
		$mail = &new MailTemplate('THESIS_ABSTRACT_CONFIRM');
		$mail->setFrom($thesisEmail, "\"" . $thesisName . "\"");
		$mail->assignParams($paramArray);
		$mail->addRecipient($thesis->getSupervisorEmail(), "\"" . $supervisorName . "\"");
		$mail->addCc($thesis->getStudentEmail(), "\"" . $studentName . "\"");
		$mail->send();
	}
	
}

?>
