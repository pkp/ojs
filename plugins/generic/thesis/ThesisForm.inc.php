<?php

/**
 * @file plugins/generic/thesis/ThesisForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ThesisForm
 * @ingroup plugins_generic_thesis
 *
 * @brief Form for journal managers to create/edit thesis abstracts.
 */

import('lib.pkp.classes.form.Form');

class ThesisForm extends Form {

	/** @var thesisId int the ID of the thesis being edited */
	var $thesisId;

	/** @var validStatus array keys are valid thesis status values */
	var $validStatus;

	/** @var validDegrees array keys are valid thesis degree values */
	var $validDegrees;

	/** @var $parentPluginName string Name of parent plugin */
	var $parentPluginName;

	/**
	 * Constructor
	 * @param $parentPluginName string Name of parent plugin
	 * @param $thesisId int leave as default for new thesis
	 */
	function ThesisForm($parentPluginName, $thesisId = null) {
		$this->parentPluginName = $parentPluginName;
		$thesisPlugin =& PluginRegistry::getPlugin('generic', $parentPluginName);
		$thesisPlugin->import('Thesis');

		$this->validStatus = array (
			THESIS_STATUS_INACTIVE => __('plugins.generic.thesis.manager.status.inactive'),
			THESIS_STATUS_ACTIVE => __('plugins.generic.thesis.manager.status.active')
		);

		$this->validDegrees = array (
			THESIS_DEGREE_MASTERS => __('plugins.generic.thesis.manager.degree.masters'),
			THESIS_DEGREE_DOCTORATE => __('plugins.generic.thesis.manager.degree.doctorate')
		);

		$this->thesisId = isset($thesisId) ? (int) $thesisId : null;

		$journal =& Request::getJournal();
		parent::Form($thesisPlugin->getTemplatePath() . 'thesisForm.tpl');


		// Status is provided and is valid value
		$this->addCheck(new FormValidator($this, 'status', 'required', 'plugins.generic.thesis.manager.form.statusRequired'));	
		$this->addCheck(new FormValidatorInSet($this, 'status', 'required', 'plugins.generic.thesis.manager.form.statusValid', array_keys($this->validStatus)));

		// Degree is provided and is valid value
		$this->addCheck(new FormValidator($this, 'degree', 'required', 'plugins.generic.thesis.manager.form.degreeRequired'));	
		$this->addCheck(new FormValidatorInSet($this, 'degree', 'required', 'plugins.generic.thesis.manager.form.degreeValid', array_keys($this->validDegrees)));

		// Department is provided
		$this->addCheck(new FormValidator($this, 'department', 'required', 'plugins.generic.thesis.manager.form.departmentRequired'));

		// University is provided
		$this->addCheck(new FormValidator($this, 'university', 'required', 'plugins.generic.thesis.manager.form.universityRequired'));

		// Approval date is provided and valid
		$this->addCheck(new FormValidator($this, 'dateApprovedYear', 'required', 'plugins.generic.thesis.manager.form.dateApprovedRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateApprovedYear', 'required', 'plugins.generic.thesis.manager.form.dateApprovedValid', create_function('$dateApprovedYear', '$minYear = date(\'Y\') + THESIS_APPROVED_YEAR_OFFSET_PAST; $maxYear = date(\'Y\'); return ($dateApprovedYear >= $minYear && $dateApprovedYear <= $maxYear) ? true : false;')));

		$this->addCheck(new FormValidator($this, 'dateApprovedMonth', 'required', 'plugins.generic.thesis.manager.form.dateApprovedRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateApprovedMonth', 'required', 'plugins.generic.thesis.manager.form.dateApprovedValid', create_function('$dateApprovedMonth', 'return ($dateApprovedMonth >= 1 && $dateApprovedMonth <= 12) ? true : false;')));

		$this->addCheck(new FormValidator($this, 'dateApprovedDay', 'required', 'plugins.generic.thesis.manager.form.dateApprovedRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dateApprovedDay', 'required', 'plugins.generic.thesis.manager.form.dateApprovedValid', create_function('$dateApprovedDay', 'return ($dateApprovedDay >= 1 && $dateApprovedDay <= 31) ? true : false;')));

		// Title is provided
		$this->addCheck(new FormValidator($this, 'title', 'required', 'plugins.generic.thesis.manager.form.titleRequired'));

		// Student first name is provided
		$this->addCheck(new FormValidator($this, 'studentFirstName', 'required', 'plugins.generic.thesis.manager.form.studentFirstNameRequired'));

		// Student last name is provided
		$this->addCheck(new FormValidator($this, 'studentLastName', 'required', 'plugins.generic.thesis.manager.form.studentLastNameRequired'));

		// Student email is provided and valid
		$this->addCheck(new FormValidatorEmail($this, 'studentEmail', 'required', 'plugins.generic.thesis.manager.form.studentEmailValid'));

		// Supervisor first name is provided
		$this->addCheck(new FormValidator($this, 'supervisorFirstName', 'required', 'plugins.generic.thesis.manager.form.supervisorFirstNameRequired'));

		// Supervisor last name is provided
		$this->addCheck(new FormValidator($this, 'supervisorLastName', 'required', 'plugins.generic.thesis.manager.form.supervisorLastNameRequired'));

		// Supervisor email is provided
		$this->addCheck(new FormValidatorEmail($this, 'supervisorEmail', 'required', 'plugins.generic.thesis.manager.form.supervisorEmailValid'));

		// Abstract is provided
		$this->addCheck(new FormValidator($this, 'abstract', 'required', 'plugins.generic.thesis.manager.form.abstractRequired'));

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$thesisPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$thesisPlugin->import('Thesis');

		$templateMgr =& TemplateManager::getManager();
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
			$thesisDao =& DAORegistry::getDAO('ThesisDAO');
			$thesis =& $thesisDao->getThesis($this->thesisId);

			if ($thesis != null) {
				$this->_data = array(
					'status' => $thesis->getStatus(),
					'degree' => $thesis->getDegree(),
					'degreeName' => $thesis->getDegreeName(),
					'department' => $thesis->getDepartment(),
					'university' => $thesis->getUniversity(),
					'dateApproved' => $thesis->getDateApproved(),
					'title' => $thesis->getTitle(),
					'url' => $thesis->getUrl(),
					'abstract' => $thesis->getAbstract(),
					'comment' => $thesis->getComment(),
					'studentFirstName' => $thesis->getStudentFirstName(),
					'studentMiddleName' => $thesis->getStudentMiddleName(),
					'studentLastName' => $thesis->getStudentLastName(),
					'studentEmail' => $thesis->getStudentEmail(),
					'studentEmailPublish' => $thesis->getStudentEmailPublish(),
					'studentBio' => $thesis->getStudentBio(),
					'supervisorFirstName' => $thesis->getSupervisorFirstName(),
					'supervisorMiddleName' => $thesis->getSupervisorMiddleName(),
					'supervisorLastName' => $thesis->getSupervisorLastName(),
					'supervisorEmail' => $thesis->getSupervisorEmail(),
					'discipline' => $thesis->getDiscipline(),
					'subjectClass' => $thesis->getSubjectClass(),
					'keyword' => $thesis->getSubject(),
					'coverageGeo' => $thesis->getCoverageGeo(),
					'coverageChron' => $thesis->getCoverageChron(),
					'coverageSample' => $thesis->getCoverageSample(),
					'method' => $thesis->getMethod(),
					'language' => $thesis->getLanguage()
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
		$this->readUserVars(array('status', 'degree', 'degreeName', 'department', 'university', 'dateApprovedYear', 'dateApprovedMonth', 'dateApprovedDay', 'title', 'url', 'abstract', 'comment', 'studentFirstName', 'studentMiddleName', 'studentLastName', 'studentEmail', 'studentEmailPublish', 'studentBio', 'supervisorFirstName', 'supervisorMiddleName', 'supervisorLastName', 'supervisorEmail', 'discipline', 'subjectClass', 'keyword', 'coverageGeo', 'coverageChron', 'coverageSample', 'method', 'language'));
		$this->_data['dateApproved'] = $this->_data['dateApprovedYear'] . '-' . $this->_data['dateApprovedMonth'] . '-' . $this->_data['dateApprovedDay']; 

		// If a url is provided, ensure it includes a proper prefix (i.e. http:// or ftp://).
		if (!empty($this->_data['url'])) {
			$this->addCheck(new FormValidatorCustom($this, 'url', 'required', 'plugins.generic.thesis.manager.form.urlPrefixIncluded', create_function('$url', 'return strpos(trim(strtolower_codesafe($url)), \'http://\') === 0 || strpos(trim(strtolower_codesafe($url)), \'ftp://\') === 0 ? true : false;'), array()));
		}
	}

	/**
	 * Save thesis. 
	 */
	function execute() {
		$thesisPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$thesisPlugin->import('Thesis');

		$thesisDao =& DAORegistry::getDAO('ThesisDAO');
		$journal =& Request::getJournal();
		$journalId = $journal->getId();

		if (isset($this->thesisId)) {
			$thesis =& $thesisDao->getThesis($this->thesisId);
		}

		if (!isset($thesis)) {
			$thesis = new Thesis();
		}

		$thesis->setJournalId($journalId);
		$thesis->setStatus($this->getData('status'));
		$thesis->setDegree($this->getData('degree'));
		$thesis->setDegreeName($this->getData('degreeName'));
		$thesis->setDepartment($this->getData('department'));
		$thesis->setUniversity($this->getData('university'));
		$thesis->setTitle($this->getData('title'));
		$thesis->setDateApproved($this->getData('dateApprovedYear') . '-' . $this->getData('dateApprovedMonth') . '-' . $this->getData('dateApprovedDay'));
		$thesis->setUrl($this->getData('url'));
		$thesis->setAbstract($this->getData('abstract'));
		$thesis->setComment($this->getData('comment'));
		$thesis->setStudentFirstName($this->getData('studentFirstName'));
		$thesis->setStudentMiddleName($this->getData('studentMiddleName'));
		$thesis->setStudentLastName($this->getData('studentLastName'));
		$thesis->setStudentEmail($this->getData('studentEmail'));
		$thesis->setStudentEmailPublish($this->getData('studentEmailPublish') == null ? 0 : 1);
		$thesis->setStudentBio($this->getData('studentBio'));
		$thesis->setSupervisorFirstName($this->getData('supervisorFirstName'));
		$thesis->setSupervisorMiddleName($this->getData('supervisorMiddleName'));
		$thesis->setSupervisorLastName($this->getData('supervisorLastName'));
		$thesis->setSupervisorEmail($this->getData('supervisorEmail'));
		$thesis->setDiscipline($this->getData('discipline'));
		$thesis->setSubjectClass($this->getData('subjectClass'));
		$thesis->setSubject($this->getData('keyword'));
		$thesis->setCoverageGeo($this->getData('coverageGeo'));
		$thesis->setCoverageChron($this->getData('coverageChron'));
		$thesis->setCoverageSample($this->getData('coverageSample'));
		$thesis->setMethod($this->getData('method'));
		$thesis->setLanguage($this->getData('language'));
		$thesis->setDateSubmitted(Core::getCurrentDate());

		// Update or insert thesis
		if ($thesis->getId() != null) {
			$thesisDao->updateThesis($thesis);
		} else {
			$thesisDao->insertThesis($thesis);
		}
	}
}

?>
