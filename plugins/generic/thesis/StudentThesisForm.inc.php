<?php

/**
 * @file StudentThesisForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins 
 * @class StudentThesisForm
 *
 * Form for students to submit thesis abstract.
 *
 * $Id$
 */

import('form.Form');

class StudentThesisForm extends Form {

	/** @var validDegrees array keys are valid thesis degree values */
	var $validDegrees;

	/** @var boolean whether or not captcha support is enabled */
	var $captchaEnabled;

	/** @var boolean whether or not upload code is enabled */
	var $uploadCodeEnabled;

	/**
	 * Constructor
	 * @param thesisId int leave as default for new thesis
	 */
	function StudentThesisForm($thesisId = null) {
		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();
		$thesisPlugin = &PluginRegistry::getPlugin('generic', 'ThesisPlugin');
		$thesisPlugin->import('Thesis');

		$this->validDegrees = array (
			THESIS_DEGREE_MASTERS => Locale::translate('plugins.generic.thesis.manager.degree.masters'),
			THESIS_DEGREE_DOCTORATE => Locale::translate('plugins.generic.thesis.manager.degree.doctorate')
		);

		import('captcha.CaptchaManager');
		$captchaManager =& new CaptchaManager();
		$this->captchaEnabled = $captchaManager->isEnabled() ? true : false;

		$this->uploadCodeEnabled = $thesisPlugin->getSetting($journalId, 'enableUploadCode');
 
		parent::Form($thesisPlugin->getTemplatePath() . 'studentThesisForm.tpl');


		// Captcha support if enabled
		if ($this->captchaEnabled) {
			$this->addCheck(new FormValidatorCaptcha($this, 'captcha', 'captchaId', 'common.captchaField.badCaptcha'));
		}
	
		// Degree is provided and is valid value
		$this->addCheck(new FormValidator($this, 'degree', 'required', 'plugins.generic.thesis.form.degreeRequired'));	
		$this->addCheck(new FormValidatorInSet($this, 'degree', 'required', 'plugins.generic.thesis.form.degreeValid', array_keys($this->validDegrees)));
	
		// Degree Name is provided
		$this->addCheck(new FormValidator($this, 'degreeName', 'required', 'plugins.generic.thesis.form.degreeNameRequired'));

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

		$this->addCheck(new FormValidatorPost($this));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$thesisPlugin = &PluginRegistry::getPlugin('generic', 'ThesisPlugin');
		$thesisPlugin->import('Thesis');
		$templateMgr = &TemplateManager::getManager();

		if ($this->captchaEnabled) {
			import('captcha.CaptchaManager');
			$captchaManager =& new CaptchaManager();
			$captcha =& $captchaManager->createCaptcha();
			if ($captcha) {
				$templateMgr->assign('captchaEnabled', $this->captchaEnabled);
				$this->setData('captchaId', $captcha->getCaptchaId());
			}
		}

		$templateMgr->assign('uploadCodeEnabled', $this->uploadCodeEnabled);
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
		$userVars = array(
			'degree',
			'degreeName',
			'department',
			'university',
			'dateApprovedYear',
			'dateApprovedMonth',
			'dateApprovedDay',
			'title',
			'url',
			'abstract',
			'uploadCode',
			'comment',
			'studentFirstName',
			'studentMiddleName',
			'studentLastName',
			'studentEmail',
			'studentEmailPublish',
			'studentBio',
			'supervisorFirstName',
			'supervisorMiddleName',
			'supervisorLastName',
			'supervisorEmail',
			'discipline',
			'subjectClass',
			'keyword',
			'coverageGeo',
			'coverageChron',
			'coverageSample',
			'method',
			'language'
		);

		if ($this->captchaEnabled) {
			$userVars[] = 'captchaId';
			$userVars[] = 'captcha';
		}

		$this->readUserVars($userVars);
		$this->_data['dateApproved'] = $this->_data['dateApprovedYear'] . '-' . $this->_data['dateApprovedMonth'] . '-' . $this->_data['dateApprovedDay']; 

		// If a url is provided, ensure it includes a proper prefix (i.e. http:// or ftp://).
		if (!empty($this->_data['url'])) {
			$this->addCheck(new FormValidatorCustom($this, 'url', 'required', 'plugins.generic.thesis.form.urlPrefixIncluded', create_function('$url', 'return strpos(trim(strtolower($url)), \'http://\') === 0 || strpos(trim(strtolower($url)), \'ftp://\') === 0 ? true : false;'), array()));
		}

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
		$thesis->setDegreeName($this->getData('degreeName'));
		$thesis->setDepartment($this->getData('department'));
		$thesis->setUniversity($this->getData('university'));
		$thesis->setTitle($this->getData('title'));
		$thesis->setDateApproved($this->getData('dateApprovedYear') . '-' . $this->getData('dateApprovedMonth') . '-' . $this->getData('dateApprovedDay'));
		$thesis->setUrl(strtolower($this->getData('url')));
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

		$thesisDao->insertThesis($thesis);

		// Send supervisor confirmation email
		if (!empty($this->uploadCodeEnabled)) {
			$uploadCode = $thesisPlugin->getSetting($journalId, 'uploadCode');
			$submittedUploadCode = $this->getData('uploadCode');
		}

		if (empty($uploadCode) || ($uploadCode != $submittedUploadCode)) {
			$journalName = $journal->getTitle();
			$thesisName = $thesisPlugin->getSetting($journalId, 'thesisName');
			$thesisEmail = $thesisPlugin->getSetting($journalId, 'thesisEmail');
			$thesisPhone = $thesisPlugin->getSetting($journalId, 'thesisPhone');
			$thesisFax = $thesisPlugin->getSetting($journalId, 'thesisFax');
			$thesisMailingAddress = $thesisPlugin->getSetting($journalId, 'thesisMailingAddress');
			$thesisContactSignature = $thesisName;

			if (!empty($thesisMailingAddress)) {
				$thesisContactSignature .= "\n" . $thesisMailingAddress;
			}
			if (!empty($thesisPhone)) {
				$thesisContactSignature .= "\n" . Locale::Translate('user.phone') . ': ' . $thesisPhone;
			}
			if (!empty($thesisFax)) {
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
				'degreeName' => $thesis->getDegreeName(),
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
	
}

?>
